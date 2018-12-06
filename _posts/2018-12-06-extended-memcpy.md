---
title: Extended memcpy in PMDK 1.5
author: marcinslusarz
layout: post
identifier: extended_memcpy
---

In PMDK 1.5 we added new APIs for bulk persistent memory modifications.
In short we did this to:
 - give applications ability to perform low-level performance optimizations
 - clean up the unfortunate naming

In order to understand what exactly and why we did that, let's review the API
as exposed by PMDK 1.4 and earlier. In PMDK 1.4 we had these functions:

{% highlight C %}
void *pmem_memmove_persist(void *pmemdest, const void *src, size_t len);
void *pmem_memcpy_persist (void *pmemdest, const void *src, size_t len);
void *pmem_memset_persist (void *pmemdest, int c, size_t len);

void *pmem_memmove_nodrain(void *pmemdest, const void *src, size_t len);
void *pmem_memcpy_nodrain (void *pmemdest, const void *src, size_t len);
void *pmem_memset_nodrain (void *pmemdest, int c, size_t len);

void *pmemobj_memcpy_persist(PMEMobjpool *pop, void *pmemdest, const void *src, size_t len);
void *pmemobj_memset_persist(PMEMobjpool *pop, void *pmemdest, int c, size_t len);
{% endhighlight %}

As you can see there are two variants of these APIs - with `_persist` and
`_nodrain` suffix. Both variants modify pmemdest argument to do what their
libc's equivalent do and make sure that the copied data are flushed.
The only difference is that the `_persist` variants also wait for the flush to
finish. As I mentioned earlier, there's one unfortunate aspect of this naming -
it doesn't follow the scheme used by other libpmem functions, where
`pmem_flush` flushes the data, `pmem_drain` waits for all flushes to finish
and `pmem_persist` does both. In line with this scheme `_nodrain` functions
should have used `_flush` suffix.

For libpmemobj functions there's no way to opt-out of drains and there's no
memmove. Functionality-wise they are wrappers around libpmem functions with
additions required by libpmemobj (replication and non-pmem safety).

The bigger problem than naming is with what they do - they try to guess
the optimal strategy to update persistent memory using only their parameters.
The logic is pretty simple - if modification is smaller than 256 bytes
(configurable using `PMEM_MOVNT_THRESHOLD` environment variable) they use
normal `mov` instrunctions followed by `pmem_flush`, but for modifications
256 bytes and longer they use non-temporal stores. Those instructions on
x86_64 have 2 properties - they bypass CPU caches (so that pmem_flush is not
needed) and treat destination memory as the write-combining type.
The last property means that if the destination memory is not in the cache,
the CPU doesn't have to fetch the full cache lines, just to flush them a moment
later. This is important not because data is not stored in the cache, but
because there's no fetch of previous data.
This means that application can update the same piece of memory multiple times
without waiting for it to be available for read.

The logic based on store size is usually optimal for sequential and random
workloads, but fails to chose the right method when application writes to
the same cache lines over and over again (like in case of hot meta-data).
Tweaking the threshold where those functions start using NT stores is not
enough, because context in which those function are called matters.

One important fact, which will matter in a moment, is that since PMDK 1.4 these
functions guarantee that if destination buffer is 8 byte aligned, size is
a multiple of 8 and application is interrupted (by crash / OS crash / power
failure), then each 8-byte location has either new or old value, never a mix of
the two. This doesn't mean there are any ordering or atomicity guarantees
beyond 8 bytes though.

So knowing all of this, in PMDK 1.5 we introduced these APIs:

{% highlight C %}
void *pmem_memmove(void *pmemdest, const void *src, size_t len, unsigned flags);
void *pmem_memcpy (void *pmemdest, const void *src, size_t len, unsigned flags);
void *pmem_memset (void *pmemdest, int c, size_t len, unsigned flags);

void *pmemobj_memcpy (PMEMobjpool *pop, void *dest, const void *src, size_t len,
		      unsigned flags);
void *pmemobj_memmove(PMEMobjpool *pop, void *dest, const void *src, size_t len,
		      unsigned flags);
void *pmemobj_memset (PMEMobjpool *pop, void *dest, int c, size_t len,
		      unsigned flags);
{% endhighlight %}

As you can see, we stopped with `_persist` and `_nodrain` distinction and
introduced the more flexible "flags" argument.
When "flags" is 0, those functions behave like `_persist` variants of older
APIs. For libpmem functions we introduced these flags:
  - `PMEM_F_MEM_NODRAIN`
  - `PMEM_F_MEM_NOFLUSH`
  - `PMEM_F_MEM_NONTEMPORAL`
  - `PMEM_F_MEM_TEMPORAL`
  - `PMEM_F_MEM_WC`
  - `PMEM_F_MEM_WB`

`PMEM_F_MEM_NODRAIN` disables `pmem_drain`, just like `_nodrain` functions
did. 

`PMEM_F_MEM_NOFLUSH` disables flushing (and implies `PMEM_F_MEM_NODRAIN`).
This flag may be useful if application knows it will update the same piece of
memory multiple times, doesn't care about possible cache eviction in between
and still wants to rely on the 8-byte atomicity guarantee.

`PMEM_F_MEM_NONTEMPORAL` tells the library that data will not be used again
soon (so it can bypass CPU cache). On x86_64 this means the usage of NT-stores.

`PMEM_F_MEM_WC` tells the library to treat destination memory as
write-combining (so it can avoid CPU cache miss on read). On x86_64 this
means the usage of NT-stores.

`PMEM_F_MEM_TEMPORAL` tells the library that data might be used again soon
(so it should keep it in CPU cache). On x86_64 this means the usage of normal
stores. On x86_64 without CLWB instruction (or motherboard without eADR)
the cache will still be invalidated (unless `PMEM_F_MEM_NOFLUSH` was also
used).

`PMEM_F_MEM_WB` tells the library to treat destination memory as write-back.
On x86_64 this means the usage of normal stores.

Few more notes on non-temporal stores usage on x86_64:
- If you use `PMEM_F_MEM_NODRAIN`, you should call `pmem_drain` at least
  when you want the copied data to be visible to other threads, because without
  that your data may be stuck in (per CPU) write-combining buffers.
- It's important to align modified data to cache-line boundary (64 bytes),
  because write-combining happens on cache-line level.
  In pmemobj you can use allocation classes with multiple of 64 bytes alignment
  to achieve that (by default objects are allocated using only 16 bytes
  alignment). 

For libpmemobj functions we introduced these flags: 
  - `PMEMOBJ_F_MEM_NODRAIN`
  - `PMEMOBJ_F_MEM_NOFLUSH`
  - `PMEMOBJ_F_MEM_NONTEMPORAL`
  - `PMEMOBJ_F_MEM_TEMPORAL`
  - `PMEMOBJ_F_MEM_WC`
  - `PMEMOBJ_F_MEM_WB`
  - `PMEMOBJ_F_RELAXED`

First 6 behave like their libpmem equivalents.

`PMEMOBJ_F_RELAXED` matters only when used with remote replication.
For now RDMA protocol (as used by librpmem to implement remote replication)
doesn't guarantee that in case of interruption the transfer won't be torn
at random place. In order to provide 8 byte atomicity guarantee mentioned
earlier, pmemobj have to use slower method of replication. This flag tells
the library that the application doesn't care about this guarantee for
this operation, so it can replicate using faster method.

If you are overwhelmed by this knowledge, sticking to older functions or setting
flags argument to 0 for the new ones is a safe choice. However if you want
to squeeze as much of performance from your brand new NVDIMMs as possible,
it's worth using those new APIs.

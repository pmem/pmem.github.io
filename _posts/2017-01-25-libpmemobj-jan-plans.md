---
title: "What's coming in libpmemobj"
author: pbalcer
layout: post
identifier: libpmemobj-q1-plans-2017
---

In my last post I've made a promise to share our plans for the near future. So
here it is: 4 ideas that we are planning to ship with the upcoming version
of libpmemobj.

Please note that most of our plans related to libpmemobj are available on
our
[github issues](https://github.com/pmem/issues/issues?q=is%3Aopen+is%3Aissue+label%3A%22Type%3A+Feature%22)
page with the "Feature" label. Feel free to join the discussion!

### Reserve/Initialize/Publish work-flow

Many of persistent memory programming models proposed by researchers [1, 2]
provide a different transactional semantics compared to libpmemobj. In the
simplest terms, they allow the programmer to reserve and initialize memory
anywhere in the code and publish that object inside of a transaction.

```
/* create an object reservation and fill it with data */
TOID(int) my_data = POBJ_RESERVE(pop, int);
*D_RW(my_data) = 5;

TX_BEGIN(pop) {
	/* you can publish objects as a part of a transaction */
	TX_PUBLISH(D_RW(root)->data, my_data);
} TX_END

/* you can also cancel the reservation */
POBJ_CANCEL(my_data);

```

We are adopting this approach in libpmemobj for improved flexibilty of our API.
There are many, otherwise impossible, constructs that this enables. For example,
you can reserve and initialize objects from multiple threads but publish
them in a single transaction.

The reserve function is also much quicker than the full allocation because it
operates exclusively on the transient state - in those terms it's equivalent
to a normal libc malloc().

Github issue link: <https://github.com/pmem/issues/issues/415>

### Asynchronous transaction cleanup

The pmemobj library, while designed with multi-threading in mind, doesn't do
any background work on its own. And that's good, a library shouldn't create
threads, but there are opportunities where parallelization of algorithms that
underpin libpmemobj is relatively easy and wouldn't affect the user at all.

One of those opportunities is background cleanup of leftovers after a transaction
has committed (the post-commit phase). This cleanup can get quite expensive for
long-running transactions that performed a lot of 'free' operations, and it's
almost irrelevant when that cleanup is actually performed.

In order to maintain the "no library background workers" policy, the threads will
be user-managed. More details in the link below.

Github issue link: <https://github.com/pmem/issues/issues/381>

### Optimized bulk allocation

The allocation process inside of a transaction consists of 4 phases:

* perform a transient block reservation
* prepare block metadata
* create redo log with ~3 entries that need to be modified in a fail-safe
atomic way
* perform the redo log mini-transaction

This last phase is also where the 90% of the time is spent, because that's
where the flushes are performed. And this is done for each and every allocation.

The idea is very simple: aggregate multiple allocations inside of a single redo
log mini-transaction which will spread and alleviate the costs associated with
the redo log across multiple allocations. This is only possible for transactions,
and the optimization will be transparent to the users. I suspect that for some
workloads this can double the throughput.

Github issue link: <https://github.com/pmem/issues/issues/380>

### Improved, configurable, transaction cache

Transaction cache is the part of the undo logs in which we keep the snapshots
of tiny modifications. For simplicity sake, the initial implementation has a
fixed size of a single cache entry equal to 32 bytes and so all larger
modifications have to perform an allocation. The cache currently looks like this:

```
struct cache_entry {
	uint64_t offset;
	uint64_t size;
	char data[N (= 32 bytes)];
};

struct cache {
	struct cache_entry entries[M = (~1000)];
};
```

This change is an evolution of this idea. The new cache will have a configurable
size and dynamically sized entries, which means that a single cached snapshot
will be more space efficient (1 byte snapshot won't take 32 bytes of space) and
the threshold that determines when to use the cache will be user configurable
and limited only by the size of the entire cache.

Github issue link: <https://github.com/pmem/issues/issues/378>

# References

[1] Atlas: Leveraging Locks for Non-volatile Memory Consistency
<https://www.hpl.hp.com/techreports/2013/HPL-2013-78.pdf>

[2] Makalu: Fast Recoverable Allocation of Non-volatile Memory
<https://makalu.blogs.rice.edu/files/2016/09/oopsla2016-copyright-1s0zst5.pdf>

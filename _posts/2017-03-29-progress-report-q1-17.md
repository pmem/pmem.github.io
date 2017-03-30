---
title: "Progress Report Q1 2017"
author: pbalcer
layout: post
identifier: progress-report-q1-2017
---

It's been three months already since the last time I wrote something ;) Time
really flies by quickly when you are doing interesting stuff.

We've been very busy with lots of improvements to the library as well as A LOT
of tiny fixes: over the last three months the team eliminated virtually every
bug found by various static analysis tools and valgrind.

But no-one wants to hear about that, so here's the meaty stuff:

### Rewritten unused memory handling & implemented flexible allocation header

For the longest time, the low level implementation of the persistent free was
very strict about putting things back where they belong and making everything
tidy. That meant coalescing even the tiniest blocks, cleaning up large unused
contiguous runs of blocks as soon as possible, putting the freed blocks back to the
corresponding thread caches and a few more things. I think it's sufficient to
say that the free was more expensive than malloc, and what's more, required
a volatile pointer inside of the header of memory blocks. Managing that pointer
was a huge pain.

But that is all in the past now. In January, after long baking, we've finally merged
a [patch](https://github.com/pmem/nvml/pull/1366) that significantly simplifies
the free operation. Right now, in the happy case, a free is a flip of a few bits
in a single 64 bit value. The performance gains are spectacular.

Before:

![before](https://cloud.githubusercontent.com/assets/8775610/20184290/7673f3f2-a768-11e6-85f2-791894d11269.png)

After:

![after](https://cloud.githubusercontent.com/assets/8775610/20183720/953c5830-a766-11e6-9a52-cbff64e77025.png)

The only not so great side effect of this change is that the freed blocks are
now lazily repopulated in the volatile state of the allocator. This means that
in sequence of malloc, free, malloc; the second malloc won't return the same
address as the first one, and the freed block will only become available once
the current bucket (a thread cache) exhausts its currently cached memory blocks.


Another change that was bundled in that pull request was introduction of a new
slimmer header for objects, as well as a new cleaner code to handle customizable
headers in the future.

The new header is only 16 bytes in size (compared to the old 64 bytes), which
means that from now on, a single allocation will waste 48 bytes less then
previously. That's a huge improvement if your application deals mostly with
small objects. This new header, alongside the new customizable header code
framework, will allow us to eventually introduce new very small allocation
classes (8, 16 bytes) with minimal overhead and allocations aligned to
arbitrary sizes.

### Dynamic range cache

The transaction range cache is the place where the snapshots of memory blocks
are placed. It was introduced to reduce the number of allocations performed
during an average transaction. Instead of calling the persistent malloc
(a very expensive operation) every time the user wanted to modify a memory
region, the library had a preallocated buffer to which the snapshot landed.
However, there was one small issue with the
implementation: only regions no larger than 32 bytes were benefiting from
this optimization. The reasoning for that was a) it made the implementation
far simpler and b) the cost of memcpy will overshadow the allocation cost for
larger blocks. The second reason turned out to be not true at all, and in this
[patch](https://github.com/pmem/nvml/pull/1533) we've managed to maintain a
fairly simple implementation of the cache while allowing for arbitrarily sized
ranges.

This change benefits workloads where snapshots larger than 32 bytes are created
on a regular basis. One example of that type of workload is our persistent B-Tree.
I've taken the liberty to measure the performance of a simple benchmark that
inserts 5mln elements into the tree, here are the results:

![results](https://cloud.githubusercontent.com/assets/8775610/24509180/01e75206-1566-11e7-8179-aa83601f63b2.png)

### Asynchronous post-commit

I've explained this one fairly well in my previous [post]({% post_url 2017-01-25-libpmemobj-jan-plans %}),
but here's the recap: The plan was to allow for worker threads (one or more)
that cleaned up the leftovers after a transaction successfully commits, allowing
the main task to move forward.

We've implemented this [change](https://github.com/pmem/nvml/pull/1671) and the
resulting performance benefits are nothing to sneeze at:

![results](https://cloud.githubusercontent.com/assets/8775610/24497173/daf67a76-153a-11e7-8e95-e185fdf0e118.png)

This is a result from a synthetic benchmark I've devised. It preallocated
certain number of objects, and then, in a transaction, called TX_FREE on those
objects. For each run of the benchmark, the number of post-commit workers
increased. On the chart here we can see that the optimization is indeed worth
while, at least for workloads that do a lot of big transactions.

There's a more in-depth explanation of the feature, as well as some more
benchmarking results, [here](https://github.com/pmem/issues/issues/381).

### Optimized internal fragmentation

We've been busily trying to optimize the allocation and class generation
algorithms toward the goal of minimizing overall fragmentation of the heap.
In it's current state I'd say the fragmentation is fairly *OK* in the general case ;)
The issue however, is that the algorithm was heavily skewed towards optimizing for
small allocations, and everything after a certain threshold was bundled
together in the "big allocations" bucket, where the requested size was rounded-up
to the size of a chunk (256 kilobytes). This resulted in a very inefficient
use of space when allocating big blocks of memory that happened to be only
slightly higher than the chunk (for example: 257 kilobytes).

We are finally fixing that problem in this [patch](https://github.com/pmem/nvml/pull/1629)

Here are the before/after results:

![before](https://cloud.githubusercontent.com/assets/8775610/24499995/03403414-1544-11e7-9404-093c882ec2c3.png)

![after](https://cloud.githubusercontent.com/assets/8775610/24500320/328bf392-1545-11e7-8ab3-c551c10c72b7.png)

The charts show the percentage of internal fragmentation for sizes between
100 bytes and 4 megabytes with the interval of 100 bytes.

The first initial spike (80%) is due to granularity with which we can allocate
memory: 64 bytes.
The 100 byte allocation receives 196 bytes buffer, and so the internal fragmentation
is fairly high percentage wise. This is only true for very tiny allocations.
All allocations between 100 bytes and 132 kilobytes have, on average,
internal fragmentation smaller than 5%.

On the first chart the heap works with only 30 allocation classes, and the second
chart shows a situation with 70, but spaced more apart, and with a bigger focus on
the huge allocations.

There's a bit more technical information [here](https://github.com/pmem/issues/issues/377).

### Two-phase, optionally aggregated, heap operations

The process of persistent allocation is, and always was, divided into two steps:
volatile reservation and persistent publication.
The first part is very quick, and could be done many millions of times per second,
whereas the second part is slow and requires numerous cache flushes, dragging
down the overall performance of the allocation process.

This is an opportunity for optimizations in the workloads where many allocations
are performed in a single transaction, and so the expensive part of the allocation,
the persistent publication, is aggregated so that we can achieve maximal possible
throughput.

We've now implemented the described optimization in this [patch](https://github.com/pmem/nvml/pull/1711),
and here are the performance results:

![results](https://cloud.githubusercontent.com/assets/8775610/23549544/065fd7ae-000d-11e7-8eea-9906196d4f46.png)

Not bad ;) Even the workload in which only one object is allocated per
transaction has improved, this is a result of slightly better allocation undo log
handling that was enabled by the work done in this patch.

### Reserve/Publish workflow

And lastly, we are in the process of adding a new API that will unlock a new
set of problems to be solved using libpmemobj.

This new API builds on top of the ideas mentioned in the previous section, namely
the separation of the allocation into two distinct steps.

The new API (still subject to change) is used like this:
```
struct pobj_action act[2];
TOID(struct foo) f = POBJ_RESERVE(pop, struct foo, &act[0]);
TOID(struct bar) b = POBJ_RESERVE(pop, struct bar, &act[1]);

/* completely different work */

TX_BEGIN(pop) { /* this transaction can happen even in a different thread! */
	/* other changes related to f and b */
	pmemobj_tx_publish(act, 2);
} TX_END

```

This can be used in many different ways, the most promising being
the ability to parallelize related work in a single transaction. I'm currently
working on an example that will demonstrate how one can achieve near-volatile
performance from a persistent container using this API (and some threads :P).

Here's a chart that shows the scaling of reserve API:

![results](https://cloud.githubusercontent.com/assets/8775610/23852782/c421f342-07e9-11e7-8b26-b6ae53bf4342.png)

and here's a chart showing the performance different between reservation and
full persistent allocation:

![results](https://cloud.githubusercontent.com/assets/8775610/23792560/c06bfd7e-0587-11e7-900d-25023694bd28.png)

### Conclusion

We are continuously committed to improving our library, and there's a lot of ideas
still in our heads waiting for their turn ;)

As always, feel free to contribute your own ideas [here](https://github.com/pmem/issues/issues?q=is%3Aopen+is%3Aissue+label%3A%22Type%3A+Feature%22),
we are always open to community suggestions.

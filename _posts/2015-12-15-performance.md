---
title: Performance improvements
author: tomaszkapela
layout: post
identifier: pmem_perf_improv
---

I would like to inform you about the performance improvements that have been going on in PMDK and libpmemobj in particular. We have not been standing still and we are trying out a couple of ideas on how to make our libraries even faster. Some of the improvements are smaller, some are larger. Some of them have already made it to the master branch and some are just ideas on how to rework the internals of libpmemobj to make it even faster. All measurements were made on a two socket, sixteen core machine.

#### Pmemobj allocations' thread scaling
The first thing we noticed, is that allocations aren't scaling properly to the expected twice the number of logical CPUs.

![pmalloc_threads_bad](/assets/pmalloc_threads_bad.png)

After a very simple [fix][a8ef08e9], the thread scaling is magically fixed.

![pmalloc_threads_good](/assets/pmalloc_threads_good.png)

### Function pointers
During the performance analysis we made recently, we noticed that calling functions through pointers adds a considerable overhead, especially for fast and frequently called functions. We decided to reduce the number of function pointer calls and additionally use the GCC ifunc attribute where appropriate. This yields, on average, a 2% performance gain in our synthetic benchmarks.

![pmalloc_ifunc](/assets/pmalloc_ifunc.png)

### Redo log optimizations
We also noticed, that we could slightly improve the redo log flushing mechanism in certain scenarios. When you change sections of persistent memory which is on the same cache line, you don't need to flush them multiple times. This can be used, for example, when updating the OOB header. This yields an additional 2% performance gain.

![redo_opt_threads](/assets/redo_opt_threads.png)

![redo_opt_sizes](/assets/redo_opt_sizes.png)

### The thread starvation problem
One other issue spotted during the performance analysis, is the possibility of starving one of the threads, should a lane not be released. This can happen when a thread takes a lane indefinitely, then at some point, a different thread will be locked waiting on the lane mutex. The chart below represents a test where all threads are performing some action (sleeping to be exact) for an average duration of 0.5 seconds. As we go over the number of available lanes (1024), we see that the max execution time goes well over half a second.

![lane_hold_starvation](/assets/lane_hold_starvation.png)

The simplest solution to this, is to use *pthread_mutex_trylock* and that is in fact the first approach we took. The performance did not suffer any penalty.

![trylock_lane_hold](/assets/trylock_lane_hold.png)

We are however not content with the speed of this mechanism, therefore we started to come up with a _"simpler"_ solution based on the compare and swap operation. The initial implementation yields a significant performance improvement, however it is not safe for applications which operate on two or more pools in the same thread.

![initial_array_lane_hold](/assets/initial_array_lane_hold.png)

We are still working on this, because it could greatly improve performance, especially for highly multi-threaded applications.

### Allocations and type numbers
In libpmemobj we have this notion of type numbers. This is among other things used to internally locate objects of a given type. Users of libpmemobj should however be wary of a possible huge performance drop, should the typenum be neglected and all objects used the same type. On the chart below one data series uses one type number for all threads and the other one allocates on a type-per-thread basis. Don't fret though, we think we have a solution for this.

![tx_alloc_one_type_vs_per_thread](/assets/tx_alloc_one_type_vs_per_thread.png)

### Summary
We are constantly looking for places where we could improve the performance of PMDK and libpmemobj in particular. For example, we are thinking of redoing the whole internal object storage module and we hope to gain some additional percents there. If you have any other idea, let us know in [pmem/issues][41493750], or better yet, do a pull request!

[41493750]: https://github.com/pmem/issues/issues "pmem/issues"
[f242534b]: http://pmem.io/2015/07/17/pmemcheck-basic.html "pmemcheck blog entry"
[efed04e3]: https://gcc.gnu.org/onlinedocs/gcc/Other-Builtins.html "__builtin_expect"
[a8ef08e9]: https://github.com/pmem/pmdk/commit/5606aa41461cc0e7278bb059f1adae88ff6fbf04 "fix merge"

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK]({% post_url 2017-12-11-NVML-is-now-PMDK %}).]

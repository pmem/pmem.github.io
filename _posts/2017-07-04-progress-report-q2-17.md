---
title: "Progress Report Q2 2017"
author: pbalcer
layout: post
identifier: progress-report-q2-2017
---

It's summer already (at least in my hemisphere) ! :) And it's time for the next
progress report.

### Control interface

After a very long in the oven, we've finally finalized and merged the CTL API
which allows for introspection and modification of the internal state of the
library.

This feature has been shaped after the `mallctl()` available in jemalloc.

The way it works is very simple. Developer defines a function-like entry
point, decides whether or not the entry point allows for reading, writing
or both, and finally specifies the argument type. All of this happens in a very
streamlined fashion.

Once an entry point is defined, the user can query that entry point to either
read its contents or write to it. The best part is that one can write to it
either directly from the application or through an environment variable or
a configuration file - basically any string.

The simplest example is the prefault entry point. It allows one to force the
page allocations to happen at pool open (or create) - which is useful when the
performance spikes due to page faults are undesirable.

The entry point is defined as: `prefault.at_open` (and `prefault.at_create`).
To look at the current value of this setting one has to call a CTL function:
```
int prefault_value;
pmemobj_ctl_get(NULL, "prefault.at_open", &prefault_value);
```

The `prefault_value` is the output variable, defined in the manpage as `int`.

To activate this behavior, the value has to be set to 1, which can be done as
follows:
```
int prefault_value = 1;
pmemobj_ctl_set(NULL, "prefault.at_open", &prefault_value);
```

Notice that the first argument (`PMEMobjpool`) is `NULL`, this is because the
pool is not opened yet and so there's no handle to use here.
As I mentioned, this can also be set from env variable/config file - it's fully
explained in the manpage.

The CTL feature, although not useful on its own, will allow us to expose much
more configuration options and statistics, and in turn this will allow for a
greater flexibility in tuning applications that use our library.

### Allocation classes

The most important feature that we've been working on are custom allocation
classes. This has been enabled by the CTL work and many previous refactors
of the allocator code base that allow for runtime modification of its runtime
transient state.

So, what's the big deal? Well, this feature will allow applications to
precisely tune the internal data structures so that the allocations are packed
closely together and bundled in memory chunks big enough not to create needless
heap contention.
This has two effects: One can entirely eliminate fragmentation by effectively
creating a scalable slab allocator and, if that's not enough, accurately
regulate the trade-off between scalability and inter-thread memory reuse
by changing the size of the memory block given to a thread for exclusive use.

The API we are planning on exposing is split in two:

* creation of allocation class & allocating from it directly - [PR](https://github.com/pmem/nvml/pull/1985)
* changing the mappings between size <> class - [PR](https://github.com/pmem/nvml/pull/1986)

We are still looking for input on those two pull requests, so if this is
something that you find useful, please share your thoughts on the proposed
API directly in the PR.

### Improved thread persistent memory utilization

Up until recently, our library naively assigned monotonically increasing
identifiers to threads and used those identifiers to select arenas from
which they allocate memory - `thread identifier % number of arenas`.

This meant that arenas were assigned inefficiently in workloads where threads
were often created and destroyed.

We've now changed the algorithm to track the number of threads currently using
an arena, and when a new thread spawns, it searches for the arena that is
currently least used. This improves both performance and the memory utilization.
Win win! :)

Alongside this change, we've renamed the "bucket_cache" to a more commonly
used term "arena". Arenas are used to provide state separation between threads
that concurrently use the heap. Each time a pmalloc() is called, arena needs to
be acquired and then subsequently released when allocation finishes. By default,
the number of arenas is equal to the number of CPUs available in the system.

### Configurable range cache

Last time I've talked about "dynamic range cache", this time, it's time for
configurable! :) This feature was also enabled by the CTL API.

To recap, a range cache is the place to which small transactional
snapshots (`tx_add_range`) are made, as opposed to creating a new allocation for
a snapshot.

What this does is it allows the user to specify a) how big are the caches and
b) what's the threshold of using the cache versus allocating an entirely new
object.

By default a single cache is 32 kilobytes in size, and the threshold of using
it is 4 kilobytes. This means that every 8 snapshot of 4 kilobytes, a new
persistent allocation is made to create the next cache. This might lead
to a lower overall throughput of transactions. If your workload is like that,
you now have an option to avoid this bottleneck.

This is also useful when operating on a very large objects. For example, if your
workload often snapshots several megabytes at a time, it might be a good idea
to increase the cache size and the threshold, so that the entire transaction
fits in a single cache and that every snapshots does indeed land in the cache
instead of creating its own allocation.

### More fragmentation improvements

And, as always, we've been continuing working on improvements of the general
case fragmentation, i.e., reducing the number of allocation classes while
lowering the number of bytes above the requested size (internal fragmentation).

These improvements mostly comes from the previous changes to the heap algorithm,
but also from a slightly redesigned class generation code. We've also adjusted
the granularity with which the classes are generated (from 64 bytes to 16 bytes),
this was enabled by the implementation of a smaller header size.

Here's are before/after charts:
![before](https://user-images.githubusercontent.com/8775610/27131769-696c780c-510c-11e7-8640-4feb4c9db000.png)

![after](https://user-images.githubusercontent.com/8775610/27131767-64cd00a0-510c-11e7-932f-015089aa498f.png)

### Conclusion

Most items mentioned are usability/stability improvements rather than
optimizations that increase the raw throughput of operations with the library.
This has mostly to do with the upcoming 1.3 release of the library. We've been
focused on stabilizing and generally improving the library rather than
introducing changes of the core algorithms.

And speaking of the release, it contains many more improvements than what I've
managed to list here. Changelog is [here](https://raw.githubusercontent.com/pmem/nvml/master/ChangeLog).

See you next time.

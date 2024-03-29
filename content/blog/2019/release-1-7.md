---
# Blog post title
title: 'New release of PMDK'

# Blog post creation date
date: 2019-10-11T19:55:17-07:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: ''

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ''

# Blog post thumbnail
# eg: image: "/images/my_blog_thumbnail.png"
image: ''

# Blog post author
author: 'pbalcer'

# Categories to which this blog post belongs
blogs: ['PMDK']

tags: []

# Redirects from old URL
aliases: ['/2019/10/11/release-1-7.html']

# Blog post type
type: 'post'
---

If you are following our mailing group, you've probably noticed a stream of
release announcements for libraries that are a part of PMDK. Here's a recap
of the most important new features and additions.

## libpmemkv 1.0

The primary goal of PMDK is enabling adoption of Persistent Memory. We do so
by creating the building blocks that applications can utilize to support PMEM.
So far, our work was mostly concentrated on important base functionality such as
memory allocation or transactions, and only recently we've started to build
on that foundation with C++ containers - making persistent memory programming
easier and easier.

And now, we are moving this one step forward. We realized that large amounts of
software doesn't need fine-grained control over every aspect of PMEM, it just
wants a convenient way to store objects in a manner that's simple, but also
fast and efficient.

And that's what we deliver with libpmemkv. It's an embedded key-value store that
builds on top of years of work we've poured into libpmemobj and libpmemobj-cpp.
It has very straightforward C/C++ interface, and bindings available to large,
and growing, amount of high-level languages.

For more information, see [pmemkv github repository](https://github.com/pmem/pmemkv).

## libpmemobj-cpp 1.8

To release a stable version of libpmemkv, we needed to stabilize the previously
experimental features of libpmemobj-cpp that it relied on. And that was the main
focus of this release.

We've stabilized `pmem::obj::container::array`, `pmem::obj::container::vector`, `pmem::obj::container::string` and `pmem::obj::container::concurrent_hash_map`
and we are now committed to maintaining backward compatibility of those APIs and
the on-media layout of underlying data structures. Applications can rely on
those now stable containers.

We are also working on new containers, and recently we've added
`pmem::obj::experimental::segment_vector`. This container has a vector-like
interface but, unlike `std::vector`, is not
backed by contiguous array, but rather by number of separate segments. This
eliminates the need for costly reallocations, improving performance and
space-efficiency of the container. This should be preferred over a standard
vector for all scenarios that do not require contiguous storage of elements.

For more information, see [libpmemobj-cpp 1.8 release announcement](https://github.com/pmem/libpmemobj-cpp/releases/tag/1.8).

## libpmemobj 1.7

As always, we are continuously working on improvements in the core library
of PMDK, libpmemobj. This time, we focused on performance and efficiency
gains for real-life workloads.

### Reducing write-amplification of undo logs

The potentially most impactful improvement we've made is to write amplification
of undo-log transactions. In versions prior to 1.7, the transaction log
lifecycle is as follows:

1. Acquire log space from the heap.
2. Create snapshots of modified data.
3. On abort, rollback the changes from the log.
4. Invalidate metadata of the log, so that the log isn't applied after a crash.
5. Zero-out the log space.

And so for every byte written to persistent memory, we need to write about
2 additional bytes: one for the snapshot itself, and one to zero out the log.
This seemed inefficient.

In the new version, the last step is entirely eliminated, and the log data
is invalidated alongside metadata. In select workloads, this improves throughput
by ~15% (B-Tree 100% insert).

### Allocator optimizations

We've received feedback about `pmemobj_reserve()` function that its performance
degrades significantly once the number of actions gets large. This made sense,
because the actions were kept in a linked-list, and that list was iterated over
every Nth reservation from the same allocation class. This made the allocation
process effectively O(n), where N is the number of pending reservations.

To address this problem, we've revamped the way reservations are tracked
internally, and reservation performance is now consistent regardless of how many
of them you've done. This enables new workloads that uses many temporary
persistent reservations and decides whether to actually publish them or not
later in the execution for the application.

### Manual management of transaction log buffers

There's one particularly nasty property of transactions that's difficult to
solve generically. What happens to a transaction when there's no space to
create the logs in? Should the transaction be aborted in an effort to clean up
some space? Well, but to clean up some space, we might need to...
run a transaction.

In libpmemobj, we have pre-allocated log buffers that enable the transaction to
grow to 3 kilobytes of data before any additional memory has to be dynamically
allocated. This means that an application is guaranteed to be able to, for
example, free up about 40 objects even when there's absolutely no memory
available in the heap. But that behavior is undocumented, and difficult to
rely on in practice.

And so to solve the above problem, we have created new APIs that allow the
application to take manual control over the buffers that are used for
a transaction. This allows applications to equip transactions with enough
memory so that they are guaranteed to succeed. We also provide
functions to calculate how much memory is required to execute transaction of
given parameters.

## librpmem 1.7

Our RDMA related efforts are still ongoing, and we've recently implemented an
optimization that splits the librpmem's persist into flush and drain operations
to be more inline with how the local memory persistence primitives work.

Before the change, librpmem's persist was synchronously replicating local
changes to the remote side, and was waiting to make sure that the data makes it
into the remote persistent domain. This is different than what's possible with
the equivalent libpmem flush and drain functions. This allows for the
application to take advantage of hardware parallelism and delay the expensive
operation, waiting for data to reach persistent domain, to a later time when
it's likely that the data was already flushed and there's no need to stall the
CPU.

The new implementation of librpmem's operation more closely follows the local
model, and the flush operations simply schedules and initiates the transfer,
and the drain method waits for previously initiated transfers to finish. This
enables asynchronous remote replication.

With these changes, all optimizations for local persistent memory, can also
benefit remote replication.

## Looking forward

This was a non-complete list of significant new PMDK additions, for more
information, see [PMDK 1.7 release announcement](https://github.com/pmem/pmdk/releases).

With this PMDK release done, we are now working hard towards the next one.
We've created a meta tracking issue for it [here](https://github.com/pmem/issues/issues/1133).
It will include, among other things:

- revamped low-level persistence library, libpmem2. It retains the best parts
  of libpmem and extends on it with new functionality, such as RAS support. We
  decided to create a new major version of libpmem because we couldn't fit the
  new features into the existing library given its constraints.
- Application-assisted defragmentation for libpmemobj, a new feature
  that will enable long-running workloads with diverse allocation sizes to
  efficiently use memory managed by libpmemobj's dynamic memory allocator.

If any of this seems interesting, please do let us know in the tracking issue
above. We appreciate all feedback.

Until next time,
PMDK Team

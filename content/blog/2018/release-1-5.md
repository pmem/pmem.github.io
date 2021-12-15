---
# Blog post title
title: 'New release of PMDK'

# Blog post creation date
date: 2018-10-22T19:55:17-07:00

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
aliases: ['/2018/10/22/release-1-5.html']

# Blog post type
type: 'post'
---

We've been very quiet on this blog as of late, mostly because of the amount of
work that we needed to put into our very ambitiously planned 1.5 release. But
we've made it, and there's finally time to get back to discussing the technical
minutiae of our work. In this post, we will go over the major library changes
that have been introduced in 1.5.

# Release planning in the open

But first, I'd like to highlight a change to our release planning process.
Instead of using a proprietary walled-off tool, we are slowly moving towards
doing our features planning entirely in the open, directly on GitHub. It's not
all the way there yet, but the [1.5](https://github.com/pmem/issues/issues/869)
release, and the upcoming [one](https://github.com/pmem/issues/issues/932),
are already using this new open process. When you enter one of the releases,
you will see that most of the planned features have their pages where they can
be discussed.

The goal of this change is to keep our users informed about future plans, but
also to encourage discussion with the community about the direction we are
taking. We'd like to invite everyone interested in PMDK to start directly
influencing and contributing to our future work.

Since PMDK project is still relatively small, this process isn't formalized.
Think we are missing a feature in a library? Write a comment on the release
planning page or create a new issue on our tracker, that's it.

I can promise that no feature request will get unanswered, at least for now.

# PMDK 1.5

This is a big release, with many quality of life enhancements, new features, and
performance improvements. We've also introduced an entirely new consistency
checking tool to complement pmemcheck.

To keep this relatively short, here I'll briefly describe each item, and the
bigger ones will get their posts explaining the details of the change.

## Reliability, Availability and Serviceability (RAS)

NVDIMMs are storage and as such have to deal with on-media errors and hardware
failures. But they are also memory, which means that an application can
encounter poisoned memory pages at runtime, and what's more, those poisoned
pages persist across restarts and need to be handled manually.

To account for that, we've worked on two main RAS features:

### Unsafe (dirty) shutdown count

This is used to detect if an ADR (feature on which persistent memory programming
model relies on to avoid having to flush the memory controller caches) failure
has occurred while a pool is open. Based on this information, the
library can determine whether the pool could have been corrupted or not.

### Bad blocks support

On Linux, if an application maps a persistent memory resident file with a
bad block, everything will work correctly up until the bad, poisoned, page is
accessed for the first time in the current instance of the application. That
first page fault will cause a SIGBUS, and the process will most likely be
terminated.

To prevent this cycle from happening over and over again, PMDK will now detect
if the files that the pool is composed of have any bad blocks, and if so, it
will refuse to open it. This allows the user to handle the failure gracefully.

In addition to that, we implemented recovery of bad blocks from poolset replicas.

This feature is currently not implemented for Windows Server platform and
disabled by default on Linux.

### libndctl dependency

To support the features mentioned above, libpmemobj on Linux now depends on
[libndctl](https://github.com/pmem/ndctl). NDCTL provides interfaces for
configuration and introspection of the libnvdimm kernel subsystem. PMDK uses it
to retrieve the information about health of the platform and NVDIMMs.

Unfortunately, at this moment retrieval of most of RAS-related information by
default requires superuser privileges. This means that, for example, open of a
libpmemobj pool would have to happen under sudo. To avoid that, the RAS features
described here are opt-in. This will be changed to opt-out once these access
restrictions are relaxed in the future kernel versions.

To enable or disable RAS features, we've developed a new pmempool command,
pmempool feature. You can find more information about on its [man page](/pmdk/manpages/linux/master/pmempool/pmempool-feature.1.html).

## pmreorder

Almost since the beginning of this project, we planned on implementing a tool
which could exhaustively check data consistency for all possible combination of
stores to the NVDIMM based on runtime binary instrumentation.
This would give us, and our users, confirmation that the algorithms inside of
our applications are in fact correct and fail-safe atomic.

We already had two takes on this problem, with the second one being almost good
enough for generic use. And so, after almost two years, we have finally decided
that it's time to revisit the topic and cleanup the old codebase so that it can
be released alongside pmemcheck.

Stay tuned for a blog post about pmreorder. In the meantime, see its
[man page](/pmdk/manpages/linux/master/pmreorder/pmreorder.1.html).

## Parameterized persistence primitives

Right from the start, libpmem shipped with custom-made memcpy/memset
implementations that made use of non-temporal stores (stores that do not go
through the CPU cache) in a deterministic fashion, which we leveraged to
optimize PMDK algorithms. But we've discovered optimization opportunities that
required even more control over the behavior of these functions, and so we
decided to add new variants of libpmem primitives (flush, memcpy, ...) that take
flags which control how the functions behave.

We found this so useful that we also exposed this functionality in libpmemobj
variants of persistence primitives.

For more information, see _pmemobj_memcpy_persist_ [man page](/pmdk/manpages/linux/master/libpmemobj/pmemobj_memcpy_persist.3).

## Removal of libpmemcto

This version of PMDK won't include the previously experimental libpmemcto. Due
to the complexity of that library, and the fact that it was based on a very
heavily modified fork of jemalloc, it has become challenging to maintain. For
those reasons, we've decided that we won't be continuing development of libpmemcto.

We recommend using [libmemkind](https://github.com/memkind/memkind) for volatile
applications and libpmem/libpmemobj for persistent ones. If you have a use case
that is not suitable for either one of those solutions, please let us know.

## libpmemobj changes

Since libpmemobj is our most important library, that's where we spent most of
our time. While there are only a few new features, the scope of changes is
broad due to our focus on transactional performance optimizations.

### Lazily-initialized volatile variables

The pmem synchronization primitives provided by libpmemobj are implemented
using an internal mechanism that calls an initialization function on the first
access to a variable. That mechanism could be useful for users wanting to employ
custom synchronization primitives or even implement entirely custom algorithms
that require volatile variables on persistent memory.

This a fairly small API addition which has a large number of potential use cases.
See its [man page](/pmdk/manpages/linux/master/libpmemobj/oid_is_null.3) for more details.

### Custom allocation class improvements

In the previous release of libpmemobj, we've included a feature to create custom
allocation class at runtime which can be used to optimize fragmentation and
performance of the heap.

The initial implementation had some limitations. The biggest of them was the 128
byte lower boundary for allocation class size. This was problematic for workloads
with smaller objects, which we observed during our Java and Python enabling efforts.

This limit has been now lifted, and the smallest allocation class possible is
1 byte, with some caveats.

Additionally, the API didn't allow for alignment of allocated objects to be
specified. This has been addressed, and allocations can now be aligned to any
power-of-two value.

See appropriate CTL namespace [entry point](/pmdk/manpages/linux/master/libpmemobj/pmemobj_ctl_get.3) for
more information.

### Rewrite of redo and undo log for transaction support

For the past couple of months, we've been optimizing libpmemobj's transactions
algorithms so that the amount of persistent memory cache misses and flushes is
minimized. The result is that both the redo and undo logs have been effectively
rewritten. They now use slightly more computationally expensive algorithms but
do not needlessly pollute the CPU cache, and they generate less traffic to
persistent memory.

The result is much faster persistent memory transactions, which can now rival
some less-optimized atomic algorithms.

We will soon post a detailed post about what specifically has changed, how we've
come to those optimizations, and what are the benchmarking results.

### New layout version and pmdk-convert

The performance optimizations I outlined above had one unfortunate consequence.
We were forced to change the on-media layout of the undo and redo logs in a way
that's incompatible with the previous version of the library. This means that
pools that were created using any of the previous libpmemobj versions, and had
unrecovered operations in the log, are not going to be compatible with the new
algorithms.

To provide users with an upgrade path, we've implemented a brand new
[pmdk-convert](/pmdk-convert/pmdk-convert.1.html) tool which will
automatically process the logs using the correct recovery
mechanism, and then will bump the major layout version to indicate that the pool
can be now opened using the new library version.

### C++

For quite some time now we've been looking for the most idiomatic and simple
solution to providing persistent C++ containers that could complement the
current library.

After many experiments, we've decided to implement our own STL-like containers.
This will be a long effort, but we think it's going to be ultimately worth it,
since we can optimize the on-media layouts and algorithms to fully exploit
persistent memory potential, but also because we can add useful
libpmemobj-specific semantics.

Due to this decision, we've moved libpmemobj-cpp to its own [repository](https://github.com/pmem/libpmemobj-cpp),
anticipating that it's soon going to become a much larger project.

We started the containers work easy, with `pmem::obj::array` that provides some
convenience features for use with transactions. See our
[doxygen](/libpmemobj-cpp/master/doxygen/structpmem_1_1obj_1_1experimental_1_1array.html) documentation for more info.

We've also renamed some function names that, in retrospect, were chosen
poorly. Functions with old names are still there, but are deprecated.
If you've been using libpmemobj-cpp, you should start seeing compile-time
warnings about that after upgrading the library. While we won't remove the old
functions for some time, we encourage everyone to update the function calls now.
See [this](https://github.com/pmem/libpmemobj-cpp/pull/75) pull request for more information.

# What's next?

With PMDK 1.5 almost behind us, ahead of us is
[PMDK 1.6](https://github.com/pmem/issues/issues/932).
In the upcoming release, we want to mostly work on the quality of life
improvements, minor performance optimizations that didn't make the cut this time,
and small new features.

Our focus will also be on improving documentation, which includes both tutorials
about how to use our library, especially the new features, as well as an
in-depth description of the algorithms that underpin PMDK.

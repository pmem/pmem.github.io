---
# Blog post title
title: "Introduction to pmemstream"

# Blog post creation date
date: 2022-01-26

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: "An overview of a new library - pmemstream"

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ""

# Blog post thumbnail
# eg: image: "/images/posts/my_blog_thumbnail.png"
image: "/images/posts/pmemstream_intro.png"

# Blog post author
author: "lukaszstolarczuk"

# Categories to which this blog post belongs
blogs: ['Pmemstream']
# Blog tags
tags: ["Intro"]

# Blog post type
type: "post"
---

# What is pmemstream?

Libpmemstream implements a pmem-optimized log data structure and provides stream-like access to data.
It presents a contiguous logical address space, divided into regions, with log entries of arbitrary size.
We intend for this library to be a foundation for various, more complex higher-level solutions. Read on
to learn about a few example use cases we have in mind. Like most libraries in the PMDK family, this one
also focuses on delivering a generic, easy-to-use set of functions.

Current implementation, examples, and known caveats are accessible at the [pmemstream GitHub page][pmemstream_gh].
Please bear in mind this library is still in an early stage of development. As this is a work in progress,
its behavior or APIs may change without prior notice.

# Why we implement it?

As keen PMDK users should know by now, we already have [libpmemlog][pmemlog_pmem_io] in our portfolio.
Even though it's very similar in basic concept, it's implemented in a straightforward manner and
does not allow easy extensions. Because we couldn't easily add all new features to the libpmemlog
we decided to design something new from scratch. If you're interested in our early rationale for creating
libpmemstream please see ["Feature Request" issue on PMDK's GitHub page][pmdk_feat_issue].

What we wanted is to provide a flexible implementation of stream/log structure, so it could be used in many
different solutions, e.g.:
- persistent double-write buffer in databases,
- persistent level in LSM tree implementations,
- a base for any copy-on-write data structure,
- transactional undo or redo logs, like in [libpmemobj][pmemobj_txs],
- PMEM-buffered file I/O primitives that offload to a slower medium.

We aim to deliver a set of functions with various parameters. Our intention is to ensure that pmemstream is
useful in a wide range of use cases. By creating this library, we hope we will eliminate the need for software
developers to create their own custom algorithms and structures for common patterns in storage and memory
solutions, especially those that utilize PMem.

Core features in libpmemstream are:
- new data entries (each of arbitrary size) are appended at the end (of a region),
- no data modification allowed (only appending),
- each entry append is atomic (no data consistency issues),
- iterators allow reading data in sequence (they provide stream-like functionality).

We hope everyone can benefit from a ready-to-use library. We wanted to assure it is as reliable as possible.
It's delivered with a set of tests and was validated with pmem debug tools. We guarantee persistency and
data integrity, which is especially important to handle on persistent memory. Using this library is easy and
doesn't require a broad knowledge of lower-level pmem libraries. And finally, PMDK developers all are
active members of pmem community, always ready to help and eager to solve issues.

# What the future holds?

First of all, as I wrote above - this library is still a work in progress, not yet ready for production code.
We continue to extend it with new features, new tests, all kinds of fixes, and even API refactors/extensions.
Some of the claims I made earlier (especially about guarantees) may not yet be entirely true. However, once
the library reaches its first release, we will guarantee correctness and reliability.

There are at least several enhancements we are considering implementing as of today. Many of our ideas are
written down (and labeled with "Type: Feature") on our [issues page on GitHub][pmemstream_gh_issue].

The most significant features are:
- [support for multi-process access][pmemstream_gh_multi] - at most one writer process, multiple reader processes.
- [asynchronous API][pmemstream_gh_async] (using, e.g., another work-in-progress library - [miniasync][miniasync_gh]).
    Right now, all functions are "blocking", and we want to change it for better performance and a more flexible
    user experience. It will allow pmemstream to more efficiently utilize asynchronous data movement interfaces,
    like [DSA][intel_dsa] or async I/O (e.g., io_uring). It will also allow exposing different types of medium
    underneath (e.g., block devices with higher latency).
- RDMA support. We'd like to enable storing data using RDMA protocol. This could be considered as yet another
    type of medium - "remote memory". Users could easily use remote stream, e.g., for backup, balancing workload,
    simple but extremely fast remote message passing, or any other use cases. We plan on implementing this using
    another of our libraries - [librpma][rpma_gh].

The above list is, of course, in no way complete, and we are likely to change plans as we learn.

I hope it goes without saying, but if you find something missing in the library or want to share some
feedback with us (perhaps, an improvement idea, or just to show your interest), don't hesitate to
[file an issue on our GitHub page][pmemstream_gh_issue]. Alternatively, you can always catch us on
[various channels of communication][community_pmem_io].


[pmdk_feat_issue]: https://github.com/pmem/pmdk/issues/4930
[pmemstream_gh]: https://github.com/pmem/pmemstream
[pmemstream_gh_issue]: https://github.com/pmem/pmemstream/issues
[pmemstream_gh_async]: https://github.com/pmem/pmemstream/issues/76
[pmemstream_gh_multi]: https://github.com/pmem/pmemstream/issues/75
[pmemlog_pmem_io]: /pmdk/libpmemlog/
[pmemobj_txs]: /blog/2015/06/an-introduction-to-pmemobj-part-2-transactions/
[community_pmem_io]: /community/
[miniasync_gh]: https://github.com/pmem/miniasync
[intel_dsa]: https://01.org/blogs/2019/introducing-intel-data-streaming-accelerator
[rpma_gh]: https://github.com/pmem/rpma

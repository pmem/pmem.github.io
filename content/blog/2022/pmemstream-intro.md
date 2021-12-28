---
# Blog post title
title: "Introduction to pmemstream"

# Blog post creation date
date: 2022-01-12

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

# What is (lib)pmemstream?

In a few words, (lib)pmemstream is a library:
 - optimized for persistent memory (the "pmem" part),
 - providing logging data structure (the "stream" part).

Like most libraries in PMDK family, this one also focuses on delivering a generic, easy-to-use
set of functions. pmemstream implements a persistent logging data structure and provides stream-like
access to data. It presents a contiguous logical address space, divided into regions, with
log entries of arbitrary size. This library may be foundational for various higher-level solutions
(example use cases are mentioned later in this blog post).

Current implementation, examples, and known caveats are accessible at the [pmemstream GitHub page][pmemstream_gh].
Please bear in mind this library is still in an early stage of development. As this is a work in progress,
it may change the behavior or the API without any announcement or any backward compatibility assurance.

# Why we implement it?

Keen PMDK users should know by now - we already have [libpmemlog][pmemlog_pmem_io] in our portfolio.
Even though it's very similar in basic concept, it's implemented in a straightforward manner and
does not allow easy extensions. When you read our early rationale for creating libpmemstream -
["Feature Request" issue on PMDK's GitHub page][pmdk_feat_issue] - you'll see we wanted something more.
We couldn't easily add all new features to the libpmemlog, so we decided to design it from scratch.

What we wanted is to provide a flexible implementation of stream/log structure, so it could be used in many
different solutions, e.g.:
- persistent double-write buffer in databases,
- persistent level in LSM tree implementations,
- a base for any copy-on-write data structure,
- transactional undo or redo logs, like in [libpmemobj][pmemobj_txs],
- PMEM-buffered file I/O primitives that offload to a slower medium.

We aim to deliver a set of functions with various parameters, so this library will be helpful in a wide range
of use cases. We understand every developer would have to write many boilerplate lines of code to get a logging
data structure like pmemstream. We want to make sure it's already implemented for all users.

Core features in libpmemstream are:
- new data entries (each of arbitrary size) are appended at the end (of a region),
- no data modification allowed (only appending),
- each entry append is atomic (no data consistency issues),
- iterators allow reading data in sequence (they provide stream-like functionality).

We hope everyone can benefit from a ready-to-use library:
- it's delivered with a set of tests - we wanted to assure it's as error-prone as possible,
- we checked it with pmem debug tools, so we want to guarantee persistency and data integrity
    (which is especially important to handle on persistent memory),
- there's no requirement to have a broad knowledge of lower-level pmem libraries,
- we all are active members of pmem community, always ready to help and eager to solve issues.

# What the future holds?

First of all, as I wrote above - this library is still a work in progress, not yet ready for production code.
We keep extending it with new features, new tests, all kinds of fixes, and even API refactors/extensions.
Some of the words written above (especially about guarantees) may not yet be entirely true, but we do our best
to keep the promise at "the end of the day".

The main features, still to come as of today, are:
- support for concurrency. We plan to add support for multi-processes access - at most one writer process,
    multiple reader processes.
- asynchronous API (using, e.g., another work-in-progress library - [miniasync][miniasync_gh]).
    Right now, all functions are "blocking", and we want to change it for better performance
    and a more flexible user experience. It will also allow exposing different types of medium
    underneath (e.g., block devices with higher latency or [DSA][intel_dsa]).
- RDMA support. We'd like to enable storing data using RDMA protocol. This could be considered as yet another
    type of medium - "remote memory". Users could easily use remote stream, e.g., for backup, balancing workload,
    or any other use cases. We'll consider implementing this using another of our libraries - [librpma][rpma_gh].

**Disclaimer** - the above list may be updated (with new features or, on the contrary, with removed items)
at any time, based on our priorities and users' interest in specific features.

I hope it goes without saying, but if you find something missing in the library or want to share some
feedback with us (perhaps, an improvement idea, or just to show your interest), don't hesitate to
[file an issue on our GitHub page][pmemstream_gh_issue]. Alternatively, you can always catch us on
[various channels of communication][community_pmem_io].


[pmdk_feat_issue]: https://github.com/pmem/pmdk/issues/4930
[pmemstream_gh]: https://github.com/pmem/pmemstream
[pmemstream_gh_issue]: https://github.com/pmem/pmemstream/issues
[pmemlog_pmem_io]: /pmdk/libpmemlog/
[pmemobj_txs]: /blog/2015/06/an-introduction-to-pmemobj-part-2-transactions/
[community_pmem_io]: /community/
[miniasync_gh]: https://github.com/pmem/miniasync
[intel_dsa]: https://01.org/blogs/2019/introducing-intel-data-streaming-accelerator
[rpma_gh]: https://github.com/pmem/rpma

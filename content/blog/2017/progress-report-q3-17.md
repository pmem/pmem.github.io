---
# Blog post title
title: 'Progress Report Q3 2017'

# Blog post creation date
date: 2017-10-05T19:55:17-07:00

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
blogs: ['Report']

tags: []

# Redirects from old URL
aliases: ['/2017/10/05/progress-report-q3-17.html']

# Blog post type
type: 'post'
---

The last quarter was rather... peaceful. But nevertheless there were a few
noteworthy things.

### FreeBSD & ARM

We always asserted that our library is multi-platform and hardware agnostic...
as long as your platform is a recent distribution of Linux (or Windows)
on x86 hardware :)

Two things happened that intend to change the current status quo:

- There's an active and ongoing effort of porting the linux-specific
  parts of the library to FreeBSD, mostly led by [@gaweinbergi](https://github.com/gaweinbergi).
  Our code is naturally very linux centric due to being developed
  alongside the kernel support for NVDIMMs, and there are quite a few
  hurdles to overcome, but everything seems to be going along quite nicely.
- Patch that ports the low-level library (libpmem) to ARM has been
  submitted to our repository by [@vvenkates27](https://github.com/vvenkates27).
  This is also a place where there's a lot of platform specific code due to
  the novelty of the topic and the fact that the library implementation requires a
  hefty amount of ISA specific intrinsics.

### Allocation classes merged

I've talked about this previously, so I won't repeat myself here, but the
allocation classes were finally merged last quarter.

For anyone to whom this is a topic of interest, I suggest looking at the
[slab
allocator example](https://github.com/pmem/pmdk/tree/master/src/examples/libpmemobj/slab_allocator)
that shows the exact use case this feature is intended for.

### Optimized startup time

Here's a short, but hopefully interesting, bit of news: the happy-case
performance of `pmemobj_open()` has increased by two orders of magnitude due to
strategic usage of lazy initialization.

### Bad blocks & high-availability features

Just as in traditional disks, NVDIMMs can have bad physical blocks on the medium.
The specification on how those blocks are exposed has been recently published
with [ACPI 6.2](https://www.uefi.org/sites/default/files/resources/ACPI_6_2.pdf).

From linux kernel file system perspective, the NVDIMM badblocks are exposed
like in traditional storage, and can be cleared through normal fs interfaces.

The story changes a bit when the memory is mapped in the address space of the
application - unsurprisingly, the semantics are similar to those of bad volatile
memory - a `SIGBUS` with appropriate flags is generated if a poisoned (bad) page
is touched by the program.

Regular software doesn't really have to worry about poisoned pages because memory
is volatile and a `SIGBUS` normally terminates the application, and when started
again, everything will be back in order.

Our initial approach focuses on letting the application terminate when a `SIGBUS`
is encountered, and then provide a set of offline tools that allow seamless
recovery of data from replicas.

The work is still in progress and the relevant PR is [here](https://github.com/pmem/pmdk/pull/2246).

### Conclusion

Vacation season might appear to have taken a toll this time around, but that's
mostly just that - an appearance. The vast majority of changes this quarter were
fixes and tiny improvements.

For more exciting developments, take a look at
[pmemkv](https://github.com/pmem/pmemkv),
[pynvm](https://github.com/pmem/pynvm) or [pmse](https://github.com/pmem/pmse).

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

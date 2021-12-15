---
# Blog post title
title: 'Introducing pmemkv'

# Blog post creation date
date: 2017-02-21T19:55:17-07:00

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
author: 'RobDickinson'

# Categories to which this blog post belongs
blogs: ['pmemkv']

tags: []

# Redirects from old URL
aliases: ['/2017/02/21/pmemkv-intro.html']

# Blog post type
type: 'post'
---

We've blogged before about
[building](/blog/2015/07/transactional-key-value-store-using-libpmemobj-diy) and
[optimizing](/blog/2015/09/kv-store-improved-measured)
key-value stores for persistent memory, and we're excited to
put these ideas to the test in a more formal way.

Our new **[pmemkv](https://github.com/pmem/pmemkv)** project is
an open-source key-value store that is optimized
for read-heavy workloads on persistent memory.
Compared with key-value stores based on the
[LSM algorithm](https://en.wikipedia.org/wiki/Log-structured_merge-tree),
pmemkv offers higher read performance and lower write amplification.
But our intent is not to deter use of LSM, only to expand the choices
developers and architects have for aligning workloads to backing stores.

Internally [pmemkv](https://github.com/pmem/pmemkv) uses a B+ tree where
inner nodes are kept in DRAM and leaf nodes are exclusively stored in
persistent memory. Mixing types of memory allows pmemkv to offer both
per-operation consistency and good performance. The diagram below shows
a simple example of what pmemkv looks like internally.

![pmemkv internals](/images/posts/pmemkv1.png)

Obviously [pmemkv](https://github.com/pmem/pmemkv) is still in its early
stages and is not yet ready for production use, but a good
[issue backlog](https://github.com/pmem/pmemkv/issues)
has been started to capture what we intend to do next.

Like PMDK itself, [pmemkv](https://github.com/pmem/pmemkv) is
open-source software (under BSD license) and community contributions
are welcomed!

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/NVML-is-now-PMDK).]

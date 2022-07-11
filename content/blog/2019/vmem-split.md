---
# Blog post title
title: 'Vmem is split out of PMDK'

# Blog post creation date
date: 2019-10-31T19:55:17-07:00

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
author: 'kilobyte'

# Categories to which this blog post belongs
blogs: ['Vmem']

tags: []

# Redirects from old URL
aliases: ['/2019/10/31/vmem-split.html']

# Blog post type
type: 'post'
---

### Introduction

We have just split **libvmem** and its companion **libvmmalloc** out of the
PMDK tree. They now live in a
[separate repository](https://github.com/pmem/vmem/), and will follow their
own release cadence. And, as these libraries are considered mature and
finished, no new releases are planned once the split has been tested and
tagged -- except for defects and new requirements of underlying platforms.

### Further development

**libvmem** remains the only way to use filesystem-managed persistent memory
for volatile allocations on Windows.

On Linux, though, you are better served by
[memkind](https://memkind.github.io/memkind/) instead of **libvmem** in new
code -- it provides extra features such as NUMA awareness and handling of
other kinds of memory.

As for **libvmmalloc**, new kernel features allow redirecting an unported
program to volatile persistent memory by attaching that memory to a separate
NUMA node you can then assign your program to with `numactl -m` or
`numactl --preferred`.

### Effects on PMDK

Besides separating out unrelated modes of use (PMDK is meant for
**Persistent** usage), **libvmem** included a different build system and
a test suite, frustrating maintenance of PMDK and making clean-ups hard.
Splitting VMEM out already revealed an issue fixing which sped up compiles
by a factor of 4.

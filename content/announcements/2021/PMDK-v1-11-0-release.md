---
# News article title
title: "PMDK v1.11.0 Release"

# Creation date
date: 2021-07-02T20:17:04Z

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "https://opengraph.githubassets.com/eb0512ba9246f5516b3bc208884b474bc5e2cb586938e8f017fd6bf54bc9eee3/pmem/pmdk"

# Announcement category
announcements: ['PMDK']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---

We've just released a new version of PMDK. I'd like to thank everyone who contributed. You can see the list of notable changes below:

This release:

- Adds new APIs for libpmem2, most notably there are new functions to shrink and extend an existing reservation and a new iterator API for mappings contained within an existing reservation. There's also a new function to retrieve a numa node for a source.
- Makes the pmemobj_open() and pmemobj_close() functions from libpmemobj thread-safe, making it easier to correctly manage persistent memory pools in a parallel environment.
- Introduces a new API in libpmemobj to globally change the method of assigning arenas to threads. The default is to rely on an OS per-thread key to store arena information, and this release introduces an option to avoid the use of thread-local keys by simply using one global arena for all threads in a pool.

Other changes and notable bug fixes:

- pmem2: don't force smaller alignment for fsdax mappings
- rpmem: various fixes for powerpc64le
- doc: fix documentation of pmem_is_pmem()
- common: fix various minor problems found by static analysis

This release introduces no changes to the on-media layout and is fully compatible with the previous version of PMDK.

As always, this release is available on github: https://github.com/pmem/pmdk/releases/tag/1.11.0

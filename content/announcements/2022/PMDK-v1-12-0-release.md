---
# News article title
title: "PMDK v1.12.0 Release"

# Creation date
date: 2022-05-25T16:05:26+02:00

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "https://opengraph.githubassets.com/73d8f958e855904dc0776a7d77d0f0d3698a65b1/pmem/pmdk"

# Announcement category
announcements: [PMDK']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---
We've just released a new version of PMDK. I'd like to thank everyone who contributed.

In this release, we are deprecating the librpmem library and rpmemd tool. 
Using the librpmem library API
will result in warnings and is no longer recommended. Those libraries and
remote replication in libpmemobj were our experiments to try out ideas on combining
persistent memory with RDMA. We learned from this experiment that this approach
is not a good way to combine those technologies. Based on these learnings,
we implemented the [librpma](/rpma/), which is currently developed by our team
and can be accessed on the [github repository](https://github.com/pmem/rpma).
The old approach has become a considerable maintenance cost for the team,
blocking some potential PMDK optimizations. This is the final warning,
before removing librpmem, along with remote replication in libpmemobj, in the
next PMDK release, so please let us know if you have any concerns with this decision.

In this release, we also introduce asynchronous data movement operations in
the libpmem2. This API is marked as experimental, and to enable it, you
have to `#define PMEM2_USE_MINIASYNC` before `#include <libpmem2.h>`.
Additionally, this API requires that you also have the [miniasync library](https://github.com/pmem/miniasync) installed.
To learn more about this feature, miniasync library, and our overall approach to the
asynchronous programming with persistent memory, please read our [blog posts](/tags/miniasync/)

Other changes and notable bug fixes:
- pmem2: Add new API to machine safe read/write operations
- common: Adds experimental support for RISC-V
- common: supress false positve of '-Wunused-parameter'
- examples: remove unnecessary persists in pminvaders
- obj: fix invalid type when setting cache size ctl (pmem/issues#5291)
- pmem: fix eADR memmove and memset (pmem/issues#5364)
- pmem2: add addr alignment prediction to vm reservation
- pmreorder: update docs with required last arg - file_name
- pmreorder: add more debug/info logs

This release introduces no changes to the on-media layout and is fully compatible with the previous version of PMDK.

As always, this release is available on GitHub: https://github.com/pmem/pmdk/releases/tag/1.12.0

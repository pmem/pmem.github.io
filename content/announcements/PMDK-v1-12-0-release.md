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
We've just released a new version of PMDK. I'd like to thank everyone who contributed. You can see the list of notable changes below:

This release:
- Deprecates librpmem library and rpmemd tool.
Using the librpmem library API will result in warnings
and is no longer recommended. Those interested in a remote
persistent memory support should use the new
rpma (https://github.com/pmem/rpma) library.
- Introduces a new set of APIs in libpmem2 to perform asynchronous data
movement operations. To use this feature, software needs to include an
optional miniasync(7) dependency.
- Adds new API to machine safe read/write operations in the pmem2 library.
- Introduces support for movdir64b instruction for memory operation
functions in the libpmem2 library.
- Adds experimental support for RISC-V.

Other changes and notable bug fixes:
- common: supress false positve of '-Wunused-parameter'
- examples: remove unnecessary persists in pminvaders
- obj: fix invalid type when setting cache size ctl (pmem/issues#5291)
- pmem: fix eADR memmove and memset (pmem/issues#5364)
- pmem2: add addr alignment prediction to vm reservation
- pmreorder: update docs with required last arg - file_name
- pmreorder: add more debug/info logs

This release introduces no changes to the on-media layout and is fully compatible with the previous version of PMDK.

As always, this release is available on github: https://github.com/pmem/pmdk/releases/tag/1.12.0

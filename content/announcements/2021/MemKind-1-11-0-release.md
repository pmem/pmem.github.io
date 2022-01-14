---
# News article title
title: "Memkind v1.11.0 Release"

# Creation date
date: 2021-02-26T20:22:59Z

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "https://opengraph.githubassets.com/f8ccbea600be4efbbb4eb4be9dd21a316c1237aede16f1efd7558874b67eb1bc/memkind/memkind"

# Announcement category
announcements: ['Memkind']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---

Hi,

I am pleased to announce that memkind 1.11.0 has been released.
In the memkind 1.11.0 release, we focus our efforts on support for the Heterogeneous Memory Attribute Table (HMAT).
With HMAT, we recognize different memory types (e.g., high bandwidth memory, low latency memory) in a unified way.

With this release comes:

- Provided a function to verify if path supports DAX - memkind_check_dax_path()
- Provided a NUMA node interleave variant for MEMKIND_DAX_KMEM (MEMKIND_DAX_KMEM_INTERLEAVE)
- Provided a function to enable/disable background threads - memkind_set_bg_threads()
- Provided a function to print statistics - memkind_stats_print()
- Extended configure mechanism by enable-memkind-initial-exec-tls option
- Extended memkind to support HMAT
- Provided memory property abstraction (MEMKIND_HIGHEST_CAPACITY*,MEMKIND_LOWEST_LATENCY_LOCAL*, MEMKIND_HIGHEST_BANDWIDTH_LOCAL*)*
- *Extended support for MEMKIND_HBW* to platforms with full HMAT support (kernel+hardware)

The source code and release note are available in:
[https://github.com/memkind/memkind/releases/tag/v1.11.0](https://github.com/memkind/memkind/releases/tag/v1.11.0)

Best Regards, 
Michal

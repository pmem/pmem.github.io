---
# News article title
title: "PMemKV v1.3 Release"

# Creation date
date: 2020-10-02T20:32:56Z

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "https://github.com/pmem/pmemkv/raw/master/doc/architecture.png"

# Announcement category
announcements: ['PMemKV']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---

Hello Community,


I’m pleased to announce that pmemkv 1.3 has been released and is available from our GitHub pages.

This release introduces a new experimental engine - radix (single-threaded sorted map, backed by libpmemobj-cpp's radix_tree container).

We have also extended the configuration class API and redesigned the optimized version of stree engine.

Features:
- radix engine (single-threaded sorted map)
- config setters with type safety for common config fields
- stree engine optimization (single-threaded sorted map with custom comparator support)

Major fixes:
- fixed operator== in stree engine
- fixed missing checks in move assignment operators

Other changes:
- operator<< for pmem::kv::status

Known issues:
- vcmap (github issues #623, #798)

Source code and detailed changelogs can be found here:
[https://github.com/pmem/pmemkv/releases/tag/1.3](https://github.com/pmem/pmemkv/releases/tag/1.3)


/Szymon 

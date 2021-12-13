---
# News article title
title: "PMDK Bugfix Releases"

# Creation date
date: 2021-09-24T19:54:24Z

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: "PMDK Bugfix Release"

# Event image
image: "https://opengraph.githubassets.com/eb0512ba9246f5516b3bc208884b474bc5e2cb586938e8f017fd6bf54bc9eee3/pmem/pmdk"

# Announcement category
announcements: ['PMDK']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---
All,

I'm pleased to announce three bugfix releases of PMDK,

You can find source code and ChangeLogs on the following pages:
- [https://github.com/pmem/pmdk/releases/tag/1.11.1](https://github.com/pmem/pmdk/releases/tag/1.11.1)
- [https://github.com/pmem/pmdk/releases/tag/1.10.1](https://github.com/pmem/pmdk/releases/tag/1.10.1)
- [https://github.com/pmem/pmdk/releases/tag/1.9.3](https://github.com/pmem/pmdk/releases/tag/1.9.3)

The major highlight of this release is a fix for a missing sfence in non-temporal version of memcpy function. Here you can find details about this issue: [https://github.com/pmem/pmdk/issues/5292](https://github.com/pmem/pmdk/issues/5292)

Łukasz Plewa

---
# News article title
title: "PMDK v1.10.0 Release"

# Creation date
date: 2020-10-28T20:28:48Z

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

Hi all,

I just wanted to share with everyone that we've just released PMDK Version 1.10. Alongside the usual small fixes and improvements, this release stabilizes the API for something we've been working on for months now: libpmem2.

As the name suggests, it's the next major version of the libpmem library. This library has an entirely new, but familiar API that addresses many shortcomings of the previous version while retaining all of its functionality.

One of the key things that libpmem2 API has, and which was missing in libpmem, is comprehensive support for RAS (Reliability, availability and serviceability), which allow applications to easily handle bad blocks or unsafe shutdown count.

Sounds interesting? See [https://pmem.io/pmdk/libpmem2/](https://pmem.io/pmdk/libpmem2/) for more information and examples.

If you are actively using the current version of libpmem and are happy with the current API and its functionality, there's no need to rush an upgrade to libpmem2. We are committed to supporting both libraries.
But if you are thinking about your first foray into low-level Persistent Memory programming or were missing some functionality in libpmem, I strongly encourage you to give libpmem2 a try.

Piotr

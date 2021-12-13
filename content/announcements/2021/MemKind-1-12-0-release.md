---
# News article title
title: "MemKind 1.12.0 Release"

# Creation date
date: 2021-09-02T20:11:31Z

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "https://opengraph.githubassets.com/f8ccbea600be4efbbb4eb4be9dd21a316c1237aede16f1efd7558874b67eb1bc/memkind/memkind"

# Announcement category
announcements: ['MemKind']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---

Hi,

I’m glad to announce that [memkind](https://memkind.github.io/) 1.12.0 has been released.

Most notably, this release introduces memory tiering feature provided in memkind as an interposer library. The library allows making allocations with the usage of multiple kinds keeping a specified ratio between them. This ratio determines how much of the total allocated memory should be allocated with each kind.

Further details on this brand new feature can be found here:

[http://memkind.github.io/memkind/man_pages/memtier.html](http://memkind.github.io/memkind/man_pages/memtier.html)

The source code and the release note are available under the following link:

[https://github.com/memkind/memkind/releases/tag/v1.12.0](https://github.com/memkind/memkind/releases/tag/v1.12.0)

Best Regards,

Patryk

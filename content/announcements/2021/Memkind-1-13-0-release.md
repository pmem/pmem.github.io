---
# News article title
title: "Memkind 1.13.0 Release"

# Creation date
date: 2021-12-23T12:59:41+01:00

# Draft as a default
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

I’m glad to announce that [memkind](https://memkind.github.io/) 1.13.0 has been released.

Most notably, this release introduces a fixed kind for user-supplied memory area (along with the C++ allocator). Creating a fixed kind can be done by using memkind_create_fixed() function.
This release also includes a new function to get the memory capacity of nodes available to a given kind - memkind_get_capacity().

For more information, see memkind man_page:

[http://memkind.github.io/memkind/man_pages/memkind.html](http://memkind.github.io/memkind/man_pages/memkind.html)

The source code and the release note are available under the following link:

[https://github.com/memkind/memkind/releases/tag/v1.13.0](https://github.com/memkind/memkind/releases/tag/v1.13.0)

Best Regards,

Patryk

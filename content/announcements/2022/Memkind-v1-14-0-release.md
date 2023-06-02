---
# News article title
title: "Memkind 1.14.0 Release"

# Creation date
date: 2022-07-08

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
featured: true
---

We've recently released a new version of the memkind library. Many thanks to all who contributed for making this release happen.

Most notably, this release upgrades the internally used jemalloc version to the most recent one – 5.3.0.
This release also includes a binary for enabling memkind tiering functionality in an easy way. One can use this binary to run
the given program with the usage of the memkind tiering feature with just a minimal configuration input from the user.
See the memkind manpage memtier(1) for more information.

Moreover, memkind tiering header file was added to the installed headers allowing for using this API with the installed version of memkind.
Missing information regarding the usage of memkind tiering API functions has been added to manpages.

The source code is available under the following link:
https://github.com/memkind/memkind/releases/tag/v1.14.0

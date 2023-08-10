---
# News article title
title: "PMDK v2.0.0 Release"

# Creation date
date: 2023-08-10

# Publish immediately.
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "https://opengraph.githubassets.com/73d8f958e855904dc0776a7d77d0f0d3698a65b1/pmem/pmdk"

# Announcement category
announcements: ['PMDK']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: true
---

We are pleased to announce the release of PMDK 2.0.0.

The 2.0.0 release:

Removes:
- support for Windows/FreeBSD,
- libpmemlog and libpmemblk libraries,
- pmem2_async experimental functions.

Simplifies the validation by:
- removing experimentally supported PowerPC and ARM architectures from the regular execution,
- dropping Fedora and Debian.

Extend the documentation by:
- Ansible playbook examples on platform provisioning instructions for the PMDK testing.

Detailed release notes and source code are available on GitHub: [PMDK Version 2.0.0](https://github.com/pmem/pmdk/releases/tag/2.0.0)

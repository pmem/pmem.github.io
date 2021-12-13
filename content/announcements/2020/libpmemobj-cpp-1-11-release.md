---
# News article title
title: "libpmemobj-cpp v1.11 Release"

# Creation date
date: 2020-09-30T20:40:40Z

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "https://opengraph.githubassets.com/ebdfb662006b94646c2ff524b66e5aacca499fd14e47e423c30e80c50a148612/pmem/libpmemobj-cpp"

# Announcement category
announcements: ['PMDK']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---

Hello Community,

I’m pleased to announce that libpmemobj-cpp 1.11 has been released and is available from our GitHub pages.

This release introduces a new experimental container - persistent radix_tree along with inline_string and string_view classes.

It also introduces a new pointer type: self_relative_ptr with std::atomic specialization.

New features:
- experimental radix_tree container (single-threaded sorted map)
- experimental inline_string class (class serves a similar purpose to pmem::obj::string, but keeps the data within the same allocation as inline_string itself)
- string_view class (support for compilers older than C++17)
- experimental self_relative_ptr and std::atomic<self_relative_ptr> specialization (persistent smart ptr which encapsulates the self offseted pointer and provides member access, dereference and array access operators - for some workloads it could be a faster alternative for persistent_ptr)


Optimizations:
- optimizations for concurrent_map with self_relative_ptr



Other changes:
- missing constructors for basic_string class
- conversion operator for basic_string class
- ported libcxx's sorted map tests
- lower and lower_eq methods for concurrent_map
- missing constructor for concurrent_map (comparator as an argument)

Source code and detailed changelogs can be found here:

[https://github.com/pmem/libpmemobj-cpp/releases/tag/1.11](https://github.com/pmem/libpmemobj-cpp/releases/tag/1.11)


/Szymon 

---
# News article title
title: "pmemstream v0.2.0 release"

# Creation date
date: 2022-06-28

# Publish immediately. 
draft: false

# Hero image
hero_image: "images/news/my_news_article_hero.jpg"

# Brief description
description: ""

# Event image
image: "/images/posts/pmemstream_intro.png"

# Announcement category
announcements: ['pmem']

# Post type
type: "announcement"

# Featured. Specify true or false to show on homepage
featured: 
---

The second (still minor) release (0.2.0) of pmemstream project is here!

It contains many functional updates, API changes, and refactors, along with new tests and examples.
As always, this release is available on GitHub: https://github.com/pmem/pmemstream/releases/tag/0.2.0 .
Big thanks to everyone, who contributed!

One of the biggest changes is the introduction of Asynchronous API. It makes use of
[recently released miniasync library](/announcements/2022/miniasync-v0-1-0-release/).
It's an extension to our existing synchronous API. `pmemstream_append` and `pmemstream_publish`
gained async counterparts - `pmemstream_async_append` and `pmemstream_async_publish`. With this update,
we also exposed two stages an append can be in: "committed" or "persisted". In a few words, it means that
committed (but not yet persisted) data will be visible for iterators, but they might not be reachable
after the application restart. Persisted data, on the other hand, is guaranteed to be committed and will
be reachable after the application's restart. When an asynchronous append is started, we don't know when
exactly it ends. That's why we introduced `pmemstream_async_wait_committed` and `pmemstream_async_wait_persisted`,
which take `timestamp` as an argument. These functions allow us to wait for specified entry to be
either available for reading or guaranteed persistent.

We also implemented support for multiple regions. For now, our simple region allocator allows
only a single region size (one selected region size for all regions within the given stream instance).
A region can be treated as a concurrency isolation unit. It enabled us to append data concurrently into
stream. As of right now, multiple threads can append data concurrently, but only to different regions.
It means no two threads can append data to the same region.

Timestamps mentioned above are also something brand new in the pmemstream. Each entry is now persistently
marked with a unique, ascending timestamp. It provides global entries' ordering. It means it's possible
to append entries to different regions and still be able to read them out in a global (stream's) sequence.

Next change was applied to our iterators `pmemstream_region_iterator` and `pmemstream_entry_iterator`.
Both of these iterators provide now `seek_first`, `next`, and `is_valid` functions. Previously
it was all hidden within `new` and (the old version of) `next`. We discovered it wasn't suitable for
all use cases, and decided it'd be much more flexible to split functionalities into several
functions. You can browse [our examples](https://github.com/pmem/pmemstream/tree/master/examples)
to see how easy it is to iterate over regions and entries.

A new function `pmemstream_region_usable_size` was also added to our API. Its goal is to read currently
available (free) size in a given region. It can be used with the combination of `pmemstream_region_size`
function, to calculate, e.g., the approximate percentage of region's used space. Based on such information,
you can, e.g., decide to allocate a new region for some future appends.

We are also no longer missing documentation of our public API. We've added a complete description
for each function in the public header, and we delivered a readable version of the documentation
in the form of installable manpages.

There are also a few minor changes, not as relevant as the above ones, but still worth mentioning:
- We've renamed `pmemstream_entry_length` to `pmemstream_entry_size`. It's more consistent with our existing
    naming convention (see e.g. `pmemstream_region_size`).
- For our internal benchmarking, we introduced a simple "append" micro-benchmark.
- Lots and lots of tests were added, i.a. enabling integrity, consistency, and sanitizers checks.
- We believe the best way to understand a new project is through examples, so we've added four more of these.

Sources, examples, and details can be found on [our GitHub page](https://github.com/pmem/pmemstream).
If you have any concerns, there's an **Issues** section where you can leave a question/comment/request for us.

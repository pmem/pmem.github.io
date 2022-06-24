---
# News article title
title: "pmemstream v0.2.0 Release"

# Creation date
date: 2022-06-24

# XXX: fix date with the actual release date!!!

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
Big thanks to everyone, who contributed!

One of the biggest changes is introduction of Asynchronous API. It makes use of
[recently released miniasync library](/announcements/2022/miniasync-v0-1-0-release/).
This API allows concurrent data appends to one stream. As of right now, multiple threads
can append data concurrently, but only to different regions. It means no two threads can
append data to the same region. This API is an extension to our existing synchronous API.
`pmemstream_append` and `pmemstream_publish` gained async counterparts - `pmemstream_async_append`
and `pmemstream_async_publish`. With this updates we also exposed two stages an append can be in:
"committed" or "persisted". In few words this means that data, which is committed (but not yet persisted)
will be visible for iterators but might not be reachable after application restart. Persisted data
is guaranteed to be committed and will be reachable after application's restart. When asynchronous
append is started we don't know when exactly it ends. That's why we introduced
`pmemstream_async_wait_committed` and `pmemstream_async_wait_persisted`, which takes `timestamp` as
an argument. These functions allow us to wait for specified entry to be either available for reading,
or guaranteed persistent.

Mentioned above timestamps is also something new. Each each entry is now marked with a timestamp. Beside
helping out with asynchronous appends it provides global entries' order (and easier recovery).

- Since the concurrent appends required a good isolation unit, we introduced multiple regions.
...

- Changed region and entry iterators API. It now provides `seek_first`, `next`, and `is_valid`
		functions for both types of iterators. It offers higher flexibility.
...

- Exposed `pmemstream_region_usable_size` function to read the currently available size
		in a given region; it can be used with the combination of "entire region size" function
		(`pmemstream_region_size`) to, e.g., calculate the already used region's space.
...

- Full documentation of public API in the form of manpages.
...


There are also few minor changes, not as relevant as the above ones, but still worth mentioning:
- We've renamed `pmemstream_entry_length` to `pmemstream_entry_size`. It's more consistent with our existing
    naming convention (see e.g. `pmemstream_region_size`).
- For our internal benchmarking we introduced a simple "append" micro-benchmark.
- Lots and lots of tests were added, i.a. enabling integrity, consistency, and sanitizers checks.
- We believe the best way to understand new project is through examples, so we've added four more of these.


As always, this release is available on GitHub: https://github.com/pmem/pmemstream/releases/tag/0.2.0

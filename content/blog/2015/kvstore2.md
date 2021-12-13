---
# Blog post title
title: 'KV-store improved & measured'

# Blog post creation date
date: 2015-09-10T19:55:17-07:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: ''

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ''

# Blog post thumbnail
# eg: image: "/images/my_blog_thumbnail.png"
image: ''

# Blog post author
author: 'pbalcer'

# Categories to which this blog post belongs
blogs: ['kvstore']

tags: []

# Redirects from old URL
aliases: ['/2015/09/10/kvstore2.html']

# Blog post type
type: 'post'
---

As promised in the
[previous post](/blog/2015/07/transactional-key-value-store-using-libpmemobj-diy/)
about the kv-store implementation I'm back with new results after implementing
the optimizations I devised a month ago. As a bonus I implemented a red-black
tree to have a fair comparison between two data structures that allocate similar
number of nodes.

tl;dr: I was right about crit-bit :)

### Test platform

The same server was used to run the benchmarks but with the latest 4.2 kernel
that contains numerous DAX improvements.

### What changed?

- Various allocator performance improvements
  - Mostly small things here and there
  - Improved multithreaded scaling (linear now!)
- Cache for small memory blocks added to the transaction
  - The cache is re-used across transactions, meaning less work to start
    transaction
- Range tree to detect and discard overlapping transaction memory ranges
  - `pmemobj_tx_add_range_direct` behaves exactly like `pmemobj_tx_add_range`
- Crit-bit leaf nodes are now embedded into internal ones
  - Halves the number of allocations

### Performance results

Let's dive straight into the interesting stuff.

Inserting 1 mln entries:

| Structure | Outer TX | Time      |
| :-------- | :------- | :-------- |
| B-Tree    | Yes      | 3.17193s  |
| B-Tree    | No       | 14.46045s |
| Crit-bit  | Yes      | 10.29985s |
| Crit-bit  | No       | 9.09759s  |
| RB-Tree   | Yes      | 10.00846s |
| RB-Tree   | No       | 21.53144s |

Removing 1 mln entries:

| Structure | Outer TX | Time      |
| :-------- | :------- | :-------- |
| B-Tree    | Yes      | 4.69913s  |
| B-Tree    | No       | 19.11727s |
| Crit-bit  | Yes      | 9.48193s  |
| Crit-bit  | No       | 8.95918s  |
| RB-Tree   | Yes      | 26.56952s |
| RB-Tree   | No       | 39.01479s |

The B-Tree performance changed a bit - removes are significantly faster and
inserts are a tiny bit slower. This is probably because of a bug-fix to the tree
I made sometime ago. Doesn't matter that much though, we are more interested in
the relative performance. I also changed the methodology to be a little bit more
scientific, those results are probably more accurate.

The difference between inserts with and without the outer TX in
crit-bit can be attributed to the fact that the long-running transaction must
dynamically allocate cache instances. Meaning that when using a single
transaction that inserts a lot of nodes the pmemobj requires thousands of cache
instances, while when you start one transaction for each insert just one
is enough.

The new challenger does not fare so well. It's better (a little) than crit-bit
at bulk inserting the nodes but that's as expected. At regular inserts the
red-black tree fails flat compared to the other data structures. This just
confirms that algorithms that intensively modify its data structures won't do
well in persistent memory.

As for the B-Tree vs Crit-bit tree considerations, bulk inserts are still quite
a lot faster in the b-tree, but for regular inserts the crit-bit takes
the lead with its consistent performance. Because this data structure modifies
very little during one insert/remove operation (just one node) it takes
far smaller performance hit when run in an undo-log transaction. This is going to
be my go-to data structure for all the persistent in-order collections.

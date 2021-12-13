---
# Blog post title
title: 'Persistent allocator design - fragmentation'

# Blog post creation date
date: 2016-02-25T19:55:17-07:00

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
blogs: ['fragmentation']

tags: []

# Redirects from old URL
aliases: ['/2016/02/25/fragmentation.html']

# Blog post type
type: 'post'
---

Implementing a memory allocator is a balance between numerous properties with
the two most important being time and space constraints. Making the malloc/free
routines reasonably fast is a must for the implementation to be considered
usable at all. The algorithm also mustn't waste excessive amounts of memory.

During development of the library we quickly realized that the performance
characteristics will be dominated by the number of cache line flushes that will
be required to perform operations in a fail-safe atomic way. In other words,
how many times does the on-medium memory layout transitions from one consistent
state to another. This lead us to measuring performance of low-level pmalloc in
a function of `pmem_persist` calls.
At the time of writing, fail-safe atomic memory allocation in our library
requires on average 3 cache line flushes.

The fact that performance is mostly dominated by modifications to persistent
memory layout means that a little bit more complex algorithm that operates on
volatile data won't hurt the time characteristics all that much. This
observation is one of the primary contributors to the decision to keep track of
all the memory blocks in both the transient (volatile) and persistent memory.

The key take-away from this lengthy introduction is that as long as the
mechanism responsible for minimizing fragmentation is mostly in volatile memory
and has a reasonable time complexity - O(1) or O(log(n)), it shouldn't trade-off
fragmentation for speed.

### Persistent fragmentation

The biggest difference between the volatile memory allocators and persistent
ones is the length of heap state life. Whereas in regular volatile applications
its life can be usually measured in hours/days and rarely weeks, a persistent
heap can live for years, possibly with different versions of application.

What are the consequences for persistent heaps then?

- Any persistent memory leakage is unacceptable.
- The variety of requested sizes is potentially bigger.
- Fragmentation deterioration behavior in the algorithm is highly undesirable.

So the challenge is much greater. But isn't this the same set of problems
file systems have to routinely solve? Yes, but...

### File system similarities

Persistent heaps don't have the luxury of virtual memory mappings. The memory
you `mmap` is the memory you get, we can't mix it.

A file is composed of number of blocks with granularity determined by pagesize.
File systems try their hardest to provide the user with contiguous memory blocks
but if it's not possible it's not a big deal (especially on flash-based storage).

Contrasting that to a single allocated memory block. It **must** be contiguous
and once allocated cannot be moved.

There was once an idea to create an additional indirection layer, so that a
PMEMoid could resolve to different virtual addresses. This could allow us to
shuffle objects around to reduce [external fragmentation](<https://en.wikipedia.org/wiki/Fragmentation_(computing)#External_fragmentation>).
We quickly abandoned that idea as too complex and potentially performance wrecking.

So, persistent heaps inherit all the problems of file systems but lack similar
solutions to those problems.

Are we doomed? :)

### Our algorithm

For large memory chunks (256 kilobytes) a simple best-fit algorithm is used.
Coalescing is not deferred.

For smaller sizes a segregated-fit algorithm is employed. There are 35
allocation classes so that allocations have on average 3% of
[internal fragmentation](<https://en.wikipedia.org/wiki/Fragmentation_(computing)#Internal_fragmentation>).

The first time a memory block from a given class is needed, an entire chunk
is split ahead of time into smaller blocks of size 8 times 'class size'. For
example, allocation class with size 128 bytes is split into 256 blocks of
1024 bytes with an annotation that this is 128 bytes times 8. All of those blocks
are then inserted into a tree (one per class).

This way allocation classes can handle up to multiple 8 of their sizes and
consequently the number of allocation classes can be reduced to minimum. It also
maps nicely onto a bitmap (which is the on-media representation of this structure).

The method described here gives us pretty decent overall fragmentation numbers.

But this does not eliminate fragmentation by any means, it's still a significant
issue. One way of ensuring near zero memory wastage is to make the heap manager
omniscient.

### Shifting responsibility to the user

A technique commonly used in systems with heavily restricted memory resources is
pool allocators. By providing total information about the future usage of the
memory the on-media layout can be fine-tuned so that the requested memory
blocks are perfectly aligned next to each other.

The pmemobj library already advises users to create a well defined memory layout
for all the objects. Why not leverage this information to give a little break to
the allocator? :)

### Closing words

We are currently contemplating providing a built-in facility to instrument
the memory allocator to create allocation classes tuned for the applications
on-media memory layout. We see this as one of the solutions to a very difficult
problem.

We also strive to provide good average-case fragmentation without any
user-provided instrumentation whatsoever.

Our allocator is heavily inspired by prior work in Doug Lea malloc, jemalloc and
tcmalloc.

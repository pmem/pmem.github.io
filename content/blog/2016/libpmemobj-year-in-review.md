---
# Blog post title
title: 'libpmemobj - a year in review'

# Blog post creation date
date: 2016-12-20T19:55:17-07:00

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
blogs: ['libpmemobj']

tags: []

# Redirects from old URL
aliases: ['/2016/12/20/libpmemobj-year-in-review.html']

# Blog post type
type: 'post'
---

It's been a while since the last post on our blog, but we've been busy with the
recently released 1.2 version of the library. It comes packed with improvements
all throughout the code base and it also brings a handful of new features that
we hope will end up being useful.

With the year coming to an end, it's a good time to look back and discuss the
things we've learned and accomplished. While the 2015 was spent mostly on
designing and implementing the core of the library, with code being furiously
written in heavy volumes, this year was much calmer and naturally focused
on refinements and iterative improvements. The experience we've gathered during
the creation of the library was invaluable and allowed us to think about the
problems we are tackling from a different, broader, perspective. This is
reflected in the things we are focused on and ship with new versions of the library.

## Performance

Making sure that the on-media data structures are always consistent is difficult,
and what's more, it's also costly on the CPU cycles and cache misses. Like I
wrote in the previous posts, the throughput of the library is a function of the
cache flushes required to keep the library state consistent - the cost of
everything else is negligible. As a direct consequence of that, in order to
improve performance, we've been studying every single instance of a flush
instruction in our code and deciding whether it's necessary or not. That
meticulous review netted various small gains that together are quite significant.

The biggest performance improvement however, came from a radical departure from
the original design of transactional undo logs. Initially, every single action
taken during the transaction was logged in a doubly-linked list. That list
was complicated, and thus cost of keeping it in an always consistent state was
high. So high in fact, that, in many instances, it overshadowed the cost of
allocation. This list also suffered from the cache misses issues that any list
implementation suffers, and that burden was additionally amplified by the
constant cache flushing.

We've decided to replace the list with a much simpler data structure - a vector.
Appending an element into it is as simple as writing a value into an array and
flushing afterwards. It naturally keeps a consistent state and is relatively
easy to repair if needed. The only downside is the fact that it's not an
intrusive data structure, meaning that the vector itself needs to be allocated,
contrary to the embedded (intrusive) nature of a list. But that's only a downside
if you are looking at it from the perspective of the old design. This fact
enables us to reduce the overall overhead of each object by the space that would
have been otherwise occupied by the list entry element. In the hindsight, I'd
say the vector is the obvious choice for the undo log structure, we just didn't
think of it at the time.

## Usability

The set of C APIs we've released as a part of the libpmemobj are difficult to use.
That's an undisputed fact (disclaimer: this is my personal opinion :)).
In our defense, we've been trying to squeeze everything we can out of the C
language to get to the point we are at right now. The macros we've added on top
of the functions are a small relief.

But ultimately it's just the nature of the beast. The C programming language in
itself is powerful and flexible, but also requires the utmost care to properly
wield. Our API simply follows suit in this regard.

This learning led us to explore different possibilities, the results of that are
the C++ and Python bindings. They are both far more user friendly and provide
similarly powerful semantics.

While the Python work is still in its early, exploratory stages, it already
shows how great the resulting work can become. The simplicity of it is really
a good reflection of the Python one way of doings things. If you are interested
in this, feel free to share your opinions with us, we are always looking at ways
we can improve the library, and feedback of others is frequently the best way to go.

The C++ bindings, although the prototyping work started in late 2015, have been
effectively rewritten and got far broader in scope. While we have the basics
and primitives pretty much figured out, it's the surrounding ecosystem that is
problematic - this includes containers and algorithms C++ programmers are
used to using. We do not want to re-implement everything from scratch, but instead
it is our intention to leverage the years of experience that was poured into the
C++ standard library implementations. To this effect, we've implemented a C++11
standard compliant allocator that, in theory, can be seamlessly used in
conjunction with the containers that support it. Sadly, the practice is a little
bit different and not all of the existing code wants to play nicely with our
allocator, but we are pushing ahead. Our ambitions are for this code base to be
truly modern and standard-compliant. In fact, we are currently on a road that,
we hope, will lead to a standardization of the persistent memory semantics in
the C++ language.

## Stability

A lot of our work still verges on research. The problems we are trying to solve are
in some parts unique and in some parts similar to work being done on file systems,
garbage collection, memory allocation and probably few other fields of computer
science. But the pioneering combination of ingredients that form the solution
we are proposing is not battle tested, it isn't widely deployed and does not
benefit from a mature ecosystem of implementations. All the data we can gather
comes from our own testing and experimentation, and that means the sample size
is tiny and does not include things we didn't think of.

The one worrisome property of the algorithms that underline libpmemobj, we've been
extensively studying the past year, is the fragmentation of the heap, and how it
increases as the time goes on. Fragmentation is the result of the imperfect
placement of the memory blocks requested by the users. That imperfection stems
from two things: the lack of knowledge regarding the length of life of said
memory blocks, and the chosen approximate solution to the NP-hard bin-packing
problem that all heap managers are a subset of. All throughout the year, we've been
fiddling with allocation classes (set of numbers that define the bins the memory
blocks are packed into) to come-up with a decent general case solution. The
culmination of that work, a much improved algorithm, shipped with 1.2 release.

But the biggest challenge with the long-term stability of the system that employs
libpmemobj, is the fact that, no matter the algorithm, the program can still make
holes in the heap by an unfortunate sequence of allocations and deallocations.
To combat this, in a slightly less traditional fashion, we've been trying to come
up with a heuristic algorithm that predicts the best possible configuration of
allocation classes for a given program as well as a length of life of individual
objects. This would allows us to separate short- and long- lived objects in the
heap and thus side-stepping the fragmentation problem. We've even went as far as
implementing an 'AI' algorithm (before AI was so cool, about 10 months ago :))
that trained on a program with the end goal of maximizing performance and
minimizing fragmentation. The end solution was sadly impractical for the real world
because training of the set was far too long to be reasonable.

It's ultimately the responsibility of the user to properly manage the heap, and
to that effect, we've developed a runtime control mechanism that will allow user
to precisely tune the heap and avoid any potential pitfalls. The same mechanism
will also allow for easy introspection of the library internals.

## Closing statements

The year 2016 was a year of lessons learned. And that will continue onwards as we
look at 2017 with the endless goal of continuous improvements. I will write a new
post early January detailing some of those improvements that are already planned
and waiting for implementation.

This post was in part inspired by the jaw-dropping monthly progress reports
of Dolphin Emulator, which is truly an exemplary open source project. In the
interest of keeping this relatively short, I've inevitable omitted many
interesting events of the past year and so it's my personal new year resolution
to write posts that resemble said progress reports at least quarterly, so that
everyone in our tiny, but growing, community is kept well informed on the
development of our library.

I guess all that's left to say is Happy Holiday season and an eventful New Year.

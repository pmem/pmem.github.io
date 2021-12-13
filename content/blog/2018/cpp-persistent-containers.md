---
# Blog post title
title: 'C++ persistent containers'

# Blog post creation date
date: 2018-11-20T19:55:17-07:00

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
author: 'szyrom'

# Categories to which this blog post belongs
blogs: ['containers']

tags: []

# Redirects from old URL
aliases: ['/2018/11/20/cpp-persistent-containers.html']

# Blog post type
type: 'post'
---

### PMEM containers

Our goal for the libpmemobj C++ bindings is to create a friendly and less
error prone API for persistent memory programming. Even with persistent memory
pool allocators, convenient interface for creating and managing transactions,
auto-snapshotting class templates and smart persistent pointers, designing an
application with persistent memory usage may still prove challenging without
a plethora of niceties that the C++ programmers are used to.

The natural step forward to make persistent programming easier, is to provide
programmers with efficient and useful containers.

### STL containers

Code reuse is a common programming principle, and we took it to heart when
sometime ago we experimented with persistent memory containers by introducing
custom persistent allocator for `libc++` STL containers. You can read more about
this approach [in this blog post](/blog/2017/07/using-standard-library-containers-with-persistent-memory).
As it turns out, there are some hard to overcome obstacles with using existing
STL containers. The two main downsides are:

- Implementation details: STL containers don’t use algorithms optimal from
  persistent memory programming point of view. PMEM containers should have
  durability and consistency properties, while not every STL method
  guarantee strong exception safety. Persistent memory containers should
  be designed with awareness of fragmentation limitations.
- Memory layout: STL does not guarantee that container layout will remain
  unchanged in new library versions.

### Assumptions

We ultimately came to the conclusion that implementing containers with optimized
on-media layouts and algorithms to fully exploit persistent memory potential
should be one of our primary focuses. Their methods should guarantee atomicity,
consistency and durability.

Beside specific internal implementation details, PMEM containers will have
well-known STL-like interface and will work with STL algorithms. Since they
will extend `libpmemobj-cpp` project, the goal is to implement them with
usage of `libpmemobj-cpp` bindings and make them easily accessible with its
interface.

Because of API similarities with STL, we are reusing ideas behind `libc++`
container tests and simply port them to our implementation and project.

### Limitations

C++ language restrictions and the persistent memory programming paradigm imply
some serious restrictions on objects, which may be stored on persistent medium.
This topic deserves a separate blog post and should cover questions about
language-based undefined behavior, objects lifetime and compiler compatibility -
just to name a few of them.

You can expect blog post about this in nearby future, but for now I will just
list the most important ideas:

- Implicit creation of objects stored in persistent medium (C++ standard states
  that lifetime of an object is a runtime property)
- memcpy-ing objects which don’t satisfy requirements of is_trivially_copyable
  (we are not calling neither constructors nor destructors during
  snapshotting memory areas)
- Objects representation (layout) might differ between compilers/compiler
  flags/ABI. C++11 provides type trait `is_standard_layout`, but
  `StandardLayoutType` is a very restrictive set of requirements
  [(see here)](https://en.cppreference.com/w/cpp/named_req/StandardLayoutType).
- Usage of volatile memory pointers in persistent structures should be treated
  as a design error.

You can read more about template parameter type restrictions for
`pmem::obj::persistent_ptr<T>` class in PMDK C++ bindings [doxygen documentation](https://pmem.io/libpmemobj-cpp/master/doxygen/classpmem_1_1obj_1_1persistent__ptr.html).

### Data structures

Implementing containers from scratch will be a long effort, hence our decision
about specific containers types to be implemented and the order of their
implementation is quite important.

We have already finished the first container - an `array`. It is included in
`pmem::obj::experimental` namespace and you can read more about it in
[this blog post](/blog/2018/11/c-persistent-containers-array).

We have been working on implementation of `vector` for some time and the next
scheduled containers are `string`, `map` and `unordered_map`. We might change
this order, because we are considering an implementation of optimized vector,
based on idea of `std::colony` container which will internally use
`pmem::obj::vector`. This approach will reduce fragmentation factor, and gain
performance benefits similar to those presented in C++ standard library
[proposal paper](http://open-std.org/JTC1/SC22/WG21/docs/papers/2017/p0447r4.html).

### Usage

Persistent memory is an emerging technology, and as we mentioned in one of the
previous blog posts, we truly believe that it will be paradigm shifting.

At this point, it is hard to predict specific use-cases for persistent memory
containers. They can be used as persistent _scratch pads_, extension for
in-memory databases or fast and flexible data storage. We also believe, that
functional programming ideas can be used in implementation of persistent memory
programming models.

We will continue our research efforts in area of purely functional persistent
data structures, concepts of filters and further optimizations (like
`pmem::obj::slice` idea, also described in [blog post about array](/blog/2018/11/c-persistent-containers-array)).

---
title: C++ persistent containers
author: szyrom
layout: post
identifier: cpp_persistent_containers
---

### PMEM containers

Our goal for the libpmemobj C++ bindings was to create a friendly and as less
error prone as possible API for persistent memory programming. Even with
persistent memory pool allocators, convenient interface for creating and
managing transactions, auto-snapshotting class templates and smart persistent
pointers, designing an application with persistent memory usage may still be
difficult and unintuitive.

Natural step forward, to make persistent programming easier, is to provide
software engineers with efficient and useful containers.

### STL containers

In most cases, it is faster and easier to change or reuse existing
implementation instead of creating new one, from scratch. We followed that rule
and, some time ago, we provided persistent memory containers by introducing
custom persistent allocator for `libc++` STL containers. You can read more about
this approach [in this blog post](http://pmem.io/2017/07/10/cpp-containers.html).
It turns out however, that there are some obstacles, we can’t jump over with
reusing existing code. The two main downsides were:

* Implementation details: STL containers don’t use algorithms optimal from
	persistent memory programming point of view, not every method guarantees
	strong exception safety, persistent memory containers should be designed
	with awareness of fragmentation limitations.
* Memory layout: STL does not guarantee that container layout will remain
	unchanged between newer library versions.

### Assumptions

We finally decided, that implementing containers with optimized on-media layouts
and algorithms to fully exploit persistent memory potential should be our focal
point of research. Their methods should guarantee atomicity, consistency,
isolation and durability. 

Beside specific internal implementation details, PMEM containers will have
well-known STL-like interface and should work with STL algorithms. Since they
will be `libpmemobj-cpp` project completion, they should be implemented with
usage of `libpmemobj-cpp` bindings and should be easily accessible with its
interface.

Because of API similarities with STL, we can reuse ideas behind `libc++`
container tests and simply port them to our implementation and project.

### Limitations

C++ language restrictions and the persistent memory programming paradigm imply
some serious restrictions on items, which may be stored on persistent medium.
This topic deserves a separate blog post and should cover questions about
language-based undefined behavior, objects lifetime and compiler compatibility -
just to name a few of them.

We are still gathering knowledge and gaining more and more experience in this
area, so you can expect blog post/article about this in nearby future.

For now, I will just list the most important ideas:

* Implicit creation of objects stored in persistent medium (C++ standard states
	that lifetime of an object is a runtime property)
* memcopy-ing objects which don’t satisfy requirements of is_trivially_copyable
	(we are not calling either constructors nor destructors during
	snapshotting memory areas)
* Objects representation (layout) might differ between compilers/compiler
	flags/ABI. C++11 provides type trait `is_standard_layout`, but
	`StandardLayoutType` is a very restrictive set of requirements
	[(see here)](https://en.cppreference.com/w/cpp/named_req/StandardLayoutType).
* Usage of volatile memory pointers in persistent structures should be treated
	as a design error.

You can read more about template parameter type restrictions for
`pmem::obj::persistent_ptr<T>` class in PMDK C++ bindings [doxygen documentation](http://pmem.io/libpmemobj-cpp/master/doxygen/classpmem_1_1obj_1_1persistent__ptr.html).

### Data structures

Implementing containers from scratch will be a long effort, hence the decision
about specific containers types to be implemented and an order of their
implementation is quite important.

We have already finished and published in `pmem::obj::experimental` namespace
the first container - an `array`. You can read more about it in
[this blog post](http://pmem.io/2018/11/02/cpp-array.html).

We have been working on implementation of `vector` for some time and the next
scheduled containers are `string`, `map` and `unordered_map`. We can change this
work order a bit, because we are seriously considering implementation of
optimized version of `vector`, based on idea of `std::colony` container which
will internally use `pmem::obj::vector`. This approach will reduce fragmentation
factor, and gain performance benefits similar to those presented in C++ standard
library [proposal paper](http://open-std.org/JTC1/SC22/WG21/docs/papers/2017/p0447r4.html).

### Usage

Persistent memory is an emerging technology and as we mentioned in one of the
previous blog posts, we truly believe that persistent memory programming will be
paradigm-shifting.

At this point, it is hard to predict specific use-cases for persistent memory
containers. They can be used as persistent *scratch pads*, extension for
in-memory databases or fast and flexible data storage. We also believe, that it
can find usage in functional programming.

We will continue our researching efforts in area of purely functional persistent
data structures, concepts of filters and further optimizations (like
`pmem::obj::slice` idea, also described in [blog post about array](http://pmem.io/2018/11/02/cpp-array.html)).

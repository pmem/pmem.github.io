---
# Blog post title
title: 'C++ bindings for libpmemobj (part 0)'

# Blog post creation date
date: 2016-01-12T19:55:17-07:00

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
aliases: ['/2016/01/12/cpp-01.html']

# Blog post type
type: 'post'
---

Our goal for the C pmemobj library was to make a fully featured implementation
of persistent memory programming model without modifying the compiler. It's
meant for authors of very low-level system software and language creators.
It's not particularly pretty nor easy to use. The amount of macros, as well as
the trickery inside them, might 'amaze' even the biggest preprocessor fans ;)

The natural next step is to leverage the high-level languages features to
create a more friendly, less error prone and generally nicer API.

This tutorial series will introduce you to libpmemobj C++ bindings that leverage
meta-programming capabilities of that language. The bindings are implemented as
header-only library and can be found in the `src/include/libpmemobj` directory.
All classes/functions are in the `pmem::obj` namespace.

The overall goal of the libpmemobj for C++ is to focus modifications to volatile
programs on data structures and not on the code. In other words, we want you to
be able to modify your `structs` and `classes` with only slight modifications to
your functions.

The tutorial posts will introduce new C++ features one-by-one and as a consequence
of that, the first few tutorials will still use C API before the C++ equivalents
are introduced.

General knowledge of persistent memory programming model is recommended.
Please read the [libpmemobj tutorial](/blog/2015/06/an-introduction-to-pmemobj-part-0-new-programming-model)
if you haven't already.

Note: Currently only C++11 (and newer) version of the language is supported.
Older standard (minimum C++03) support is going to be added at a later date.

### C++ limitations

- References as members of persistent structures/classes are forbidden.
- Polymorphic persistent classes are forbidden.
- Be mindful about static member variables. Not using them is recommended.

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/NVML-is-now-PMDK).]

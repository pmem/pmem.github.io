---
# Blog post title
title: 'An introduction to pmemobj (part 0) - new programming model'

# Blog post creation date
date: 2015-06-12T19:55:17-07:00

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
blogs: ['model']

tags: []

# Redirects from old URL
aliases: ['/2015/06/12/pmem-model.html']

# Blog post type
type: 'post'
---

The aim of this tutorial series is to introduce you to programming with persistent, byte-addressable memory using the pmemobj library. We will go over all the available features, implement an example application and learn something about the inner workings of libpmemobj. If you haven't read the [NVM Library overview](/blog/2014/09/nvm-library-overview/) I encourage you to do that now.

When designing the library API, we've put a heavy emphasis on ease of use and "management explainability", as well as flexibility and performance. Our hope is that the most intuitive solution to a problem is the correct one, but usually there is more than one way of implementing something. In the course of this tutorial I'll make it clear when that's the case and explain what are the pros and cons of the different approaches.

Programmers nowadays are used to 2 kinds of memory: fast, byte addressable, volatile memory and slower, persistent, block storage. This library, combined with the right hardware (NVDIMMs), provides an elegant way of utilizing a third type of memory: fast, byte addressable and persistent. It might not be easy to grasp at first, because we truly believe it to be paradigm-shifting and requires a slightly different approach, I'll try my best to explain how to use it and the new challenges involved.

Imagine you are tasked with writing two simple applications: one that takes a string from the standard input and writes it to some storage and a second one that reads the string from storage and writes it to the standard output. All while making sure that the string is either read completely or not at all. There are many ways of solving this problem the standard way. One would be to write buffer and its length to a file in the first application, and in the second read the buffer only if it's complete. Now let's say you can somehow magically have pointers to memory that does not go away - the problem becomes simpler since now all you have to do is to allocate the memory, write the length, memcpy the buffer and, in the second application, read the same pointer. But we still have to resort to trickery to satisfy the complete read requirement, more on that later in the series.

And this, in a nutshell, is what our library provides - persistent pointers and a way to atomically manipulate them. And I hope by now it's clear how this impacts the programming model we are all used to and introduces a whole new set of problems to solve, luckily there's libpmemobj :)

#### Code snippets and examples

This tutorial will include embedded code for you to type, compile and run. Please study them carefully and if you are ever left wondering - feel free to [contact us](/about/). In addition to that, our library comes with ever-growing collection of [examples](https://github.com/pmem/pmdk/tree/master/src/examples), I encourage you to check them out to see some simple use-cases we implemented.

To run applications based on libpmemobj you don't need real persistent memory in the platform, all that's really necessary is a device with a file system (doesn't have to be DAX) - the only difference will be performance. Depending on the configuration, the library will issue a lot of `clflush` or `msync` calls, the latter being obviously way slower. If you end up with unbearable performance while testing your programs without real pmem, you can do something like this:

    PMEM_IS_PMEM_FORCE=1 ./app

This will make the library think you are using persistent memory and not issue `msync`. Keep in mind that this breaks the persistence of your application and is only advisable for basic testing.

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/NVML-is-now-PMDK).]

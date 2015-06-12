---
title: An introduction to pmemobj (part 0) - new programming model
author: pbalcer
layout: post
---

The aim of this tutorial series is to introduce you to programming with persistent, byte-addressable memory using the pmemobj library. We will go over all the available features, implement an example application and learn something about the inner workings of the libpmemobj. If you haven't read the [NVML overview]({% post_url 2014-09-01-nvm-library-overview %}) I encourage you to do that now.

When designing the library API we've put a heavy emphasis on ease of use and "management explainability" as well as flexibility and performance. Our hope is that the most intuitive solution to a problem is the correct one, but usually there is more then one way of implementing something. In the course of this tutorial I'll make it clear when that's the case and explain what are the pros and cons of the different approaches.

Programmers nowadays are used to 2 kinds of memory: fast, byte addressable, volatile memory and slower, persistent, block storage. This library, combined with the right hardware (NVDIMMs), provides an elegant way of utilizing a third type of memory: fast, byte addressable and persistent. It might not be easy to grasp at first, because we truly believe it to be paradigm-shifting and requires a slightly different approach, I'll try my best to explain how to use it and the new challenges involved.

Imagine you are tasked with writing two simple applications: one that takes a string from the standard input and writes it to some storage and second that reads the string from storage and writes it to the standard output. All while making sure that the string is either read completely or not at all. There are many ways of solving this problem the standard way, one would be to just write length and buffer to a file and read the buffer only if its complete. Now let's say you can somehow magically have pointers to memory that does not go away - the problem becomes simpler since now all you have to do is to allocate the memory, write the length and memcpy the buffer, and then read the same pointer in the second application. But we still have to resort to trickery to satisfy the complete read requirement, but more on that later in the series.

And this, in a nutshell, is what our library provides - persistent pointers and a way to atomically manipulate them. And I hope by now it's clear how this impacts the programming model we are all used to and introduces a whole new set of problems to solve, luckily there's libpmemobj :)


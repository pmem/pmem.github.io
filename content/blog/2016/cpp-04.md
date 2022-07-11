---
# Blog post title
title: 'C++ bindings for libpmemobj (part 3) - persistent queue example'

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
aliases: ['/2016/01/12/cpp-04.html']

# Blog post type
type: 'post'
---

The best way to learn to code is usually by implementing an example. We are going
to be creating a linked-list based queue data structure using the the
`pmem::obj::p` and `pmem::obj::persistent_ptr` classes and libpmemobj C API. But
first, a little bit of CS 101 :)

## Linked-list queue

Queue is a collection of elements with two important operations:

- `push` - adds element to the `tail` of the structure
- `pop` - removes element from the `head` of the structure

This makes the queue a First-In-First-Out (FIFO) data structure.

The common implementations use either arrays or singly linked-lists to store
elements. As previously stated, we are going to use the latter.

![queue_0](/images/posts/queue_0.png)

### pmem_queue::push()

##### Step 1

Push operation consists of several steps. First, a new object is created that
contains the data (`value`) and a pointer to the `next` entry. Initially the
memory content of those variables is unknown, so we need to assign user input
to the `value` and `NULL` to the `next`.

![queue_1](/images/posts/queue_1.png)

##### Step 2

After we've done that, let's link the newly created object to the structure
by assigning it to the `next` field of the current tail entry. Obviously, if the
tail is `NULL` there's nothing to do :)

![queue_1_1](/images/posts/queue_1_1.png)

##### Step 3

As the last step, we have to update the `tail` variable of the queue structure to
point to our new object. If this is the first entry and both `head` and `tail` are `NULL`,
we have to remember to update the `head` as well.

![queue_1_2](/images/posts/queue_1_2.png)

### pmem_queue::pop()

##### Step 1

The first step of the pop operation is to update the `head` variable of the queue
structure to point to the `head->next` (the second entry). The object which is
being removed is currently not linked to anything.

![queue_1_2](/images/posts/queue_2.png)

##### Step 2

Next, the removed object has to be freed. And if there are no more entries in
the queue (the `head` variable is `NULL`) the `tail` variable has to be set to `NULL`.

![queue_1_2](/images/posts/queue_2_1.png)

## What about persistence?

When implementing algorithms that operate on persistent memory, we have to
carefully consider what happens to the data structure if the application is
interrupted (for example, by a power failure) at any moment during its operation.

The pmemobj library takes care of those issues and it's enough to simply wrap
your code in a transaction block to make it failsafe atomic (i.e. done completely or
not at all).

I intentionally described the queue operations in indivisible steps, so that it's
clear that when the algorithm is interrupted for some reason, we can end up
with either **persistent** memory leak or corrupted data structure.

## C++ implementation

As the operations were already described, and the actual implementation, in
alignment to the premise of C++ bindings, is identical to the volatile one, I'll
talk only about the data structure.

First let's define our list entry structure. As we just learned, it has to
contain a `next` pointer and a value.

```c++
struct pmem_entry {
    persistent_ptr<pmem_entry> next;
    p<uint64_t> value;
};
```

Our queue structure, which will also be our root object, consists of two fields:
`head` and `tail`:

```c++
class pmem_queue {
private:
    persistent_ptr<pmem_entry> head;
    persistent_ptr<pmem_entry> tail;
};
```

The `pmem_queue::push()` and `pmem_queue::pop()` functions look exactly like a
volatile implementation, but with a transaction block and
`pmemobj_alloc()`/`pmemobj_free()` instead of `new`/`delete`.

Complete implementation is available
[here](https://github.com/pmem/libpmemobj-cpp/blob/master/examples/queue/queue.cpp).

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

###### [This entry was edited on 2018-07-06 to change link to queue.cpp]

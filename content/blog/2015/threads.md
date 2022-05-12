---
# Blog post title
title: 'An introduction to pmemobj (part 6) - threading'

# Blog post creation date
date: 2015-06-18T19:55:17-07:00

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
aliases: ['/2015/06/18/threads.html']

# Blog post type
type: 'post'
---

All of the pmemobj library **functions** are thread-safe, with following two exceptions: pool management functions (open, close and friends) and `pmemobj_root` when providing different sizes in different threads - so as long as you are using this function the way it's meant to be used you don't have to worry about it. As for macros - generally only the FOREACH macros are not thread-safe for obvious reasons.

### Synchronization

If you need to put a lock inside a structure that resides on persistent memory, our library provides pthread-like API for that purpose. There's no need to initialize those locks or to verify their state. When an application crashes they are all automatically unlocked. As for the reason why something like this is needed, consider following code:

{{< highlight C "linenos=table" >}}
struct foo {
pthread_mutex_t lock;
int bar;
};

int fetch_and_add(TOID(struct foo) foo, int val) {
pthread_mutex_lock(&D_RW(foo)->lock);

    int ret = D_RO(foo)->bar;
    D_RW(foo)->bar += val;

    pthread_mutex_unlock(&D_RW(foo)->lock);

    return ret;

}
{{< /highlight >}}

If a crash happens, well anywhere in `fetch_and_add` really, the `pthread_mutex_t` structure will contain invalid values and the application will most likely segfault when an attempt to use it is made. The solution to that would be to call `pthread_mutex_init` on every single pmem-resident lock. Manually. Here's the proper way to do it:

{{< highlight C "linenos=table" >}}
struct foo {
PMEMmutex lock;
int bar;
};

int fetch_and_add(TOID(struct foo) foo, int val) {
pmemobj_mutex_lock(pop, &D_RW(foo)->lock);

    int ret = D_RO(foo)->bar;
    D_RW(foo)->bar += val;

    pmemobj_mutex_unlock(pop, &D_RW(foo)->lock);

    return ret;

}
{{< /highlight >}}

### Transactions

A single transaction block works in the context of a single thread. And that's it. When we were considering the performance ramifications of multi-threaded transaction we came to the conclusion that it's simply not worth it - there are other ways of parallelizing problems. As an example, take a look at the [PI](https://en.wikipedia.org/wiki/Leibniz_formula_for_%CF%80) example [here](https://github.com/pmem/pmdk/tree/master/src/examples/libpmemobj).

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

---
# Blog post title
title: 'C++ bindings for libpmemobj (part 7) - synchronization primitives'

# Blog post creation date
date: 2016-05-31T19:55:17-07:00

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
author: 'tomaszkapela'

# Categories to which this blog post belongs
blogs: ['libpmemobj']

tags: []

# Redirects from old URL
aliases: ['/2016/05/31/cpp-08.html']

# Blog post type
type: 'post'
---

To finish off the C++ bindings to libpmemobj blog marathon, I will introduce to
you the synchronization mechanisms we implemented. They are mostly C++11-like
implementations of different kinds of mutexes and the condition variable. They
satisfy their respective concepts (Mutex, SharedMutex and so on), the difference
is that they are based on the persistent memory resident synchronization
primitives provided by libpmemobj.

### Mutex

The `pmem::obj::mutex` class satisfies the requirements of the _Mutex_ and
_StandardLayoutType_ concepts. The usage of this class should be really
straightforward for anyone who has ever used the `std::mutex`. The only
difference is that the `pmem::obj::mutex` has to be placed in persistent memory,
within a libpmemobj pool. This is because the implementation needs to be able to
reset the mutex the next time the pool is opened after a power failure/crash.
In persistent memory, the mutex would not be reinitialized automatically in
such case.

You can use the `pmem::obj::mutex` with standard wrapper classes like:

{{< highlight cpp "linenos=table" >}}
pmem::obj::mutex pmutex;
{
std::lock_guard<pmem::obj::mutex> lock(pmutex);
}
std::unique_lock<pmem::obj::mutex> lock(pmutex);
{{< /highlight >}}

### Shared Mutex and Timed Mutex

The `pmem::obj::shared_mutex` and `pmem::obj::timed_mutex` are also very similar
to their `std` counterparts. They also satisfy their respective _SharedMutex_
and _TimedMutex_ as well as the _StandardLayoutType_ concepts. Their usage is
also very straightforward:

{{< highlight cpp "linenos=table" >}}
pmem::obj::shared_mutex smutex;
pmem::obj::timed_mutex tmutex;
{
std::shared_lock<pmem::obj::shared_mutex> lock(smutex);
}
std::unique_lock<pmem::obj::shared_mutex> lock(smutex);

tmutex.try_lock_for(std::chrono::milliseconds(100));
std::unique_lock<pmem::obj::timed_mutex> lock(tmutex);
{{< /highlight >}}

The `pmem::obj::shared_mutex` and `pmem::obj::timed_mutex` are persistent
memory resident synchronization mechanisms.

### Condition Variable

The `pmem::obj::condition_variable`, as you probably by now noticed, is pretty
much the standard `std::condition_variable`, with the exception of it being
persistent memory resident. The usage is also very similar:

{{< highlight cpp "linenos=table" >}}
pmem::obj::mutex pmutex;
pmem::obj::condition*variable cond;
pmutex.lock();
cond.wait(proot->pmutex, [&]() { /* check condition here \_/ });
// do some meaningful work here
pmutex.unlock();
{{< /highlight >}}

With this we have ended the introduction to the core classes and functions of
the C++ bindings to libpmemobj. If you ever find yourself in doubt about the
usage of the C++ bindings or PMDK in general, don't hesitate to send us a
message on our [Google Group][33a989a9].

[33a989a9]: https://groups.google.com/forum/#!forum/pmem 'Pmem Google Group'

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/NVML-is-now-PMDK).]

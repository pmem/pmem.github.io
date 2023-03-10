---
# Blog post title
title: 'C++ bindings for libpmemobj (part 5) - make_persistent'

# Blog post creation date
date: 2016-05-19T19:55:17-07:00

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
aliases: ['/2016/05/19/cpp-06.html']

# Blog post type
type: 'post'
---

One of the most important features of the C++ bindings to libpmemobj is the
`persistent_ptr` smart pointer template. While using it is fairly
straightforward, the allocation and object construction with the use of the C
API is hard to get right. So like it's C++ standard's counterparts, it needed an
allocation mechanism with appropriate object construction. This is exactly what
this post will try to explain.

### Transactional allocations

Probably the most common usage of the allocating functions is within pmemobj
transactions. The easiest way to allocate an object into a `persistent_ptr` is
by doing the following:

```c++
auto pop = pool_base::create(...);
persistent_ptr<entry> pentry;
transaction::exec_tx(pop, [&] { pentry = make_persistent<entry>(); });
```

Don't be taken aback by the strange transaction syntax. I will clarify
everything in one of the next blog posts. The second line just starts
a transaction and allocates one `entry` object. The more vigilant readers might
point out, that using the default constructor is arguably the most effective
way of creating objects. I could easily imagine the following constructor:

```c++
entry(int a, double b);
```

But this is not an issue, you can just type:

```c++
auto pop = pool_base::create(...);
persistent_ptr<entry> pentry;
transaction::exec_tx(pop, [&] { pentry = make_persistent<entry>(1, 2.0); });
```

And it forwards the parameters to the appropriate constructor of the `entry`
class.

Say you need an array of objects of type `entry`, or a 2-D array of said
objects. This is also possible to do using `make_persistent`:

```c++
auto a = make_persistent<entry[]>(3);     /* allocate an array of three entries */
auto b = make_persistent<entry[3]>();     /* allocate an array of three entries */
auto c = make_persistent<entry[3][2]>();  /* allocate a 3 by 2 array entries */
```

Unfortunately the constructor arguments passing does not work with arrays of
objects, so the object has to be default constructible.

When you are done with a persistent object and would like for it to be
deallocated, you need to call the complementary `delete_persistent` function.

```c++
delete_persistent<entry>(pentry); /* delete persistent object */
delete_persistent<entry[]>(a, 3); /* delete persistent array 'a' */
delete_persistent<entry[3]>(b);   /* delete persistent array 'b' */
delete_persistent<entry[3][2]>(c); /* delete persistent array 'c' */
```

In case of transactional object destruction, the libpmemobj library calls the object's
destructor. This is however not the case with atomic allocations, where there
is no way to atomically destroy and deallocate an object.

Transactional allocations are the most convenient way of creating persistent
objects, especially if the allocation is one in a sequence of operations that
have to be made atomically with respect to persistence. There is however another
way of creating objects.

### Atomic allocations

If you only need to allocate an object atomically, you do not have to start a
transaction for that. You can do that with the C API and now, of course, the
same facility is available in C++. For that you use the `make_persistent_atomic`
function template.

```c++
auto pop = pool_base::create(...);
persistent_ptr<entry> pentry;
make_persistent_atomic<entry>(pop, pentry);
```

As with transactional allocations, their atomic counterparts support both
parameter passing and array allocations.

```c++
auto pop = pool_base::create(...);
persistent_ptr<entry> pentry;
persistent_ptr<entry[]> pentry_array;
make_persistent_atomic<entry>(pop, pentry, 1, 2.0);
make_persistent_atomic<entry[]>(pop, pentry_array, 3);
```

Atomic deletions of persistent pointers is done through the
`delete_persistent_atomic` function template, much like the transactional
versions.

```c++
delete_persistent_atomic<entry>(pentry); /* delete persistent object */
delete_persistent_atomic<entry[]>(pentry_array, 3); /* delete persistent array 'a' */
```

_An atomic allocation/deletion guarantees an object allocation and
initialization/deletion that is atomic with respect to persistence_. This is
important enough to have a separate section to explain.

### Transactions and atomic allocations

This is the thing I absolutely have to convey clearly, **_atomic allocations and
transactions do NOT mix_**. So something like the following is not a good idea:

```c++
auto pop = pool_base::create(...);
persistent_ptr<entry> pentry;
transaction::exec_tx(pop, [&] {
  make_persistent_atomic<entry>(pop, pentry); /* do NOT do this */
});
```

This might look like a small issue at first, but it could baffle you once you
encounter a transaction abort. Everything gets rolled-back, except the
allocation. You might get a persistent leak, an inconsistent state and depending
on the logic, segfaulting is also an option. Either way, don't try this at home.
It is stated in the C API, in debug builds you get a warning log, but I still
feel the need to reinforce this, because there is no way to generate
a compile time error.

To conclude:

- The atomic allocations API should **NOT** be used inside transactions
- In case of atomic deallocations the memory gets freed, but the object's
  destructor is **never called**.
- The transactional versions can only be used within transactions. If used
  outside of transaction scope, an exception is thrown.

```c++
auto pop = pool_base::create(...);
persistent_ptr<entry> pentry;
transaction::exec_tx(pop, [&] {
  make_persistent_atomic<entry>(pop, pentry); /* legal but dangerous */
  auto b = make_persistent<entry>(); /* OK */
  delete_persistent<entry>(b); /* call ~entry() and free memory */
});

make_persistent_atomic<entry>(pop, pentry); /* OK */
auto b = make_persistent<entry>(); /* throw an exception */
delete_persistent_atomic<entry>(pop, pentry); /* free memory, no call to ~entry() */
```

This concludes the introduction of atomic and transactional allocations. If you
ever feel like looking at more code, try our [examples][f8602ec1] or
[tests][8e3dfe2a].

In the next blog post I will introduce what I think is the heart of the C++
bindings to libpmemobj - transactions.

[f8602ec1]: https://github.com/pmem/pmdk/tree/master/src/examples/libpmemobj 'Libpmemobj examples'
[8e3dfe2a]: https://github.com/pmem/pmdk/tree/master/src/test 'PMDK tests'

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

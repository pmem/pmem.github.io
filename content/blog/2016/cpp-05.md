---
# Blog post title
title: 'C++ bindings for libpmemobj (part 4) - pool handle wrapper'

# Blog post creation date
date: 2016-05-10T19:55:17-07:00

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
aliases: ['/2016/05/10/cpp-05.html']

# Blog post type
type: 'post'
---

One of the necessary steps in developing the C++ libpmemobj bindings was the
introduction of an abstraction of the C pool handle. We decided to do a very
simple hierarchy where the `pool` template inherits from a generic `pool_base`.
This was necessary to be able to have functions/methods which do not depend on
the pool's template argument. Please note that this makes both of these handles
**impossible** to keep in persistent memory, due to the presence of a vtable.
It wouldn't make much sense either way, because the pool handle is _runtime_
and not _persistent_ data.

### Basic usage

The `pool` is a class template, where the single template parameter is the type
of the root object. So if your root object was a `struct root`, you would use
a similar definition to the following:

```c++
pool<root> pop;
```

There are three basic operations you can do on a pool, that is

- `open` which opens an existing pmemobj pool
- `create` which creates a new pmemobj pool
- `close` which closes an already opened/created pool

An example usage would be:

```c++
if (access(path.c_str(), F_OK) != 0) {
    pop = pool<root>::create(path, "some_layout", PMEMOBJ_MIN_POOL, S_IRWXU);
} else {
    pop = pool<root>::open(path, "some_layout");
}
```

And that's pretty much it, you now have an opened and mapped pmemobj pool file
ready to use. You can also perform a consistency check by calling the `check()`
method. When you are done with the pmemobj pool you can close it with the
`close()` method.

The next step you probably want to do is get the pool's root object.

```c++
auto q = pop.get_root();
```

There is one thing I have to mention here that is of utmost importance. The
root object's constructor **will _NOT_ be called**. This is a design decision
we have made, because the root object might be reallocated and that might lead
to unresolvable complications and possibly to memory leaks. This means that it
is the responsibility of the user to properly initialize the root object.

### Advanced features

The `pool` class also has a set of functions for fine-grained low-level
management of persistent memory. So you can:

- `flush` - flush persistent objects/regions in persistent memory
- `persist` - flush and persist objects/regions in persistent memory
- `drain` - issue a persistent memory drain
- `memcpy_persist` - do a `memcpy` followed by a `persist`
- `memset_persist` - do a `memset` followed by a `persist`

Should you ever need to interface with the C API, you can get the pmemobj pool
handle by calling the `get_handle()` method.

These are all of the features of the C++ pool wrapper. As always, any and all
comments and suggestions are welcome.

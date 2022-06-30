---
# Blog post title
title: 'C++ bindings for libpmemobj (part 1) - pmem resident variables'

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
aliases: ['/2016/01/12/cpp-02.html']

# Blog post type
type: 'post'
---

One of the biggest hurdles and error prone things about our C API is that the
user has to manually keep track of modifications to the persistent memory resident
variables while in a transaction. A special semi-transparent template property
class has been implemented to automatically add variable modifications to the
transaction undo log.

### pmem::obj::p

Let's start with the vector example from the previous tutorial series. It looked
like this:

```c++
struct vector {
    int x;
    int y;
    int z;
}

PMEMoid root = pmemobj_root(pop, sizeof (struct vector));

struct vector *vectorp = pmemobj_direct(root);
TX_BEGIN(pop) {
    pmemobj_tx_add_range(root, 0, sizeof (struct vector));
    vectorp->x = 5;
    vectorp->y = 10;
    vectorp->z = 15;
} TX_END
```

As you can see, the programmer has to remember to call `pmemobj_tx_add_range`
function before any modifications to the memory. In a simple case like this one
it might not be such a big deal, but once the code gets complex it may lead to
some difficult to find consistency issues.

By using the C++ API we can simplify this code like so:

```c++
#include <libpmemobj/p.hpp>

using namespace pmem::obj;

struct vector {
    p<int> x;
    p<int> y;
    p<int> z;
}

PMEMoid root = pmemobj_root(pop, sizeof (struct vector));

struct vector *vectorp = pmemobj_direct(root);
TX_BEGIN(pop) {
    vectorp->x = 5;
    vectorp->y = 10;
    vectorp->z = 15;
} TX_END
```

The template class `pmem::obj::p` does not add storage overhead. The size of
the vector structure is exactly the same as in the C version.

This mechanism works overriding `operator=` and adding the memory to the undo log
before modification. It's pretty straightforward.

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

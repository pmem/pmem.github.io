---
# Blog post title
title: 'C++ bindings for libpmemobj (part 2) - persistent smart pointer'

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
aliases: ['/2016/01/12/cpp-03.html']

# Blog post type
type: 'post'
---

In our C API the programmer has to deal with custom pointers represented by
the PMEMoid structure. Thanks to some macro magic we made it so that those PMEMoids
are somewhat usable. C++ allows us to evolve this concept.

### pmem::obj::persistent_ptr

Almost everyone who ever touched a C++ code knows the idea behind smart pointers
(for example, `std::shared_ptr`). Our persistent pointer works in the same way.
It wraps around a type and provides implementation of `operator*`, `operator->`
and `operator[]`.

A constructor from raw PMEMoid is provided, so that mixing the C API with C++ is
possible.

As always, we are going to start with an example:

{{< highlight C "linenos=table" >}}
#include <libpmemobj/p.hpp>
#include <libpmemobj/persistent_ptr.hpp>

using namespace pmem::obj;

struct rectangle {
p<int> a;
p<int> b;
};

struct root {
persistent_ptr<rectangle> rect;
};

{{< /highlight >}}

It's a modified rectangle example from transactional allocations tutorial.
Layout declaration using macros is no longer required :)

As I previously said, the persistent pointers can be constructed from PMEMoids,
and as such, we are going to allocate the rectangle by using the regular C API.

{{< highlight C "linenos=table" >}}

persistent_ptr<root> rootp = pmemobj_root(pop, sizeof (root));

TX_BEGIN(pop) {
persistent_ptr<rectangle> rect = pmemobj_tx_alloc(sizeof (rectangle), 0);
rect->x = 5;
rect->y = 10;

    rootp->rect = rect; /* assignments are automatically added to TX */

} TX_END

{{< /highlight >}}

As you can see, pretty easy. No more ugly D_RW or D_RO macros ! :)

There's one thing to highlight here: The rectangle constructor is NOT called in
this example. This is because we are using C allocation function.
This is equivalent to a following construct in a regular C++:

{{< highlight C "linenos=table" >}}
shared_ptr<rectangle> rect((rectangle \*)malloc(sizeof (rectangle)));
{{< /highlight >}}

To free a `persistent_ptr` using the C API, a special `raw()` function is available
that returns a const reference to the PMEMoid.

{{< highlight C "linenos=table" >}}

TX_BEGIN(pop) {
pmemobj_tx_free(rootp->rect.raw());
rootp->rect = nullptr;
} TX_END

{{< /highlight >}}

Later tutorials will introduce proper allocator functions that do
call the constructor and destructors accordingly.

The `persistent_ptr` class also implements a `raw_ptr()` function which returns
a pointer to the PMEMoid - this enables usage of the C failsafe atomic API.

Right now the persistent*ptr class can only be used with non-polymorphic and
trivially default constructible classes. Those limitations \_might* be relaxed
in later versions of the bindings.

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

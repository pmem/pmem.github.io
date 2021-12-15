---
# Blog post title
title: 'C++ persistent containers - array'

# Blog post creation date
date: 2018-11-02T19:55:17-07:00

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
author: 'igchor'

# Categories to which this blog post belongs
blogs: ['Libpmemobj-cpp']

tags: []

# Redirects from old URL
aliases: ['/2018/11/02/cpp-array.html']

# Blog post type
type: 'post'
---

### Introduction

Until now, our C++ bindings were missing one important component - persistent
containers. In 1.5 release we have introduced the first one - [pmem::obj::array][cpp_array].
This container is currently placed in `experimental` namespace and folder (this means
that both API and layout can change). It has almost the same functionality as `std::array`
from C++11 but takes care of adding elements to a transaction. Once experimental status
will be dropped, it will also guarantee a stable in-memory layout (it will be the same for all compilers).
API for `pmem::obj::array` and `std::array` is the same, except for the following:

- `pmem::obj::array` defines [range()][cpp_array_range] method (described below)
- `pmem::obj::array` does not mark any non-const function as `noexcept` -
  elements must be added to a transaction which could result in an exception

If you want to store a sequence of objects, whose length is known at compile time,
you should use `pmem::obj::array`. In contrast to plain arrays, `pmem::obj::array`
automatically adds modified elements to the enclosing transaction.

### Usage

Let's start with a simple example:

{{< highlight C "linenos=table" >}}
#include <libpmemobj++/experimental/array.hpp>

struct data {
data() {
array = {6, 5, 4, 3, 2, 1};
}

    pmem::obj::experimental::array<int, 6> array;

}

pmem::obj::transaction::run(pop, [&] {
ptr = pmem::obj::make_persistent<data>();

    for (auto &e : ptr->array)
    	e++;

    for (auto it = ptr->array.begin(); it != ptr->array.end(); it++)
    	*it += 2;

    for (int i = 0; i < ptr->array.size(); i++)
    	ptr->array[i]--;

});

{{< /highlight >}}

As seen above, `pmem::obj::array` can be used just like an ordinary `std::array`.
For iterating over it you can use indexing operator, range-based for loops or
iterators. Array can also be processed using `std::algorithms`:

{{< highlight C "linenos=table" >}}
pmem::obj::transaction::run(pop, [&] {
std::sort(ptr->array.begin(), ptr->array.end());
}
{{< /highlight >}}

If there is an active transaction, elements (accessed using any of the listed
methods) are snapshotted. In case of iterators returned by begin() and end()
snapshotting happens during iterator dereferencing. Of course, snapshotting is
done only for mutable elements. In case of `const` iterators or `const`
versions of indexing operator, nothing is added to a transaction. That's why
it is extremely important to use `const` functions (cbegin(), cend(), etc.)
whenever possible. It will reduce number of snapshots and can significantly
reduce the performance impact of transactions.

### pmem::obj::slice

In cases where loop is known to modify several consecutive elements in the array,
a bulk-snapshot optimization can be performed using a special [range()][cpp_array_range]
function which returns an instance of [pmem::obj::slice][cpp_array_slice] struct.
This structure provides interface to access sequence of objects - it implements
indexing operators as well as begin() and end() methods (plus const and reverse
variants).

Here's sample usage:

{{< highlight C "linenos=table" >}}
pmem::obj::transaction::run(pop, [&] {
auto slice = ptr->array.range(0, ptr->array.size(), 2);

    for (auto it = slice.begin(); it != slice.end(); it++)
    	*it++;

    for (auto &e : slice)
    	e++;

    std::sort(slice.begin(), slice.end());

    for (int i = 0; i < slice.size(); i++)
    	slice[i]--;

}
{{< /highlight >}}

This examples shows that `pmem::obj::slice` can be iterated the same way as `pmem::obj::array`.
The difference is that elements are not snapshotted one by one, instead they are
added to a transaction in bulk. Let's analyze what is happening in case of first
`for` loop in the above example. First, notice that the third argument in `range()`
function is equal to `2`. This means that elements will be snapshotted in pairs.
At the beginning of the loop, first two elements in the array will be already
added to a transaction (this is done in `range()` method), so that it will not
have to be done in the first and the second iteration. In the third iteration,
elements at indexes 2 and 3 will be snapshotted, and so on. Assuming size of
array equal to 6, number of snapshots will be thus equal to 3. This mechanism is
also described [here][cpp_array_iterator].

If all elements (or most of them) are expected to be modified, `range()` can be called like this:

{{< highlight C "linenos=table" >}}
auto slice = ptr->array.range(0, ptr->array.size());
{{< /highlight >}}

This will add the entire array to a transaction once.

There is no universal rule, when to use `range()`. Performance gain will depend
on snapshot size, element type and type of workload. Usage of this method should
be carefully thought out or benchmarked.

### pmem::obj::array and pmem::obj::persistent_ptr

Above examples used `pmem::obj::array` as a struct member but it is also possible
to have direct `pmem::obj::persistent_ptr` to it. There is, however, one thing users
should be aware of while using this approach. Consider the following code:

{{< highlight C "linenos=table" >}}
using array_type = pmem::obj::experimental::array<int, 5>;

pmem::obj::transaction::run(pop, [&] {
// not possible before C++17
ptr = pmem::obj::make_persistent<array_type>(1, 2, 3, 4, 5);

    	// always works
    	ptr2 = pmem::obj::make_persistent<array_type>();
    });

{{< /highlight >}}

As stated in the comment, initializing `pmem::obj::array` in `pmem::obj::make_persistent`
with list of values is only possible since C++17. This is because `pmem::obj::array`,
just like `std::array`, is an aggregate type and needs special initialization syntax (brace
initialization must be used). The problem is that, in order to support aggregate initialization,
we must check whether a type is an aggregate in `pmem::obj::make_persistent` and
this check is only available since C++17.

### Summary

To summarize, if you need to store fixed-length array in persistent memory you should
always use `pmem::obj::array`. This is currently the only persistent container
in our library. However, we are working on `pmem::obj::vector` and `pmem::obj::string`,
so you can expect our containers collection to grow in the near future.

[cpp_array]: /libpmemobj-cpp/master/doxygen/structpmem_1_1obj_1_1experimental_1_1array.html 'pmem::obj::array'
[cpp_array_range]: /libpmemobj-cpp/master/doxygen/structpmem_1_1obj_1_1experimental_1_1array.html#a113016b4fb574f71dc12f72a90048471 'range() method'
[cpp_array_slice]: /libpmemobj-cpp/master/doxygen/classpmem_1_1obj_1_1experimental_1_1slice.html 'slice struct'
[cpp_array_iterator]: /libpmemobj-cpp/master/doxygen/structpmem_1_1obj_1_1experimental_1_1range__snapshotting__iterator.html 'range_snapshotting_iterator'

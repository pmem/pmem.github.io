---
# Blog post title
title: 'C++ persistent containers - vector'

# Blog post creation date
date: 2019-02-20T19:55:17-07:00

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
author: 'szyrom'

# Categories to which this blog post belongs
blogs: ['Libpmemobj-cpp']

tags: []

# Redirects from old URL
aliases: ['/2019/02/20/cpp-vector.html']

# Blog post type
type: 'post'
---

###### [Note: [pmem::obj::vector&lt;&gt;][pmem_obj_vector] is no longer experimental. The rest of the information in this blog post is still accurate.]

### Introduction

The main idea behind pmem containers is to fully exploit persistent memory potential
by designing optimized on-media layouts and algorithms for persistent memory programming.
On November, we published a [blog post][cpp_containers] about pmem containers.
If you haven’t read it yet, I encourage you to do that now.

We have recently added [pmem::obj:experimental:vector][cpp_vector] container to
libpmemobj-cpp library. This container is currently placed in `experimental` namespace
and folder - this means that both API and layout may change. It provides API similar
to `std::vector` from C++11 but guarantees full exception safety via commit or
rollback semantics and allocates data in persistent memory.

### Limitations

[pmem::obj::vector][cpp_vector] allocates data in persistent memory libpmemobj pool.
This limits maximum allocation size to value equal to `PMEMOBJ_MAX_ALLOC_SIZE` macro.
Due to this limitation and due to the fact that [pmem::obj:experimental:vector][cpp_vector]
is dynamic contiguous array, maximum number of elements that can be stored in the
pool is equal to `PMEMOBJ_MAX_ALLOC_SIZE / sizeof(element_type)` and this value can
be returned by `max_size()` API function.

Since stored elements will reside in persistent memory, element's type **should** satisfy
requirements of:

- `StandardLayoutType` (because objects representation (layout) might differ between
  compilers/compiler flags/ABI)
- `TriviallyCopyable` (because we are not calling neither constructors nor destructors
  during snapshotting memory areas).

As a consequence type of stored element:

- shouldn't be polymorphic,
- shouldn't have non-static data members of reference type,
- every copy constructor, move constructor, copy assignment operator, move assignment
  operator should be trivial (i.e. implicitly-defined or defaulted) or deleted,
- at least one copy constructor, move constructor, copy assignment operator, or
  move assignment operator is non-deleted and should have trivial non-deleted destructor.

However, it is important to realize that pointers are trivially copyable types too.
Whenever there are pointer inside the data structure that will be snapshotted
(memcopyed) you have to make sure that copying them around is proper. The same
rule applies for persistent_ptr type, even if it doesn't satisfy `TriviallyCopyable`
name requirements (because of explicitly-defined constructors).

[pmem::obj:experimental:vector][cpp_vector] user and every persistent memory programmer
should always check whether persistent_ptr could be copied in that specific case
and if that wouldn't cause errors and (persistent) memory leaks. One should realize
that `std::is_trivially_copyable` is the syntax check only and it doesn't tests semantics.
Technically speaking, using persistent_ptr in this context leads to undefined behavior.
There is no golden mean and since C++ standard does not fully support persistent
memory programming, we should make sure that copying persistent_ptr is safe to use
in our case.

It is very important to mention here that storing volatile memory pointers in persistent
memory is almost always a design error (after application crash, pointer to virtual
memory is no longer valid). Using persistent_ptr is safe and it provides only way
to access specific memory area after application crash.

### API extensions

API for `pmem::obj:experimental:vector` and `std::vector` is the same, except for the following:

- `pmem::obj:experimental:vector` defines `range()` method (detailed description you can find in
  `pmem::obj:array` [blog post][cpp_array_blogpost])
- `pmem::obj:experimental:vector` does not mark any non-const function as `noexcept` -
  elements must be added to a transaction which could throw an exception
- `pmem::obj:experimental:vector` overloads constructor, assign method and assign
  operator to work with `std::vector` objects
- `pmem::obj:experimental:vector` defines non-member compare functions between `pmem::obj:experimental:vector`
  and `std::vector`
- `pmem::obj:experimental:vector` defines `free_data()` function that is recommended
  to being called before `pmem::obj:experimental:vector` destructor (freeing
  allocated persistent memory in transaction may throw an exception)
- `pmem::obj:experimental:vector` defines `const_at()`, `cfront()`, `cback()` and `cdata()`
  element access methods. We decided that using `at()`, `front()`, `back()`
  and `data()` overloads which return const_reference (or const_pointer)
  is not enough (overload deduction depends on the const-qualification of
  the object it is called on and it is burdensome to cast `pmem::obj:experimental:vector`
  into `const pmem::obj:experimental:vector`), especially in persistent memory programming,
  where accessing element's value for read-only purposes might be frequent
  operation and there is no need for doing it in transaction. Note that
  this is not possible to overcome this problem for `operator[]`.

### Usage

One of our main goals while designing `pmem::obj:experimental:vector` was to create as much
similar API to `std::vector` as possible. The only usage difference in persistent
memory version of vector is creation of an object.
`pmem::obj:experimental:vector` resides on persistent memory so you need a way to access stored
elements even after program crash, which can be done using pool's root object.
The root object is the anchor to which all the memory structures should be attached.

Here is an example how to create `pmem::obj:experimental:vector`:

{{< highlight C "linenos=table" >}}
#include <libpmemobj++/make_persistent.hpp>
#include <libpmemobj++/transaction.hpp>
#include <libpmemobj++/persistent_ptr.hpp>
#include <libpmemobj++/pool.hpp>
#include <libpmemobj++/experimental/vector.hpp>
#include <libpmemobj++/experimental/slice.hpp>

using vector_type = pmem::obj::experimental::vector<int>;

struct root {
pmem::obj::persistent_ptr<vector_type> vec_p;
};

...

/_ creating pmem::obj::vector in transaction _/
pmem::obj::transaction::run(pop, [&] {
root->vec*p = pmem::obj::make_persistent<vector_type>(/* optional constructor arguments \_/);
});

vector_type &pvector = \*(root->vec_p);

...

{{< /highlight >}}

As you can see in above code snippet `pmem::obj:experimental:vector` must be created and
allocated in persistent memory using inside of transaction (an exception will be
thrown otherwise). Vector's element type constructor may construct an object by
internally opening another transaction. In this case inner transaction will be
flattened to outer one.

From now on usage of `pmem::obj:experimental:vector` is similar to usage of `std::vector`:

{{< highlight C "linenos=table" >}}

...

pvector.reserve(10);
assert(pvector.size() == 0);
assert(pvector.capacity() == 10);

pvector = {0, 1, 2, 3, 4};
assert(pvector.size() == 5);
assert(pvector.capacity() == 10);

pvector.shrink_to_fit();
assert(pvector.size() == 5);
assert(pvector.capacity() == 5);

for (unsigned i = 0; i < pvector.size(); ++i)
assert(pvector.const_at(i) == static_cast<int>(i));

pvector.push_back(5);
assert(pvector.const_at(5) == 5);
assert(pvector.size() == 6);

pvector.emplace(pvector.cbegin(), pvector.back());
assert(pvector.const_at(0) == 5);
for (unsigned i = 1; i < pvector.size(); ++i)
assert(pvector.const_at(i) == static_cast<int>(i - 1));

...

{{< /highlight >}}

Note that every single modifier method opens transaction internally and guarantees
full exception safety (modifications will be either committed or rolled-back if
an exception was thrown, or crash happened). There is no need for using transaction
when calling modifier methods whatsoever.

As you can see, we are checking `i < pvector.size()` on every loop iteration.
Since `pvector` is a reference to dereferenced persistent pointer, this check is
fast and can be optimized by compiler. But if you will use `root->vec_p->size()`
from the other hand, you will notice performance overhead. The reason behind
that is dereferencing of persistent_ptr in current implementation cannot be
optimized and cached by compilers. We are working on workaround for this issue,
but it is recommended to avoid unnecessary persistent_ptr dereferencing operations.

Iterating over `pmem::obj:experimental:vector` works just like for an ordinary `std::vector`:
you can use indexing operator, range-based for loops or iterators. `pmem::obj:experimental:vector`
can also be processed using `std::algorithms`:

{{< highlight C "linenos=table" >}}

...

std::vector<int> stdvector = {5, 4, 3, 2, 1};
pvector = stdvector;

try {
pmem::obj::transaction::run(pop, [&] {
for (auto &e : pvector)
e++;
/_ 6, 5, 4, 3, 2 _/

            for (auto it = pvector.begin(); it != pvector.end(); it++)
    	        *it += 2;
                /* 8, 7, 6, 5, 4 */

            for (unsigned i = 0; i < pvector.size(); i++)
            	pvector[i]--;
                /* 7, 6, 5, 4, 3 */

                std::sort(pvector.begin(), pvector.end());
                for (unsigned i = 0; i < sz; ++i)
                        assert(pvector.const_at(i) == static_cast<int>(i + 3));

                pmem::obj::transaction::abort(0);
        });

} catch (pmem::manual*tx_abort &) {
/* expected transaction abort \_/
} catch (std::exception &e) {
std::cerr << e.what() << std::endl;
}

assert(pvector == stdvector); /_ pvector element's value was rolled back _/

try {
pmem::obj::delete_persistent<vector_type>(&pvector);
} catch (std::exception &e) {
std::cerr << e.what() << std::endl;
}

{{< /highlight >}}

If there is an active transaction elements (accessed using any of the presented
above methods) are snapshotted. In case of iterators returned by begin() and end()
snapshotting happens during iterator dereferencing. Of course, snapshotting is
done only for mutable elements. In case of `const` iterators or `const` versions
of indexing operator, nothing is added to the transaction. That's why it is extremely
important to use const qualified function overloads (cbegin(), cend(), etc.) whenever possible
(if an object was snapshotted in current transaction, second snapshot of
the same memory address won't be performed and thus won't have performance overhead).
This will reduce number of snapshots and can significantly reduce the performance
impact of transactions.

Note also that `pmem::obj:experimental:vector` does define convenient constructors and compare
operators which take `std::vector` as an argument.

### pmem::obj::slice

In cases where loop is known to modify several consecutive elements in the vector,
a bulk-snapshot optimization can be performed using a special `range()` function.
The usage of `range()` and `pmem::obj::slice` was described in blog post about
`pmem::obj:array` [here][cpp_array_blogpost]. It works for `pmem::obj:experimental:vector` in
the same way.

### Summary

To summarize if you need persistent scratch pad, extension for in-memory database
or fast and flexible data storage with attributes of sequence container representing
arrays that can change in size, you should use `pmem::obj:experimental:vector`.

libpmemobj-cpp library provides two persistent containers now: `pmem::obj:array`
and `pmem::obj:experimental:vector`. We are currently working on `pmem::obj::string` implementation,
stay tuned!

[cpp_containers]: /blog/2018/11/c-persistent-containers/ 'blog post'
[cpp_vector]: /libpmemobj-cpp/master/doxygen/classpmem_1_1obj_1_1experimental_1_1vector.html 'pmem::obj:experimental:vector'
[cpp_array_blogpost]: /blog/2018/11/c-persistent-containers-array/ 'here'
[pmem_obj_vector]: /libpmemobj-cpp/master/doxygen/classpmem_1_1obj_1_1vector.html 'pmem::obj::vector'

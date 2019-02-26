---
title: C++ persistent containers - vector
author: szyrom
layout: post
identifier: cpp_vector
---

### Introduction

We have recently added [pmem::obj::vector][cpp_vector] container to libpmemobj-cpp library. 
This container is currently placed in `experimental` namespace and folder (this means
that both API and layout may change). It provides API similar to `std::vector`
from C++11 but guarantees commit or rollback semantics (full exception safety) and
allocates data in persistent memory.

### Limitations

[pmem::obj::vector][cpp_vector] allocates data in persistent memory libpmemobj pool,
hence maximum allocation size limitation equal to `PMEMOBJ_MAX_ALLOC_SIZE = 17177771968`
bytes (15,99 GB). Due to this limitation and due to the fact that [pmem::obj::vector][cpp_vector]
is dynamic contiguous array, maximum possible number of elements stored is equal
to `PMEMOBJ_MAX_ALLOC_SIZE / sizeof(element_type)`.

Since stored elements will reside in persistent memory, element's type **should** satisfy
requirements of:
* `StandardLayoutType` (because objects representation (layout) might differ between
        compilers/compiler flags/ABI)
* `TriviallyCopyable` (because we are not calling neither constructors nor destructors
        during snapshotting memory areas).

As a consequence, it means that stored element's type shouldn't be polymorphic,
should not have non-static data members of reference type, every copy constructor,
move constructor, copy assignment operator, move assignment operator should be trivial
(that is, it is implicitly-defined or defaulted) or deleted, at least one copy constructor,
move constructor, copy assignment operator, or move assignment operator is non-deleted
and should have trivial non-deleted destructor.

However, it is important to realize that pointers are trivially copyable types,
too. Whenever there are pointer inside the data structure that will be snapshotted
(memcopyed), you have to make sure that copying them around is proper. The same
rule applies for persistent_ptr type, even if it doesn't satisfy `TriviallyCopyable`
name requirements (because of explicitly-defined constructors).

[pmem::obj::vector][cpp_vector] user and every persistent memory programmer should 
keep in mind to check whether persistent_ptr could be copied in that specific case
and if that wouldn't cause errors and (persistent) memory leaks. One should realize,
that `std::is_trivially_copyable` is only the syntax check, but not the semantic test.
Technically speaking, using persistent_ptr in this context is an undefined behavior
but there is no golden mean and since C++ standard does not fully support persistent
memory programming, we should brainually make sure that copying persistent_ptr is
safe and use them.

It is very important to mention here, that storing volatile memory pointers in persistent
memory is almost always a design error (after application crash, pointer to virtual
memory is no longer valid). Using persistent_ptr is safe and the only way to access
specific memory area after application crash.

### API extensions

API for `pmem::obj::vector` and `std::vector` is the same, except for the following:
* `pmem::obj::vector` defines `range()` method (described below)
* `pmem::obj::vector` does not mark any non-const function as `noexcept` -
	elements must be added to a transaction which could result in an exception
* `pmem::obj::vector` defines constructor, assign method and assign operator which
        take `std::vector` as a argument
* `pmem::obj::vector` defines non-member compare functions between `pmem::obj::vector`
        and `std::vector`
* `pmem::obj::vector` defines `free_data()` function and it is recommended to call it
        before `pmem::obj::vector` destructor (freeing allocated persistent memory
        in transaction may throw an exception)
* `pmem::obj::vector` defines `const_at()`, `cfront()`, `cback()` and `cdata()`
        element access methods. We decided that using `at()`, `front()`, `back()`
        and `data()` overloads which return const_reference (or const_pointer)
        is not enough (overload deduction depends on the const-qualification of
        the object it is called on and it is burdensome to cast `pmem::obj::vector`
        into `const pmem::obj::vector`), especially in persistent memory programming,
        where accessing element's value for read-only purposes might be frequent
        operation and there is no need for doing it in transaction. Note that
        this is not possible to overcome this problem for `operator[]`.

### Usage

One of our main goals while designing `pmem::obj::vector` was to create as much
similar API to `std::vector` as possible. The only usage difference in persistent
memory version of vector is creation of an object.
`pmem::obj::vector` resides on persistent memory, so you must have access to stored
elements even after program crash, hence you must have a way to access your container
using pool's root object. The root object is the anchor to which all the memory
structures should be attached.

Here is an example how to create `pmem::obj::vector`:

{% highlight C linenos %}
#include <libpmemobj++/make_persistent.hpp>
#include <libpmemobj++/experimental/vector.hpp>

using vector_type = pmem::obj::experimental::vector<int>;

/* creating pmem::obj::vector in transaction */
pmem::obj::transaction::run(pop, [&] {
	root->vec_p = pmem::obj::make_persistent<vector_type>(/* optional constructor arguments */);
});

vector_type &pvector = *(root->vec1_p);

...

{% endhighlight %}

As you can see in above code snippet, `pmem::obj::vector` must be created and
allocated in persistent memory using transactional API (an exception will be
thrown otehwise). Vector's element type constructor may construct an object by
internally opening another transaction. In this case, inner transaction will be
flattened to outer one.

From now on, usage of `pmem::obj::vector` is similar to usage of `std::vector`:

{% highlight C linenos %}

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

unsigned sz = pvector.size();
for (unsigned i = 0; i < sz; ++i)
        assert(pvector.const_at(i) == static_cast<int>(i));

pvector.push_back(5);
assert(pvector.const_at(5) == 5);
assert(pvector.size() == 6);

pvector.emplace(pvector.cbegin(), pvector.back());
assert(pvector.const_at(0) == 5);
sz = pvector.size();
for (unsigned i = 1; i < sz; ++i)
        assert(pvector.const_at(i) == static_cast<int>(i - 1));

...

{% endhighlight %}

Note that there is no need for using transaction when calling modifier methods
whatsoever. Every single modifier method opens internally transaction and guarantees
full exception safety (modifications will be either committed, or rolled-back if
an exception was thrown, or crash happened).

As you can see, size of vector is cached in `sz` variable instead of checking 
`i < pvector.size()` on every loop iteration. The reason behind that is dereferencing
of persistent_ptr in current implementation cannot be optimized and cached by compilers.
We are working on workaround for this issue, but it is recommended to avoid unnecessary
persistent_ptr dereferencing operations.

Iterating over `pmem::obj::vector` works just like for an ordinary `std::vector`:
you can use indexing operator, range-based for loops or iterators. `pmem::obj::vector`
can also be processed using `std::algorithms`:

{% highlight C linenos %}

...

std::vector<int> stdvector = {5, 4, 3, 2, 1};
pvector = stdvector;

try {
        pmem::obj::transaction::run(pop, [&] {
	        for (auto &e : pvector)
		        e++;
                /* 6, 5, 4, 3, 2 */

	        for (auto it = pvector.begin(); it != pvector.end(); it++)
		        *it += 2;
                /* 8, 7, 6, 5, 4 */

                sz = pvector.size();
	        for (unsigned i = 0; i < sz; i++)
	        	pvector[i]--;
                /* 7, 6, 5, 4, 3 */

                std::sort(pvector.begin(), pvector.end());
                for (unsigned i = 0; i < sz; ++i)
                        assert(pvector.const_at(i) == static_cast<int>(i + 3));

                pmem::obj::transaction::abort(0);
        });
} catch (pmem::manual_tx_abort &) {
        /* expected transaction abort */
} catch (std::exception &e) {
        std::cerr << e.what() << std::endl;
}

assert(pvector == stdvector); /* pvector element's value was rolled back */

try {
        pmem::obj::delete_persistent<vector_type>(&pvector);
} catch (std::exception &e) {
        std::cerr << e.what() << std::endl;
}

{% endhighlight %}

If there is an active transaction, elements (accessed using any of the presented
above methods) are snapshotted. In case of iterators returned by begin() and end()
snapshotting happens during iterator dereferencing. Of course, snapshotting is
done only for mutable elements. In case of `const` iterators or `const` versions
of indexing operator, nothing is added to a transaction. That's why it is extremely
important to use `const` functions (cbegin(), cend(), etc.) whenever possible
(if an object was snapshotted in current transaction, the second one snapshot of
the same memory address won't be performed and won't have performance overhead).
It will reduce number of snapshots and can significantly reduce the performance
impact of transactions.

Note also that `pmem::obj::vector` does define convenient constructors and compare
operators which take `std::vector` as an argument.

### pmem::obj::slice

In cases where loop is known to modify several consecutive elements in the vector,
a bulk-snapshot optimization can be performed using a special `range()` function.
The usage of `range()` and `pmem::obj::slice` was described in blog post about
`pmem::obj:array` [here][cpp_array_blogpost]. It works for `pmem::obj::vector` in
exact the same way.

### Summary

To summarize, if you need persistent scratch pad, extension for in-memory database
or fast and flexible data storage with attributes of sequence container representing
arrays that can change in size, you should use `pmem::obj::vector`.

libpmemobj-cpp library provides now two persistent containers: `pmem::obj:array`
and `pmem::obj::vector`. We are currently working on `pmem::obj::string` implementation,
when it will be finished, expect new blog post about it.

[cpp_vector]: http://pmem.io/libpmemobj-cpp/master/doxygen/classpmem_1_1obj_1_1experimental_1_1vector.html "pmem::obj::vector"
[cpp_array_blogpost]: http://pmem.io/2018/11/02/cpp-array.html "here"

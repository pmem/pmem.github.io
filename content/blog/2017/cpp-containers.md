---
# Blog post title
title: 'Using Standard Library Containers with Persistent Memory'

# Blog post creation date
date: 2017-07-10T19:55:17-07:00

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
blogs: ['Containers']

tags: []

# Redirects from old URL
aliases: ['/2017/07/10/cpp-containers.html']

# Blog post type
type: 'post'
---

### Introduction

Somewhere along the road, when we were doing the C++ bindings for libpmemobj, we found the need for some kind of containers. We were faced with two viable solutions: write everything from scratch or adapt an existing implementation. The obvious choice was NOT to implement from scratch. We would have to implement at least the basic containers from the C++ standard: vector, list, set, map and their multi- companions. That would be a lot of work, not to mention the testing and maintenance effort. I'd say it would be the last resort, should all of our other options fail.

Ideally, if there we a set of containers, which were standard compliant, maintained by the community and enabled the user to substitute the allocation scheme and pointer type, that would be ideal. As it turns out, there is more than one. The C++ standard has long ago defined something called the...

### Allocator

There have been a lot of bad things said about the std::allocator and it's usability. Despite all of the bad press the allocator has, it was exactly the thing we needed. We have our own, persistent memory friendly allocator written in C and we have our custom pointer type - the persistent_ptr. All of the containers defined in the C++ standard use the std::allocator for the memory management of both own and user's data. Since this is defined in the standard, all we needed to do was implement the interface described by the standard, bind it together with the persistent_ptr and hope for the best. As it turned out, it wasn't that easy, but we'll get to that in a moment.

The `pmem::obj::allocator` is actually pretty straightforward. It is able to allocate sufficient storage and construct the specific object in the specified place. It can as well destroy the object and return the memory afterwards. The design is modular, so the user can override the construction/destruction of a specific object type as well as the allocation and deallocation. The latter two are however discouraged, unless you know what you're doing. The default rules implemented in the allocator work _as expected_, so in the 99% case, you don't really have to bother with the details. One of the most important parts of the allocator are the public typedefs, and in our case that would be the `pointer_type`, which is set to the `persistent_ptr`.

Now that we have a standard compliant, persistent memory specific implementation of the allocator, we have to check if it actually works with any of the containers from a popular standard library implementations.

_NOTE_
The allocator uses the transactional C API, so all operations using it must be enclosed in a transaction.

### libc++ or libstdc++?

The two rather obvious choices are GCC's libstdc++ and clang's libc++. We needed an open source implementation, so that if the need arose, we could freely modify the source code. And as it turned out, we needed to. As far as I remember, out of the box, only the `std::vector` worked flawlessly for both implementations. The `std::list` in libc++ also worked without any modifications, while the libstdc++ version didn't compile. At this point, it was quite obvious we would choose to go with libc++ for the initial version.

## std::vector

The vector implementation is basically identical in both libc++ and libstdc++. It's just three pointers, _begin, end, current_. This is because the vector is a really simple and at the same time really powerful container. This is taken from libc++
{{< highlight cpp "linenos=table" >}}
pointer **begin\_;
pointer **end*;
**compressed_pair<pointer, allocator_type> **end_cap*;
{{< /highlight >}}

`__compressed_pair` is a fancy way of saving space. You can think of it as a std::pair on a radical diet. These are the only data members. This works, because the pointer type is taken from:
{{< highlight cpp "linenos=table" >}}
typedef \_Allocator allocator_type;
typedef allocator_traits<allocator_type> **alloc_traits;
typedef typename **alloc_traits::pointer pointer;
{{< /highlight >}}
And in turn the allocator defines something like this:
{{< highlight cpp "linenos=table" >}}
using value_type = T;
using pointer = persistent_ptr<value_type>;
{{< /highlight >}}

This is a common pattern for all standard library containers. And finally how to use it:

{{< highlight cpp "linenos=table" >}}
using foovec = std::vector<foo, pmem::obj::allocator<foo>>;
pmem::obj::transaction::exec_tx(pop, [] {
auto pvec = pmem::obj::make_persistent<foovec>();
pvec->push_back(foo());
pvec->emplace_back(Last_val);
});
{{< /highlight >}}

## std::map

The `std::map` implementation is different and at the same time similar. The typedefs are there, albeit slightly changed:
{{< highlight cpp "linenos=table" >}}
template <class \_Key, class \_Tp>
struct \_\_value_type
{
typedef \_Key key_type;
typedef \_Tp mapped_type;
typedef pair<const key_type, mapped_type> value_type;
...
};

typedef \_VSTD::**value_type<key_type, mapped_type> **value_type;
typedef **map_value_compare<key_type, **value_type, key_compare> **vc;
typedef typename **rebind_alloc_helper<allocator_traits<allocator_type>,
**value_type>::type **allocator_type;
typedef **tree<**value_type, **vc, **allocator_type> **base;
typedef typename **base::**node_traits **node_traits;
typedef allocator_traits<allocator_type> \_\_alloc_traits;

template <class \_Tp, class \_Compare, class \_Allocator>
class **tree
{
...
typedef typename **rebind_alloc_helper<**alloc_traits, **node>::type **node_allocator;
typedef allocator_traits<**node_allocator> \_\_node_traits;
{{< /highlight >}}

This might be intimidating at first, but bear with me for a moment. What this says is really simple. The `std::map` implementation is in fact a `__tree`, which holds `std::pair<const key_type, value_type>`. However, the last two lines mean, that the tree will be allocating it's nodes using the allocator we specified in `std::map`. So let's see what the nodes look like.
{{< highlight cpp "linenos=table" >}}
template <class _VoidPtr>
class **tree_node_base
{
...
pointer **right*;
**parent_pointer **parent*;
bool \__is_black_;
}
{{< /highlight >}}

### Let's tell the containers they're persistent!

The vector was just a couple of pointers, but here we can see that nodes have state, and rightfully so, it's a red-black tree. The `__is_black` flag will change during the lifetime of the container. This means, it needs to be tracked by libpmemobj inside transactions, in case we need to roll back the state of the container. If you recall, it's the `pmem::obj::p` that wraps basic types for transactional modifications. So we somehow have to tell `__tree_node_base` that there's this thing called `p` that it has to use for it's own data. Up to this point, we used the allocator as the entry point for all typedefs and it would be the ideal to place this new typedef as well. However, as you can see in the class definition, the `__tree_node_base` has absolutely no notion of an allocator. Therefore no way to fetch `p` from it.

## std::pointer_traits::persistency_type?

But we can leverage the fact that it does have a `pointer` typedef. So if we place the newly devised `persistency_type` in the `std::pointer_traits` of the `persistent_ptr`, the `__tree` implementation behind `std::map` will have all the necessary information to properly function in a persistent memory context. So how is it done, exactly? Within `persistent_ptr` you can find:
{{< highlight cpp "linenos=table" >}}
/\*\*

- The persistency type to be used with this pointer.
  \*/
  using persistency_type = p<T>;
  {{< /highlight >}}

And this is all we could do within PMDK to help support more complex containers. The rest of the work has to be done within the standard library. The `pointer_traits` implementation is placed in the _memory_ header file. The `persistency_type` has to be optional and have a default value, not to break existing code. There is a trick for that:
{{< highlight cpp "linenos=table" >}}
template <class _Ptr>
struct **has_persistency_type
{
private:
struct **two {char **lx; char **lxx;};
template <class _Up> static **two **test(...);
template <class _Up> static char **test(typename \_Up::persistency_type\* = 0);
public:
static const bool value = sizeof(**test<\_Ptr>(0)) == 1;
};

template <class \_Tp, class \_Ptr, bool = **has_persistency_type<\_Ptr>::value>
struct **pointer_traits_persistency_type
{
typedef \_Tp type;
};

template <class \_Tp, class \_Ptr>
struct **pointer_traits_persistency_type<\_Tp, \_Ptr, true>
{
typedef typename \_Ptr::persistency_type type;
};
{{< /highlight >}}
This piece of code, that might look like gibberish at first, but actually it is a nifty trick. If `_Ptr` defines a `persistency_type` type, the `**test<\_Ptr>(0)`will first resolve to the function returning`char`, therefore the `sizeof`call will return one, setting the`bool value`to true. Thanks to this`**pointer_traits_persistency_type::type`will resolve to the`\_Ptr::persistency_type`only when it is available. Otherwise it will not alter the type. As I said, a nifty trick. Now that we have this, let's use it in the`**tree`.
{{< highlight cpp "linenos=table" >}}
typedef typename \_\_rebind_persistency_type<pointer, bool>::type bool_type;

pointer **right\_;
**parent*pointer \_\_parent*;
bool*type \_\_is_black*;
{{< /highlight >}}
The `__rebind_persistency_type` is a helper for simple type change. Here this means that for `pointer` being `pmem::obj::persistent_ptr`, `bool_type` is in fact `std::pointer_traits<pointer>::persistency_type<bool>::type` so in fact `pmem::obj::p<bool>`. All in one clean line. This, in a big shortcut is how we adapted the containers from libc++.

### Conclusion

It is always better to reuse well written and tested code, than to write everything from scratch. At least as a first attempt. The upside to the approach we have taken is that all standard library containers could be adapted with little effort and it doesn't brake legacy code. The downside is that this is a non-standard approach that will not hit upstream in the nearest future. You can see the changes made to libc++ in our [repo][33a989af] under pmem. Please note that this is an experimental implementation and should _NOT_ be used in a production environment.

[33a989af]: https://github.com/pmem/libcxx 'Pmem libc++'

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

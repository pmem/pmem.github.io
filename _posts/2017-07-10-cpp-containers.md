---
title: Using Standard Library Containers with Persistent Memory
author: tomaszkapela
layout: post
identifier: cpp_containers
---

### Introduction

Somewhere along the road, when we were doing the C++ bindings for NVML, we found the need for some kind of containers. We were faced with two viable solutions: write everything from scratch or adapt an existing implementation. The obvious choice was NOT to implement from scratch. We would have to implement at least the basic containers from the C++ standard: vector, list, set, map and their multi- companions. That would be a lot of work, not to mention the testing and maintenance effort. I'd say it would be the last resort, should all of our other options fail.

Ideally, if there we a set of containers, which were standard compliant, maintained by the community and enabled the user to substitute the allocation scheme and pointer type, that would be ideal. As it turns out, there is more than one. The C++ standard has long ago defined something called the...

### Allocator

There have been a lot of bad things said about the std::allocator and it's usability. Despite all of the bad press the allocator has, it was exactly the thing we needed. We have our own, persistent memory friendly allocator written in C and we have our custom pointer type - the persistent_ptr. All of the containers defined in the C++ standard use the std::allocator for the memory management of both own and user's data. Since this is defined in the standard, all we needed to do was implement the interface described by the standard, bind it together with the persistent_ptr and hope for the best. As it turned out, it wasn't that easy, but we'll get to that in a moment.

The `nvml::obj::allocator` is actually pretty straightforward. It is able to allocate sufficient storage and construct the specific object in the specified place. It can as well destroy the object and return the memory afterwards. The design is modular, so the user can override the construction/destruction of a specific object type as well as the allocation and deallocation. The latter two are however discouraged, unless you know what you're doing. The default rules implemented in the allocator work *as expected*, so in the 99% case, you don't really have to bother with the details. One of the most important parts of the allocator are the public typedefs, and in our case that would be the `pointer_type`, which is set to the `persistent_ptr`.

Now that we have a standard compliant, persistent memory specific implementation of the allocator, we have to check if it actually works with any of the containers from a popular standard library implementations.

*NOTE*
The allocator uses the transactional C API, so all operations using it must be enclosed in a transaction.

### libc++ or libstdc++?

The two rather obvious choices are GCC's libstdc++ and clang's libc++. We needed an open source implementation, so that if the need arose, we could freely modify the source code. And as it turned out, we needed to. As far as I remember, out of the box, only the `std::vector` worked flawlessly for both implementations. The `std::list` in libc++ also worked without any modifications, while the libstdc++ version didn't compile. At this point, it was quite obvious we would choose to go with libc++ for the initial version.

## std::vector

The vector implementation is basically identical in both libc++ and libstdc++. It's just three pointers, _begin, end, current_. This is because the vector is a really simple and at the same time really powerful container. This is taken from libc++
{% highlight cpp linenos %}
pointer                                     __begin_;
pointer                                     __end_;
__compressed_pair<pointer, allocator_type>  __end_cap_;
{% endhighlight %}

`__compressed_pair` is a fancy way of saving space. You can think of it as a std::pair on a radical diet. These are the only data members. This works, because the pointer type is taken from:
{% highlight cpp linenos %}
typedef _Allocator                               allocator_type;
typedef allocator_traits<allocator_type>         __alloc_traits;
typedef typename __alloc_traits::pointer         pointer;
{% endhighlight %}
And in turn the allocator defines something like this:
{% highlight cpp linenos %}
using value_type = T;
using pointer = persistent_ptr<value_type>;
{% endhighlight %}

This is a common pattern for all standard library containers. And finally how to use it:

{% highlight cpp linenos %}
using foovec = std::vector<foo, nvml::obj::allocator<foo>>;
nvml::obj::transaction::exec_tx(pop, [] {
        auto pvec = nvml::obj::make_persistent<foovec>();
        pvec->push_back(foo());
        pvec->emplace_back(Last_val);
});
{% endhighlight %}

## std::map

The `std::map` implementation is different and at the same time similar. The typedefs are there, albeit slightly changed:
{% highlight cpp linenos %}
template <class _Key, class _Tp>
struct __value_type
{
    typedef _Key                                     key_type;
    typedef _Tp                                      mapped_type;
    typedef pair<const key_type, mapped_type>        value_type;
...
};

typedef _VSTD::__value_type<key_type, mapped_type>             __value_type;
typedef __map_value_compare<key_type, __value_type, key_compare> __vc;
typedef typename __rebind_alloc_helper<allocator_traits<allocator_type>,
                                                 __value_type>::type __allocator_type;
typedef __tree<__value_type, __vc, __allocator_type>   __base;
typedef typename __base::__node_traits                 __node_traits;
typedef allocator_traits<allocator_type>               __alloc_traits;

template <class _Tp, class _Compare, class _Allocator>
class __tree
{
...
typedef typename __rebind_alloc_helper<__alloc_traits, __node>::type __node_allocator;
typedef allocator_traits<__node_allocator>         __node_traits;
{% endhighlight %}

This might be intimidating at first, but bear with me for a moment. What this says is really simple. The `std::map` implementation is in fact a `__tree`, which holds `std::pair<const key_type, value_type>`. However, the last two lines mean, that the tree will be allocating it's nodes using the allocator we specified in `std::map`. So let's see what the nodes look like.
{% highlight cpp linenos %}
template <class _VoidPtr>
class __tree_node_base
{
  ...
  pointer          __right_;
  __parent_pointer __parent_;
  bool __is_black_;
}
{% endhighlight %}
### Let's tell the containers they're persistent!

The vector was just a couple of pointers, but here we can see that nodes have state, and rightfully so, it's a red-black tree. The `__is_black` flag will change during the lifetime of the container. This means, it needs to be tracked by libpmemobj inside transactions, in case we need to roll back the state of the container. If you recall, it's the `nvml::obj::p` that wraps basic types for transactional modifications. So we somehow have to tell `__tree_node_base` that there's this thing called `p` that it has to use for it's own data. Up to this point, we used the allocator as the entry point for all typedefs and it would be the ideal to place this new typedef as well. However, as you can see in the class definition, the `__tree_node_base` has absolutely no notion of an allocator. Therefore no way to fetch `p` from it.

## std::pointer_traits::persistency_type?

But we can leverage the fact that it does have a `pointer` typedef. So if we place the newly devised `persistency_type` in the `std::pointer_traits` of the `persistent_ptr`, the `__tree` implementation behind `std::map` will have all the necessary information to properly function in a persistent memory context. So how is it done, exactly? Within `persistent_ptr` you can find:
{% highlight cpp linenos %}
/**
 * The persistency type to be used with this pointer.
 */
using persistency_type = p<T>;
{% endhighlight %}

And this is all we could do within NVML to help support more complex containers. The rest of the work has to be done within the standard library. The `pointer_traits` implementation is placed in the _memory_ header file. The `persistency_type` has to be optional and have a default value, not to break existing code. There is a trick for that:
{% highlight cpp linenos %}
template <class _Ptr>
struct __has_persistency_type
{
private:
    struct __two {char __lx; char __lxx;};
    template <class _Up> static __two __test(...);
    template <class _Up> static char __test(typename _Up::persistency_type* = 0);
public:
    static const bool value = sizeof(__test<_Ptr>(0)) == 1;
};

template <class _Tp, class _Ptr, bool = __has_persistency_type<_Ptr>::value>
struct __pointer_traits_persistency_type
{
    typedef _Tp type;
};

template <class _Tp, class _Ptr>
struct __pointer_traits_persistency_type<_Tp, _Ptr, true>
{
    typedef typename _Ptr::persistency_type type;
};
{% endhighlight %}
This piece of code, that might look like gibberish at first, but actually it is a nifty trick. If `_Ptr` defines a `persistency_type` type, the `__test<_Ptr>(0)` will first resolve to the function returning `char`, therefore the `sizeof` call will return one, setting the `bool value` to true. Thanks to this `__pointer_traits_persistency_type::type` will resolve to the `_Ptr::persistency_type` only when it is available. Otherwise it will not alter the type. As I said, a nifty trick. Now that we have this, let's use it in the `__tree`.
{% highlight cpp linenos %}
typedef typename __rebind_persistency_type<pointer, bool>::type bool_type;

pointer          __right_;
__parent_pointer __parent_;
bool_type __is_black_;
{% endhighlight %}
The `__rebind_persistency_type` is a helper for simple type change. Here this means that for `pointer` being `nvml::obj::persistent_ptr`, `bool_type` is in fact `std::pointer_traits<pointer>::persistency_type<bool>::type` so in fact `nvml::obj::p<bool>`. All in one clean line. This, in a big shortcut is how we adapted the containers from libc++.

### Conclusion

It is always better to reuse well written and tested code, than to write everything from scratch. At least as a first attempt. The upside to the approach we have taken is that all standard library containers could be adapted with little effort and it doesn't brake legacy code. The downside is that this is a non-standard approach that will not hit upstream in the nearest future. You can see the changes made to libc++ in our [repo][33a989af] under pmem. Please note that this is an experimental implementation and should *NOT* be used in a production environment.

[33a989af]: https://github.com/pmem/libcxx "Pmem libc++"

---
title: "C++ bindings for libpmemobj (epilogue) - converting existing applications"
author: tomaszkapela
layout: post
identifier: cpp-ctree
---

During the development of the C++ bindings, we wrote a couple of examples and
even more tests. But these are new applications written from scratch to
understand persistence. While this approach is OK for newly developed apps,
there is a lot of existing code out there that is not designed for persistent
memory. It would be a real shame, if the existing solutions couldn't benefit
from the existence of persistent memory because of the amount of work needed
to redesign and change them. This was one of the main pain points that we wanted
to address with the C++ bindings. We tried to make them as seamless to use and
as easily introducible to existing software as possible. In this blog post I
will show you exactly how much work is necessary to change an implementation
of a ctree from oblivious to fully persistent memory aware.

### The transient ctree

The implementation of the transient object oriented [ctree][27f95bc8] is based
on the example written by @pbalcer, which can be found [here][2ee5f9a5]. There
are of course a couple of differences, the most significant being the decision
to not use the object type for insertion logic. The thing is, you could do it
if you made the inheritance non-virtual and made sure to never destroy objects
through base class pointers. However due to our strict policy about warnings
and errors, I decided to do it in a bit different way. Other than that, the
C and C++ implementations are very similar.

First and foremost, if you're switching to C++11, and you obviously are if you
want to use our C++ bindings, use `auto` as much as possible. This will greatly
reduce the amount of changes you need to make. So for example:

{% highlight cpp linenos %}
// do this
auto new_node = new node();
auto leaf = get_leaf(key, &parent);

// instead of this
node *new_node = new node();
entry *lead = get_leaf(key, &parent);
{% endhighlight %}

If you have this and are used to defining typedefs for your structure types, you
have most of the work done already! If you think that this will be a
long and complicated blog post on how much magic you'd have to utilize, you are
mistaken - we are nearly done (sorry, there will be no [magic][b60cbeed] this
time).

### The process of adaptation

The first thing you need to do is change all your data members to persistent
data members. Just wrap the simple types with the `p<>` template and the rest in
`persistent_ptr<>`. So in the case of the ctree example:

{% highlight cpp linenos %}
typedef T *value_type;
...
key_type key;
node *inode;
...
entry *root;
{% endhighlight %}

Change to:

{% highlight cpp linenos %}
typedef pmem::obj::persistent_ptr<T> value_type;
...
pmem::obj::p<key_type> key;
pmem::obj::persistent_ptr<node> inode;
...
pmem::obj::persistent_ptr<entry> root;
{% endhighlight %}

The next thing you have to take into account are the allocations and frees. You
have to use the `make_persistent` and `delete_persistent` respectively. This is
where it gets just a bit tricky, because:

{% highlight cpp linenos %}
ctree_map_transient() : root(new entry())
{
}
// changes to
ctree_map_persistent()
{
	auto pop = pmem::obj::pool_by_vptr(this); // get the pool handle

	pmem::obj::transaction::exec_tx( // make the allocation atomic
		pop, [&] { root = pmem::obj::make_persistent<entry>(); });
}
{% endhighlight %}

You might be wondering, why not just use the `make_persistent_atomic` and not do
a one-line transaction? Well, the constructor itself might be called in a
transaction, and by now I'm sure you know, that the atomic and transactional API
are not meant to be used together.

Besides the constructor, all the other allocations are just mechanical changes:

{% highlight cpp linenos %}
auto new_node = new node();
// changes to
auto new_node = pmem::obj::make_persistent<node>();
{% endhighlight %}

The necessary changes to deletions are also straightforward:

{% highlight cpp linenos %}
delete dest_entry->value;
// changes to
pmem::obj::delete_persistent<T>(dest_entry->value);
{% endhighlight %}

Because the API of the ctree has to be atomic with respect to persistence, there
are a lot of `transaction::exec_tx` calls inside each public method. This is
manual work that has to be done if you want your tree to be consistent at all
times. And believe me, you do.

The last thing you need to do is open a pool and allocate a ctree instance from
it. If you have been thorough about your changes, you now have a persistent
memory resident data structure. You might want to check it with
[pmemcheck][033d3abb] though, just in case.


### The persistent ctree

The implementation is most certainly not optimal, because it was to serve as
an example on exactly how much effort is needed to make algorithms/data
structures persistent memory aware. As it turns out, fairly little.

The whole example is available [here][c14a5bbd] and as always I urge you to
play with it, change it and hopefully write something of your own.

### Summary

With this I believe we have concluded this series of blog posts on C++. Together
with @pbalcer we have introduced all of the major components of the C++ bindings
to libpmemobj. We have also shown how to adapt your existing applications to
persistent memory using C++. What is left now is for you to use our bindings and
give us feedback. Remember the C++ bindings are still an **experimental API**.
We are doing our best to see whether this API holds or if it needs some tweaks,
but your feedback is invaluable. [Thank you!][ecfe85f3]

[27f95bc8]: https://github.com/pmem/libpmemobj-cpp/blob/master/examples/map_cli/ctree_map_transient.hpp "transient ctree"
[2ee5f9a5]: https://github.com/pmem/pmdk/blob/master/src/examples/libpmemobj/tree_map/ctree_map.c "C ctree"
[b60cbeed]: http://giphy.com/gifs/rainbow-unicorn-highway-G0nTMRctvIp4Q "unicorns and rainbows"
[033d3abb]: https://github.com/pmem/valgrind "pmemcheck"
[c14a5bbd]: https://github.com/pmem/libpmemobj-cpp/blob/master/examples/map_cli "ctree examples"
[ecfe85f3]: http://giphy.com/gifs/end-looney-tunes-thats-all-folks-jYAGkoghdmD9S "That's all folks!"

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK]({% post_url 2017-12-11-NVML-is-now-PMDK %}).]
###### [This entry was edited on 2018-07-06 to change links to examples]

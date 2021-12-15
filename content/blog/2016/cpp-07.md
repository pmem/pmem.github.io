---
# Blog post title
title: 'C++ bindings for libpmemobj (part 6) - transactions'

# Blog post creation date
date: 2016-05-25T19:55:17-07:00

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
aliases: ['/2016/05/25/cpp-07.html']

# Blog post type
type: 'post'
---

As I mentioned in my previous blog [post][b31a2ae5], transactions are the heart
of libpmemobj. That is why we had to take utmost care while designing their
C++ versions, so that they are as easy to use as possible. There are, however, a
couple of compromises we had to make due to the inadequacies of the C++11
standard. That is why we encourage using the lambda, until the C++17 standard is
more widely implemented. Frankly, even after C++17, the lambda transactions are
more user friendly.

### Closure-like transactions

This is the type of transactions we encourage everyone to use. It automatically
handles all of the _commit/abort_ semantics behind the scenes, so you don't have
to worry about it. As the worker for the transactions, it accepts a
`std::function<void()>` object, so you don't have to use lambda expressions if
you don't want to. The transactions however look really nice and clear, so don't
be discouraged by the peculiar C++ lambda syntax. Here is an example:

{{< highlight cpp "linenos=table" >}}
auto pop = pool_base::create(...);
persistent_ptr<entry> pentry;
transaction::exec_tx(pop, [&] {
pentry = make_persistent<entry>();
// make other changes inside the transaction
});
{{< /highlight >}}

Of course, if you need to take locks for the whole durations of the transaction,
you can do that. The `transaction::exec_tx`, takes a `locks` variadic template
parameter:

{{< highlight cpp "linenos=table" >}}
auto pop = pool_base::create(...);
transaction::exec_tx(pop, transaction_fn, locks...);
{{< /highlight >}}

By `locks`, I of course mean the C++ persistent memory resident locks which
are available in the libpmemobj bindings.

The closure-like transactions handle cases where there are exceptions thrown
inside the transaction. The transaction is then aborted and the original
exception is rethrown. That way you never loose the original exception and at
the same time, the transaction state is handled properly by our library. If the
transaction aborts because of internal errors (such as out of memory errors),
you will get an `pmem::transaction_error` exception.

This is the preferred way of handling transactions, because every aspect of the
transaction is handled automatically by the library. If you however find the
lambda functions cumbersome to write, you have another option.

### Manual transactions

The manual transactions are a little tricky to use, because they abort the
transaction by default. What I mean by that is that the following example will
abort:

{{< highlight cpp "linenos=table" >}}
auto pop = pool_base::create(...);
{
transaction::manual tx(pop);
auto pentry = make_persistent<entry>();
} // here the transaction aborts
{{< /highlight >}}

You might wonder, why did we at all decide to do manual transactions if the
`std::uncaught_exception` is available in C++11? Why not go automatic from the
start? Yes it is available, but with the way it is designed and implemented, it
is not usable. You can read [this][55cd0734] nice article and the
[solution][b54915a7] proposed by Herb Sutter to C++17. To sum it up (although
I encourage you to read the articles), with the C++11 `uncaught_exception`
you do not know if the object is being destroyed to perform stack unwinding or
was the unwinding already in progress. So for example, if a transaction would
be started as cleanup in an object's destructor, you wouldn't know whether to
abort or commit the transaction - there would already be an active exception
at the start of the transaction. The only way out of this predicament in C++11
is to manually commit the transaction:

{{< highlight cpp "linenos=table" >}}
auto pop = pool_base::create(...);
{
transaction::manual tx(pop);
auto pentry = make_persistent<entry>();
transaction::commit(); // here the transaction commits
}
{{< /highlight >}}

By now, you are probably wondering

> How will I know if the transaction aborted?

And that is a very good question. There is an API call
`transaction::get_last_tx_error()` which tells you whether the last transaction
errored. This makes this scoped RAII approach really tedious and fragile. Yet
another reason to get used to the closure version of transactions.

### Automatic transactions

The automatic scoped RAII transactions leverage the improved
`std::uncaught_exceptions` from C++17, which is available if the
`__cpp_lib_uncaught_exceptions` feature test macro is defined. It is available
since GCC 6.1(libstdc++) and clang 3.7(libc++). The automatic version releases
the developer from the burden of manually committing the transaction.

{{< highlight cpp "linenos=table" >}}
auto pop = pool_base::create(...);
{
transaction::automatic tx(pop);
auto pentry = make_persistent<entry>();
} // here the transaction commits
{{< /highlight >}}

However you still don't know whether the transaction committed or aborted and
still have to use the `transaction::get_last_tx_error()`. So if you really need
the scoped transactions, try to use the automatic versions as they are less
error prone. However, I would still recommend the closure ones.

### Utils

Besides the closure and scoped transactions, the `transaction` class has a
couple of utility methods that might come in handy.

- `static void abort(int err)` aborts the transaction and sets the given error
  code
- `static void commit()` commits the transaction
- `static int get_last_tx_error()` return the error code of the last transaction

There is one more thing I need to mention. There should be no code after the
calls to `transaction::abort` or `transaction::commit`. There are a bunch of
reasons why this is wrong, the main being something called _undefined behavior_.

We are reaching the end of this series of blog posts on C++ bindings. In the
next post I will show you the persistent memory resident synchronization
variables available in our C++ bindings for libpmemobj. After that I would like
to do a short story on how to convert existing code to understand persistent
memory using C++.

[b31a2ae5]: /blog/2016/05/c-bindings-for-libpmemobj-part-5-make_persistent 'make_persistent'
[55cd0734]: http://www.gotw.ca/gotw/047.htm 'uncaught_exception'
[b54915a7]: https://isocpp.org/files/papers/N4152.pdf 'uncaught_exceptions'

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/NVML-is-now-PMDK).]

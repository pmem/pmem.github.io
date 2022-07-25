---
# Blog post title
title: 'Concurrency considerations in libpmemobj-cpp'

# Blog post creation date
date: 2021-09-17

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
aliases: ['/2021/09/17/concurrency.html']

# Blog post type
type: 'post'
---

### Introduction

Ensuring data consistency on pmem is a challenging task. It gets even more
complicated if data is modified concurrently. This blog post describes several
challenges related to data visibility, using transactions in multi-threaded
environments, and memory leaks.

### Lock-free programming on pmem

A fundamental issue (if [eADR][eadr] is not used) is data visibility. When a
thread issues a temporal (e.g., MOV) store instruction, the modification might
be visible to other threads before it is persistent (data can still be in a CPU
cache). Consider the following scenario with one thread writing to a variable
using atomic operations and the second one reading from it:

```c++
// thread 1
// initial value of pmem->a is 0
atomic_store(&pmem->a, 1);                  // visible = 1, persistent = ?
pmem_persist(&pmem->a, sizeof(pmem->a));    // visible = 1, persistent = 1

// thread 2
// initial value of pmem->b is 0
if (atomic_load(&pmem->a) == 1) {
    pmem->b = 1;                                // visible = 1, persistent = ?
    pmem_persist(&pmem->b, sizeof(pmem->b));    // visible = 1, persistent = 1
}
```

Let's analyze the values of pmem->a and pmem->b from the second thread
perspective for a x86 architecture (visibility vs persistency guarantees
might differ for different architectures). If a crash happened during the
application execution, the possible values on restart are:

- (`pmem->a = 0`, `pmem->b = 0`) – e.g., when the crash happened before the
  first thread started
- (`pmem->a = 1`, `pmem->b = 1`) – e.g., when the crash happened after both
  threads completed
- (`pmem->a = 1`, `pmem->b = 0`) – e.g., when the crash happened after the
  first thread completed but before the second one started

But what happens if there was a crash just after the first thread set the
value, but before it called `pmem_persist`? The second thread could have
observed the new value (which is not yet persistent) and set the `pmem->b`
variable to `1`. On restart, it's possible that `pmem->a` is equal to `0`,
and `pmem->b` is equal to `1`. All four combinations of values are possible
on restart. The fact that the second thread might take some actions based
on a non-persistent state is problematic as it causes data inconsistency.
One possible way of preventing this situation is by persisting all values just
after reading them (but before any other action is taken based on the
value). This guarantees that the value was persistent at some point in time.
The second thread from the earlier example could look like presented below:

```c++
// thread 2
// initial value of pmem->b is 0
if (atomic_load(&pmem->a) == 1) {
    pmem_persist(&pmem->a, sizeof(pmem->a));    // visible = 1, persistent = 1
    pmem->b = 1;                                // visible = 1, persistent = ?
    pmem_persist(&pmem->b, sizeof(pmem->b));    // visible = 1, persistent = 1
}
```

This solution makes (`pmem->a = 0`, `pmem->b = 1`) case impossible at the cost
of additional persist on each read. To optimize this approach, we can add a
dirty bit inside the value, which indicates whether the value was explicitly
persisted or not. When modifying the value, the dirty flag is set to true. All
readers first check this flag and proceed as follows:

- If a flag is set: a reader uses compare-and-swap to clear the bit and then
  persists the value.
- If a flag is not set: the value is for sure persistent, and the reader can
  proceed.

You can see an example of such implementation [here][ringbuf-example].

### Shared state consistency

One other problem which arises in concurrent applications is keeping shared
state consistent. For example, to guarantee that calculating the number of
elements in a data structure has O(1) time complexity, a size variable is
usually used. In a case where only a single thread modifies the variable, one
can safely use pmemobj transaction to ensure the size consistency like this:

```c++
struct list {
    pmem::obj::p<size_t> size;

    void push_back(Value v) {
        pmem::obj::transaction::run(pop, [&]{
            ... // allocate new node and insert it at the end

            size++;
        });
    }
};
```

Just as a reminder (more details can be found [here][cpp-02])
when a variable of type `pmem::obj::p` (or other persistent-aware structure
like `persistent_ptr` or `self_relative_ptr`) is modified, its old value is saved
in an undo log. In case the enclosing transaction aborts or there is a power
failure, the value is rolled back. If the transaction succeeds, all modified
variables of persistent-aware types are persisted.

In a concurrent case, when multiple threads can insert new elements and update
the size, this becomes more problematic. A shared variable cannot be simply
modified in a pmemobj (undo log based) transaction without taking a global
lock. To see why consider the following example:

```c++
struct list {
    pmem::obj::p<std::atomic<size_t>> size;

    // This function is called concurrently from multiple threads
    void push_back(Value v) {
        pmem::obj::transaction::run(pop, [&]{
            ... // allocate new node and insert it at the end

            size++;
        });
    }
};
```

Let's assume that there are two threads calling `push_back()` concurrently, and
they start incrementing size simultaneously. They will both store the same
initial `size` value in theirs undo logs. If the transaction in the first
thread aborts, it will abort the `size` value to the initial state. It will
completely ignore the changes made by the second transaction.

One other problem is that readers might suffer from the 'dirty reads'
phenomenon. If a reader reads size variable while another thread modifies it
inside a transaction, it reads uncommitted data. If a transaction aborts or a
power failure happens, the size variable can be rolled back, and the reader's
view of the data might be incorrect. In addition to that, the rollback itself
is not thread-safe.

One possible solution is to make the global size volatile. The volatile
variable can be modified using atomic instructions (after the transaction
completes) while providing the number of elements in O(1) time. To avoid losing
size on application restart we can introduce a persistent, per-thread `diff`
variable which will track how many elements were inserted or deleted by a
thread. Since it is per-thread, it can be safely modified in a transaction.
On recovery, all `diff` variables can be summed-up to provide the actual size.
Libpmemobj-cpp implements a helper data structure (which as of publish date
of this blog post is not yet released as a public API) called
[enumerable_thread_specific][ptls-doc]. It provides API to request memory which
will be private to a thread and iteration API. Enumerable thread-specific is
used in concurrent_map and concurrent_hash_map to solve the shared state
problem.

Following example shows `recover` and reimplemented `push_back` method for list
data structure considered above:

```c++
struct list {
    std::atomic<size_t> global_size;
    enumerable_thread_specific<pmem::obj::p<int64_t>> ptls;

    void push_back(Value v) {
        // `diff` is a reference to thread-local storage on pmem
        auto &diff = ptls.local();

        pmem::obj::transaction::run(pop, [&]{
            ... // allocate new node and insert it at the end

            diff++;
        });

        global_size++;
    }

    void recover() {
        int64_t size = 0;
        for (auto &diff : ptls)
            size += diff;

        global_size = size;
    }
};
```

#### Publishing allocations

In the above example, we ignored allocating and publishing new nodes - let's
look at this now. The example below shows an attempt at implementing the
`push_back` method in a concurrent, lock-free list. For simplicity, this example
will assume that there can be multiple concurrent operations on the list, but
only one of them can be `push_back` (it will be a single-writer,
multi-reader list).

```c++
using pmem::obj;
using pmem::obj::experimental;

struct list {
    struct Node {
        Value v;
        std::atomic<self_relative_ptr<Node>> next;
    };

    std::atomic<self_relative_ptr<Node>> head;

    void push_back(Value v) {
        self_relative_ptr<Node> new_node;
        transaction::run(pop, [&]{
            new_node = make_persistent<Node>(v);
            find_last()->next.store(new_node);
        });
    }

    void iterate(F callback) {
        self_relative_ptr<Node> ptr = head.load();
        while (ptr != nullptr) {
            callback(ptr->v);
            ptr = ptr->next.load();
        }
    }
};
```

The implementation uses self_relative_ptr as it's currently the only `pmem::obj`
pointer type that is working with `std::atomic`.

A careful reader will notice that the implementation will not work correctly.
Here, we encounter the same problem with dirty reads which was described in
section [Shared state consistency](#shared-state-consistency).

To solve this problem, one can try to move atomic store outside of the
transaction:

```c++
void push_back(Value v) {
    self_relative_ptr<Node> new_node;
    pmem::obj::transaction::run(pop, [&]{
        new_node = make_persistent<Node>(v);
    });

    find_last()->next.store(new_node);
    pop.persist(&find_last()->next, sizeof(new_node));
}
```

To make sure there are no visibility problems, iterate method would need to be
changed to use persist on read, as described in section
[Lock-free programming on pmem](#lock-free-programming-on-pmem):

```c++
void iterate(F callback) {
    self_relative_ptr<Node> ptr = head.load();
    pop.persist(&head, sizeof(head));

    while (ptr != nullptr) {
        callback(ptr->v);

        auto next = ptr->next.load();
        pop.persist(&ptr->next, sizeof(ptr->next));

        ptr = next;
    }
}
```

However, there is still one other issue - a possible memory leak. If a crash
happens during the `push_back` method just after the new node is allocated,
but before it is linked to the list, it will not be reachable after a restart.
`new_node` pointer is kept on the stack, which means it will not survive a restart.

A solution to this problem is to move `new_node` pointer variable from stack to
persistent memory. In this example it can be a separate variable within the
`list` structure.

```c++
void push_back(Value v) {
    pmem::obj::transaction::run(pop, [&]{
        // new_node resides on pmem.
        this->new_node = make_persistent<Node>(v);
    });

    find_last()->next.store(this->new_node);
    pop.persist(&find_last()->next, sizeof(this->new_node));
}
```

Because `new_node` now resides on persistent memory, even if it is not linked
to the list, it is always reachable on restart. There are no problems with
dirty reads or visibility as `new_node` is never read during normal execution
(only on recovery). If `push_back` would need to be thread-safe, `new_node`
variable could be replaced with enumerable_thread_specific.

### Summary

In this blog post, I highlighted a few challenges related to concurrent
programming on persistent memory.

First, I discussed the fact that data modification might be visible before
becoming persistent, which can lead to data inconsistencies in lock-free
programming.

Then, I moved on to discuss how to handle shared state on persistent memory.
I proposed a solution for keeping the shared state consistent by using
persistent, per-thread storage.

Finally, I talked about avoiding memory leaks in pmemobj-based, lock-free
applications. I suggested splitting global state modifications into two steps.
First, store a pointer to newly allocated memory to a private (possibly
per-thread) persistent variable and only then make it visible for other
threads.

[eadr]: /glossary/#eadr
[cpp-02]: /blog/2016/01/c-bindings-for-libpmemobj-part-1-pmem-resident-variables
[ringbuf-example]: https://github.com/pmem/pmdk/blob/317a11cfc131681ace43e2865e1dd244a177ed00/src/examples/libpmem2/ringbuf/ringbuf.c#L212-L241
[ptls-doc]: /libpmemobj-cpp/master/doxygen/classpmem_1_1detail_1_1enumerable__thread__specific.html

---
title: Concurrency considerations in libpmemobj-cpp
author: igchor
layout: post
identifier: concurrency_considerations
---

### Introduction

Ensuring data consistency on pmem is a challenging task. It gets even more complicated if data is accessed concurrently. This blog post describes several challenges related to data visibility, using transactions in multi-threaded environments, and memory leaks.

### Lock-free programming on pmem

A fundamental issue (if [eADR](https://pmem.io/glossary/#eadr) is not used) is data visibility. When a thread issues a temporal (e.g., MOV) store instruction, the modification is visible to other threads before it is persistent (data can still be in cache). Consider the following scenario with one thread writing to a variable using atomic operations and the second one reading from it:

```c++
// thread 1
// initial value of pmem->a is 0
atomic_store(&pmem->a, 1);                  // visible = 1, persistent = ?
pmem_persist(&pmem->a, sizeof(pmem->a));    // visible = 1, persistent = 1
```

```c++
// thread 2
// initial value of pmem->b is 0
if (atomic_load(&pmem->a) == 1) {
    pmem->b = 1;                                // visible = 1, persistent = ?
    pmem_persist(&pmem->b, sizeof(pmem->b));    // visible = 1, persistent = 1
}
```

Let's analyze the values of pmem->a and pmem->b from a second thread perspective. If a crash happened during the application execution, the possible values on restart are:
* (`pmem->a = 0`, `pmem->b = 0`) – e.g., when the crash happened before the first
thread started
* (`pmem->a = 1`, `pmem->b = 1`) – e.g., when the crash happened after both threads
completed
* (`pmem->a = 1`, `pmem->b = 0`) – e.g., when the crash happened after the first
thread completed but before the second one started

But what happens if there was a crash just after the first thread set the value, but before it called `pmem_persist`? The second thread could have observed the new value (which is not yet persistent) and set the `pmem->b` variable to `1`. On restart, it's possible that `pmem->a` is equal to `0`, and `pmem->b` is equal to `1`. All four combinations of values are possible on restart. The fact that the second thread might take some actions based on a non-persistent state is problematic as it causes data inconsistency. One possible way of preventing this situation is by persisting all values just after reading them (but before any other action is taken based on the
value). This guarantees that the value was persistent at some point in time. The second thread from the earlier example could look like presented below:

```c++
// thread 2
// initial value of pmem->b is 0
if (atomic_load(&pmem->a) == 1) {
    pmem_persist(&pmem->a, sizeof(pmem->a));    // visible = 1, persistent = 1
    pmem->b = 1;                                // visible = 1, persistent = ?
    pmem_persist(&pmem->b, sizeof(pmem->b));    // visible = 1, persistent = 1
}
```

This solution makes (`pmem->a = 0`, `pmem->b = 1`) case impossible at the cost of additional persist on each read. To optimize this approach, we can add a dirty bit inside the value, which indicates whether the value was explicitly persisted or not. When modifying the value, the dirty flag is set to true. All readers, first check this flag and proceed as follows:
* If a flag is set: a reader uses compare-and-swap to clear the bit and then persists
the value.
* If a flag is not set: the value is for sure persistent, and the reader can proceed.

You can see an example of such implementation [here](
https://github.com/pmem/pmdk/blob/master/src/examples/libpmem2/ringbuf/ringbuf.c)

### Shared state consistency

One other problem which arises in concurrent applications is keeping shared state consistent. For example, to guarantee that calculating the number of elements in a data structure has O(1) time complexity, a size variable is usually used. In a case where only a single thread modifies the variable, one can safely use pmemobj transaction to ensure the size consistency like this:

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

Just as a reminder (more details can be found [here](https://pmem.io/2016/01/12/cpp-02.html)) when a variable of type `pmem::obj::p` (or other persistent-aware structure like persistent_ptr or self_relative_ptr) is modified, its old value is saved in an undo log. In case the enclosing transaction aborts or there is a power failure, the value is rolled back. If the transaction succeeds, all modified variables of persistent-aware types are persisted.

In a concurrent case, when multiple threads can insert new elements and update the size, this becomes more problematic. A shared variable cannot be simply modified in a pmemobj (undo log based) transaction without taking a global lock. To see why consider the following example:

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

Let's assume that there are two threads calling insert() concurrently, and they start incrementing size simultaneously. They will both store the same initial `size` value in theirs undo logs. If the transaction in the first thread aborts, it will abort the `size` value to the initial state. It will completely ignore the changes made by the second transaction.

One other problem is that readers might suffer from the 'dirty reads' phenomenon. If a reader reads size variable while another thread modifies it inside a transaction, it reads uncommitted data. If a transaction aborts or a power failure happens, the size variable can be rolled back, and the reader's view of the data might be incorrect. In addition to that, the rollback itself is not thread-safe.

One possible solution is to make the global size volatile. The volatile variable can be modified using atomic instructions (after the transaction completes) while providing the number of elements in O(1) time. To restore the size on application restart, we can introduce a per-thread size variable that can be safely modified in a transaction. Per-thread sizes can be summed up to provide the actual size. Libpmemobj-cpp implements a helper data structure (which as of 2021-07-28 is not yet released as a public API) called [enumerable_thread_specific](https://pmem.io/libpmemobj-cpp/master/doxygen/classpmem_1_1detail_1_1enumerable__thread__specific.html). It provides API to request memory which will be private to a thread and iteration API. Enumerable thread-specific is used in concurrent_map and concurrent_hash_map to solve the shared state problem.

**Publishing allocations**

In the above example, we ignored allocating and publishing new nodes - let's look at this now. The example below shows an attempt at implementing the push_back method in a concurrent, lock-free list. For simplicity, this example will assume that there can be multiple concurrent operations on the list, but only one of them can be `push_back` (it will be a single-writer, multi-reader list).

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
        pmem::obj::transaction::run(pop, [&]{
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

The implementation uses self_relative_ptr as it's currently the only pmem::obj pointer type that is supported by std::atomic.

A careful reader will notice that the implementation will not work correctly. Here, we encounter the same problem with dirty reads which was described in 'Shared state consistency'.

To solve this problem, one can try to move atomic store outside of the transaction:

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

To make sure there are no visibility problems, iterate method would need to be changed to use persist on read, as described in 'Lock-free programming on pmem':

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

However, there is still one other issue - a possible memory leak. If a crash happens during the `push_back` method just after the new node is allocated, but before it is linked to the list, it will not be reachable after a restart. `new_node` pointer is kept on the stack, which means it will not survive a restart.

A solution to this problem is, once again, enumerable_thread_specific. With the help of this data structure, `push_back` can be rewritten like this:

```c++
void push_back(Value v) {
    // ptls is a variable of type enumerable_thread_specific
    self_relative_ptr<Node> &tls_ref = ptls->local();

    pmem::obj::transaction::run(pop, [&]{
        tls_ref = make_persistent<Node>(v);
    });

    find_last()->next.store(tls_ref);
    pop.persist(&find_last()->next, sizeof(tls_ref));
}
```

Because `tls_ref` points to a region on persistent memory, even if the new node is not linked to the list, it is always reachable on restart. There are no problems with dirty reads or visibility as `tls_ref` is private to a thread.


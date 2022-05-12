---
# Blog post title
title: 'An introduction to pmemcheck (part 2) - transactions'

# Blog post creation date
date: 2015-07-20T19:55:17-07:00

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
blogs: ['pmemcheck']

tags: []

# Redirects from old URL
aliases: ['/2015/07/20/pmemcheck-transactions.html']

# Blog post type
type: 'post'
---

In my previous blog post I described the key features of the new persistent memory analysis tool we created - pmemcheck. You should now be aware of the main pitfalls of persistent memory programming and of ways pmemcheck informs you about possible misuses of PMEM. We should now dive into a more general approach of using persistent memory in a failsafe manner - transactions. This shouldn't be an alien concept for anybody who had anything to do with databases. The idea of transactions in persistent memory is very similar. You enclose a set of operations, which are to be performed as a whole, inside a transaction. The transaction should ensure that on transaction commit you get a durable and consistent state. By durable I mean that all changes will be made persistent and by consistent I mean you will get either the state from before the transaction or after all of the modifications were made. I mentioned databases, because the concept is similar and everybody should be familiar with it, but you have to remember that this is on a slightly different level of abstraction. This is raw access of memory and not database inserts. This of course depends on the implementation of transactions, but it might prove very helpful not to think, that all persistent memory transactions will have ACID properties. Or at least they won't be on a level you expect them to be.

As always examples will be the best way to show what I mean. I will be showing all of my examples with the use of [PMDK][806fc533] and the transactions available in [libpmemobj][40b153e7]. I will not cover how they work, because that has already been done by @pbalcer in this great blog [post][57acd504]. This is roughly how a transaction in pmemobj looks like:

{{< highlight C "linenos=table" >}}
TX*BEGIN(pop) {
/* modify pmem inside transaction \_/
} TX_END
{{< /highlight >}}

This is the simplest form you can imagine. With transaction in pmemobj you get _Atomicity_ and _Durability_ for free. As for _Isolation_, you have to take care of it yourself. Pmemobj is more of a filesystem than a database, synchronization of access is your job as the user of pmemobj. However pmemobj comes with a convenience macro for beginning the transaction, which proves very useful in multi-threaded environments.

{{< highlight C "linenos=table" >}}
TX*BEGIN_LOCK(pop,
TX_LOCK_MUTEX, mutexp,
TX_LOCK_RWLOCK, rwlockp,
TX_LOCK_NONE) {
/* modify pmem inside transaction \_/
} TX_END
{{< /highlight >}}

The locks specified in `TX_BEGIN_LOCK` are held throughout the whole transaction. This is pretty much all you are going to get from the library. There is however still the issue of _Consistency_. libpmemobj ensures that all pool metadata will be consistent, but the consistency of your data depends on the usage. The most frequent and probable cause of errors pertaining _Consistency_ is modifying objects that are not part of the transaction. I'll give you an example which has no real use, but will show you what I mean.

{{< highlight C "linenos=table" >}}
#include <fcntl.h>
#include <libpmemobj.h>

struct my_root {
int value;
int is_odd;
};

POBJ_LAYOUT_BEGIN(example);
POBJ_LAYOUT_ROOT(example, struct my_root);
POBJ_LAYOUT_END(example);
{{< /highlight >}}

The aforementioned piece of code is common for all of the examples which are based on libpmemobj, hence I will not repeat it anymore.

{{< highlight C "linenos=table,hl_lines=11 12 14" >}}
int main(int argc, char\*_ argv)
{
/_ create a pool within an existing file */
PMEMobjpool *pop = pmemobj_create("example/path",
POBJ_LAYOUT_NAME(example),
0, S_IWUSR | S_IRUSR);

    TX_BEGIN(pop) {
    	TOID(struct my_root) root = POBJ_ROOT(pop, struct my_root);
    	/* track the value field */
    	TX_ADD_FIELD(root, value);
    	D_RW(root)->value = 4;
    	/* modify an untracked value */
    	D_RW(root)->is_odd = D_RO(root)->value % 2;
    } TX_END

}
{{< /highlight >}}

This might seem like a lot of code, but bear with me, we only need to concentrate on a few lines. The rest is pmemobj pool creation and typesafety specific. If you want to know more about them please read our other [blog posts][c749cb90]. What we will be analyzing are lines 11 - 14. We explicitly add one field of our root object to the transaction. This means that the value of `root->value` is saved and will be reverted should the transaction abort for some reason. We then assign a value to it and make a decision based on it in line 14. However there's something terribly wrong here. We modified `root->is_odd`, which is not tracked by the transaction. Not to mention that, since it's not added to transaction, it won't be flushed to persistence at `TX_END`. Should the application crash after line 14 we are left with a number of possible states, the worst one being `root->is_odd` got persisted and the transaction got rolled back.

Finally after a long introduction we get back to pmemcheck. As you can read in pmemcheck's [documentation][d324cfe0], it has built-in support for transactions. The most basic function you can imagine, would be tracking stores made outside of transactions. That is exactly what it does:

    Number of stores made outside of transactions: 1
    Stores made outside of transactions:
    [0]    at 0x400A51: main (example.c:14)
           Address: 0x100001a2404	size: 4

It tells you that during a transaction, you modified a region of persistent memory, which wasn't tracked by the active transaction. This is rather simple and obvious - do not modify something you know won't be rolled-back on transaction abort. Remembering to add all necessary objects in a transaction as short as in the given example is easy. Now imagine a longer transaction, where more objects get modified and you forgot to add one to the transaction undo log. Debugging this memory corruption situation would be a nightmare. Thanks to pmemcheck, you get the full stacktrace, where the store has been made. The only things left are: analyze and fix the issue - things couldn't get much simpler.

How does pmemcheck do this? Well it is actually quite straightforward. Conceptually it keeps track of all transactions the given thread contributes to, and each transaction has a set of regions it keeps track of. Since we already analyze each store made to persistent memory, it would be a waste not to to check them against some kind of transactions. Once again, pmemcheck is oblivious to the type and implementation of transactions. Just like before, it uses macros to gain knowledge of the active transactions and the regions tracked by them. In case of libpmemobj, things are rather simple, because transactions are flattened. This means that each thread can be in exactly one active transaction in each given moment. Moreover libpmemobj **does not support** multiple threads cooperating within single transactions. If you want to know more about challenges of multi-threaded transactions please read our [blog post][aa87ca41] about this topic. Pmemcheck however does not limit itself to this transaction model, it is legal for threads to contribute to other threads' transactions. For example:

{{< highlight C "linenos=table" >}}
#include "common.h"
#include <stdint.h>
#include <pthread.h>

#define FILE*SIZE (16 * 1024 \_ 1024)

/_ Thread worker arguments. _/
struct thread*ops {
/* The txid to contribute to and close. \_/
int txid;

    /* What to modify. */
    int32_t *i32p;

};

/\*

- Perform tx in a thread.
  _/
  static void _
  make_tx(void *arg)
  {
  struct thread_ops *args = arg;

      VALGRIND_PMC_ADD_THREAD_TX_N(args->txid);

      VALGRIND_PMC_ADD_TO_TX_N(args->txid, args->i32p, sizeof (*(args->i32p)));
      /* dirty stores */
      *(args->i32p) = 3;

      VALGRIND_PMC_END_TX_N(args->txid);
      return NULL;

  }

int main ( void )
{
/_ make, map and register a temporary file _/
void \*base = make_map_tmpfile(FILE_SIZE);

    struct thread_ops arg;

    arg.txid = 1234;
    arg.i32p = (int32_t *)(base);

    VALGRIND_PMC_START_TX_N(arg.txid);

    pthread_t t1;
    pthread_create(&t1, NULL, make_tx, &arg);
    pthread_join(t1, NULL);

    return 0;

}
{{< /highlight >}}

I'm sorry for the somewhat longish example, but we will learn a couple of things thanks to it. This is in fact a test taken from pmemcheck - [trans_mt_cross.c][051ba546]. First of all I need to clarify what some of the macros are. `VALGRIND_PMC_START_TX_N` starts a transaction with the given id, `VALGRIND_PMC_END_TX_N` ends a transaction with the given id. If a thread creates a transaction, the transaction is automatically added to this thread's active transaction list. If a different thread wants to contribute to this transaction, it has to use the `VALGRIND_PMC_ADD_THREAD_TX_N` macro. To add a region to the list of tracked regions of a transaction you have to use the `VALGRIND_PMC_ADD_TO_TX_N` macro. The are other flavors of these macros, as well as other macros which are described in [pmemcheck's documentation][d324cfe0] and I won't explain them here. What is important in this example, is that it is absolutely legal for a thread, that did not start a transaction, to modify objects of the given transaction. What's more, it is also legal for the other thread to end the transaction - however weird that may seem.

Now imagine you have **two separate transactions** in pmemobj, which somehow failed to synchronize properly and **added the same object** to their undo logs. I think that after reading so many blog entries, you can see where this is going. One transaction commits and makes some decisions based on the value of the object, while the other one fails and rolls the object back. This is pure **evil**, because frankly, you don't even know what object you're going to end up with. The second (the aborting) transaction could record a mix of the object modified by the first transaction. To some extent pmemcheck also helps you with this issue (although frankly, Valgrind's [DRD][a7a3d90c] and [Helgrind][717a4630] are better suited for this, as this is a multithreading issue). Imagine the given code:

{{< highlight C "linenos=table" >}}
/_ make_tx -- start a transaction and change root->value _/
static void *
make_tx(void *args)
{
PMEMobjpool \*pop = args;

    TX_BEGIN(pop) {
    	TOID(struct my_root) root = POBJ_ROOT(pop, struct my_root);
    	/* track the value field */
    	TX_ADD_FIELD(root, value);
    	D_RW(root)->value = rand();
    } TX_END

}

int main(int argc, char\*_ argv)
{
/_ create a pool within an existing file */
PMEMobjpool *pop = pmemobj*create("testfile1",
POBJ_LAYOUT_NAME(example),
1024 * 1024 \_ 1024, S_IWUSR | S_IRUSR);

    TX_BEGIN(pop) {
    	TOID(struct my_root) root = POBJ_ROOT(pop, struct my_root);
    	/* track the value field */
    	TX_ADD_FIELD(root, value);
    	D_RW(root)->value = 4;
    	/* create new tx in a separate thread */
    	pthread_t thread;
    	pthread_create(&thread, NULL, make_tx, pop);
    	pthread_join(thread, NULL);
    } TX_END

}
{{< /highlight >}}

You can see that we started a new thread in line 29 and the new thread started a new transaction. In libpmemobj, transactions are flattened per-thread, so these are in fact to separate transactions. The library will allow you to add `root->value` to both transactions, but as I mentioned previously, the result in case of failure is undefined. If you run this under pmemcheck, the result would be:

    Number of overlapping regions registered in different transactions: 1
    Overlapping regions:
    [0]    at 0x4C3C8DC: constructor_tx_add_range (tx.c:196)
        by 0x4C2F5E1: pmalloc_construct (pmalloc.c:186)
        by 0x4C3302A: list_insert_new (list.c:764)
        by 0x4C3FD11: pmemobj_tx_add_common (tx.c:1221)
        by 0x4C40037: pmemobj_tx_add_range (tx.c:1295)
        by 0x400A9E: make_tx (example.c:10)
        by 0x4E54181: start_thread (pthread_create.c:312)
        by 0x516447C: clone (clone.S:111)
     	Address: 0x100001a2400	size: 4	tx_id: 2
        First registered here:
    [0]'   at 0x4C3C8DC: constructor_tx_add_range (tx.c:196)
        by 0x4C2F5E1: pmalloc_construct (pmalloc.c:186)
        by 0x4C3302A: list_insert_new (list.c:764)
        by 0x4C3FD11: pmemobj_tx_add_common (tx.c:1221)
        by 0x4C40037: pmemobj_tx_add_range (tx.c:1295)
        by 0x400C08: main (example.c:25)
     	Address: 0x100001a2400	size: 4	tx_id: 1

After you cut out all the library details of adding the object to the undo log, you're left with:

    Number of overlapping regions registered in different transactions: 1
    Overlapping regions:
    [0]   ...
        by 0x400A9E: make_tx (example.c:10)
        by 0x4E54181: start_thread (pthread_create.c:312)
        by 0x516447C: clone (clone.S:111)
         Address: 0x100001a2400	size: 4	tx_id: 2
        First registered here:
    [0]'   ...
        by 0x400C08: main (example.c:25)
         Address: 0x100001a2400	size: 4	tx_id: 1

Which is exactly what we were looking for. Please note that the mechanism for finding these issues is not as sophisticated in pmemcheck as in [DRD][a7a3d90c] or [Helgrind][717a4630] and it might not report as many issues as they would.

The last type of errors pmemcheck reports in context of transactions are leftover running transactions. Imagine you have a transaction, which for some reason didn't end. Be it a simple programming error (no explicit transaction end called) or some sophisticated multi-threading issue, if your application ends with any running transaction, pmemcheck will inform you about it, as in this example.

{{< highlight C "linenos=table" >}}
int main(int argc, char\*_ argv)
{
/_ create a pool within an existing file */
PMEMobjpool *pop = pmemobj*create("testfile1",
POBJ_LAYOUT_NAME(example),
1024 * 1024 \_ 1024, S_IWUSR | S_IRUSR);

    TX_BEGIN(pop) {
    	TOID(struct my_root) root = POBJ_ROOT(pop, struct my_root);
    	/* track the value field */
    	TX_ADD_FIELD(root, value);
    	D_RW(root)->value = 4;
    	/* return without ending the transaction */
    	return 0;
    } TX_END

}
{{< /highlight >}}

Among other issues, pmemcheck will report this:

    Number of active transactions: 1
    [0]    at 0x4C3EED0: pmemobj_tx_begin (tx.c:943)
       by 0x400870: main (example.c:8)
           tx_id: 1	 nesting: 1

This means that on line 8 we started a transaction that didn't end. This means that something that should never happen occurred and should be thoroughly investigated.

This concludes the pmemcheck's built-in transaction support. If you want to know more about pmemcheck please take a look at the provided [documentation][d324cfe0]. I hope this tool will prove useful in your endeavor into persistent memory programming.

[a7a3d90c]: https://valgrind.org/info/tools.html#drd 'DRD'
[717a4630]: https://valgrind.org/info/tools.html#helgrind 'Helgrind'
[806fc533]: /pmdk/ 'Persistent Memory Development Kit'
[40b153e7]: /pmdk/libpmemobj/ 'pmemobj'
[57acd504]: /blog/2015/06/an-introduction-to-pmemobj-part-2-transactions/ 'pmemobj transactions'
[c749cb90]: /blog/ 'Blog posts'
[d324cfe0]: https://github.com/pmem/valgrind/blob/pmem-3.15/pmemcheck/docs/pmc-manual.xml 'Pmemcheck documentation'
[aa87ca41]: /blog/2015/09/challenges-of-multi-threaded-transactions/ 'Challenges of multi-threaded transactions'
[051ba546]: https://github.com/pmem/valgrind/blob/pmem/pmemcheck/tests/trans_mt_cross.c 'Pmemcheck test'

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

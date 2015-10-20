---
title: Evaluation of a better object container
author: pbalcer
layout: post
---

During performance evaluation of our library, I asked myself a following
question:

Which data structure has computational complexity of "insert at end" and "remove
given element" operations no worse than a doubly-linked list, but with a smaller
constant?

The point of that mental exercise was to come up with a persistent data
structure that could replace doubly-linked list in object stores
(right now a linked lists of every single user-allocated object) and undo logs.

One might ask why replace something that works fine, and has O(1) complexity of
the operations we use. But once we count the number of memory stores necessary
to remove (or insert) an entry from a doubly-linked list, the reason becomes
apparent. And keep in mind that this data structure must be consistent across
power failures. Our circular doubly-linked list implementation currently has
~2000 lines of code, while I realize that this might not be the best measure of
complexity, but for a list? :) Things get complicated fast once you start
thinking about persistence.

Back on topic. First thought I had was an array with a simple counter to get
next index at which to store the element. That index would be then embedded
into the element (like how next/prev pointers are stored in linked-lists).
Using an array also has the benefit of being much more CPU cache-friendly, which
is especially important when dealing with hardware that has slower reads (compared
to DRAM).

But there's a one major drawback: a hard limit for number of objects. While our
low-level persistent allocator does support reallocations, the cost of doing
it in runtime would be too big (not to mention wasteful).

Next thought was to replace the array with a radix tree (with the same deal for
indexes/keys). A shallow radix tree would probably work fine, but it's pretty
complex for something so simple.

And then I randomly stumbled on
[this](http://www.stroustrup.com/lock-free-vector.pdf). The gist of this paper
is a vector that uses an array of arrays, where the sizes of each consecutive
array form a geometric sequence with the common ratio of 2.

Both insert and remove operations to this vector have complexity of O(1) and
a constant of 2 memory stores (update array element and set the embedded entry).

When doing all of this I also had an ulterior motive: I want our undo log and
object stores containers to be lock-free. This would enable better scaling of
atomic allocations and `pmemobj_tx_commit`. It would also play nicely
with the idea of multi-threaded transactions.

But if you've read my previous [post]({% post_url 2015-09-16-mt-tx %}) you know
that there are no persistent and concurrent atomic operations available - which
poses a significant challenge in implementing a lockfree data structure.

In implementation of the persistent lock-free vector I imposed one limitation:
only `push_back` operation is supported. No inserts at the beginning
or in the middle. This is all we need for the undo log and the object store.
Here's the important piece of code:

{% highlight C linenos %}
n = __sync_fetch_and_add(&v->next, 1); /* v->next is volatile! */
uint64_t tab;
uint64_t tab_idx;
vector_tab_from_idx(v, n, &tab, &tab_idx);

while (v->entries[tab] == 0) {
	if (tab_idx == 0)
		pmalloc(&v->entries[tab], ...);
	sched_yield();
}
{% endhighlight %}

Initially the `v->entries[]` array is zeroed. An insert operation grabs a next
index `n` in a thread-safe manner. The `tab` variable determines in which
table the element will end-up in and the `tab_idx` indicates the position in that
table. The thread which gets the `tab_idx == 0` is the one which has
to allocate the new table. All other threads that have `tab_idx > 0` and found
`v->entries[tab] == 0` (the destination table not allocated) have to busy-wait
for it to become allocated. Having the guarantee that elements are always
appended at the end, we can be sure that those threads won't wait endlessly.


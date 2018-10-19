---
title: Pmreorder basics
author: wlemkows
layout: post
identifier: pmreorder-basics
---


## Introduction

It's good practice to run persistent memory application under pmemcheck -
a tool which is described [here][pmemcheck1Link] and [here][pmemcheck2Link].

In this post, we are going to learn about another tool for persistence
correctness checking. As you might already know if you've read posts linked
above, pmemcheck verifies if all stores are made persistent in a proper manner.
Our new tool, pmreorder, extends this functionality. It traverses the
sequences of stores between flush-fence barriers made by the application,
and then replays these memory operations many times in different combinations,
to simulate the various possible ways the stores to the NVDIMM could be ordered
by the system.

Each possible combination of stores is verified by the user-defined consistency
checker and any errors are logged. Given an exhaustive consistency
checking function, this process will uncover potential application bugs
that otherwise could have been encountered only under specific system failures.


## Example

Let's start with an example of a simple persistent linked list
implemented using libpmem and an atomic algorithm.

You can find a full implementation and description
of this example in [pmdk repository][pmreorderExampleLink].

All nodes are allocated statically from a list_node array
which is located in a list_root structure. The same structure
contains head field - id of the root node.
The list insert function of this example looks like this:

{% highlight C linenos %}
static void
list_insert(struct list_root *root, node_id node, int value)
{
	struct list_node *new = NODE_PTR(root, node);

	new->next = root->head;
	pmem_persist(&new->next, sizeof(node));

	root->head = node;
	pmem_persist(&root->head, sizeof(root->head));

	new->value = value;
	pmem_persist(&new->value, sizeof(value));
}
{% endhighlight %}

First, the next value in the node is set and persisted.
Then, the node is linked to the beginning of the list,
its value is set and both are persisted separately.

When you run this code under pmemcheck you will get the following result:

{% highlight sh %}
==22161== Number of stores not made persistent: 0
==22161== ERROR SUMMARY: 0 errors
{% endhighlight %}

It means that all stores are persisted properly, as we expected.

Now it is time to check the list under pmreorder tool.
To do that you need to consider when your list is consistent
and on that basis write consistency checker function.


## Checker

Consistency checker is just a binary or a function that defines
conditions necessary to fulfill your consistency assumptions.
It should return 0 if the state is consistent and 1 if it isn't.

In case of our list with three elements:

{% highlight C linenos %}
 // params: root, node_id, value
list_insert(r, 5, 55);
list_insert(r, 3, 33);
list_insert(r, 6, 66);
{% endhighlight %}

We would like to be sure that if nodes (5, 3, 6) are present
in the list, then their values (55, 33, 66) are set properly.

If the node is present in the list, but its value field isn't
properly assigned, then the scenario should be recognized as
inconsistent and function should return 1.

For example:
{% highlight C linenos %}
static int
check_consistency(struct list_root *root)
{
	struct list_node *node = NODE_PTR(root, root->head);

	/*
	 * If node is linked to the list then its
	 * value should be set properly.
	 */
	if (node == NULL)
		return 0;

	do {
		if (node->value == 0)
			return 1;
		node = NODE_PTR(root, node->next);
	} while (node != NULL);

return 0;
}
{% endhighlight %}

That's it. List and consistency checker are ready.
The next step is to generate a log of memory operations
made by the application. We call that a store log.


## Store log

To get the list of all memory operations we are going to use pmemcheck
logging functionality. To turn on logging use `log-stores` parameter:

{% highlight sh %}
$ valgrind --tool=pmemcheck --log-stores=yes --print-summary=yes --log-file=store_log.log pmreorder_list
{% endhighlight %}

The above command outputs a text file with a list of stores, flushes,
fences, user-defined markers and possibly some other data that can be
consumed by pmreorder tool.

Let's look at an excerpt from our example's store log.
Make sure to read the embedded comments.

{% highlight sh %}
STORE;0x5600060;0x0;0x8|	# set new->next for node_id 5
FLUSH;0x5600040;0x40|		# persist next for node_id 5
FENCE|
STORE;0x5600000;0x5;0x8|	# set root->head for node_id 5
FLUSH;0x5600000;0x40|		# persist head for node_id 5
FENCE|
STORE;0x5600058;0x37;0x4|	# set new->value for node_id 5
FLUSH;0x5600040;0x40|		# persist value for node_id 5
FENCE|
STORE;0x5600040;0x5;0x8|	# set new->next for node_id 3
FLUSH;0x5600040;0x40|		# persist  next for nod_id 3
FENCE|
STORE;0x5600000;0x3;0x8|	# set root->head for node_id 3
FLUSH;0x5600000;0x40|		# persist head for node_id 3
FENCE|
STORE;0x5600038;0x21;0x4|	# set new->value for node_id 3
FLUSH;0x5600000;0x40|		# persist value for node_id 3
FENCE|
STORE;0x5600070;0x3;0x8|	# set new->next for node_id 6
FLUSH;0x5600040;0x40|		# persist next for node_id 6
FENCE|
STORE;0x5600000;0x6;0x8|	# set root->head for node_id 6
FLUSH;0x5600000;0x40|		# persist head for node_id 6
FENCE|
STORE;0x5600068;0x42;0x4|	# set new->value for node_id 6
FLUSH;0x5600040;0x40|		# persist value for node_id 6
FENCE|
STOP
==32611== Number of stores not made persistent: 0
==32611== ERROR SUMMARY: 0 errors
{% endhighlight %}

It often happens that store log contains multiple stores and operations
made on the pool that are irrelevant for your tests.
There is a mechanism called markers that enables ignoring selected set of stores
during consistency check. For more information about this and other features
take a look at the [pmreorder man page][pmreorderManLink].

As you probably guessed by now, store log generation was the last step before
pmreorder execution, so let's move on.


## Pmreorder

Let's start with a brief description of how the pmreorder works.

Pmreorder parses the store log provided by the user and generates sequences
of stores between barriers. These sequences are written to a pool file.
For each generated sequence, the tool runs the consistency checker function
and reports scenarios for which the function returned 1.

This procedure is repeated multiple times with different
order of stores. After each check pmreorder reverts already
tested sequence and takes the next combination to check.


### How many combinations are tested?

It depends on the engine used. There are a few available engine types.
For example, full reorder engine generates all possible combinations
without repetition, a random engine generates a specified number of randomly
generated combinations and no reorder engine passes-through the stores without
reordering.

In this example, I am going to use the reverse accumulative engine which
checks correctness on a reversed growing subset of the original sequence.

{% highlight sh %}
Example:
        input: (a, b, c)
        output:
               ()
               (c)
               (c, b)
               (c, b, a)
{% endhighlight %}

For more examples and information about engines look at the [pmreorder man page][pmreorderManLink].

Note that pmreorder requires python3 to be present in the system.

Using that information we can run pmreorder with the following command:

{% highlight sh %}
$ python3 pmreorder.py
	-l store_log.log  			# file generated by pmemcheck

	-o output_file.log			# output from pmreorder

	-x pmem_memset_persist=NoReorderNoCheck	# do not check stores from pmem_memset_persist
						# available only if PMREORDER_EMIT_LOG=1
						# environment variable is set

	-r ReorderReverseAccumulative		# default engine

	-p "pmreorder_list c"			# checker binary with parameters
						# (c - checker mode)
{% endhighlight %}

Take a look at the result of this command:

{% highlight sh %}
$ cat output_file.log

WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:
Store [0]:
     by  No trace available

WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:

[...]
{% endhighlight %}

In this case, output_file.log is not empty,
which means that some issues were detected.

We can see a few scenarios that cause inconsistency,
but there is no trace available.

To get more information about the issue we should
add additional flags to pmemcheck during store log
generation:

{% highlight sh %}
--log-stores-stacktraces=yes
--log-stores-stacktraces-depth=2
{% endhighlight %}

Afterwards, output log is more comprehensive:

{% highlight sh %}
WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:
Store [0]:
    by	0x400CDB: list_insert_inconsistent (pmreorder_list.c:144)
    by	0x400E84: main (pmreorder_list.c:185)

WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:

WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:
Store [0]:
    by	0x400CDB: list_insert_inconsistent (pmreorder_list.c:144)
    by	0x400E9A: main (pmreorder_list.c:186)

WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:

WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:
Store [0]:
    by	0x400CDB: list_insert_inconsistent (pmreorder_list.c:144)
    by	0x400EB0: main (pmreorder_list.c:187)

WARNING:pmreorder:File /tmp/test_ex_pmreorder1/testfile inconsistent
WARNING:pmreorder:Call trace:
{% endhighlight %}

Now we are able to check specific line in the code where inconsistency occurred.
Empty call trace means that even if crash occurs before any of these stores,
state will be also inconsistent.

Where we made a mistake and how to fix it?
Look once again at list insert and checker code.

If the node is linked to the list, then we are checking if the value
of the node is set. But in our case, persist of the list's head is located
before node's value persist. So it can happen that the node is in the list
but its value is not set yet, which means that an issue occurs because of
the order of operations.

To fix the problem, we have to change their order.
Also worth noting that we do not need to persist the value
and next field separately. It can be done by one pmem_persist:

{% highlight C linenos %}
static void
list_insert_consistent(struct list_root *root, node_id node, int value)
{
	struct list_node *new = NODE_PTR(root, node);

	new->value = value;
	new->next = root->head;
	pmem_persist(new, sizeof(new)); /* persist the node */

	root->head = node;
	pmem_persist(&root->head, sizeof(root->head)); /* add to the list */
}
{% endhighlight %}

This insert is constructed properly and consistency check passes.

I hope that now you have enough knowledge to check your program
with the use of pmreorder tool.
Good luck!

[pmemcheck1Link]: http://pmem.io/2015/07/17/pmemcheck-basic.html
[pmemcheck2Link]: http://pmem.io/2015/07/20/pmemcheck-transactions.html
[pmreorderExampleLink]: https://github.com/pmem/pmdk/tree/master/src/examples/pmreorder
[pmreorderManLink]: http://pmem.io/pmdk/manpages/linux/master/pmreorder/pmreorder.1.html

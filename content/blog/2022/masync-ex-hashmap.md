---
# Blog post title
title: "Basic asynchronous hashmap with Miniasync library"

# Blog post creation date
date: 2022-06-03T10:00:00+00:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: ""

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ""

# Blog post thumbnail
# eg: image: "/images/posts/my_blog_thumbnail.png"
image: ""

# Blog post author
author: "Krzysztof Święcicki"

# Categories to which this blog post belongs
blogs: ['PMDK']
# Blog tags
tags: ["miniasync", "concurrency", "async", "asynchronous"]

# Blog post type
type: "post"
---

Miniasync library provides a framework for the composition and execution of asynchronous
tasks in C. To accommodate different user-defined tasks and various types of data that they
take in, libminiasync makes use of macros.

Using libminiasync for the first time can be challenging. There are multiple examples
on the [miniasync repo][masync-examples] to make it easier. One of them is a
[hashmap][masync-hashmap] example.

## Hashmap example overview

The hashmap example on the Miniasync repository presents a hashmap with a fixed size that
allocates memory upon key-value pair insertion. This particular implementation uses
linear probing for hashmap entry lookup. Linear probing means that whenever we encounter a
collision when inserting a key-value pair, we will move on to the next hashmap entry until
we find an unoccupied entry. For the hash function, we use the Austin Appleby [MurmurHash3][murmurmash3-wiki]
64-bit finalizer.

Each operation associated with hashmap entry insertion, deletion and search is implemented
using Miniasync [future][masync-future-manpage] API.

## Creating a future

Future is an abstraction of an asynchronous task. Each future has to be defined using
`FUTURE(_name, _data_type, _output_type)` macro. It takes future name `_name`,
input data type `_data_type` and output data type `_output_type`. For more information
about the futures, see [future][masync-future-manpage] manpage on Miniasync github
repository.

The `lookup` future definition from the hashmap example:

```C
struct hashmap_lookup_data {
	struct hashmap *hm;
	uint64_t key;
	enum hashmap_entry_state state;
};
struct hashmap_lookup_output {
	struct hashmap_entry *hme;
};
FUTURE(hashmap_lookup_fut, struct hashmap_lookup_data,
		struct hashmap_lookup_output);
```

The lookup data `struct hashmap_lookup_data` contains a pointer to hashmap `hm`, `key`, and `state` of the
searched entry. `hm` should point to an actual hashmap instance, `key` is an entry key used in the hash function,
`state` indicates whether the lookup function should search for an existing or unoccupied hashmap entry.

The output data `struct hashmap_lookup_output` contains a pointer to the hashmap entry `hme` that points
to the found hashmap entry.

`FUTURE(...)` macro defines a `struct hashmap_lookup_fut` future struct with `struct hashmap_lookup_data`
as input data and `struct hashmap_lookup_output` as output.

## Future task function

```C
typedef enum future_state (*future_task_fn)(struct future_context *context,
			struct future_notifier *notifier);
```

The future task function contains the actual logic of the future. Every such task implementation
must conform to the definition above. Future context provides access to the input and output data
of a particular future.

>Notifier feature is not used in the hashmap example, hence it will not be discussed. For more information
about the notifiers take a look [here][masync-future-manpage].

The return value of future task function indicates the state of the future task.

```C
enum future_state {
	FUTURE_STATE_IDLE,
	FUTURE_STATE_COMPLETE,
	FUTURE_STATE_RUNNING,
};
```
`FUTURE_STATE_IDLE` indicates that the future task did not start yet, `FUTURE_STATE_COMPLETE`
means that it has already completed, and `FUTURE_STATE_RUNNING` signalizes that it's still running.

The `lookup` future task function:

```C
static enum future_state
hashmap_lookup_impl(struct future_context *ctx,
		struct future_notifier *notifier)
{
	struct hashmap_lookup_data *data =
			future_context_get_data(ctx);
	struct hashmap_lookup_output *output =
			future_context_get_output(ctx);
	struct hashmap *hm = data->hm;
	uint64_t key = data->key;
	enum hashmap_entry_state state = data->state;
	struct hashmap_entry *hme = NULL;
	if (key == 0) {
		printf("invalid key %" PRIu64 "\n", key);
		goto set_output;
	} else if (state == HASHMAP_ENTRY_STATE_UNOCCUPIED &&
			hm->capacity == hm->length) {
		printf("no space left for key %" PRIu64 "\n", key);
		goto set_output;
	} else if (state == HASHMAP_ENTRY_STATE_UNOCCUPIED &&
			hashmap_entry_lookup(hm, key,
					HASHMAP_ENTRY_STATE_PRESENT) != -1) {
		printf("key %" PRIu64 " already exists\n", key);
		goto set_output;
	}
	int index = hashmap_entry_lookup(hm, key, state);
	if (index == -1) {
		switch (state) {
			case HASHMAP_ENTRY_STATE_PRESENT:
			/* Entry with given key is not present in the hashmap */
				goto set_output;
			case HASHMAP_ENTRY_STATE_UNOCCUPIED:
			/*
			 * An unoccupied entry wasn't found despite hashmap not
			 * being full. Re-run the lookup future.
			 */
				return FUTURE_STATE_RUNNING;
			default:
				assert(0); /* should not be reachable */
		}
	}
	hme = &hm->entries[index];
set_output:
	output->hme = hme;
	return FUTURE_STATE_COMPLETE;
}
```

Let's break down the above code snippet.

```C
	struct hashmap_lookup_data *data =
			future_context_get_data(ctx);
	struct hashmap_lookup_output *output =
			future_context_get_output(ctx);
	struct hashmap *hm = data->hm;
	uint64_t key = data->key;
	enum hashmap_entry_state state = data->state;
```
We get the pointers to the data and output structures through the `ctx` parameter
using `future_context_get_data(...)` and `future_context_get_output(...)` functions.
Data and output types are the same types provided to the `FUTURE(...)` macro.

```C
	if (key == 0) {
		printf("invalid key %" PRIu64 "\n", key);
		goto set_output;
	} else if (state == HASHMAP_ENTRY_STATE_UNOCCUPIED &&
			hm->capacity == hm->length) {
		printf("no space left for key %" PRIu64 "\n", key);
		goto set_output;
	} else if (state == HASHMAP_ENTRY_STATE_UNOCCUPIED &&
			hashmap_entry_lookup(hm, key,
					HASHMAP_ENTRY_STATE_PRESENT) != -1) {
		printf("key %" PRIu64 " already exists\n", key);
		goto set_output;
	}
```
Then, we perform some checks on the obtained input data. In case one of the checks
failed, we set the output entry address to `NULL` and return `FUTURE_STATE_COMPLETE`.

```C
int index = hashmap_entry_lookup(hm, key, state);
```
Next, we use the obtained input data to find the index of the hashmap entry.

```C
	if (index == -1) {
		switch (state) {
			case HASHMAP_ENTRY_STATE_PRESENT:
			/* Entry with given key is not present in the hashmap */
				goto set_output;
			case HASHMAP_ENTRY_STATE_UNOCCUPIED:
			/*
			 * An unoccupied entry wasn't found despite hashmap not
			 * being full. Re-run the lookup future.
			 */
				return FUTURE_STATE_RUNNING;
			default:
				assert(0); /* should not be reachable */
		}
	}
```
If searching for the index of an existing entry failed, we proceed similarly to the
failed check case.

If we were looking for the index of an unoccupied entry and nothing was found, we return
`FUTURE_STATE_RUNNING`. `hashmap_lookup_impl` will be re-run upon the next poll,
preserving all the changes made to the data and output.

```C
	hme = &hm->entries[index];
set_output:
	output->hme = hme;
	return FUTURE_STATE_COMPLETE;
```
Lastly, when we found an entry, we set the output entry pointer to its address and
return `FUTURE_STATE_COMPLETE`.

## Initializing a future

Having defined the future input, output and task function, we can create an instance of
the `lookup` future.

```C
static struct hashmap_lookup_fut
hashmap_lookup(struct hashmap *hm, uint64_t key, enum hashmap_entry_state state)
{
	struct hashmap_lookup_fut future;
	/* Set input values */
	future.data.hm = hm;
	future.data.key = key;
	future.data.state = state;
	/* Set default output value */
	future.output.hme = NULL;
	FUTURE_INIT(&future, hashmap_lookup_impl);
	return future;
}
```

In the above code fragment, we create a `struct hashmap_lookup_fut` structure and set its input data and
default output values. After that, we initialize this future and bind it with a `hashmap_lookup_impl(...)`
task function using the `FUTURE_INIT(...)` macro. After those operations, `struct hashmap_lookup_fut` is
ready to be polled.

## Creating a chained future

Multiple futures can be composed into a single chained future. When polled, chained
future executes its future entries sequentially in the order they were defined.
We can define a future as the chained future entry using `FUTURE_CHAIN_ENTRY(_future_type, _name)`
macro. It takes two parameters, `_future_type` is the type of the future, and `_name`
is the name of the variable stored in the data structure.

```C
struct hashmap_lookup_lock_entry_data {
	FUTURE_CHAIN_ENTRY(struct hashmap_lookup_fut, lookup);
	FUTURE_CHAIN_ENTRY(struct hashmap_entry_set_state_fut, set_state);
	FUTURE_CHAIN_ENTRY_LAST(struct chain_entries_rerun_fut, entries_rerun);
	struct future_chain_entry *entriesp[2];
};
struct hashmap_lookup_lock_entry_output {
	struct hashmap_entry *hme;
};
FUTURE(hashmap_lookup_lock_entry_fut, struct hashmap_lookup_lock_entry_data,
		struct hashmap_lookup_lock_entry_output);
```
Here, `struct hashmap_lookup_lock_entry_fut` future consists of three other futures,
`struct hashmap_lookup_fut` that was discussed, `struct hashmap_entry_set_state_fut`,
and 'struct chain_entries_rerun_fut' that follow the same principles the `lookup` future does.
`struct hashmap_lookup_lock_entry_fut` is a chained future responsible for finding an appropriate
hashmap entry and locking it in the `HASHMAP_ENTRY_STATE_LOCKED` state. Hashmap entry locking ensures
that no other future will interact with this hashmap entry until we finish processing it and change its state.

If we would like to store some additional data in the `struct hashmap_lookup_lock_entry_data`
structure, besides the future entries, we can use the `FUTURE_CHAIN_ENTRY_LAST(_future_type, _name)` macro.
It works as the `FUTURE_CHAIN_ENTRY(...)` macro, but we should use it to define the last future entry.
Then, we can safely declare some additional data after it. For example, `struct hashmap_lookup_lock_entry_data`
stores `entriesp`, an array of pointers to `struct future_chain_entry`. We use `entriesp` to save the chained future
entries that should be re-run. Then, we employ the `chain_entry_rerun_fut` future to re-run those future entries.

>We should declare additional chained future data only after using the `FUTURE_CHAIN_ENTRY_LAST(...)` macro.

## Initializing a chained future

Chained futures are initialized quite differently from regular futures. Each member future
must be initialized using either `FUTURE_CHAIN_ENTRY_INIT(_entry, _fut, _map, _map_arg)`
or `FUTURE_CHAIN_ENTRY_LAZY_INIT(_entry, _init, _init_arg, _map, _map_arg)` macro.
We will focus on `FUTURE_CHAIN_ENTRY_INIT(...)` macro.

`FUTURE_CHAIN_ENTRY_INIT(...)` takes four parameters. `_entry` is a pointer to the
future entry. `_fut` is an initialized future structure. `_map` defines the mapping behavior
of the data between the future currently being initialized and the next future in the chain.
Mapping function of the last future entry should map the data between this entry and the chained
future containing it. `_map_arg` is the mapping function argument.

```C
static struct hashmap_lookup_lock_entry_fut
hashmap_lookup_lock_entry(struct hashmap *hm, uint64_t key,
		enum hashmap_entry_state state)
{
	struct hashmap_lookup_lock_entry_fut chain;
	/* Initialize chained future entries */
	FUTURE_CHAIN_ENTRY_INIT(&chain.data.lookup,
			hashmap_lookup(hm, key, state),
			lookup_to_set_state_map, NULL);
	FUTURE_CHAIN_ENTRY_INIT(&chain.data.set_state,
			hashmap_entry_set_state(NULL, state,
					HASHMAP_ENTRY_STATE_LOCKED),
			NULL, NULL);
	FUTURE_CHAIN_ENTRY_LAZY_INIT(&chain.data.entries_rerun,
			chain_entry_rerun_init, NULL, NULL, NULL);
	/* Set default chained future output value */
	chain.output.hme = NULL;
	FUTURE_CHAIN_INIT(&chain);
	return chain;
}
```
In this code fragment we create and initialize a `struct hashmap_lookup_lock_entry_fut`
future structure and its future entries.

```C
FUTURE_CHAIN_ENTRY_INIT(&chain.data.lookup,
			hashmap_lookup(hm, key, state),
			lookup_to_set_state_map, NULL);
```
We initialize the `lookup` future entry using `FUTURE_CHAIN_ENTRY_INIT(...)` macro.
`&chain.data.lookup` is an address of the `lookup` future entry. `hashmap_lookup(hm, key, state)`
returns a new, initialized `struct hashmap_lookup_fut` future. `lookup_to_set_state_map` maps the
data from `lookup` future to the `set_state` future.

```C
static void
lookup_to_set_state_map(struct future_context *lookup_ctx,
		struct future_context *set_state_ctx, void *arg)
{
	struct hashmap_lookup_output *lookup_unoccupied_output =
			future_context_get_output(lookup_ctx);
	struct hashmap_entry_set_state_data *set_state_data =
			future_context_get_data(set_state_ctx);
	struct hashmap_entry *hme = lookup_unoccupied_output->hme;
	if (hme == NULL) {
		/*
		 * Entry lookup failed, no need to lock the entry in
		 * 'locked' state.
		 */
		set_state_ctx->state = FUTURE_STATE_COMPLETE;
	}
	set_state_data->hme = hme;
}
```
In the `lookup_to_set_state_map` mapping function we access the data of `lookup`
and `set_state` futures through their contexts. Then, we check if the `lookup` future has found a
hashmap entry. If `lookup` future didn't find an entry, we mark the `set_state` future as completed.
`set_state` will not be executed when it's marked as completed.
Lastly, we set the `lookup` output entry in the `set_state` input data.

We initialize the `set_state`, and 'entries_rerun' futures entries similarly to the `lookup` future
entry.

```C
FUTURE_CHAIN_INIT(&chain);
```
After initializing each chained future entry, we initialize the chained future by passing
its address to the `FUTURE_CHAIN_INIT(...)` macro. `hashmap_lookup_lock_entry_fut` future
is now ready to be polled.

## Initializing chained future entries lazily

We can also initialize chained future entries lazily using
`FUTURE_CHAIN_ENTRY_LAZY_INIT(_entry, _init, _init_arg, _map, _map_arg)` macro.
`_entry`, `_map`, and `_map_arg` parameters are the same as in the `FUTURE_CHAIN_ENTRY_INIT(...)` macro.
`_init` is an entry initialization function that has to conform to the
`typedef void (*future_init_fn)(void *future, struct future_context *chain_fut, void *arg)` definition,
`_init_arg` is the initialization function argument.

>Lazily initialized future entry is initialized right before its execution.

We can find an example of a lazy entry initialization in the `hashmap_get_copy_fut` future.

```C
static struct hashmap_get_copy_fut
hashmap_get_copy(struct vdm *vdm, struct hashmap *hm, uint64_t key)
{
	struct hashmap_get_copy_fut chain;
	/* Initialize chained future entries */
	FUTURE_CHAIN_ENTRY_INIT(&chain.data.lookup_lock_entry,
			hashmap_lookup_lock_entry(hm, key,
					HASHMAP_ENTRY_STATE_PRESENT), NULL,
					NULL);
	FUTURE_CHAIN_ENTRY_LAZY_INIT(&chain.data.memcpy_value,
			memcpy_value_init, vdm, NULL, NULL);
	FUTURE_CHAIN_ENTRY_LAZY_INIT(&chain.data.set_state,
			set_state_init_for_get, NULL, NULL, NULL);
	/* Set default output values */
	chain.output.size = 0;
	chain.output.value = NULL;
	FUTURE_CHAIN_INIT(&chain);
	return chain;
}
```
`hashmap_get_copy(...)` looks similar to the already discussed `hashmap_lookup_lock_entry(...)`
function, except we initialize some of its future entries lazily. We will focus
on the `set_state` entry.

```C
FUTURE_CHAIN_ENTRY_LAZY_INIT(&chain.data.set_state,
			set_state_init_for_get, NULL, NULL, NULL);
```
Previously we have used `hashmap_entry_set_state(...)` to initialize the `set_state` entry.
Now, we pass along an address of the `set_state_init_for_get(...)` function that is responsible
for initializaing the `set_state` entry.

```C
static void
set_state_init_for_get(void *future,
		struct future_context *hashmap_get_copy_ctx, void *arg)
{
	struct hashmap_get_copy_data *data =
			future_context_get_data(hashmap_get_copy_ctx);
	struct hashmap_entry_set_state_fut fut = {.output.changed = 0};
	struct hashmap_entry *hme = data->lookup_lock_entry.fut.output.hme;
	if (hme == NULL) {
		/*
		 * 'lookup_lock_entry' future entry failed to find a hashmap
		 * entry. 'set_state' future entry shouldn't be executed.
		 */
		FUTURE_INIT_COMPLETE(&fut);
	} else {
		/*
		 * 'lookup_lock_entry' was successful.
		 * Set hashmap entry state to 'present'.
		 */
		fut = hashmap_entry_set_state(hme,
				HASHMAP_ENTRY_STATE_LOCKED,
				HASHMAP_ENTRY_STATE_PRESENT);
	}
	memcpy(future, &fut, sizeof(fut));
}
```
In the `set_state_init_for_get` function, we get the output from the `lookup_lock_entry`
future entry and check if we found and locked a valid hashmap entry. We want to omit the
`set_state` future entry execution when `lookup_lock_entry` output is invalid. In that case, we
use the `FUTURE_INIT_COMPLETE(_futurep)` macro to mark the `set_state` future as completed.
In case the `lookup_lock_entry` future output is valid, we call the `hashmap_entry_set_state(...)`
function with appropriate parameters. Finally, we copy the initialized future `fut` to the
address pointed by the `future` parameter using the `memset(...)` function. `future` points to the
future entry that we are currently initializing.

## Summary

In summary, we have covered the future creation and initialization. We have also discussed the
concept of chained futures and how we should initialize their entries. Lastly, we have talked
over the lazy future initialization.

For more information about the Miniasync library, see its [GitHub repository][masync].

[masync]: https://github.com/pmem/miniasync
[masync-examples]: https://github.com/pmem/miniasync/tree/master/examples
[masync-hashmap]: https://github.com/pmem/miniasync/blob/master/examples/hashmap/hashmap.c
[masync-future-manpage]: https://github.com/pmem/miniasync/blob/master/doc/miniasync_future.7.md
[murmurmash3-wiki]: https://en.wikipedia.org/wiki/MurmurHash

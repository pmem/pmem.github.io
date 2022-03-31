---
# Blog post title
title: "Basic asynchronous hashmap with Miniasync library"

# Blog post creation date
date: 2022-03-30T15:11:18+02:00

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

Miniasync library provides a framework for the composition and execution of
asynchronous tasks in C. In order to accomodate different user-defined tasks
and various types of data that they take in, libminiasync makes use of macros.

Using libminiasync for the first time can be challenging. There are multiple examples
on the [miniasync repo][masync-examples] to make it easier. One of them is
[hashmap][masync-hashmap] example.

## Creating a future

Future is an abstraction of asynchronous task. Each future has to be created using
`FUTURE(_name, _data_type, _output_type)` macro. It takes future name `_name`,
input data type `_data_type` and output data type `_output_type`.

The lookup future definition from the hashmap example:

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

The lookup data `struct hashmap_lookup_data` contains a pointer to hashmap `hm`, `key` and `state` of the
searched entry. `hm` should point to an actual hashmap instance. `key` is used in hash function. `state` indicates
whether lookup function should search for an already existing hashmap entry or an entry that is unoccupied.

The output data `struct hashmap_lookup_output` contains a pointer to the hashmap entry `hm` that points
to the found hashmap entry.

`FUTURE(...)` macro defines a `struct hashmap_lookup_fut` future struct with `struct hashmap_lookup_data`
as input data and `struct hashmap_lookup_output` as output.

## Future task function

```C
typedef enum future_state (*future_task_fn)(struct future_context *context,
			struct future_notifier *notifier);
```

Future task function contains the actual logic of the future. Every such task implementation
must conform to the definition above. Future context `struct future_context` provides access
to the input data as well as output of a particular future.

>Notifier feature is not used in hashmap example, hence it will be not discussed. For more info about the notifiers take
a look [here][masync-future-manpage].

The return value of future task function indicates the state of the future task.

```C
enum future_state {
	FUTURE_STATE_IDLE,
	FUTURE_STATE_COMPLETE,
	FUTURE_STATE_RUNNING,
};
```
`FUTURE_STATE_IDLE` indicates that the future task has not been run yet, `FUTURE_STATE_COMPLETE`
means that it has already completed and `FUTURE_STATE_RUNNING` signalizes that it's still running.

The lookup future task function:

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
Data and output types are the same types provided to `FUTURE(...)` macro.

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
Next, we use the input data to find the index of the hashmap entry.

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

If we were looking for the index of an unoccupied entry and it wasn't found, we return
`FUTURE_STATE_RUNNING`. `hashmap_lookup_impl` will then be re-run preserving all
the changes made to the data and output.

```C
	hme = &hm->entries[index];
set_output:
	output->hme = hme;
	return FUTURE_STATE_COMPLETE;
```
Lastly, when an entry was found, we set the output entry address to the found entry and
return `FUTURE_STATE_COMPLETE`.

## Initializing a future

Having defined the future input, output and task function, we can create an instance of
the lookup future.

```C
static struct hashmap_lookup_fut
hashmap_lookup(struct hashmap *hm, uint64_t key, enum hashmap_entry_state state)
{
	struct hashmap_lookup_fut future;
	/* set input values */
	future.data.hm = hm;
	future.data.key = key;
	future.data.state = state;
	/* set default output value */
	future.output.hme = NULL;
	FUTURE_INIT(&future, hashmap_lookup_impl);
	return future;
}
```

The code fragment above creates a lookup future structure, sets its input data and
default output values. After that, it initializes this future and binds it with a
`hashmap_lookup_impl(...)` task function using `FUTURE_INIT(...)` macro. Futures initialized
that way are ready for polling.

## Creating a chained future

Multiple futures can be composed into a single chained future. When polled, chained
future executes its future entries sequentially in the order they were defined.
Future are defined as the chained future entries using `FUTURE_CHAIN_ENTRY(_future_type, _name)`
macro. It takes two parameters, `_future_type` is the type of the future and `_name`
is the name of the variable stored in the data structure.

```C
struct hashmap_lookup_lock_entry_data {
	FUTURE_CHAIN_ENTRY(struct hashmap_lookup_fut, lookup);
	FUTURE_CHAIN_ENTRY(struct hashmap_entry_set_state_fut, set_state);
};

struct hashmap_lookup_lock_entry_output {
	struct hashmap_entry *hme;
};

FUTURE(hashmap_lookup_lock_entry_fut, struct hashmap_lookup_lock_entry_data,
		struct hashmap_lookup_lock_entry_output);
```
Here, `struct hashmap_lookup_lock_entry_fut` future consists of two other futures
`struct hashmap_lookup_fut` that was discussed and `struct hashmap_entry_set_state_fut` that
follows the same principles the lookup future does. `struct hashmap_lookup_lock_entry_fut`
chained future is responsible for finding an appropriate hashmap entry and locking it in
`HASHMAP_ENTRY_STATE_PROCESSED` state. This ensures that no other future will interact with this
entry until we change its state from the processed state.

## Initializing a chained future

Chained futures are initialized quite differently from regular futures. Each member future
must be initialized using either `FUTURE_CHAIN_ENTRY_INIT(_entry, _fut, _map, _map_arg)`
or `FUTURE_CHAIN_ENTRY_LAZY_INIT(_entry, _init, _init_arg, _map, _map_arg)` macro.
We will focus on `FUTURE_CHAIN_ENTRY_INIT(...)` macro.

`FUTURE_CHAIN_ENTRY_INIT(...)` takes four parameters. `_entry` is a pointer to the
future entry. `_fut` is an initialized future structure. `_map` defines mapping of
the data between the future currently being initialized and the next future in the
chain. `_map_arg` is the mapping function argument.

```C
static struct hashmap_lookup_lock_entry_fut
hashmap_lookup_lock_entry(struct hashmap *hm, uint64_t key,
		enum hashmap_entry_state state)
{
	struct hashmap_lookup_lock_entry_fut chain;
	/* initialize chained future entries */
	FUTURE_CHAIN_ENTRY_INIT(&chain.data.lookup,
			hashmap_lookup(hm, key, state),
			lookup_to_set_state_map, NULL);
	FUTURE_CHAIN_ENTRY_INIT(&chain.data.set_state,
			hashmap_entry_set_state(NULL, state,
					HASHMAP_ENTRY_STATE_PROCESSED),
			set_state_to_output_map, NULL);
	/* set default output value */
	chain.output.hme = NULL;

	FUTURE_CHAIN_INIT(&chain);

	return chain;
}
```
This code fragment creates and initializes a `struct hashmap_lookup_lock_entry_fut`
future structure.

```C
FUTURE_CHAIN_ENTRY_INIT(&chain.data.lookup,
			hashmap_lookup(hm, key, state),
			lookup_to_set_state_map, NULL);
```
In the first entry initialization the `&chain.data.lookup` is an address of the `lookup`
entry, `hashmap_lookup(hm, key, state)` returns a new `struct hashmap_lookup_fut` future.
`lookup_to_set_state_map` maps the data from `lookup` future to the `set_state` future
and looks like this:

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
		 * 'processed' state.
		 */
		set_state_ctx->state = FUTURE_STATE_COMPLETE;
	}

	set_state_data->hme = hme;
}
```
In the function above we accesses the data of `lookup` and `set_state` through their contexts.
Then we checks if `lookup` future has found an entry and marks the `set_state` future
as completed when it hasn't found any entry. `set_state` will not be executed when its
marked as completed. Lastly, we set the `lookup` output entry in the `set_state` input data.

We initialize the `set_state` future the same way the `lookup` future was initialized.

```C
FUTURE_CHAIN_INIT(&chain);
```
After initializing each chain future entry, we initialize the chain future by passing
its address to the `FUTURE_CHAIN_INIT(...)` macro. `hashmap_lookup_output` future is now
ready to be polled.

## Initializing chained future entries lazly

Chained future entries can also be initialized lazly using
`FUTURE_CHAIN_ENTRY_LAZY_INIT(_entry, _init, _init_arg, _map, _map_arg)` macro.
The `_entry`, `_map` and `_map_arg` parameters are the same as in `FUTURE_CHAIN_ENTRY_INIT(...)` macro.
`_init` is an entry initialization function that has to conform to the
`typedef void (*future_init_fn)(void *future, struct future_context *chain_fut, void *arg)` definition,
`_init_arg` is the initialization function argument.

>Entries meant to be initialized lazly are initialized right before they are executed.

```C
static struct hashmap_get_copy_fut
hashmap_get_copy(struct vdm *vdm, struct hashmap *hm, uint64_t key)
{
	struct hashmap_get_copy_fut chain;
	/* initialize chained future entries */
	FUTURE_CHAIN_ENTRY_INIT(&chain.data.lookup_lock_entry,
			hashmap_lookup_lock_entry(hm, key,
					HASHMAP_ENTRY_STATE_PRESENT), NULL,
					NULL);
	FUTURE_CHAIN_ENTRY_LAZY_INIT(&chain.data.memcpy_value,
			memcpy_value_init, vdm, NULL, NULL);
	FUTURE_CHAIN_ENTRY_LAZY_INIT(&chain.data.set_state,
			set_state_init_for_get, NULL, NULL, NULL);
	/* set default output values */
	chain.output.size = 0;
	chain.output.value = NULL;

	FUTURE_CHAIN_INIT(&chain);

	return chain;
}
```
An example of a lazy entry initialization can be found in `hashmap_get_copy_fut` future.
`hashmap_get_copy(...)` looks similar to the already discussed `hashmap_lookup_lock_entry(...)`
function, except it initializes some of the future entries lazly. We will focus
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
		/* Entry wasn't found, entry state shouldn't be changed */
		FUTURE_INIT_COMPLETE(&fut);
	} else {
		/* Entry value was copied, set entry state to 'present' */
		fut = hashmap_entry_set_state(hme,
				HASHMAP_ENTRY_STATE_PROCESSED,
				HASHMAP_ENTRY_STATE_PRESENT);
	}

	memcpy(future, &fut, sizeof(fut));
}
```
`future` is a pointer to the future entry being initialized, `hashmap_get_copy_ctx`
is a context of the chained future. We get the output from the `lookup_lock_entry`
future entry and check for a valid hashmap entry. When a future is initialized using
`FUTURE_INIT_COMPLETE(_futurep)` macro its marked as an already complete future.
We use it here to avoid executing `set_state` when the output from `lookup_lock_entry`
is not valid. In case the output is valid, we create this the future using
`hashmap_entry_set_state(...)` function that was mentioned before. Finally, we copy the
initialized future to the address pointed by `future`.

[masync-examples]: https://github.com/pmem/miniasync/tree/master/examples
[masync-hashmap]: https://github.com/kswiecicki/libuasync/blob/masync-example-hashmap/examples/hashmap/hashmap.c
[masync-future-manpage]: https://github.com/pmem/miniasync/blob/master/doc/miniasync_future.7.md

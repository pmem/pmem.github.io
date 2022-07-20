---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["libpmemstream.3.html"]
title: libpmemstream
section: 3
secondary_title: pmemstream
---

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2022, Intel Corporation)

[comment]: <> (libpmemstream.3 -- man page for libpmemstream API)

[NAME](#name)\
[SYNOPSIS](#synopsis)\
[DESCRIPTION](#description)\
[SEE ALSO](#see-also)


# NAME #

**libpmemstream** - a logging data structure optimized for persistent memory.

# SYNOPSIS #

```c
#include <libpmemstream.h>

struct pmemstream;
struct pmemstream_entry_iterator;
struct pmemstream_region_iterator;
struct pmemstream_region_runtime;
struct pmemstream_region {
	uint64_t offset;
};

struct pmemstream_entry {
	uint64_t offset;
};

struct pmemstream_async_wait_data;
struct pmemstream_async_wait_output {
	int error_code;
};

FUTURE(pmemstream_async_wait_fut,
	struct pmemstream_async_wait_data, struct pmemstream_async_wait_output);

int pmemstream_from_map(struct pmemstream **stream, size_t block_size, struct pmem2_map *map);
void pmemstream_delete(struct pmemstream **stream);

int pmemstream_region_allocate(struct pmemstream *stream, size_t size, struct pmemstream_region *region);
int pmemstream_region_free(struct pmemstream *stream, struct pmemstream_region region);

size_t pmemstream_region_size(struct pmemstream *stream, struct pmemstream_region region);
size_t pmemstream_region_usable_size(struct pmemstream *stream, struct pmemstream_region region);

int pmemstream_region_runtime_initialize(struct pmemstream *stream, struct pmemstream_region region,
					 struct pmemstream_region_runtime **runtime);

int pmemstream_reserve(struct pmemstream *stream, struct pmemstream_region region,
		       struct pmemstream_region_runtime *region_runtime, size_t size,
		       struct pmemstream_entry *reserved_entry, void **data);
int pmemstream_publish(struct pmemstream *stream, struct pmemstream_region region,
		       struct pmemstream_region_runtime *region_runtime, struct pmemstream_entry entry, size_t size);
int pmemstream_append(struct pmemstream *stream, struct pmemstream_region region,
		      struct pmemstream_region_runtime *region_runtime, const void *data, size_t size,
		      struct pmemstream_entry *new_entry);

int pmemstream_async_publish(struct pmemstream *stream, struct pmemstream_region region,
			     struct pmemstream_region_runtime *region_runtime, struct pmemstream_entry entry,
			     size_t size);
int pmemstream_async_append(struct pmemstream *stream, struct vdm *vdm, struct pmemstream_region region,
			    struct pmemstream_region_runtime *region_runtime, const void *data, size_t size,
			    struct pmemstream_entry *new_entry);

uint64_t pmemstream_committed_timestamp(struct pmemstream *stream);
uint64_t pmemstream_persisted_timestamp(struct pmemstream *stream);

struct pmemstream_async_wait_fut pmemstream_async_wait_committed(struct pmemstream *stream, uint64_t timestamp);
struct pmemstream_async_wait_fut pmemstream_async_wait_persisted(struct pmemstream *stream, uint64_t timestamp);

const void *pmemstream_entry_data(struct pmemstream *stream, struct pmemstream_entry entry);
size_t pmemstream_entry_size(struct pmemstream *stream, struct pmemstream_entry entry);
uint64_t pmemstream_entry_timestamp(struct pmemstream *stream, struct pmemstream_entry entry);

int pmemstream_entry_iterator_new(struct pmemstream_entry_iterator **iterator, struct pmemstream *stream,
				  struct pmemstream_region region);

int pmemstream_entry_iterator_is_valid(struct pmemstream_entry_iterator *iterator);
void pmemstream_entry_iterator_next(struct pmemstream_entry_iterator *iterator);
void pmemstream_entry_iterator_seek_first(struct pmemstream_entry_iterator *iterator);
struct pmemstream_entry pmemstream_entry_iterator_get(struct pmemstream_entry_iterator *iterator);
void pmemstream_entry_iterator_delete(struct pmemstream_entry_iterator **iterator);

int pmemstream_region_iterator_new(struct pmemstream_region_iterator **iterator, struct pmemstream *stream);
int pmemstream_region_iterator_is_valid(struct pmemstream_region_iterator *iterator);
void pmemstream_region_iterator_seek_first(struct pmemstream_region_iterator *iterator);
void pmemstream_region_iterator_next(struct pmemstream_region_iterator *iterator);
struct pmemstream_region pmemstream_region_iterator_get(struct pmemstream_region_iterator *iterator);
void pmemstream_region_iterator_delete(struct pmemstream_region_iterator **iterator);
```

# DESCRIPTION #

Most of API functions are called with `struct pmemstream *stream` as a first argument. It is a structure
representing runtime state of a single *pmemstream* instance. It has to be a pointer to a valid
*pmemstream* instance, created/opened using `pmemstream_from_map` call.

When it comes to iterator-related API - first parameter in these functions is usually
`struct pmemstream_region_iterator *iterator` or `struct pmemstream_entry_iterator *iterator`.
These structures represent *region* and *entry* iterators and should be created using appropriate *new* functions:
`pmemstream_region_iterator_new` or `pmemstream_entry_iterator_new`. In both these functions `stream` parameter
is passed to iterator, so such iterator is bound to a selected pmemstream instance and it's not required
to pass it along to other iterator-related functions.

For high level description of pmemstream or its features/functionalities please see **libpmemstream**(7).
For detailed description of a specific function and its parameters see below.

`int pmemstream_from_map(struct pmemstream **stream, size_t block_size, struct pmem2_map *map);`

:	Creates new pmemstream instance from the given *pmem2_map* `map` and assigns it to `stream` pointer.
	`block_size` defines alignment of regions - must be a power of 2 and multiple of CACHELINE size.
	See **libpmem2**(7) for details on creating pmem2 mapping.
	If this function is called with a map representing an empty file, the new pmemstream instance will be initialized.
	If a mapping points to a previously existing pmemstream instance, it re-opens it and reads persisted header's data.
	In any other case, it's undefined behavior.
	It returns 0 on success, error code otherwise.

`void pmemstream_delete(struct pmemstream **stream);`

: Releases the given 'stream' resources and sets 'stream' pointer to NULL.

`int pmemstream_region_allocate(struct pmemstream *stream, size_t size, struct pmemstream_region *region);`

:	Allocates new region with specified 'size'. Actual size might be bigger due to alignment requirements.
	Only fixed-sized regions are supported for now (all `pmemstream_region_allocate` calls within a single
	pmemstream instance have to use the same size).
	Optional 'region' parameter is updated with the new region information.
	It returns 0 on success, error code otherwise.

`int pmemstream_region_free(struct pmemstream *stream, struct pmemstream_region region);`

:	Frees previously allocated, specified 'region'.
	It returns 0 on success, error code otherwise.

`size_t pmemstream_region_size(struct pmemstream *stream, struct pmemstream_region region);`

:	Returns size of the given 'region'. It may be bigger than the size passed to 'pmemstream_region_allocate'
	due to an alignment.
	On error returns 0.

`size_t pmemstream_region_usable_size(struct pmemstream *stream, struct pmemstream_region region);`

:	Returns current usable (free) size of the given 'region'.
	It equals to: 'region's end offset' - 'region's append offset'.
	This function serves only as an approximation of available space for use.
	See `pmemstream_entry_size` to read more about space used by entries.
	On error returns 0.

`int pmemstream_region_runtime_initialize(struct pmemstream *stream, struct pmemstream_region region, struct pmemstream_region_runtime **runtime);`

:	Initializes pmemstream_region_runtime for the given 'region'. The runtime holds current, runtime
	data (like append_offset) for a region. The runtime is managed by libpmemstream - user does not have
	to explicitly delete/free it. Runtime becomes invalid after corresponding region is freed.
	Pointer to initialized pmemstream_region_runtime is returned via 'runtime' parameter.
	Call to this function might be expensive. If it is not called explicitly, pmemstream will call it
	inside a first append/reserve in a region.
	Returns 0 on success, error code otherwise.

`int pmemstream_reserve(struct pmemstream *stream, struct pmemstream_region region, struct pmemstream_region_runtime *region_runtime, size_t size, struct pmemstream_entry *reserved_entry, void **data);`

:	Reserves space (for a future, custom write) of the given 'size', in a 'region' at offset determined
	by 'region_runtime'. Entry's data have to be copied into reserved space by the user and then published
	using pmemstream_publish. This approach is only recommended for special use cases, e.g. custom memcpy
	or use with C++ "placement new" syntax.
	For regular usage, pmemstream_append should be simpler and safer to use and provide better performance.
	'region_runtime' is an optional parameter which can be obtained from pmemstream_region_runtime_initialize.
	If it's NULL, it will be obtained from its internal structures (which might incur overhead).
	'reserved_entry' is updated with an offset of the reserved entry - this entry has to be passed to
	pmemstream_publish for completing the custom append process.
	'data' is updated with a pointer to reserved space - this is a destination for, e.g., custom memcpy.
	It is not allowed to call pmemstream_reserve for the second time before calling pmemstream_publish.
	It returns 0 on success, error code otherwise.

`int pmemstream_publish(struct pmemstream *stream, struct pmemstream_region region, struct pmemstream_region_runtime *region_runtime, struct pmemstream_entry entry, size_t size);`

:	Synchronously publishes previously custom-written 'entry' in a 'region'.
	After calling pmemstream_reserve and writing/memcpy'ing data into a reserved_entry, it's required
	to call this function for setting proper entry's metadata and persist the data.
	'region_runtime' is an optional parameter which can be obtained from pmemstream_region_runtime_initialize.
	If it's NULL, it will be obtained from its internal structures (which might incur overhead).
	'size' of the entry has to match the previous reservation and the actual size of the data written by user.
	It returns 0 on success, error code otherwise.

`int pmemstream_append(struct pmemstream *stream, struct pmemstream_region region, struct pmemstream_region_runtime *region_runtime, const void *data, size_t size, struct pmemstream_entry *new_entry);`

:	Synchronously appends data buffer to a given region, at offset determined by region_runtime.
	Fails if no space is available.
	'region_runtime' is an optional parameter which can be obtained from pmemstream_region_runtime_initialize.
	If it's NULL, it will be obtained from its internal structures (which might incur overhead).
	'data' is a pointer to the data buffer, to be appended.
	'size' is the size of the data buffer, to be appended.
	'new_entry' is an optional pointer. On success, it will contain information about newly appended entry
	(with its offset within pmemstream).
	It returns 0 on success, error code otherwise.

`int pmemstream_async_publish(struct pmemstream *stream, struct pmemstream_region region, struct pmemstream_region_runtime *region_runtime, struct pmemstream_entry entry, size_t size);`

:	Asynchronous version of pmemstream_publish.
	It publishes previously custom-written entry. 'entry' is marked as ready for commit.
	There is no guarantee whether data is visible by iterators or persisted after this call.
	To commit (and make the data visible to iterators) or persist the data use: pmemstream_async_wait_committed or
	pmemstream_async_wait_persisted.
	It returns 0 on success, error code otherwise.

`int pmemstream_async_append(struct pmemstream *stream, struct vdm *vdm, struct pmemstream_region region, struct pmemstream_region_runtime *region_runtime, const void *data, size_t size, struct pmemstream_entry *new_entry);`

:	Asynchronous version of pmemstream_append.
	It appends 'data' to the region and marks it as ready for commit.
	There is no guarantee whether data is visible by iterators or persisted after this call.
	To commit (and make the data visible to iterators) or persist the data use: pmemstream_async_wait_committed or
	pmemstream_async_wait_persisted and poll returned future to completion.
	It returns 0 on success, error code otherwise.

`uint64_t pmemstream_committed_timestamp(struct pmemstream *stream);`

:	Returns the most recent committed timestamp in the given stream. All entries with timestamps less than or equal to
	that timestamp can be treated as committed.
	On error it returns invalid timestamp (a special flag properly handled in all functions using timestamps).

`uint64_t pmemstream_persisted_timestamp(struct pmemstream *stream);`

:	Returns the most recent persisted timestamp in the given stream. All entries with timestamps less than or equal to
	that timestamp can be treated as persisted.
	It is guaranteed to be less than or equal to committed timestamp.
	On error it returns invalid timestamp (a special flag properly handled in all functions using timestamps).

`struct pmemstream_async_wait_fut pmemstream_async_wait_committed(struct pmemstream *stream, uint64_t timestamp);`

:	Returns future for committing all entries up to specified 'timestamp'.
	To get "committed" guarantee for given 'timestamp', the returned future must be polled until completion.
	Data which is committed, but not yet persisted, will be visible for iterators but might not be reachable after
	application's restart.
	When returned future is polled to completion, it's best to check its output field `error_code`
	(see: `struct pmemstream_async_wait_output`) for any non-zero returned value.

`struct pmemstream_async_wait_fut pmemstream_async_wait_persisted(struct pmemstream *stream, uint64_t timestamp);`

:	Returns future for persisting all entries up to specified 'timestamp'.
	To get "persisted" guarantee for given 'timestamp', the returned future must be polled until completion.
	Persisted data is guaranteed to be reachable after application's restart.
	If entry is persisted, it is also guaranteed to be committed.
	When returned future is polled to completion, it's best to check its output field `error_code`
	(see: `struct pmemstream_async_wait_output`) for any non-zero returned value.

`const void *pmemstream_entry_data(struct pmemstream *stream, struct pmemstream_entry entry);`

:	Returns pointer to the data of the given 'entry' (if it points to a valid entry).
	On error returns NULL.

`size_t pmemstream_entry_size(struct pmemstream *stream, struct pmemstream_entry entry);`

:	Returns the size of the data of given 'entry'. It's the same value as was passed to `pmemstream_append`.
	Note that pmemstream_entry contains metadata along with appended data - the space occupied
	by pmemstream_entry is actually bigger than the size of appended data.
	It returns 0, if 'entry' does not point to a valid entry or error occurred.

`uint64_t pmemstream_entry_timestamp(struct pmemstream *stream, struct pmemstream_entry entry);`

:	Returns timestamp related to the given 'entry' (if it points to a valid entry).
	On error returns invalid timestamp (a special flag properly handled in all functions using timestamps).

`int pmemstream_region_iterator_new(struct pmemstream_region_iterator **iterator, struct pmemstream *stream);`

:	Creates a new pmemstream_region_iterator and assigns it to 'iterator' pointer.
	Such iterator is bound to the given 'stream'.
	Default state is undefined: every new iterator should be moved (e.g.) to first element in the stream.
	Returns 0 on success, and error code otherwise.

`int pmemstream_region_iterator_is_valid(struct pmemstream_region_iterator *iterator);`

:	Checks if given region 'iterator' is in valid state.
	Returns 0 when iterator is valid, and error code otherwise.

`void pmemstream_region_iterator_seek_first(struct pmemstream_region_iterator *iterator);`

:	Sets region 'iterator' to the first region (if such region exists), or sets iterator to invalid region.

`void pmemstream_region_iterator_next(struct pmemstream_region_iterator *iterator);`

:	Moves region 'iterator' to next region (if any more exists), or sets iterator to invalid region.
	Regions are accessed in the order of allocations.
	Calling this function on iterator pointing to an invalid region is undefined behavior.
	It should always be called after `pmemstream_region_iterator_is_valid()`.
	```
		if(pmemstream_region_iterator_is_valid(it) == 0)
			pmemstream_region_iterator_next(it);
	```

`struct pmemstream_region pmemstream_region_iterator_get(struct pmemstream_region_iterator *iterator);`

:	Gets region from the given region 'iterator'.
	If the given iterator is valid, it returns a region pointed by it,
	otherwise it returns an invalid region.

`void pmemstream_region_iterator_delete(struct pmemstream_region_iterator **iterator);`

:	Releases the given 'iterator' resources and sets 'iterator' pointer to NULL.

`int pmemstream_entry_iterator_new(struct pmemstream_entry_iterator **iterator, struct pmemstream *stream, struct pmemstream_region region);`

:	Creates a new pmemstream_entry_iterator for given 'region' and assigns it to 'iterator' pointer.
	Entry iterator will iterate over all committed (but not necessarily persisted) entries within the region.
	The entry iterator is bound to the given 'stream' and 'region'.
	Default state is undefined: every new iterator should be moved (e.g.) to first element in the region.
	Returns 0 on success, and error code otherwise.

`int pmemstream_entry_iterator_is_valid(struct pmemstream_entry_iterator *iterator);`

:	Checks that entry 'iterator' is in valid state.
	Returns 0 when iterator is valid, and error code otherwise.

`void pmemstream_entry_iterator_seek_first(struct pmemstream_entry_iterator *iterator);`

:	Sets entry 'iterator' to the first entry in the region (if such entry exists),
	or sets iterator to invalid entry.

`void pmemstream_entry_iterator_next(struct pmemstream_entry_iterator *iterator);`

:	Moves entry 'iterator' to next entry if possible.
	It iterates over all committed (but not necessarily persisted) entries. They are accessed
	in the order of appending (which is always linear). Note: entries cannot be removed from the stream,
	with exception of removing the whole region.
	Calling this function on iterator pointing to an invalid entry is undefined behavior.
	It should always be called after `pmemstream_entry_iterator_is_valid()`.
	```
	if(pmemstream_entry_iterator_is_valid(it) == 0)
		pmemstream_entry_iterator_next(it);
	```

`struct pmemstream_entry pmemstream_entry_iterator_get(struct pmemstream_entry_iterator *iterator);`

:	Gets entry from the given entry 'iterator'.
	If the given iterator is valid, it returns an entry pointed by it,
	otherwise it returns an invalid entry.

`void pmemstream_entry_iterator_delete(struct pmemstream_entry_iterator **iterator);`

:	Releases the given 'iterator' resources and sets 'iterator' pointer to NULL.

# SEE ALSO #

**libpmemstream**(7), **libpmem2**(7), **miniasync**(7), and **<https://pmem.io/pmemstream>**

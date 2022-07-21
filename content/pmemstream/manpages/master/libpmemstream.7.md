---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["libpmemstream.7.html"]
title: libpmemstream
section: 7
secondary_title: pmemstream
---

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2021-2022, Intel Corporation)

[comment]: <> (libpmemstream.7 -- man page for libpmemstream API)

# Table of Contents

[NAME](#name)\
[SYNOPSIS](#synopsis)\
[DESCRIPTION](#description)\
[KNOWN CONSTRAINTS](#known-constraints)\
[USE CASES](#use-cases)\
[DETAILED DESCRIPTIONS](#detailed-descriptions)\
[APPENDING DATA METHODS](#appending-data-methods)\
[TIMESTAMPS](#timestamps)\
[ASYNC API](#async-api)\
[ITERATORS](#iterators)\
[EXAMPLES](#examples)\
[SEE ALSO](#see-also)


# NAME #

**libpmemstream** - a logging data structure optimized for persistent memory.

# SYNOPSIS #

```c
#include <libpmemstream.h>
cc ... -lpmemstream
```

# DESCRIPTION #

Libpmemstream implements a pmem-optimized log data structure and provides stream-like access
to data. It presents a contiguous logical address space, divided into regions, with log entries
of arbitrary size. It delivers a generic, easy-to-use, well-tested set of functions. This library
may be a foundation for various, more complex, higher-level solutions (see [Use Cases section below](#use-cases)).
It uses **libpmem2**(7) and **libminiasync**(7) underneath - the second one specifically for asynchronous API.

Up-to-date information about this library can always be found on
[GitHub repository page](https://github.com/pmem/pmemstream).

Libpmemstream is a successor for **libpmemlog**(7). These two libraries are very similar in basic concept,
but libpmemlog was developed in a straightforward manner and does not allow easy extensions.
That's why this library is designed from scratch to enable more advanced features.

Core features:
- pmemstream may contain multiple regions,
- new data entries are appended at the end (of a selected region),
- each entry may be of arbitrary size,
- each entry append is atomic (there're no data consistency issues),
- alternative API (to regular `append`) - `reserve` + `publish`, to allow custom writing
    (memcpy-ing) entry's data (see [Examples section below](#examples)),
- asynchronous (additional to synchronous) API for appending,
- multiple threads can append data concurrently (only to different regions - see below!),
- entry_iterator allows reading data in sequence (within a region),
- each entry is marked with timestamp, to provide global entries' order (and easier recovery).

Some of these features are described in more details below.

## KNOWN CONSTRAINTS ##

>This is experimental pre-release software and should not be used in production systems.
>APIs and file formats may change at any time without preserving backwards compatibility.
>All known issues and limitations are logged as GitHub issues.

There are few relevant constraints, we're aware of (some are only temporary and will be
fixed in future releases):
- region allocator is constrained with a single allocation size - first region allocated in a stream
    defines the size for other regions within that stream,
- no entry modification or removal allowed (the only way to remove an entry is by removing the region containing it),
- as stated above - multiple threads can append data concurrently, but only to different regions.
    No two threads can append to the same region (concurrently),
- most functions return (on error) generic `-1` value, instead of more specific error codes
    (see specific function's description for details of returned type and values),
- there's a limited number of slots for concurrent operations - currently, only 1024 operations can be
    processed in the stream at any given moment. When all slots are taken, a thread has to wait for
    some of the previous operations to finish.

## USE CASES ##

This library is meant to provide a flexible implementation of stream/log structure so it could be used
in many different solutions. Example use cases are:
- persistent double-write buffer in databases,
- persistent level in LSM tree implementations,
- a base for any copy-on-write data structure,
- transactional undo or redo logs, like in **libpmemobj**(7),
- PMEM-buffered file I/O primitives that offload to a slower medium,
and many, many more.

## DETAILED DESCRIPTIONS ##

Some of the internal features/functionalities require an additional explanation. We recommend reading this section
to better understand how pmemstream works.

### APPENDING DATA METHODS ###

Libpmemstream's main operation is an append. We support that in a few various ways:
1. "regular", synchronous append with `pmemstream_append`,
2. asynchronous append with `pmemstream_async_append`,
3. reserve + custom write + publish approach, using `pmemstream_reserve` and `pmemstream_publish`,
4. asynchronous variant of reserve-publish, with `pmemstream_async_publish`.

While the most natural and the easiest way of appending data to the stream is option number one above,
we introduced other approaches for specific users' needs.

An asynchronous approach (either with async_append or just async_publish) may be a little bit complicated
but gives users more flexibility. For a description of how to properly handle asynchronous appends, see
[Async API section below](#async-api).

See [examples section below](#examples) for a pointer where to look for sample pieces of code.
There are various examples showing, i.a., how asynchronous API works or how to best apply the reserve-publish
approach in a user's application.

### TIMESTAMPS ###

Timestamps were introduced in version 0.2.0. Each entry is always persistently marked with a unique, monotonically
increasing number - a **timestamp**. Timestamps provide global entries' ordering. It means it's possible to append
entries to different regions within a single stream and still be able to read them out in a global (stream's)
sequence. It's possible to read out the timestamp of a given entry using `pmemstream_entry_timestamp`.

In case of an application crash, power failure, or just a restart, timestamps are used for recovery. Every entry
with a timestamp lower than or equal to a **persisted_timestamp** will be treated as properly stored on the underlying
medium. There are two functions returning the most recently committed/persisted timestamp within the stream.
Accordingly, these are: `pmemstream_committed_timestamp` and `pmemstream_persisted_timestamp`.

### ASYNC API ###

Asynchronous API was also introduced in version 0.2.0. It makes use of [miniasync library](https://github.com/pmem/miniasync).
It's an extension to synchronous API. `pmemstream_append` and `pmemstream_publish` have their async
counterparts: `pmemstream_async_append` and `pmemstream_async_publish`. Note: `pmemstream_reserve` does not
require one since it's always an immediate operation - it only reserves an internal offset for the upcoming entry.
With asynchronous appends, we introduced two defined stages an append can be in: **committed** or **persisted**.
The third stage an append can be in, is "in progress" (may be treated as an undefined stage - we don't know yet if
the append has finished). **Committed** (but not yet persisted) data will be visible for iterators,
but it might not be reachable after the application restart. **Persisted** data, on the other hand, is guaranteed to
be committed and will be reachable after the application's restart. When an asynchronous append is started,
we don't know when exactly it ends. To solve that problem, there are dedicated (also asynchronous) functions:
`pmemstream_async_wait_committed` and `pmemstream_async_wait_persisted`. They both take a **timestamp** as an argument.
These two functions allow us to wait (and make progress towards) for a specified entry with a given timestamp,
to be either available for reading or to be guaranteed persistent.

`pmemstream_async_wait_committed` and `pmemstream_async_wait_persisted` return `struct pmemstream_async_wait_fut`,
which is a miniasync's **future**. It's a concept representing a task (or tasks) that can be executed incrementally
by polling until the operation is complete. For detailed information on using miniasync library and `future` type see
**miniasync_future**(7). In libpmemstream, the important part is that the user has to poll a returned future until
completion. It's worth noticing that, even though `pmemstream_async_append` is asynchronous, it does not return a
**future**. The user's only option to actually execute started appends is by calling any of the
`pmemstream_async_wait_*` functions. Without that, they may be indefinitely "in progress" and never finish (meaning,
they never be either committed or persisted).

With asynchronous API and usage of Miniasync, there comes an additional benefit. (Virtual) Data Mover abstraction
enables users to take advantage of parallel execution thanks to optimized threaded-based implementations
as well as hardware accelerators (like DSA). Using such an accelerator is possible, e.g., with the implementation of
**DML** data mover API in Miniasync (see **miniasync_vdm_dml**(7) for more details). To read more about DSA and see
possible use cases or benefits, you can look at [our blog posts regarding this topic](https://pmem.io/tags/dsa).

### ITERATORS ###

Operating on data within libpmemstream, means operating on entries located in regions. To iterate over all entries
or regions there's a special set of functions.

Firstly, a user may want to list all regions within the stream. There's a dedicated `struct pmemstream_region_iterator`
representing a region's iterator and a family of functions related to that API, all prefixed with:
`pmemstream_region_iterator_`. To create a new iterator user have to call `pmemstream_region_iterator_new`.
Initially, a user should call `pmemstream_region_iterator_seek_first` to move the iterator onto the first region.
Before reading the region pointed by the iterator, it is required to check if the iterator is at a
valid position: `pmemstream_region_iterator_is_valid`. If the iterator is valid, a user may safely get a
`struct pmemstream_region` using `pmemstream_region_iterator_get`. When a user wants to go to next region,
it is required to call `pmemstream_region_iterator_next`. It's worth noticing here that regions are iterated in the
order of their creation. It's important because regions are allowed to be removed (using `pmemstream_region_free`).
When the iterator is no longer needed (or when a user wants to close the application), it is required
to call `pmemstream_region_iterator_delete`, to free iterator's structure resources.

An iterator for browsing through all entries within a selected region - `struct pmemstream_entry_iterator` behaves
very similarly. The sequence of steps is alike: **new iterator**, **seek (first)**, **next**/**get** and finally
**delete**. The API related to the entry iterator is always prefixed with `pmemstream_entry_iterator_`.
The main difference for this iterator is that it returns `struct pmemstream_entry` when `pmemstream_entry_iterator_get` is called.
Since pmemstream does not support removing a single entry and append always places new entries at the end,
entries within a region are also iterated in the order of their creation (which happens to be linear).

It's important to note, for both iterators, that calling `_next`, `_seek*` or `_get` on an invalid iterator
is undefined behavior.

# EXAMPLES #

See [examples dir on our GitHub](https://github.com/pmem/pmemstream/tree/master/examples)
for libpmemstream API usage.

<!-- XXX: describe or include here some examples? inject sample, e.g., for async API and iterators -->

# SEE ALSO #

**libpmem2**(7), **miniasync**(7), **libpmemlog**(7), **libpmemstream**(3), and **<https://pmem.io/pmemstream>**

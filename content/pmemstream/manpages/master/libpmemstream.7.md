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

[NAME](#name)\
[SYNOPSIS](#synopsis)\
[DESCRIPTION](#description)\
[KNOWN CONSTRAINTS](#known-constraints)\
[USE CASES](#use-cases)\
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
    (see specific function's description for details of returned type and values).

## USE CASES ##

This library is meant to provide a flexible implementation of stream/log structure so it could be used
in many different solutions. Example use cases are:
- persistent double-write buffer in databases,
- persistent level in LSM tree implementations,
- a base for any copy-on-write data structure,
- transactional undo or redo logs, like in **libpmemobj**(7),
- PMEM-buffered file I/O primitives that offload to a slower medium,
and many, many more.

<!-- XXX:
## Detailed description
### Appending data (append vs reserve+publish)
### Entries committed vs persisted
### Async API
### Iterators
...
-->

# EXAMPLES #

See [examples dir on our GitHub](https://github.com/pmem/pmemstream/tree/master/examples)
for libpmemstream API usage.

<!-- XXX: describe or include here some examples? -->

# SEE ALSO #

**libpmem2**(7), **miniasync**(7), **libpmemlog**(7), **libpmemstream**(3), and **<https://pmem.io/pmemstream>**

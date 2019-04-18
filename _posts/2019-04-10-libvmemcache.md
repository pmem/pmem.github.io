---
title: libvmemcache - buffer-based LRU cache
author: ldorau
layout: post
identifier: libvmemcache
---

### Introduction

**libvmemcache** is a volatile key-value store optimized for operating on
NVDIMM based space. However, it can work with any filesystem whether
stored in memory (tmpfs) or on some kind of a disk (what whould be
less performant).
**libvmemcache** is an embeddable and lightweight in-memorycaching solution.
It is designed to fully take advantage of large capacity memory,
such as persistent memory with DAX through memory mapping in an efficient
and scalable way.

### Creation of cache

At the very beginning you **have to call vmemcache_new()**
in order to create a new vmemcache instance:

```c
	cache = vmemcache_new();
```

It creates an empty unconfigured vmemcache instance initialized
with the default values.

Next, you can configure the parameters of the cache to change
their default values.

You can set the size of the cache (it will be rounded up towards
a whole page size alignment (4KB on x86):

```c
	vmemcache_set_size(cache, new_size);
```

You can set the block size of the cache (256 bytes minimum,
strongly recommended to be a multiple of 64 bytes). If the cache is backed
by a non byte-addressable medium, the block size should be 4096 (or a multiple)
or performance will greatly suffer.

```c
	vmemcache_set_extent_size(cache, block_size);
```

You can set also the replacement policy:

```c
	vmemcache_set_eviction_policy(cache, repl_p);
```

that defines what should happen on a put into a full cache:

- *VMEMCACHE_REPLACEMENT_NONE* - manual eviction only - puts into a full cache
   will fail
- *VMEMCACHE_REPLACEMENT_LRU* - least recently accessed entry will be evicted
   to make space when needed

Then you **have to call vmemcache_add()** function in order to associate
the cache with a backing storage medium in the given path:

```c
	vmemcache_add(cache, "/path/to/backing/storage/medium");
```

which may be a */dev/dax* device or a directory on a regular filesystem
(which may or may not be mounted with -o dax, either on persistent memory
or any other backing storage).

The cache is ready to be used now.

### Use of the cache

There are three basic operations on the cache.

You can put a new element into the cache using the **vmemcache_put()** function

```c
	vmemcache_put(cache, key, key_size, value, value_size);
```

that inserts the given key:value pair into the cache.

You can get an element from the cache using the **vmemcache_get()** function:

```c
	vmemcache_get(cache, key, key_size, vbuf, vbufsize, offset, vsize);
```

that searches for an entry with the given key.

You can also evict an element from the cache using the **vmemcache_evict()**
function:

```c
	vmemcache_evict(cache, key, ksize);
```

that removes the given key from the cache.

### Callbacks

You can register a hook to be called during eviction or after a cache miss,
using **vmemcache_callback_on_evict()** or **vmemcache_callback_on_miss()**,
respectively:

```c
	vmemcache_callback_on_evict(cache, callback_on_evict, arg);
	vmemcache_callback_on_miss(cache, callback_on_miss, arg);
```

The extra *arg* will be passed to your function.

The 'on evict' callback is called when an entry is being removed from the cache.
The eviction can't be prevented, but until the callback returns,
the entry remains available for queries.
The thread that triggered the eviction is blocked in the meantime.

The 'on miss' callback is called when a *get* query fails, to provide
an opportunity to insert the missing key. If the callback calls *put*
for that specific key, the *get* will return its value,
even if it did not fit into the cache.


### Miscellaneous

It is possible to obtains a piece of statistics about the cache
using the **vmemcache_get_stat()** function:

```c
	vmemcache_get_stat(cache, statistic, value, value_size);
```

The *statistic* can be one of the following:

- *VMEMCACHE_STAT_PUT* -- count of puts
- *VMEMCACHE_STAT_GET* -- count of gets
- *VMEMCACHE_STAT_HIT* -- count of gets that were served from the cache
- *VMEMCACHE_STAT_MISS* -- count of gets that were not present in the cache
- *VMEMCACHE_STAT_EVICT* -- count of evictions
- *VMEMCACHE_STAT_ENTRIES* -- current number of cache entries
- *VMEMCACHE_STAT_DRAM_SIZE_USED* -- current amount of DRAM used
- *VMEMCACHE_STAT_POOL_SIZE_USED* -- current usage of data pool
- *VMEMCACHE_STAT_HEAP_ENTRIES* -- current number of heap entries

Statistics are enabled by default. They can be disabled at the compile time
of the vmemcache library if the **STATS_ENABLED** CMake option is set to OFF.

A human-friendly description of the last error can be retrieved using
the **vmemcache_errormsg()** function:

```c
	vmemcache_errormsg();
```


### Delete the cache

At the end you have to free any structures associated with the cache:

```c
	vmemcache_delete(cache);
```

The complete example code can be found
[here](https://github.com/pmem/vmemcache/blob/master/tests/example.c).

### Documentation

The complete libvmemcache manual can be found
[here](https://pmem.io/vmemcache/manpages/master/vmemcache.3.html).

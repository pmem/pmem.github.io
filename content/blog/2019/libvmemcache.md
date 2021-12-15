---
# Blog post title
title: 'libvmemcache - buffer-based LRU cache'

# Blog post creation date
date: 2019-05-07T19:55:17-07:00

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
author: 'ldorau'

# Categories to which this blog post belongs
blogs: ['libvmemcache']

tags: []

# Redirects from old URL
aliases: ['/2019/05/07/libvmemcache.html']

# Blog post type
type: 'post'
---

### Introduction

**libvmemcache** is a volatile key-value store optimized for operating on
NVDIMM based space. However, it can work with any filesystem whether it is
stored in memory (tmpfs) or on any storage device. Consequently, libvmemcache
will be significantly less performant if it is stored on the storage device
other than NVDIMMs.

**libvmemcache** is an embeddable and lightweight in-memory caching solution.
It is designed to fully take advantage of large capacity memory,
such as persistent memory with DAX through memory mapping in an efficient
and scalable way.

### Creation of cache

At the very beginning you have to call `vmemcache_new()`
in order to create a new vmemcache instance:

```c
	cache = vmemcache_new();
```

It creates an empty unconfigured vmemcache instance initialized
with the default values.

Next, you can configure the parameters of cache to change
their default values.

You can set the size of cache - it will be rounded up
to a whole page size (4KB on x86):

```c
	vmemcache_set_size(cache, new_size);
```

You can set the block size of cache (256 bytes minimum,
strongly recommended to be a multiple of 64 bytes). If cache is backed
by a non byte-addressable medium, the block size should be 4096 (or a multiple)
or performance will greatly suffer.

```c
	vmemcache_set_extent_size(cache, block_size);
```

You can also set the replacement policy that defines what will happen
when an element is inserted into full cache:

```c
	vmemcache_set_eviction_policy(cache, repl_p);
```

- _VMEMCACHE_REPLACEMENT_NONE_ - insert operation into full cache will fail
  (only manual eviction is possible)
- _VMEMCACHE_REPLACEMENT_LRU_ - least recently accessed entry will be evicted
  to make space when needed

Then you have to call the `vmemcache_add()` function in order to associate
cache with a backing storage medium in the given path:

```c
	vmemcache_add(cache, "/path/to/backing/storage/medium");
```

which may be a _/dev/dax_ device or a directory on a regular filesystem
(which may or may not be mounted with -o dax, either on persistent memory
or any other backing storage).

Cache is ready to be used now.

### Use of cache

There are three basic operations on cache.

You can put a new element into cache using the `vmemcache_put()` function

```c
	vmemcache_put(cache, key, key_size, value, value_size);
```

that inserts a given (key, value) pair into cache.

You can get an element from cache using the `vmemcache_get()` function:

```c
	vmemcache_get(cache, key, key_size, vbuf, vbufsize, offset, vsize);
```

that searches for an entry with the given key.

You can also evict an element from cache using the `vmemcache_evict()`
function:

```c
	vmemcache_evict(cache, key, ksize);
```

that removes the given key from cache.

### Callbacks

You can register a hook to be called during eviction or after a cache miss,
using `vmemcache_callback_on_evict()` or `vmemcache_callback_on_miss()`,
respectively:

```c
	vmemcache_callback_on_evict(cache, callback_on_evict, arg);
	vmemcache_callback_on_miss(cache, callback_on_miss, arg);
```

The extra _arg_ will be passed to your function.

The 'on evict' callback function is called when an entry is being removed from
cache. The function cannot prevent the eviction but the entry remains available
for queries until the callback function returns. The thread that triggered
the eviction is blocked in the meantime.

The 'on miss' callback function is called when a _get_ query fails in order to
provide an opportunity to insert the missing key. If the callback function
calls _put_ for that specific key, the _get_ will return its value even
if it does not fit into cache.

### Miscellaneous

It is possible to obtain a piece of statistics about cache
using the `vmemcache_get_stat()` function:

```c
	vmemcache_get_stat(cache, statistic, value, value_size);
```

The _statistic_ can be one of the following:

- _VMEMCACHE_STAT_PUT_ -- count of puts
- _VMEMCACHE_STAT_GET_ -- count of gets
- _VMEMCACHE_STAT_HIT_ -- count of gets that were served from cache
- _VMEMCACHE_STAT_MISS_ -- count of gets that were not present in cache
- _VMEMCACHE_STAT_EVICT_ -- count of evictions
- _VMEMCACHE_STAT_ENTRIES_ -- current number of cache entries
- _VMEMCACHE_STAT_DRAM_SIZE_USED_ -- current amount of DRAM used
- _VMEMCACHE_STAT_POOL_SIZE_USED_ -- current usage of data pool
- _VMEMCACHE_STAT_HEAP_ENTRIES_ -- current number of heap entries

Statistics are enabled by default. They can be disabled at the compile time
of the vmemcache library if the `STATS_ENABLED` CMake option is set to OFF.

A human-friendly description of the last error can be retrieved using
the `vmemcache_errormsg()` function:

```c
	vmemcache_errormsg();
```

### Delete cache

At the end you have to free any structures associated with cache:

```c
	vmemcache_delete(cache);
```

The complete example code can be found in the
[vmemcache repository](https://github.com/pmem/vmemcache/blob/master/tests/example.c).

### Documentation

The complete libvmemcache manual can be found at
[pmem.io](/vmemcache/manpages/master/vmemcache.3.html).

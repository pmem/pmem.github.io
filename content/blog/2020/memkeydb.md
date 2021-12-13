---
# Blog post title
title: 'MemKeyDB - Redis with Persistent Memory'

# Blog post creation date
date: 2020-09-25T19:55:17-07:00

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
author: 'jschmieg'

# Categories to which this blog post belongs
blogs: ['memkeydb']

tags: []

# Redirects from old URL
aliases: ['/2020/09/25/memkeydb.html']

# Blog post type
type: 'post'
---

# Context

Redis is an in-memory database that supports various data-structures and stores
them in main memory. To support data durability, Redis relies on creating
periodical snapshots of data or logging all commands that reach the server.

When Persistent Memory was first introduced, we've started working on various
approaches of using it in Redis. Apart from using its persistence, we also had
its huge capacity at our disposal.

Our first approach focused on the replacement of the internal mechanisms of
Redis' persistence by the persistency of the medium. However, we have concluded
that the exclusion of these features can be negatively perceived by existing
users for at least two reasons: the same mechanism (snapshot creation) is used
for replication and current infrastructure and software are adopted to use
snapshot files for backup. Keeping support for these mechanisms when introducing
PMem requires solving additional problems. Redis uses a copy-on-write mechanism
to generate database snapshots.
In order to use the CoW functionality in Redis, files should be mapped with the
MAP_PRIVATE flag. On FS DAX mapping a file with this parameter will copy the
writable pages to DRAM. This excludes the use of this mechanism on PMem. To
solve above conceptual problems, we have tried various workarounds such as:
adopting kind of lazy-free mechanism and other ways not to trigger data
modification during Redis `fork()`. The result was a complex modification of the
application, which occurred in many places in the code.

The situation became simpler when the Linux kernel 5.1 included support for the
[KMEM DAX](https://patchwork.kernel.org/cover/10829019/) mechanism and the
possibility of exposing memory from the device as an additional NUMA node. Using
this feature, support for Copy-on-Write was transparent and the memory pages
modified during the `fork()` were allocated from the same NUMA node to which the
parent process was bound. Support for this mechanism has been added in our
volatile memory allocator –
[Memkind](https://pmem.io/blog/2020/01/introduction-to-libmemkind) as an additional [static
kind](https://pmem.io/blog/2020/01/memkind-support-for-kmem-dax-option). The disadvantage of
this approach is a possible decrease in performance due to copying entire
pages from the PMem to the PMem while duplicating them if modified.

With the possibilities described above, we modified Redis by adding a new type
of memory used in a volatile way, while retaining the native mechanisms of
persistence: logging (AOF) and snapshoting (RDB). At the same time, we collected
functional requirements from stakeholders and created a community around the
project on github: [MemKeyDB](https://github.com/memKeyDB/memKeyDB).

# Technical description

Antirez’ Redis uses jemalloc to allocate memory for the application. In
MemKeyDB, it was replaced with Memkind allocator which is based on jemalloc. It
manages allocations from DRAM and PMem by introducing memory kinds. Separate
`malloc` calls are used to allocate on both mediums. To simplify code
modification, it is very convenient to use another Memkind feature which is
`free` call common for every memory kind. Allocator itself can identify which
memory was used for a given pointer and free it properly. This allows us to
modify only the “allocation” part for a given structure without the need of
tracking origins of allocation until `free` is called.

```c
memkind_free(NULL, ptr);
```

Having two types of memories in the application, we used the size of the
allocation as a criterion for choosing which one to use. Structures whose size
is above a certain threshold are to be allocated from PMem as the larger medium
and smaller from DRAM:

```c
void *zmalloc(size_t size) {
    return (size < pmem_threshold) ? zmalloc_dram(size) : zmalloc_pmem(size);
}
```

The proportion of DRAM and PMem utilization is user configurable by defining
DRAM/PMem ratio parameter. It allows to select the best value for current
hardware and workload type. The dynamic threshold mechanism controls the
location of data. It monitors allocator statistics related to DRAM and PMem and
periodically adjusts the internal dynamic threshold. This balances utilization
of each media to be close to the target value configured for the application.
The algorithm also checks the rate and trend of the current DRAM/PMem ratio to
speed-up reaching the desired ratio.

# Redis Optimizations

Besides user data for storing keys and values, the application uses a lot of
internal structures. They are frequently allocated and deallocated, e.g. when a
new client connects to the Redis server. Since they are used as temporary
structures with limited total size compared to user data, they don’t affect DRAM
/ PMem ratio. For performance reason, it is always worth allocating them on
DRAM. This is done with simple code modification for “client” structure and a
lot of its side structures, e.g. buffers used for storing incoming data or used
for creating a list of objects to be sent to the client.

![Client optimization](/images/posts/memkeydb_client.png)

Other structures are metadata that describe user' data. They are usually very
small compared to keys and values but may influence performance when stored on
PMem. We introduced code optimizations related to:

- storing Redis object structure (robj) always in DRAM (except Embedded
  Strings),
- storing Main Redis Hashtable by default in DRAM. As the size of hashtable may
  be significant for scenarios when the dataset contains mostly small objects,
  there is a configuration option that allows to change the placement of the
  main hashtable to PMem.

For structures that are optimized to always be allocated on DRAM, it is
recommended to pass this piece of information to Memkind allocator. This
eliminates the need of identification where it was allocated on and speeds up
the execution.

# Memkind optimizations

Memkind is a general-purpose allocator, but for an application like Redis it was
also optimized by passing specific parameters during “configure” part:

- The number of arenas – for PMem kinds Memkind usually creates 4 x CPU arenas.
  For a single-threaded application, it can be limited to 1 arena. This speeds
  up scenarios when allocator statistics are gathered by iterating through all
  arenas.
- Lg_quantum – inherits the value from jemalloc config in Redis. It creates an
  additional allocation class which is very often used by Redis. This helps to
  increase memory utilization.

# Project location

Project is available here:
[https://github.com/memKeyDB/memKeyDB](https://github.com/memKeyDB/memKeyDB).

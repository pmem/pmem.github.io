---
title: Introduction to libmemkind
author: jschmieg
layout: post
identifier: libmemkind
---


# Introduction

One of the components of PMDK is libmemkind, a library which was developed to
simplify
usage of persistent memory in a volatile mode. There are NVDIMMs technologies,
such as Intel Optane DCPMM, that provides persistency, byte-addressability, but 
also a high capacity when compared with DRAM modules. By extension, they can be 
used as an augmentation of main memory and utilized by applications which
consume 
a large amount of memory and do not require persistency e.g. in-memory
databases, 
caching engines and scientific simulations.

# Libmemkind

According to the current persistent memory programming model, NVDIMMs are
exposed by operating system as devices on which user should create file-system.
It is necessary to have a way to provide this memory to
application / enable that application with a new memory. Libmemkind fills this
gap
by utilizing jemalloc on temporary files created on NVDIMMs and acts as a memory
allocator for applications. The new option of utilizing persistent memory as a
hotplugged memory region (KMEM DAX) is already available in Linux kernel
beginning from
version 5.1 and Memkind support for this feature will be described in the next
blog entry.
Libmemkind provides various memory pools called “kinds” for memories with
miscellaneous characteristics: DRAM, persistent memory and High-Bandwidth
Memory. This
allows partitioning a heap of an application between these kinds. On a system
equipped
with DRAM and NVDIMMs, it is possible to re-design an application in a way 
to store objects that are accessed frequently and require fast access in DRAM
while larger objects which are accessed less frequently can be stored on
Persistent
Memory. 

# Managing application heap

With libmemkind it is possible to allocate in DRAM by calling `memkind_malloc`
function with static kind `MEMKIND_DEFAULT` or `MEMKIND_REGULAR`:
```c
void * ptr_default = memkind_malloc(MEMKIND_DEFAULT, size);
```
and in persistent memory by calling the same function with dynamically created
pmem kind pointing to directory on FS DAX:
```c
struct memkind *pmem_kind = NULL;
memkind_create_pmem("/mnt/pmem", PMEM_MAX_SIZE, &pmem_kind);
void * ptr_pmem = memkind_malloc(pmem_kind, size);
```

Freeing allocated objects is done by calling:
```c
memkind_free(MEMKIND_DEFAULT, ptr_default);
memkind_free(pmem_kind, ptr_pmem);
```
To simplify the adoption of applications to many memory pools, it is worth using
another library feature: calling `free` without specifying a kind that was used
for the allocation. Library will automatically recognize if a given pointer
belongs to DRAM or PM. This precludes tracking each allocation origin in the
application:
```c
memkind_free(NULL, ptr_default);
memkind_free(NULL, ptr_pmem);
```

# Other features
Libmemkind provides also:
* rest of malloc-style API functions, 
* an automatically increased pool size,
* configuring memory policy: performance vs fragmentation,
* C++ bindings,
* choosing alternative heap manager (TBB).


# Documentation
The complete libmemkind manual can be found at 
[memkind.github.io](https://memkind.github.io/memkind/man_pages/memkind.html).


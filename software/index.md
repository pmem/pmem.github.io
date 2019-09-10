---
title: Software
---

#### ndctl

NDCTL is a collection of libraries and tools for managing the libnvdimm
sub-system in the Linux kernel. It includes the **ndctl** and **daxctl**
tools, as well as **libndctl** and **libdaxctl** libraries.

See the [NDCTL page](../ndctl/) for documention.

#### pmdk

The Persistent Memory Development Kit (PMDK) is a collection of libraries and tools.

See the [PMDK page](../pmdk/) for documentation and examples.

#### pmdk-convert

The **pmdk-convert** tool performs conversion of the specified pool
from the old layout versions to the newest one supported by this tool.

See the [pmdk-convert](../pmdk-convert/) for current documentation.

#### C++ bindings

The C++ bindings aim at providing an easier to use, less error prone
implementation of **libpmemobj** (from PMDK). The C++ implementation requires a compiler
compliant with C++11 and one feature requires C++17.

See the [C++ bindings page](../libpmemobj-cpp/) for documentation and examples.

#### pmemkv

**pmemkv** is a local/embedded key-value datastore optimized for persistent memory.
Rather than being tied to a single language or backing implementation,
**pmemkv** provides different options for language bindings and storage engines.

See the [pmemkv page](../pmemkv/) for available documentation.

#### libvmemcache

**libvmemcache** is an embeddable and lightweight in-memory caching solution.
It's designed to fully take advantage of large capacity memory, such as
persistent memory with DAX, through memory mapping in an efficient
and scalable way.

See the [libvmemcache](../vmemcache/) for current documentation.

#### memkind

The **memkind** library is a user extensible heap manager built on top of jemalloc
which enables control of memory characteristics and a partitioning of the heap
between kinds of memory.

Since v1.8 memkind includes PMEM kind which enables support for persistent memory.

See [libmemkind](http://memkind.github.io/memkind/) for more information.

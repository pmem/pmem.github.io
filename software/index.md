---
title: Software
---

#### ndctl

NDCTL is a collection of libraries and tools for managing the libnvdimm
sub-system in the Linux kernel. It includes the **ndctl** and **daxctl**
tools, as well as **libndctl** and **libdaxctl** libraries.

See the [ndctl page](../ndctl/) for documention.

#### pmdk

The Persistent Memory Development Kit is a collection of libraries and tools
for development of persistent memory-aware applications.

See the [pmdk page](../pmdk/) for documentation and examples.

#### pmdk-convert

The **pmdk-convert** tool provides an upgrade path for pools created with older
versions of pmdk.

See the [pmdk-convert page](../pmdk-convert/) for current documentation.

#### libpmemobj-cpp

The C++ bindings for libpmemobj (from pmdk) aim at providing an easier to use,
less error prone API for writing persistent memory-aware applications.

See the [libpmemobj-cpp page](../libpmemobj-cpp/) for documentation and
examples.

#### pmemkv

**pmemkv** is a local/embedded key-value datastore optimized for persistent
memory. Rather than being tied to a single language or backing implementation,
**pmemkv** provides different options for language bindings and storage engines.

See the [pmemkv page](../pmemkv/) for available documentation.

#### libvmemcache

**libvmemcache** is an embeddable and lightweight in-memory caching solution.
It's designed to fully take advantage of large capacity memory, such as
persistent memory with DAX, through memory mapping in an efficient
and scalable way.

See the [vmemcache page](../vmemcache/) for current documentation.

#### memkind

The **memkind** library is a user extensible heap manager built on top of
jemalloc which enables control of memory characteristics and a partitioning
of the heap between kinds of memory.

Since v1.8 memkind includes PMEM kind which enables support for persistent
memory.

See [memkind page](http://memkind.github.io/memkind/) for more information.

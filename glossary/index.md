---
title: Glossary
---

#### Glossary of Terms Used on This Site

It seems the industry is _mostly_ aligned on these terms, but not 100%.
Taken literally, the terms _Non-Volatile Memory_ and _Persistent Memory_
would seem to be the same thing.  But in contemporary usage, the terms
are different.  Here's how these terms are used by this project.

**Non-Volatile Memory (NVM)**

This term refers to the category of solid-state storage devices,
from the [Flash Memory](https://en.wikipedia.org/wiki/Flash_memory)
used in Solid State Disk (SSD) drives, to battery-backed up
memory cards, to the emerging non-volatile technologies such
as [3D XPoint](https://en.wikipedia.org/wiki/3D_XPoint),
[PCM](https://en.wikipedia.org/wiki/Phase-change_memory),
[Memristor](https://en.wikipedia.org/wiki/Memristor),
[STT-RAM](https://en.wikipedia.org/wiki/Spin-transfer_torque), etc.

In this project we use the term _NVM_ to refer to the entire class
of non-volatile technologies.  For the faster varieties, where direct,
load/store access is appropriate, read on...

**Persistent Memory**

This term refers to memory that maintains its contents across
power failure.  We specifically refer to memory technologies that
are fast enough so it is _reasonable to stall a CPU load instruction_,
waiting for a load directly from persistent memory.

By this definition, the NAND Flash used in SSDs would not qualify
as persistent memory when accessed directly, but battery-backed DRAM
or (hopefully) the emerging non-volatile memory technologies would
qualify for direct access.

**Persistent Memory Development Kit**

[PMDK][pmdk] is a project with the goal of making persistent memory
programming easier.  It currently supports **ten libraries**,
targeted at various use cases for persistent
memory, along with language support for
**C**, **C++**, **Java**, and **Python**,
tools like the **pmemcheck**
plug-in for valgrind, and an increasing body of documentation, code examples,
tutorials, and [blog entries](/blog/).  The libraries are tuned and validated
to **production quality** and issued with a license that allows their use in
both open- and closed-source products.  And the project continues to grow as we
learn about new use cases.

Since the programming model for persistent memory is based on
memory-mapped files, the PMDK libraries will work correctly on top of any
NVM technology (like an SSD with a traditional file system on it).  However,
it is optimized for the fine-grained, load/store persistence provided
by persistent memory, so the performance will on traditional storage
will be non-optimal, as operations like cache line flushes get turned
into page writes to a block device on traditional storage.  This means you
can use storage (like your laptop's disk) to try out the PMDK libraries
and you will get correct results.  But you won't see performant
results unless you use actual persistent memory.

[pmdk]: https://pmem.io/pmdk/ "Persistent Memory Development Kit"

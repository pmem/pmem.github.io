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
from the [Flash Memory](http://en.wikipedia.org/wiki/Flash_memory)
used in Solid State Disk (SSD) drives, to battery-backed up
memory cards, to the emerging non-volatile technologies such
as [PCM](http://en.wikipedia.org/wiki/Phase-change_memory),
[Memristor](http://en.wikipedia.org/wiki/Memristor),
[STT-RAM](http://en.wikipedia.org/wiki/Spin-transfer_torque), etc.

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

**NVM Library**

Given the above definitions, one might ask why the current project
is called the _NVM Library_ and not the _Persistent Memory Library_.
The goal is to design the [NVM Library](/nvml/) so it can leverage the direct
load/store capabilities of persistent memory, but also work on other NVM
technologies (albeit not as optimally).
Since the programming model for persistent memory is based on
memory-mapped files, the library will work correctly on top of any
NVM technology (like an SSD with a traditional file system on it).

---
title: Vmem is split out of PMDK
author: kilobyte
layout: post
identifier: vmem-split
---

### Introduction

We have just split **libvmem** and its companion **libvmmalloc** out of the
PMDK tree.  They now live in a
[separate repository](https://github.com/pmem/vmem/), and will follow their
own release cadence.  And, as these libraries are considered mature and
finished, no new releases are planned once the split has been tested and
tagged -- except for defects and new requirements of underlying platforms.

### Further development

**libvmem** remains the only way to use filesystem-managed persistent memory
for volatile allocations on Windows.

On Linux, though, you are better served by
[memkind](http://memkind.github.io/memkind/) instead of **libvmem** in new
code -- it provides extra features such as NUMA awareness and handling of
other kinds of memory.

As for **libvmmalloc**, there's no direct equivalent yet, but incoming
kernel features will allow redirecting an unported program to volatile
persistent memory by attaching that memory to a separate NUMA node you can
then assign your program to with `numactl -m` or `numactl --preferred`.

### Effects on PMDK

Besides separating out unrelated modes of use (PMDK is meant for
**Persistent** usage), **libvmem** included a different build system and
a testsuite, frustrating maintenance of PMDK and making clean-ups hard.
Splitting VMEM out already revealed an issue fixing which sped up compiles
by a factor of 4.

---
title: Memkind support for KMEM DAX option
author: michalbiesek
layout: post
identifier: memkind-support-for-KMEM-DAX-option
---

### Introduction

Linux kernel version 5.1 brings in [support][kernel-patch] for the volatile-use of persistent memory
as a hotplugged memory region (**KMEM DAX**).
From the perspective of OS, persistent memory will be seen as a separate NUMA node(s).
[libmemkind][memkind-release] extends its API by new kinds which allow for automatic detecting and allocating to
persistent memory.

### Requirements

At least kernel 5.1 version with enabled **KMEM DAX** option

{% highlight console %}
$ make nconfig
	Device Drivers --->
		-*- DAX: direct access to differentiated memory --->
			<M>   Device DAX: direct access mapping device
			<M>     PMEM DAX: direct access to persistent memory
			<M>     KMEM DAX: volatile-use of persistent memory
			< >   PMEM DAX: support the deprecated /sys/class/dax interface
{% endhighlight %}

[ndctl and daxctl][ndctl-release] with version 66 or later.

### System Configuration

Using NVDIMM as an extension of DRAM needs proper configuration with `ndctl` and `daxctl`.
A Reconfiguration of Device-DAX depends on dax-bus device model.
Kernel should support `/sys/bus/dax model`. To migrate it from
`sys/class/dax` to `/sys/bus/dax model` please use [daxctl-migrate-device-mode][daxctl-migrate-device-model].

### Reconfigure dax devices

To see the list of available NUMA nodes on the system you could use `numactl`.
An example of initial configuration is presented below:

{% highlight sh %}
$ numactl -H

available: 2 nodes (0-1)
node 0 cpus: 0 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 56 57 58 59 60 61 62 63 64 65 66 67 68 69 70 71 72 73 74 75 76 77 78 79 80 81 82 83
node 0 size: 80249 MB
node 0 free: 68309 MB
node 1 cpus: 28 29 30 31 32 33 34 35 36 37 38 39 40 41 42 43 44 45 46 47 48 49 50 51 52 53 54 55 84 85 86 87 88 89 90 91 92 93 94 95 96 97 98 99 100 101 102 103 104 105 106 107 108 109 110 111
node 1 size: 80608 MB
node 1 free: 71958 MB
node distances:
node   0   1
  0:  10  21
  1:  21  10
{% endhighlight %}

To [create a namespace][ndctl-create-namespace] in Device-DAX mode as standard memory from all the available capacity of NVDIMM:

{% highlight sh %}
$ ndctl create-namespace --mode=devdax --map=mem
{% endhighlight %}

To [list DAX devices][daxctl-list]:

{% highlight sh %}
$ daxctl list

[
  {
    "chardev":"dax1.0",
    "size":539016298496,
    "target_node":3,
    "mode":"devdax"
  }
]
{% endhighlight %}

To [reconfigure][daxctl-reconfigure-device] DAX device from `devdax` mode to a `system-ram-mode`:

{% highlight console %}
$ daxctl reconfigure-device dax1.0 --mode=system-ram
{% endhighlight %}

With this operation persistent memory could be seen as separate NUMA and be used as volatile RAM.
For configuration below persistent memory NUMA node is Node 3 (NUMA node to which does not belong any logical CPU)

{% highlight sh %}
$ numactl -H

  available: 3 nodes (0-1,3)
node 0 cpus: 0 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 56 57 58 59 60 61 62 63 64 65 66 67 68 69 70 71 72 73 74 75 76 77 78 79 80 81 82 83
node 0 size: 80249 MB
node 0 free: 68309 MB
node 1 cpus: 28 29 30 31 32 33 34 35 36 37 38 39 40 41 42 43 44 45 46 47 48 49 50 51 52 53 54 55 84 85 86 87 88 89 90 91 92 93 94 95 96 97 98 99 100 101 102 103 104 105 106 107 108 109 110 111
node 1 size: 80608 MB
node 1 free: 71958 MB
node 3 cpus:
node 3 size: 512000 MB
node 3 free: 512000 MB
node distances:
node   0   1   3
  0:  10  21  28
  1:  21  10  17
  3:  28  17  10
{% endhighlight %}

### Support in memkind library

Libmemkind supports the **KMEM DAX** option by three variants.
For a better description of memory policies from these variants  see animations below.
With an example configuration, like:

{% highlight sh %}
$ numactl -H

  available: 4 nodes (0-3)
node 0 cpus: 0 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 56 57 58 59 60 61 62 63 64 65 66 67 68 69 70 71 72 73 74 75 76 77 78 79 80 81 82 83
node 0 size: 80249 MB
node 0 free: 68309 MB
node 1 cpus: 28 29 30 31 32 33 34 35 36 37 38 39 40 41 42 43 44 45 46 47 48 49 50 51 52 53 54 55 84 85 86 87 88 89 90 91 92 93 94 95 96 97 98 99 100 101 102 103 104 105 106 107 108 109 110 111
node 1 size: 80608 MB
node 1 free: 71958 MB
node 2 cpus:
node 2 size: 1026048 MB
node 2 free: 1026048 MB
node 3 cpus:
node 3 size: 512000 MB
node 3 free: 512000 MB
node distances:
node   0   1   2   3
  0:  10  21  17  28
  1:  21  10  28  17
  2:  17  28  10  28
  3:  28  17  28  10
{% endhighlight %}

The following figure corresponds to it:

![memkind_dax_kmem](/assets/memkind_dax_kmem_config.png)

Note:
- Image is simplified to present only 2 CPUs: CPU 0 which belong to Node 0 and CPU 30 which belong to Node 1
- Numbers in squares corresponds to NUMA node distances between Nodes 0/1 and all of the Nodes
- Node 0 and Node 1 are based on DRAM memory where Node 2 and Node 3 are based on NVDIMM memory
- Moving physical memory to swap space is limited only to one Node
- To simplify examples below we assume that process uses only CPU 0

**MEMKIND_DAX_KMEM**:
This is the first variant where the allocation comes from the closest persistent memory NUMA node
at the time of allocating.

![memkind_dax_kmem](/assets/memkind_dax_kmem.gif)

The process runs only CPU 0, CPU which is assigned to Node 0. Node 2 is the closest persistent memory NUMA node to Node 0,
therefore using **MEMKIND_DAX_KMEM** results in taking memory only from Node 2. If there is not enough free memory in Node 2
to satisfy an allocation request, inactive pages from Node 2 are moved into the swap space - freeing up the memory for
**MEMKIND_DAX_KMEM** use case.

**MEMKIND_DAX_KMEM_ALL**:
This is the second variant where the allocation comes from the closest persistent memory NUMA node
available at the time allocating.

![memkind_dax_kmem_all](/assets/memkind_dax_kmem_all.gif)

A similar situation to scenario presented in **MEMKIND_DAX_KMEM** except that when there is not enough free
memory in Node 2 to satisfy the allocation request, an allocation pattern switch to Node 3. When available free space is
exhausted in Node 3 - swap space is used.

**MEMKIND_DAX_KMEM_PREFERRED**:
This is the third variant where the allocation comes from the closest persistent memory NUMA node at the time
of allocating. If there is not enough memory to satisfy the request the allocation will fall back on other memory
NUMA nodes.

![memkind_dax_kmem_preferred](/assets/memkind_dax_kmem_preferred.gif)

Again the allocation starts from Node 2. When there is not enough free memory in Node 2, the allocation switches to other
nodes in order of increasing distance from the preferred node based on information provided by the platform firmware:
Node 0, Node 3 and ending in Node 1. When available free space is
exhausted in Node 1 - swap space is used.


**Caveats**

For **MEMKIND_DAX_KMEM_PREFERRED**, the allocation will not succeed if two or more
persistent memory NUMA nodes are in the same shortest distance to the same CPU on which the process is eligible to run.
A check on that eligibility is done upon starting the application.

### Environment variable

**MEMKIND_DAX_KMEM_NODES** is the environment variable - a comma-separated list of NUMA nodes that
are treated as persistent memory. This mechanism is dedicated to overwriting the automatic
mechanism of detection of the closest persistent memory NUMA nodes.

### Example usage with memkind library

Using of new kinds is similar to the usage presented in the previous [post][memkind-basic-usage-post]
with one exception that kinds presented in this post do not need to be created first with `memkind_create_pmem()` method;

With the usage of `libmemkind` it is possible to distinguish the physical placement allocation:

- **MEMKIND_DAX_KMEM** allocation goes to NVDIMM
- **MEMKIND_REGULAR** allocation goes to DRAM


[kernel-patch]: https://patchwork.kernel.org/cover/10829019/
[ndctl-release]: https://github.com/pmem/ndctl/releases
[daxctl-migrate-device-model]: https://pmem.io/ndctl/daxctl-migrate-device-model.html
[daxctl-list]: https://pmem.io/ndctl/daxctl-list.html
[daxctl-reconfigure-device]: https://pmem.io/ndctl/daxctl-reconfigure-device.html
[ndctl-create-namespace]: https://pmem.io/ndctl/ndctl-create-namespace.html
[memkind-release]: https://github.com/memkind/memkind/releases/tag/v1.10.0
[memkind-basic-usage-post]: https://pmem.io/2019/12/12/libmemkind.html

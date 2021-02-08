---
title: Memkind support for heterogeneous memory attributes
author: michalbiesek
layout: post
identifier: hmat_memkind
---

### Introduction

Memkind is a library that provide support for Persistent Memory, but it is not limited only for
it. It is a general solution designated for the platform with heterogeneous memory.

But before we go to heterogenous memory itself, let's start with a short recap about [NUMA][kernel-numa].
Primary the NUMA concept aims the problem of the dynamic extension of CPU count-per-socket and system memory.
Before NUMA, commonly used model as uniform memory access (UMA), where all the processors share the physical memory uniformly.
With the UMA approach and rapid growth of numbers of processors and memory in multi-socket machines, platforms now faced scalability problems.
Single shared bus between all CPU(s) and DRAM memory become a bottleneck, as each CPU decreases the available bandwidth.
To handle each new devices, the length of the bus must be extended, what in turn impacts the latency.

The overcome the challenges mentioned above, engineers decide to introduce a new strategy: None Unified Memory Access(NUMA).
Instead of providing a single shared bus, the concept proposes a solution to divide the hardware resources into `NUMA Nodes` abstraction.
Depending on the exact implementation on multi-sockets platforms, NUMA Node could be exposed CPU(s) and memory placed inside the same socket.
Accessing the memory placed on the same socket (`local memory access`) is faster since it avoids additional overhead from interconnection bus,
like the memory access between two sockets (`remote memory access`).

<figure class="image">
  <img src="/assets/numa_overview.png" alt="NUMA overview">
  <figcaption>Figure 1. NUMA overview.</figcaption>
</figure>

The operating system made the memory allocation decision among other factors based on `NUMA distance.` The distance represents
relative memory latency between NUMA nodes, and is based on hardware configuration - System Locality Information Table (SLIT) and
System Resource Affinity Table (SRAT) presented in [ACPI][ACPI-doc].
NUMA aware system Operating System can still consume all possible memory, but the OS will optimize it for local memory access.
In practice, this means that memory allocation will come from the NUMA Node with the smallest distance from the CPU of the calling thread.

NUMA memory access pattern could be decribed as **heterogenous memory access**.

But today's NUMA Node concept is vague compared to the original one. Extension of different Processing Units
provides **heterogenous computing**, but the memory doesn't stay behind in this race. The NUMA Node itself now
doesn't have to be a couple between DRAM and CPU.
For example, we could have a memory-only NUMA Node (hot-added memory device), the NUMA Node, which cannot
generate memory requests and contains only memory.
From a processing perspective, we could have a different type of memory initiators: today, the CPU can perform
memory allocation request and other processing units as GPU or FPGA. The same applies to memory targets as this
could be different memory physical mediums like DRAM, PMEM, and HBW.

This post focuses on the last aspect of mentioned varieties - on **heterogenous memory**.

<figure class="image">
  <img src="/assets/heterogeneous_architecture.png" alt="PMEM KMEM-DAX">
  <figcaption>Figure 2. Heterogeneous architecture overview.</figcaption>
</figure>

### Memory technology vs memory property

Different physical medium offers other memory performance characteristics like latency, bandwidth or capacity depending on media type and memory bus.
From the software layer, application requirements against tasks are often related not to the underlying hardware itself but to mitigate specific bottlenecks related to the memory property. To overcome bounds, we use memory-aware programming - this approach is already used in different [interfaces][openmp].
Expected memory behavior doesn't have to be related to which medium currently supports it, this metric in different memory topoology.
This approach allows us to change the point of view from hardware into **memory property** perspective. With different abstraction types described further in this post, the memkind library could support more hardware-agnostic interfaces.

The following table contains a comparison between hardware and expected memory property associated with it:

| Physical medium      | Memory property |
| -------------------- | ----------------|
| DRAM                 | Latency         |
| [MCDRAM][MCDRAM]     | Bandwidth       |
| PMem                 | Capacity        |

<figure class="image">
  <img src="/assets/hazelcast_3.png" alt="PMEM KMEM-DAX">
  <figcaption>Figure 3. Memory architecture overview based on physical medium.</figcaption>
</figure>

And presented same view in different form:

<figure class="image">
  <img src="/assets/memkind_memory_view.png" alt="CAPACITY LATENCY VIEW">
  <figcaption>Figure 4. Memory architecture overview based on memory property.</figcaption>
</figure>

To follow this belief we extend memkind library with additional semantics.

### Prerequisites

[ACPI-6.2][ACPI-doc] extends the ACPI standard about Heterogeneous Memory Attribute
Table (HMAT) table. HMAT provides a way for the firmware to propagate to Operating System memory hardware
characteristics - in details, it describes bandwidth and latency from the  **initiator of memory requests**
(a processor or an accelerator) to any **memory target**.

Besides support for HMAT in hardware, support is also required on the Linux kernel layer*:

{% highlight console %}
$ make nconfig
	Power management and ACPI options --->
		[*] ACPI (Advanced Configuration and Power Interface) Support --->
			-*-   NUMA support
			[*]     ACPI Heterogeneous Memory Attribute Table Support
{% endhighlight %}

* Option **CONFIG_ACPI_HMAT** is available since version 5.5

To utilize information provided by the OS memkind use [hwloc library][hwloc] therefore, it is additional mandatory dependency
if you want to use new features offer by memkind.

### Memory kind summary

With [memkind 1.11.0 release][memkind-release] comes support for previously mentioned memory property semantics in following API:

| New API                                       | Associated Memory property |
| ----------------------------------------------| -------------------------- |
| **MEMKIND_HIGHEST_CAPACITY**	                |   Capacity                 |
| **MEMKIND_HIGHEST_CAPACITY_PREFERRED**		    |   Capacity                 |		
| **MEMKIND_HIGHEST_CAPACITY_LOCAL**            |   Capacity                 |
| **MEMKIND_HIGHEST_CAPACITY_LOCAL_PREFERRED**  |   Capacity                 |
| **MEMKIND_LOWEST_LATENCY_LOCAL**              |   Latency                  |
| **MEMKIND_LOWEST_LATENCY_LOCAL_PREFERRED**    |   Latency                  |
| **MEMKIND_HIGHEST_BANDWIDTH_LOCAL**           |   Bandwidth                |
| **MEMKIND_HIGHEST_BANDWIDTH_LOCAL_PREFERRED**	|   Bandwidth                |

The new semantics based on comparison specific memory property between different NUMA Nodes presented on the platform and choosing
the best attribute.

### What's new 

**Locality**

So far in memkind, we determined the locality as the nearest NUMA based on the shortest NUMA distance metrics. 
In this strategy, we miss information about Socket and NUMA Node relation. In the case of non-symmetrical memory topology,
where for example, PMEM is placed only on one socket, and we would like to avoid remote memory access we could use
one of the memory kinds from the new API to allocate from memory in local NUMA domain.

<figure class="image">
  <img src="/assets/memkind_single_pmem_medium_view.png" alt="non-symetric-medium-view">
  <figcaption>Figure 5. Memory architecture overview based on physical medium.</figcaption>
</figure>

Using `MEMKIND_HIGHEST_CAPACITY_LOCAL` memkind will allocate to highest capacity Node inside socket:

<figure class="image">
  <img src="/assets/memkind_single_pmem_property_view_alloc.png" alt="non-symetric-property-view">
  <figcaption>Figure 6. Memory architecture overview based on memory property.</figcaption>
</figure>

**New API**

Memory property like bandwidth and latency depends on the specific initiator of the memory request. This results in multidimensional analysis based on the different NUMA Node relationships and [performance characteristics][numa-performance]. Based on this fact, with the new API, memkind addresses different scenarios of potential usage in more complicated memory topologies.

**High Bandwidth**

To help using legacy API: `MEMKIND_HBW*` and determine what value of memory bandwidth could be treated as high bandwidth, we introduced
`MEMKIND_HBW_THRESHOLD` - a threshold, from which NUMA Node is identified as High Bandwidth Memory NUMA Node.

### What’s Coming Next

NUMA Node concept evolves, and it’s much different compared to the past. No longer, it focuses only on local and remote access. The modern approach based on a concept with the initiator of a memory request and target of a memory request. Both initiator and target are characterized by different performance properties. No longer if some entity can make an allocation request means that it has to be CPU. A similar rule applies to memory – if the processing unit requests memory, it doesn’t mean that memory must come from DRAM.

We believe that more complex memory architectures will be widely available in the future.

With this perspective, a one-dimensional approach based on NUMA distance metrics needs to be revised.
Hardware/Firmware architects already defined solutions with HMAT, which means that HMAT aware platforms expect to appear in the future.
The support for them is available in the kernel.

On the application layer, libmemkind tries to measure those challenges with new features presented in this post.

[kernel-numa]: https://www.kernel.org/doc/html/latest/vm/numa.html
[ACPI-doc]: https://uefi.org/sites/default/files/resources/ACPI_6_2.pdf
[openmp]: https://www.openmp.org/spec-html/5.1/openmpsu60.html#x87-970002.13.1
[MCDRAM]: https://software.intel.com/content/www/us/en/develop/blogs/an-intro-to-mcdram-high-bandwidth-memory-on-knights-landing.html
[hwloc]: https://www.open-mpi.org/software/hwloc/v2.3/
[memkind-release]: https://github.com/memkind/memkind/releases/tag/v1.11.0
[numa-performance]: https://www.kernel.org/doc/html/latest/admin-guide/mm/numaperf.html

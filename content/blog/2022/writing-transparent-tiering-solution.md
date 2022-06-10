---
# Blog post title
title: "Writing Transparent Tiering Solution"

# Blog post creation date
date: 2022-06-01T13:29:10+02:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: "Writing custom transparent tiering solution - description of available options and trade-offs between them"

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ""

# Blog post thumbnail
# eg: image: "/images/posts/my_blog_thumbnail.png"
image: ""

# Blog post author
author: "Maciej Paczocha"

# Categories to which this blog post belongs
blogs: ['Memory Tiering']
# Blog tags
tags: ["Memory", "Tiering", "CXL", "PMEM", "NUMA balancing", "memory balancing"]

# Blog post type
type: "post"
---

This article is intended for those who would like to learn how tiering/numa
balancing or memory profiling solutions work under the hood. We will focus
on a high-level overview of how tiering/numa balancing could be designed,
instead of dissecting any particular solution. This approach means that
the readers can also learn some general knowledge on topics such as Linux
API, hardware-accelerated performance monitoring or architecture of Linux apps.

### Key components

Before we delve into details, we will shortly discuss key requirements and
map them to components of the software we want to design. The basis of
a tiering system consists either of making a decision about the target tier
in allocation time, or of monitoring which pages to move and moving them.

A user might want to use features such as:

- per-application configuration,
- configuration of how aggressively balancing should be performed,
- auto-configuration, auto profiling and tuning of an application,

It might be important for them:

- not to recompile kernel or introduce new kernel modules,
- to have a portable solution - not hardware or kernel-specific.

In order to satisfy those requirements, the basic functionalities must
be implemented:

- handling configuration,
- monitoring hotness or other metrics,
- decision-making,
- page movement (or intelligent data placement).

All of them can be implemented either in user space, using the libnuma library,
or directly in kernel. We will explore some of the possible design decisions
and how they affect the aforementioned functionalities.

### Options - user space vs kernel space

One of the first decisions to make is to decide on whether the solution should
be located in user space or in kernel space. Kernel space might seem to be the
obvious answer, as there are multiple reasons to do this, among others:

- access to MMU and PAGE_FAULT handling:
    - this is how automatic NUMA balancing (kernel) checks accesses,
- reduced context switch overhead:
    - certain operations can only be done from kernel space,
    - calling syscalls in a loop is not that efficient,
- better integration with default kernel balancing possible,
- can use data structures from kernel, lower overhead:
    - no need to duplicate data between userspace and kernelspace in order to
    track the state of pages.

On the other hand, user space has the following strong points:

- safety: a bug in user space will not shut the whole machine down or destroy
data of other processes,
- ease and speed of experimentation: no need to compile custom kernel or reboot
the machine; a separate kernel module is not that easy to experiment
with neither,
- portability: a user space solution should work with many different production
systems, typically those who meet the minimum up-to-date criteria,
- access to allocations - lower granularity than OS, which can only move pages.

As the reader might see, the answer to where to implement the solution is not
obvious. There are solutions available and already deployed on production
systems that handle
[balancing in kernel](https://documentation.suse.com/sles/15-SP1/html/SLES-all/cha-tuning-numactl.html),
and in user space, available and on different stages of development.

There is also another question - can user and kernel space solutions work
together? There are plenty of opportunities for that, e.g. objects of similar
hotness might be placed on the same pages in user space, so that kernal can
efficiently move data between different tiers. The placement can be done
either in allocation time or in runtime, by merging partially-empty pages.
There is always a question of cost and of possible performance gain -
performing any logic in ```malloc()``` can impact performance a lot, but so
can misallocating the data to unsuitable tier.

#### Handling configuration

It might seem to be a trivial thing to do, but the mechanism of configuring
the application also requires some thought. In user space, a text config file
or arguments passed via command line might be enough, but a kernel solution
would require a more complicated config handling, either via additional system
calls, or by a special file, e.g. writing to which would trigger
re-configuration of tiering.

A user space solution (or a user space component that handles communication
with kernel) might even contain a full-scale TCP or CLI client, with an
easy-to-configure graphical user interface. In this case, only imagination
is the limit. And time. And human resources.

#### Tracking hotness

There are multiple ways of tracking hotness of pages, but some of them are
easier to deal with in kernel space. A good example is the hotness tracking
mechanism
[from the Linux kernel](https://documentation.suse.com/sles/15-SP1/html/SLES-all/cha-tuning-numactl.html),
which works by periodically unmapping pages and handling hint page faults.
Even though such handling might be
[possible in user space](https://lwn.net/Articles/550555/#:~:text=Page%20fault%20handling%20is%20normally,with%20data%20from%20secondary%20storage.),
it comes with a great deal of issues ranging from additional overhead and low
performance to possible data races. It is noteworthy that this solution lets
the developer check on which CPU the process was executed and migrate the data
between CPU sockets instead of just between DRAM and PMEM or CXL - this is the
basis of kernel numa balancing.

Another way to monitor page accesses is via hardware mechanisms originally
destined for profiling applications, such as
[Intel PEBS](https://easyperf.net/blog/2018/06/08/Advanced-profiling-topics-PEBS-and-LBR#last-branch-record-lbr).
It is worth noting that, in this case, the information about accesses comes
with some delay - first, an access occurs, then it's stored in a buffer and,
after some time, the event is processed by the user. PEBS also exposes info
about the core the process was executed on. These features, however,
are hardware specific and might not be available on all platforms or might
not be supported by virtual machines.

Linux kernel provides an API that can be used to track hotness as well. The
files to check are ```/sys/kernel/mm/page_idle/bitmap```
and ```/proc/kpageflags```. Both, the
[former](https://www.kernel.org/doc/Documentation/vm/idle_page_tracking.txt)
and the
[latter](https://www.kernel.org/doc/html/latest/admin-guide/mm/pagemap.html?highlight=kpageflags)
work by setting pages status to IDLE and checking when the status changes.
The good thing about it is that it's hardware-agnostic, but might come with
various performance. Also, not every kernel has support for this API enabled.

Finally, kernel 5.15 added support for
[DAMON](https://www.phoronix.com/scan.php?page=news_item&px=DAMON-For-Linux-5.15):
Data Access MONitor that was created for the exact purpose of *BA DUM TSS*
monitoring data access.

##### Other metrics

For some it may seem counterintuitive, but page access does not always cost us
the same amount of time. Modern hardware can do wonders when it comes to IO
optimization -
[out-of-order execution](https://en.wikipedia.org/wiki/Out-of-order_execution)
can perform the required loads before the memory is actually needed, so that
the CPU is not stalled. Modern CPUs take advantage not only of multiple cores,
but also of
[instruction-level parallelism](https://en.wikipedia.org/wiki/Instruction-level_parallelism).
[Branch prediction](https://en.wikipedia.org/wiki/Branch_predictor) and
[instruction pipelineing](https://en.wikipedia.org/wiki/Instruction_pipelining)
in general are other things that can sometimes affect performance of a system
much more than memory bandwith or latency.

The great thing is that all these features are an integral part of modern CPUs
and they all automagically work behind the scenes. Of course, a developer
conscious of their existence can usually help the compiler optimize the code
better and make better programs, as this is the case here.

A sample statistic that we can use is
[Pressure Stall Information](https://www.kernel.org/doc/html/latest/accounting/psi.html)
(PSI). The three special files located under ```/proc/pressure``: ```cpu```,
```io``` and ```memory``` show how long a given process was stalled due to
each of the resources in a given time window. We can incorporate this statistic
to give more lower latency memory to a process that really needs it - the one
that is stalled due to IO, instead of the one that is bottlenecked on the CPU.
This metric can be combined with the cgroups interface to monitor particular
processes or groups of processes;
[PSI has been successfully used for the purpose of memory offloading](https://engineering.fb.com/2022/06/20/data-infrastructure/transparent-memory-offloading-more-memory-at-a-fraction-of-the-cost-and-power/).

We can also monitor bandwith usage of given tiers to keep some specified
balance between them or use some other relevant
[hardware performance counter](https://en.wikipedia.org/wiki/Hardware_performance_counter)
that noone else has ever imagined using for this purpose.

Last but not least, the obvious one - memory usage. We can check how much
memory is consumed by an application or a whole system and offload some of that
capacity to lower tiers, e.g. by allocating new data directly in PMEM or by
proactively demoting the data. We can also use that information to keep some
particular ratio between tiers, as this is done in Memkind, via STATIC_RATIO
and DYNAMIC_THRESHOLD policies.

#### Decision-making

The most straight-forward way to decide which pages should be promoted or demoted
would be to adapt some well-known caching algorithm, such as
[LRU](https://en.wikipedia.org/wiki/Cache_replacement_policies#Least_recently_used_(LRU)).
The algorithm can be adapted e.g. by keeping two LRU queues, one for active
pages and the other one for inactive and by swapping the most recently used
page on PMEM with the least recently used on DRAM, when appropriate. This is
the approach used by the Linux kernel.

What is important is not really when the page was used for the last time, but
when and how often it would be used in the future. The person who knows such
thighs best is probably either the program developer, whom we cannot ask (per
requirement - tiering was supposed to be transparent), or the end user. Even
if we could convince the end user to give us some hint on when they will
access their data, the server-side implementation of this feature in existing
production systems would take a lot of time and resources. Let's just simplify
this issue and assume that we do not know the next data access time.
The only thing we can do is try to forcast the accesses. A measure of how often the data was accessed in the
past is a good start. Maybe there was some pattern in the data usage?

This part of the system is largely based on heuristics, so we will leave a
more in-depth consideration to the user.

#### Page movement

Moving pages is a way to improve memory access performance when the allocation
decision heuristic fails, is absent, or access pattern changes over time.

![Memory management - illustration](/images/posts/allocator_mm.png "Illustration of memory management - dependencies between user space, kernel space and hardware")

Page movement itself might boil down to a single function call, e.g. from the
libnuma library, but there is also a question of what we really want to do.
Do we want to move the page, or assure it stays in the moved place forever? Or,
maybe, we would like to specify which nodes are acceptable and which ones are
not - this might be useful e.g. to pin page A to DRAM, page B to PMEM and
page C to CXL, without specifying the particular nodes, and let the auto numa
balance the data between nodes of the same memory type, but of different
distance to the executing CPU.

![mbind vs move_pages](/images/posts/tiering_movement.png "Illustration of memory management - move_pages vs VMA")

Modifying VMA policy might sound great, but specifying a different policy for
a chunk of memory adds additional
[VMA](https://www.oreilly.com/library/view/linux-device-drivers/9781785280009/4759692f-43fb-4066-86b2-76a90f0707a2.xhtml).
Let's imagine a contiguous address space of 10 pages, which are entirely
located in DRAM and share the same policy. At some point in time, the page
number 3 is moved to PMEM using ```mbind```. After the operation is executed,
there would be 3 VMAs instead of one: the first one for pages 0-2, one
for page 3 and the last one for pages 4-9. The number of VMAs can grow
over time, which adds memory overhead. Another thing to bear in mind is that
there is a per-process cap on the number of VMAs, accessible via
```sysctl vm.max_map_count``` - if this number is exceeded, a syscall that
would create additional VMA, e.g. ```mmap()``` or ```mbind()```, will fail.

Just a short recap - the policy can be changed from user space with
```mbind()```; pages can be moved in user space without changing the policy
using ```numa_move_pages()```. Both functions are a part of the
[libnuma](https://man7.org/linux/man-pages/man3/numa.3.html) library.

### User space solution: daemon or LD_PRELOAD

In the case of user space solution, another decision needs to be made. Will it
be run in the same address space as the tiered application?

Running tiering in the same address space can be achieved by using the
LD_PRELOAD functionality and by overloading the default ```malloc```
or mmap to add tracking of used addresses. This comes with the
additional profit of having fine-grained access to the data and enables
treating different mallocs differently, e.g. by grouping allocations by size,
time of allocation, place in code, etc. The developer still has to decide,
what kind of function calls they want to hijack - do they want to provide
memory allocation themselves by handling ```malloc()``` and ```free()```,
or is ```mmap``` enough for their required functionalities?

![daemon vs ld_preload](/images/posts/tiering_ld_preload_vs_daemon.png "Illustration of page movement - daemon vs LD_PRELOAD")

Another solution would be to run a separate process with superuser privilege,
which checks mapped regions of other programs. This comes with the perk
of being able to tier the memory of multiple applications at the same time,
while specifying exact policies for each one of them, or by creating a process
hierarchy similar to [cgroups](https://en.wikipedia.org/wiki/Cgroups),
not to mention the fact that the tiered applications could continue working
even if the daemon crashes.

In this case, the daemon has to query the OS to check the address space of each
program that it wants to perform tiering on. The tiered process communicates
with kernel the same way it would without any tiering; the tiering process only
has access to the information that the OS exposes, e.g. via
```/proc/<pid>/maps```. As noted before, super user privilege is required
to move pages of a different process.

## Summary

Creation of a memory tiering system requires making numerous design decisions
and multiple trade-offs which were briefly outlined in this article.
I hope that even the readers who are not interested in writing such a system
themselves found it interesting to learn how these systems might work and that
they have broadened their knowledge about Linux.

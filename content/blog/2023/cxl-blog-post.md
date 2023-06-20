---
# Blog post title
title: "Exploring the Software Ecosystem for Compute Express Link (CXL) Memory"

# Blog post creation date
date: 2023-05-25T13:46:31+02:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: ""

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ""

# Blog post thumbnail
# eg: image: "/images/posts/my_blog_thumbnail.png"
image: ""

# Blog post author
author: "Piotr Balcer"

# Categories to which this blog post belongs
blogs: ['CXL']
# Blog tags
tags: ["Memory", "CXL", "PMEM", "Memkind"]

# Blog post type
type: "post"
---


## CXL Software ecosystem

The Compute Express Link (CXL) is going to be a transformative new technology
in the heterogeneous memory space. While the transition from
Persistent Memory (PMem) to CXL.mem may seem challenging at first, developers who have
optimized their applications for PMem will find that no significant changes
may be required. In this article, we will explore the CXL software ecosystem
and its compatibility with the established PMem concepts and libraries.

Because CXL Type 3 (memory) devices can provide both volatile or persistent capacity,
it should be possible to transition from PMem to CXL no matter the use case. See
the table below for a break-down of different possible configurations.

{{<table "table table-striped table-bordered">}}
| CXL Memory Configuration                                  	| Administrative steps                             	| Use cases                                                                	| Programming model<br>(same as PMem)                                                                                                                                                                 	|
|-----------------------------------------------------------	|--------------------------------------------------	|--------------------------------------------------------------------------	|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|
| <b><i>Default</i></b><br>Global volatile memory<br>(system ram as NUMA) 	| None.                                            	| Adding more volatile memory capacity, potentially with software tiering. 	| Unmodified apps: Traditional memory management, OS-managed NUMA locality.<br>Modified apps: Speciality NUMA allocators (e.g., `libnuma`, `memkind`).<br>All apps: Direct use of `mmap`/`mbind`. 	|
| Volatile devdax                                           	| Reconfiguring namespace to devdax.               	| Adding new isolated memory capacity, manual tiering.                     	| Speciality allocators capable of operating on raw memory ranges (e.g., `memkind`),<br>manual use of `mmap`.                                                                                      	|
| Persistent fsdax                                          	| Configuring pmem region<br>and fsdax namespace.  	| Existing PMem-aware or storage-based software that uses regular files.   	| SNIA Persistent Memory Programming Model.<br>Unmodified apps just work. New ones can use PMDK.                                                                                                   	|
| Persistent devdax                                         	| Configuring pmem region<br>and devdax namespace. 	| Custom software requiring full control of memory.                        	| Raw access through `mmap`, can flush using CPU instructions. Apps can use PMDK.                                                                                                                  	|
{{</table>}}

CXL.mem software support in operating systems and applications is an evolution
of the ecosystem developed for Persistent Memory. Developers that have invested
time to optimize their applications for PMem will be happy to know
that most software will require no changes to adapt to CXL.mem.

Linux developers have already started the process of creating CXL drivers
and integrating the necessary components into the kernel. Much of this work
builds on the existing infrastructure that was established for Persistent Memory.
Instead of introducing new concepts, CXL will leverage fsdax, a block device
that enables direct access to memory through a file system, and devdax,
a character device for direct memory access. This means that any software currently
utilizing PMem through fsdax or devdax will seamlessly function without
requiring modifications.

However, the introduction of CXL memory devices brings an important change relevant
to system administrators. Unlike Persistent Memory, where the regions
are statically defined by ACPI, CXL memory regions are dynamic. As a result,
an additional configuration step might now be necessary to create these regions when needed.
For volatile memory, this is handled automatically.

Although work on CXL support in Linux is still ongoing, some initial support
has been released in the upstream kernel. Enabling CXL is just one kernel
configuration option away. Notably, having physical CXL-capable hardware is not
required, as qemu also already includes some CXL features. It's important
to keep in mind that the CXL software ecosystem is still in its early stages, so
bugs and missing features should be expected. However, motivated developers
can already experiment with the existing capabilities. If you are interested
in playing with emulated CXL for yourself, head over to Steve Scargall's
[How To Emulate CXL Devices using KVM and QEMU][steves-qemu-cxl-post] blog post.
Additionally, the [run_qemu][run_qemu_gh] github repository contains a set of
scripts that might also be useful when configuring an emulated system from scratch.

## System topology with CXL

Now, let's look into how the system topology changes with the introduction of CXL.
If you're already familiar with Persistent Memory and how it is exposed
to applications, you'll find that very little is new. The overall structure remains the same.

![overview](/images/posts/cxl-stack.png)

Memory regions are created from one or more devices, potentially with interleaving.
In the case of CXL, memory regions can be configured as either RAM for volatile use
or PMem for persistent use.

But, again, there is a difference in how regions are configured. With CXL, regions
can now be dynamically configured at runtime using a utility called `cxl`, rather
than being statically configured at boot through BIOS or `ipmctl` as in the case
of Intel® Optane™ Persistent Memory.

Namespaces, which represent the actual system devices used by applications, are created
on top of these regions. The two primary types of namespaces are devdax and fsdax,
as discussed earlier in the article.

Namespaces can be utilized in various ways. In many cases, devdax is used directly.
But it can also be converted into [system-ram][system-ram] mode, which creates
a memory-only, headless NUMA node. The fsdax namespaces can be overlaid with
a DAX-enabled file system, enabling easy access to persistent data.

If every layer does its job correctly, from the perspective of applications,
nothing changes. They can continue to leverage the same methods for utilizing CXL.mem
as they did for PMem. This holds true regardless of whether the software used
devdax or fsdax, and whether it leveraged persistence or only utilized volatile capacity.

All software within PMDK, including libraries such as libpmem2 or libmemkind,
will function equally well for CXL.mem as they did for PMem.

## Allocating CXL.mem through KMEM DAX

Let's explore how an application can utilize libmemkind to directly allocate from CXL.mem.
In this example, we'll assume that the CXL.mem region's type is RAM, with
a devdax namespace likewise configured into system-ram mode, also known as
Kernel Memory DAX. This configuration means that we expect our CXL memory
to show up as an additional memory-only NUMA node in the system.

```C
#include <memkind.h>

#define KMEM_ALLOC_SIZE 1024

int main(int argc, char *argv[])
{
    char *my_kmem_alloc = memkind_malloc(MEMKIND_DAX_KMEM, KMEM_ALLOC_SIZE);
    assert(my_kmem_alloc != NULL);

    strcpy(my_kmem_alloc, "Hello CXL!");

    memkind_free(MEMKIND_DAX_KMEM, my_kmem_alloc); /* bye CXL... */

    return 0;
}
```

Since we're utilizing Kernel Memory DAX, a well-established feature initially developed
for PMem, we can take advantage of the existing support for this feature in libmemkind.
Creating a new allocation is straightforward. We simply need to call
the `memkind_malloc` function with the appropriate option. The library will automatically
select the most suitable NUMA node on the platform for this allocation.

This object can be used just like any other allocation. However, it's important
to remember that when using memkind, we must call `memkind_free` function instead
of the standard library's `free` function.

The [complete example][memkind-full-example] is available on memkind's repository.
If you're interested in learning more about KMEM DAX, you can find additional information
in [this][kmem-dax] article. If you prefer to manage memory explicitly and not rely
on automatic selection by the library, you can refer
to [this example][fixed-kind-example], which demonstrates how to do so.

## Additional Resources

- ndctl and cxl tools documentation: https://pmem.io/ndctl/
- ndctl and cxl user guide: https://docs.pmem.io/ndctl-user-guide/
- Memkind repository: https://github.com/memkind/memkind

[system-ram]: https://docs.pmem.io/ndctl-user-guide/daxctl-man-pages/daxctl-create-device#description
[memkind-full-example]: https://github.com/memkind/memkind/blob/master/examples/pmem_and_dax_kmem_kind.c
[kmem-dax]: https://pmem.io/blog/2020/01/memkind-support-for-kmem-dax-option/
[fixed-kind-example]: https://github.com/memkind/memkind/blob/master/examples/fixed_malloc.c
[steves-qemu-cxl-post]: https://stevescargall.com/blog/2022/01/20/how-to-emulate-cxl-devices-using-kvm-and-qemu/
[run_qemu_gh]: https://github.com/pmem/run_qemu

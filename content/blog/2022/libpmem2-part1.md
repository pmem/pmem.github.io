---
# Blog post title
title: "Introduction to libpmem2 (part 1)"

# Blog post creation date
date: 2022-06-30T10:00:00+00:00

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
author: "Weronika Lewandowska"

# Categories to which this blog post belongs
blogs: ['PMDK']
# Blog tags
tags: ["libpmem2", "Intro"]

# Blog post type
type: "post"
---

## A new beginning

This blog post will be about the recently created library [libpmem2][libpmem2-man],
which is a part of PMDK repository. Before we go into details it's worth having a short look
at the history. About seven years ago, the first persistent memory library
known as [libpmem][libpmem-man] was started. The concept of persistent
memory was becoming a reality. Initially, libpmem provided simple support
for memory memory management on pmem.
Over time, the development of hardware, software, as well as changing customer needs made it
necessary to evolve the library to include new features such as support for Windows,
[DevDax, RAS, eADR][glossary-page], and others. The initial API design was no longer 
sufficient and it wasn't flexible enough to extend.
This led us to create an improved version of libpmem: **libpmem2**.

## New concept, new structures

[Pmem2][libpmem2-man] is a low-level standalone library providing a
platform-agnostic interface. If you've used [libpmem][libpmem-man] before, you're
probably familiar with the [pmem_is_pmem][pmem-is-pmem] function, which checks
if the specified mapped area is entirely on a persistent medium. 

As one of the big changes in *libpmem2*, we dropped this mechanism
in favor of a more intuitive and structured solution. The user now
does not have to deal with direct checking for the presence of pmem.
Instead, software gets a set of structures on which user operates, and
all the detection happens automatically.

To best understand the new concept, you can imagine a very simple recipe:
**result = what + how**, where:

**map (result)** - the virtual address space mapping, represented
in the library as a `pmem2_map` structure. Through this object, software can access
its address ([pmem2_map_get_address][pmem2-map-get-address]), size ([pmem2_map_get_size][pmem2-map-get-size]),
granularity ([pmem2_map_get_store_granularity][pmem2-map-get-store-granularity]) or flushing pointers (more about these in a moment)
by calling the appropriate functions.

**source (what)** - information necessary to create a new `pmem2_map` - the object is represented as
`pmem2_source` structure. As a source, you can use a file (providing a file descriptor or a file handle)
or use a file-independent source, i.e. anonymous mapping. These source types can
be created using the *libpmem2* API. Following functions are provided: [pmem2_source_from_fd][pmem2-source-from-fd],
[pmem2_source_from_handle][pmem2-source-from-fd], [pmem2_source_from_anon][pmem2-source-from-anon].
Usage of these functions is very simple, it only requires specifying the file handle or size.
This is enough to create `pmem2_source` - a component of the recipe for the above-described `pmem2_map`.

**config (how)** - now that we know what our source is, we can define more features
that the output mapping should have. In the library the structure that allows configuring such
information is `pmem2_config`. Many functions have been created to set mapping parameters,
starting from very basic features like setting length ([pmem2_config_set_length][pmem2-config-set-length]), setting offset ([pmem2_config_set_offset][pmem2-config-set-offset]), defining mapping visibility ([pmem2_config_set_sharing][pmem2-config-set-sharing]), to more advanced features for indicating
reservations ([pmem2_config_set_vm_reservation][pmem2-config-set-vm-reservation]), which will be discussed in the next part
of this blog post series.

## Meet granularity

The previously discussed parameters are optional. But there's one important configuration
option that is mandatory - store granularity. It is set using function [pmem2_config_set_required_store_granularity][pmem2-config-set-required-store-granularity].
Setting the granularity is the last step needed to successfully create a complete `pmem2_map` object.

Granularity is a value that was not present in the *libpmem*. This is another big difference
between the new and the old versions of the library. 
In *libpmem*, we use a simple pmem/non-pmem distinction to differentiate between different types of mappings.
However, this distinction is insufficient to capture the full spectrum of available platform types and potential
differences in power-fail protected domains between systems.

Thus, for example, we may have three different platforms available: 

- One that has traditional block storage devices (SSD, HDD) and must use system API calls such as `msync()`,
`fsync()` on Linux, or `FlushFileBuffers()`, `FlushViewOfFile()` on Windows to write data reliably.
In this case, each write is rounded up to page size and it is not possible to do a single write of smaller size.

- Another one that has NVDIMMS with asynchronous DRAM refresh and requires
flushing a cache line using `CLWB` (or `CLFLUSHOPT` or `CLFLUSH`) call.
For these instructions, the best possible write to memory is the size of the cache line.

- Or, a platform with an available eADR and the largest persistence domain where the processor
caches are also protected during power failure. Only the `SFENCE` command is necessary to
maintain full data persistence. No flushing functions are necessary, so the write granularity is
the finest possible.

As we can observe, a division into three types of granularity emerges, successively:
page granularity, cache line granularity, and byte granularity. 
These values are implemented in the *libpmem2* API using fields:

```C
enum pmem2_granularity {
	PMEM2_GRANULARITY_BYTE,
	PMEM2_GRANULARITY_CACHE_LINE,
	PMEM2_GRANULARITY_PAGE,
};
```

So back to creating our map recipe, we need to set the granularity of interest in
the config using the [pmem2_config_set_required_store_granularity][pmem2-config-set-required-store-granularity] function.
The specific feature of this function is that the granularity value passed is not
the precision with which the data should be written to the medium, but the largest acceptable value
that you as the user allow in this case. In other words, this is the maximum granularity allowed.

Let's look at some examples:

1. If you have a platform with only a **traditional storage device**.

- set the required store granularity to PAGE - the setting is correct,
and writes will be performed using the highest-performance option available,
in this case, page granularity.
- set the required store granularity to CACHELINE - your platform does not support it,
the requirement is not fulfilled, and you get an unhandled granularity error.
- set the required store granularity to BYTE - your platform does not support it,
the requirement is not fulfilled, and you get an unhandled granularity error.

2. If you have a platform with full **persistent memory support, but no eADR**.

- set the required store granularity to PAGE - the setting is correct,
and writes will be performed using the highest-performance option available,
in this case, cache line granularity.
- set the required store granularity to CACHELINE - the setting is correct,
 and writes will be performed using the highest-performance option available,
 in this case, cache line granularity.
- set the required store granularity to BYTE - your platform does not support this,
the requirement is not fulfilled, and you get an unhandled granularity error.

3. If you have a platform with full **persistent memory support and eADR**.

- set the required store granularity to PAGE - the setting is correct,
and writes will be performed using the highest-performance option available,
in this case, byte granularity.
- set the required store granularity to CACHELINE - the setting is correct,
and the writes will be performed using the highest-performance option available,
in this case, byte granularity.
- set the required store granularity to BYTE - the setting is correct,
and writes will be performed using the highest-performance option available,
in this case, byte granularity.

To sum up, user software chooses a maximum store granularity it will support
based on what makes sense for the storage algorithms that it uses.
At runtime, pmem2 will detect what is the actual store granularity of the underlying
storage media, compare that against the chosen granularity, and then will either allow or
decline to create a mapping. If created, the mapping will have the highest-performance
granularity option available at that moment. This is correct because *libpmem2* does not define
any functional differences between granularities.

If you are not sure what the effective granularity is for the mapping you've created,
you can use a function that will return this information [pmem2_map_get_store_granularity][pmem2-map-get-store-granularity].
Just pass in the `pmem2_map` created as described and you will get the information
about the granularity used based on the platform capabilities and the requirement
defined in the config.

## Look at the example

Basic configuration and mapping should not be a problem at this stage, and the following
[example][basic-example] should be clear:

```C
#include <libpmem2.h>

int
main(int argc, char *argv[])
{
    /* in this example we are going to use a mapping file */
    int fd;
    struct pmem2_config *cfg;
    struct pmem2_map *map;
    struct pmem2_source *src;
    pmem2_persist_fn persist;
    /* basic checking of the input arguments */
    if (argc != 2) {
       fprintf(stderr, "usage: %s file", argv[0]);
       exit(1);
    }
    if ((fd = open(argv[1], O_RDWR)) < 0) {
        perror("open");
        exit(1);
    }
    /* define what we want to map using pmem2_source */
    if (pmem2_source_from_fd(&src, fd)) {
        pmem2_perror("pmem2_source_from_fd");
        exit(1);
    }
    /* define how we want to map using pmem2_config */
    if (pmem2_config_new(&cfg)) {
        pmem2_perror("pmem2_config_new");
        exit(1);
    }
    /* set the maximum granularity allowed */
    if (pmem2_config_set_required_store_granularity(cfg,
        PMEM2_GRANULARITY_PAGE)) {
        pmem2_perror('pmem2_config_set_required_store_granularity');
        exit(1);
    }
    /* all ready to create pmem2_map, we just pass the source and config */
    if (pmem2_map_new(&map, cfg, src)) {
        pmem2_perror("pmem2_map_new");
        exit(1);
    }
    /* we can perform any operation on the read address */
    char *addr = pmem2_map_get_address(map);
    size_t size = pmem2_map_get_size(map);
    strcpy(addr, "hello, persistent memory");
    /* in the line above we have done the copying of the sentence to the specified 
    address, and the only thing missing is flushing to the available medium */
    persist = pmem2_get_persist_fn(map);
    persist(addr, size);
    /* don't forget to clean up after yourself */
    pmem2_map_delete(&map);
    pmem2_source_delete(&src);
    pmem2_config_delete(&cfg);
    close(fd);
    return 0;
}

```

## Pointers to functions from the map

If you have read this far, you may have noticed in the example
above an interesting way of executing the persistence function on the map.
First, the pointer to the persistence function for the given map was read
and then the function was called. No additional action from the user was required.
The software doesn't have to make any decisions on how to persist data.
The library has all the information required to perform this operation
based on the previously declared granularity and the system properties.
The same mechanism is applied to other functions operating on the *pmem2 map* object:
[pmem2_get_flush_fn][pmem2-get-flush-fn], [pmem2_get_drain_fn][pmem2-get-drain-fn], [pmem2_get_memmove_fn][pmem2-get-memmove-fn], [pmem2_get_memset_fn][pmem2-get-memmove-fn] and [pmem2_get_memcpy_fn][pmem2-get-memmove-fn].

## Summary

In this blog post, I've discussed the basic concept and functionality of *libpmem2*.
In the next post in this series, I'll be introducing more advanced features of the library,
such as virtual memory reservation APIs.


[libpmem-man]: /pmdk/manpages/linux/master/libpmem/libpmem.7
[libpmem2-man]: /pmdk/manpages/linux/master/libpmem2/libpmem2.7
[pmem-is-pmem]: /pmdk/manpages/linux/master/libpmem/pmem_is_pmem.3
[pmem2-map-get-address]: /pmdk/manpages/linux/master/libpmem2/pmem2_map_get_address.3
[pmem2-map-get-size]: /pmdk/manpages/linux/master/libpmem2/pmem2_map_get_size.3
[pmem2-map-get-store-granularity]: /pmdk/manpages/linux/master/libpmem2/pmem2_map_get_store_granularity.3
[pmem2-source-from-fd]: /pmdk/manpages/linux/master/libpmem2/pmem2_source_from_fd.3
[pmem2-source-from-anon]: /pmdk/manpages/linux/master/libpmem2/pmem2_source_from_anon.3
[pmem2-config-set-offset]: /pmdk/manpages/linux/master/libpmem2/pmem2_config_set_offset.3
[pmem2-config-set-length]: /pmdk/manpages/linux/master/libpmem2/pmem2_config_set_length.3
[pmem2-config-set-sharing]: /pmdk/manpages/linux/master/libpmem2/pmem2_config_set_sharing.3
[pmem2-config-set-vm-reservation]: /pmdk/manpages/linux/master/libpmem2/pmem2_config_set_vm_reservation.3
[pmem2-config-set-required-store-granularity]: /pmdk/manpages/linux/master/libpmem2/pmem2_config_set_required_store_granularity.3
[pmem2-get-flush-fn]: /pmdk/manpages/linux/master/libpmem2/pmem2_get_flush_fn.3
[pmem2-get-drain-fn]: /pmdk/manpages/linux/master/libpmem2/pmem2_get_drain_fn.3
[pmem2-get-memmove-fn]: /pmdk/manpages/linux/master/libpmem2/pmem2_get_memmove_fn.3
[pmdk-github]: https://github.com/pmem/pmdk
[glossary-page]: /glossary/
[basic-example]: https://github.com/pmem/pmdk/blob/master/src/examples/libpmem2/basic/basic.c

---
# Blog post title
title: "Introduction to libpmem2 library - part 1"

# Blog post creation date
date: 2022-06-20T10:00:00+00:00

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
tags: ["libpmem2", "pmem2", "Intro"]

# Blog post type
type: "post"
---

## A new beginning

This blog post will be about the recently created new library [libpmem2][libpmem2-man]
in the PMDK. Before we go into details it's worth having a short look at the 
history. About seven years ago, the first persistent memory library
known as [libpmem][libpmem-man] was started. The concept of persistent
memory was becoming a reality. Initially, the [libpmem][libpmem-man] library
provided simple support for memory allocation operations on pmem. Over time,
hardware and software developments and customer needs made it necessary to
develop the library with new features such as support for Windows, DevDax, RAS,
eADR, and others. The initial API design was no longer sufficient
and flexible. This led us to create an improved version of libpmem: [libpmem2][libpmem2-man].

## New concept, new structures

[Libpmem2][libpmem2-man] is a low-level standalone library providing a
platform-agnostic interface. If you've used [libpmem][libpmem-man] before, you're
probably familiar with the [pmem_is_pmem][pmem-is-pmem] function, which checks
if the specified mapped area is entirely on a persistent medium. 

One of the big changes in [libpmem2][libpmem2-man] is the abandonment of this
mechanism in favor of a more intuitive  and structured solution where the user
does not have to deal with directly checking for the presence of pmem memory.
He gets a set of structures on which he operates, and all the detection happens
automatically.

To best understand the new concept, you can imagine a very simple recipe:
**result = what + how**, where:

**map (result)** - this is created mapping in the virtual addreess space, represented
in the library as a *pmem2_map* structure, on such a resulting object functions can be
executed to read its address ([pmem2_map_get_address][pmem2-map-get-address]), ([pmem2_map_get_size][pmem2-map-get-size]),
granularity ([pmem2_map_get_store_granularity][pmem2-map-get-store-granularity]) or flushing functions (about those in a moment).

**source (what)** - information necessary to create a new *pmem2_map*, the object is represented as
*pmem2_source* structure in the library. As a source, you can use a file with the file descriptor,
file handle, or use a file-independent source, i.e. anonymous mapping. These source types can
be created using the *libpmem2* API, the following functions are provided: [pmem2_source_from_fd][pmem2-source-from-fd],
[pmem2_source_from_handle][pmem2-source-from-fd], [pmem2_source_from_anon][pmem2-source-from-anon].
The usage is very simple, it only requires specifying the file handle or size.
This is enough to create *pmem2_source* - a component of the recipe for the above-described *pmem2_map*.

**config (how)** - now that we know what our source is, we can define more features
that the output mapping should have. In the library the structure that allows configuring such
information is *pmem2_config*. Many functions have been created to set mapping parameters,
starting from very basic features like setting length ([pmem2_config_set_length][pmem2-config-set-length]), setting offset ([pmem2_config_set_offset][pmem2-config-set-offset]), defining mapping visibility ([pmem2_config_set_sharing][pmem2-config-set-sharing]), to more advanced features for indicating
reservations ([pmem2_config_set_vm_reservation][pmem2-config-set-vm-reservation]) which will be discussed in the next part of the blog
about [libpmem2][libpmem2-man].

## Meet granularity

Besides the mentioned coniguration parmeters there is one most important, mandatory one
called **granularity**, which is set using the function [pmem2_config_set_required_store_granularity][pmem2-config-set-required-store-granularity].
Setting the granularity is the last step needed to successfully create a finished *pmem2_map* object.

Granularity is a value that was not present in the *libpmem*, this is another big difference
between the new and old versions of the library. So far we have used the pmem/non-pmem distinction
which was not precise enough from the point of view of the available power-fail
protected domain on various platforms. 
Thus, for example, we may have three different platforms available: 

- One that has traditional block storage devices (SSD, HDD) and must use system API calls such as msync(),
fsync() on Linux, or FlushFileBuffers(), FlushViewOfFile() on Windows to write data reliably.
In this case, each write is rounded up to page size, it is not possible to do a single write of smaller size.

- Another one that has NVDIMMS with asynchronous DRAM refresh and requires
flushing a cache line using CLWB (or CLFLUSHOPT or CLFLUSH) call.
For these instructions, the best possible write to memory is the size of the cache line.

- Or, a platform with an available eARD and the largest persistence domain where the processor
caches are also protected during power failure and only the SFENCE command is necessary to
maintain full data persistence. No flushing functions are necessary, so the write granularity is
the finest, although it should be noted that the mechanism used here is neither optimal nor fast
and should be called as rarely as possible.

As one can easily observe, a division into three types of granularity emerges, successively:
page granularity, cache line granularity, and byte granularity. 
These values are implemented in the *libpmem2* API using fields:

```C
enum pmem2_granularity {
	PMEM2_GRANULARITY_BYTE,
	PMEM2_GRANULARITY_CACHE_LINE,
	PMEM2_GRANULARITY_PAGE,
};
```

So back to creating our map recipe we need to set the granularity of interest in
the config using the [pmem2_config_set_required_store_granularity][pmem2-config-set-required-store-granularity] function.
The specific feature of this function is that the granularity value passed is not
the precision with which the data should be written to the medium, but the worst possible
value that you as the user allow in this case. In other words, this is the maximum granularity allowed.

Let's look at some examples:

1. If you have a platform with only a traditional storage device.

- set the required store granularity to PAGE - the setting is correct.
- set the required store granularity to CACHELINE - your platform does not support it,
the requirement is not merged, and you get an unhandled granularity error.
- set the required store granularity to BYTE - your platform does not support it,
the requirement is not fulfilled, and you get an unhandled granularity error.

2. If you have a platform with full persistent memory support, but no eADR.

- set the required store granularity to PAGE - the setting is correct,
and writes will be performed at the best option for the user, in this case, cache line granularity.
- set the required store granularity to CACHELINE - the setting is correct and writes will be
done in the best option for the user, including cache line granularity.
- set the required store granularity to BYTE - your platform does not support this,
the requirement is not fulfilled, and you get an unhandled granularity error.

3. If you have a platform with full persistent memory support and eADR.
- set the required store granularity to PAGE - the setting is correct,
and writes will be performed at the best option for the user, in this case, byte granularity.
- set the required store granularity to CACHELINE - the setting is correct,
and the writes will be done in the best option for the user, in this case, byte granularity.
- set the required store granularity to BYTE - the setting is correct,
and writes will be done in the best option for the user, in this case, byte granularity.

The behavior of the function should be clearer now, but even if you're still not sure
what the effective granularity is for the mapping you've created, you can use a function
that will return this information [pmem2_map_get_store_granularity][pmem2-map-get-store-granularity].
Just pass in the *pmem2_map* created as described and you will get the information
about the granularity used based on the platform capabilities and the requirement
defined in the config.

## Look at the example

Basic configuration and mapping should not be a problem at this stage, and the following
[example][basic-example] should be clear:

```C
#include <fcntl.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#ifndef _WIN32
#include <unistd.h>
#else
#include <io.h>
#endif
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
The user does not have to decide which way to persist choose.
The library has all the information required to perform this operation
based on the previously declared granularity and the system properties.
Exactly the same mechanism is applied to other functions operating on the *pmem2 map* object:
[pmem2_get_flush_fn][pmem2-get-flush-fn], [pmem2_get_drain_fn][pmem2-get-drain-fn], [pmem2_get_memmove_fn][pmem2-get-memmove-fn], [pmem2_get_memset_fn][pmem2-get-memmove-fn] and [pmem2_get_memcpy_fn][pmem2-get-memmove-fn].

## Summary

This blog ends with the above example. You have learned the basic concept and functionality of the *libpmem2* library.
Let it be a good basis for the next part of the blog about *libpmem2* which I hope will appear soon.


[libpmem-man]: https://pmem.io/pmdk/manpages/linux/master/libpmem/libpmem.7
[libpmem2-man]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/libpmem2.7
[pmem-is-pmem]: https://pmem.io/pmdk/manpages/linux/master/libpmem/pmem_is_pmem.3
[pmem2-map-get-address]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_map_get_address.3
[pmem2-map-get-size]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_map_get_size.3
[pmem2-map-get-store-granularity]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_map_get_store_granularity.3
[pmem2-source-from-fd]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_source_from_fd.3
[pmem2-source-from-anon]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_source_from_anon.3
[pmem2-config-set-offset]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_config_set_offset.3
[pmem2-config-set-length]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_config_set_length.3
[pmem2-config-set-sharing]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_config_set_sharing.3
[pmem2-config-set-vm-reservation]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_config_set_vm_reservation.3
[pmem2-config-set-required-store-granularity]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_config_set_required_store_granularity.3
[pmem2-get-flush-fn]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_get_flush_fn.3
[pmem2-get-drain-fn]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_get_drin_fn.3
[pmem2-get-memmove-fn]: https://pmem.io/pmdk/manpages/linux/master/libpmem2/pmem2_get_memmove_fn.3
[pmdk-github]: https://github.com/pmem/pmdk
[basic-example]: https://github.com/pmem/pmdk/blob/master/src/examples/libpmem2/basic/basic.c
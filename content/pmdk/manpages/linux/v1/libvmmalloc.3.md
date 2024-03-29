---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["libvmmalloc.3.html"]
title: "libvmmalloc | PMDK"
header: "vmmalloc API"
---

NAME
====

libvmmalloc - general purpose volatile memory allocation library

SYNOPSIS
========

    $ LD_PRELOAD=libvmmalloc.so command [ args... ]

    or

    #include <stdlib.h>
    #include <malloc.h>
    #include <libvmmalloc.h>

    $ cc [ flag... ] file... -lvmmalloc [ library... ]


    void *malloc(size_t size);
    void free(void *ptr);
    void *calloc(size_t number, size_t size);
    void *realloc(void *ptr, size_t size);

    int posix_memalign(void **memptr, size_t alignment, size_t size);
    void *aligned_alloc(size_t alignment, size_t size);
    void *memalign(size_t alignment, size_t size);
    void *valloc(size_t size);
    void *pvalloc(size_t size);

    size_t malloc_usable_size(const void *ptr);
    void cfree(void *ptr);

DESCRIPTION
===========

**libvmmalloc** transparently converts all the dynamic memory
allocations into Persistent Memory allocations.

The typical usage of **libvmmalloc** does not require any modification
of the target program. It is enough to load **libvmmalloc** before all
other libraries by setting the environment variable **LD\_PRELOAD**.
When used in that way, **libvmmalloc** interposes the standard system
memory allocation routines, as defined in **malloc**(3),
**posix\_memalign**(3) and **malloc\_usable\_size**(3), and provides
that all dynamic memory allocations are made from a *memory pool* built
on memory-mapped file, instead of a system heap. The memory managed by
**libvmmalloc** may have different attributes, depending on the file
system containing the memory-mapped file. In particular, **libvmmalloc**
is part of the *Non-Volatile Memory Library* because it is sometimes
useful to use non-volatile memory as a volatile memory pool, leveraging
its capacity, cost, or performance characteristics.

**libvmmalloc** may be also linked to the program, by providing the
**-lvmmalloc** argument to the linker. Then it becomes the default
memory allocator for given program.

**NOTE: Due to the fact the library operates on a memory-mapped file,**
**it may not work properly with the programs that perform** **fork**(3)
**not followed by** **exec**(3).

**There are two variants of experimental** **fork**() **support
available in libvmmalloc. The desired library behavior** **may be
selected by setting VMMALLOC\_FORK environment variable.** **By default
variant \#1 is enabled.** **See ENVIRONMENT section for more details.**

**libvmmalloc** uses the **mmap**(2) system call to create a pool of
volatile memory. The library is most useful when used with *Direct
Access* storage (DAX), which is memory-addressable persistent storage
that supports load/store access without being paged via the system page
cache. A Persistent Memory-aware file system is typically used to
provide this type of access. Memory-mapping a file from a Persistent
Memory-aware file system provides the raw memory pools, and this library
supplies the traditional *malloc* interfaces on top of those pools.

The memory pool acting as a system heap replacement is created
automatically at the library initialization time. User may control its
location and size by setting the environment variables described in
**ENVIRONMENT** section. The allocated file space is reclaimed when
process terminates or in case of system crash.

Under normal usage, **libvmmalloc** will never print messages or
intentionally cause the process to exit. The library uses
**pthreads**(7) to be fully MT-safe, but never creates or destroys
threads itself. The library does not make use of any signals,
networking, and never calls **select**() or **poll**().

ENVIRONMENT
===========

There are two configuration variables that **must** be set to make
**libvmmalloc** work properly. If any of them is not specified, or if
their values are not valid, the library prints the appropriate error
message and terminates the process.

**VMMALLOC\_POOL\_DIR**

:   Specifies a path to directory where the memory pool file should be
    created. The directory must exist and be writable.

**VMMALLOC\_POOL\_SIZE**

:   Defines the desired size (in bytes) of the memory pool file. It must
    be not less than the minimum allowed size **VMMALLOC\_MIN\_POOL** as
    defined in **\<libvmmalloc.h\>.** Note that due to the fact the
    library adds some metadata to the memory pool, the amount of actual
    usable space is typically less than the size of the memory pool
    file.

Setting the **VMMALLOC\_FORK** configuration variable is optional. It
controls the behavior of **libvmmalloc** in case of **fork**(3), and can
be set to the following values:

0.  Fork support is disabled. The behavior is undefined in such case,
    but most likely results in the memory pool corruption and the
    program crash due to segmentation fault.

1.  The memory pool file is remapped with MAP\_PRIVATE flag before the
    fork completes. From this moment, any access to memory that modifies
    the heap pages, both in the parent and in the child process, will
    trigger creation of a copy of those pages in RAM (copy-on-write).
    The benefit of such approach is that it does not significantly
    increase the time of fork operation, and does not require additional
    space on the file system. However, all the subsequent memory
    allocations and modifications of the memory allocated before fork,
    will consume system memory resources instead of the memory pool.\
    This is the default setting.

2.  A copy of the entire memory pool file is created for the use of the
    child process. This requires additional space on the file system,
    but both the parent and the child process may still operate on their
    memory pools, not consuming the system memory resources. NOTE: In
    case of large memory pools, creating a copy of the pool file may
    stall the fork operation for a quite long time.

3.  The library first attempts to create a copy of the memory pool (as
    for option \#2), but if it fails (i.e. because of insufficient
    amount of free space on the file system), it will fall back to
    option \#1.

DEBUGGING
=========

Two versions of **libvmmalloc** are typically available on a development
system. The normal version is optimized for performance. That version
skips checks that impact performance and never logs any trace
information or performs any run-time assertions. A second version,
accessed when using the libraries under **/usr/lib/nvml\_debug**,
contains run-time assertions and trace points. The typical way to access
the debug version is to set the environment variable
**LD\_LIBRARY\_PATH** to **/usr/lib/nvml\_debug** or
**/usr/lib64/nvml\_debug** depending on where the debug libraries are
installed on the system. The trace points in the debug version of the
library are enabled using the environment variable
**VMMALLOC\_LOG\_LEVEL**, which can be set to the following values:

0.  Tracing is disabled. This is the default level when
    **VMMALLOC\_LOG\_LEVEL** is not set.

1.  Additional details on any errors detected are logged (in addition to
    returning the errno-based errors as usual).

2.  A trace of basic operations is logged.

3.  This level enables a very verbose amount of function call tracing in
    the library.

4.  This level enables voluminous tracing information about all the
    memory allocations and deallocations.

The environment variable **VMMALLOC\_LOG\_FILE** specifies a file name
where all logging information should be written. If the last character
in the name is \"-\", the PID of the current process will be appended to
the file name when the log file is created. If **VMMALLOC\_LOG\_FILE**
is not set, output goes to stderr.

Setting the environment variable **VMMALLOC\_LOG\_LEVEL** has no effect
on the non-debug version of **libvmmalloc**.

**VMMALLOC\_LOG\_STATS=1**

> Setting this environment variable to 1 enables logging the
> human-readable summary statistics at the program termination.
> Statistics are written only for the debug version of **libvmmalloc**.

NOTES
=====

Unlike the normal **malloc**(), which asks the system for additional
memory when it runs out, **libvmmalloc** allocates the size it is told
to and never attempts to grow or shrink that memory pool.

BUGS
====

**libvmmalloc** may not work properly with the programs that perform
**fork**(3) and do not call **exec**(3) immediately afterwards. See
**ENVIRONMENT** section for more details about the experimental
**fork**() support.

If the trace points in the debug version of the library are enabled and
the process performs fork, there is no new log file created for the
child process, even if the configured log file name is terminated with
\"-\" character. All the logging information from the child process will
be written to the log file owned by the parent process, which may lead
to corruption or partial loss of the log data.

Malloc hooks (see **malloc\_hook**(3)), are not supported when using
**libvmmalloc**.

ACKNOWLEDGEMENTS
================

**libvmmalloc** depends on jemalloc, written by Jason Evans, to do the
heavy lifting of managing dynamic memory allocation. See:

> http://www.canonware.com/jemalloc/

SEE ALSO
========

**ld.so**(8), **malloc**(3), **posix\_memalign**(3),
**malloc\_usable\_size**(3), **malloc\_hook**(3), **jemalloc**(3),
**libvmem**(3), **libpmem**(3).

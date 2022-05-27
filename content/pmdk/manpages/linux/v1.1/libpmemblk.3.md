---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["libpmemblk.3.html"]
title: "libpmemblk | PMDK"
header: "pmemblk API"
---

NAME
====

libpmemblk - persistent memory resident array of blocks

SYNOPSIS
========

    #include <libpmemblk.h>

    cc ... -lpmemblk -lpmem

    Most commonly used functions:

    PMEMblkpool *pmemblk_open(const char *path, size_t bsize);
    PMEMblkpool *pmemblk_create(const char *path, size_t bsize,
     size_t poolsize, mode_t mode);
    void pmemblk_close(PMEMblkpool *pbp);
    size_t pmemblk_bsize(PMEMblkpool *pbp);
    size_t pmemblk_nblock(PMEMblkpool *pbp);
    int pmemblk_read(PMEMblkpool *pbp, void *buf, long long blockno);
    int pmemblk_write(PMEMblkpool *pbp, const void *buf,
     long long blockno);
    int pmemblk_set_zero(PMEMblkpool *pbp, long long blockno);
    int pmemblk_set_error(PMEMblkpool *pbp, long long blockno);

    Library API versioning:

    const char *pmemblk_check_version(
     unsigned major_required,
     unsigned minor_required);

    Managing library behavior:

    void pmemblk_set_funcs(
     void *(*malloc_func)(size_t size),
     void (*free_func)(void *ptr),
     void *(*realloc_func)(void *ptr, size_t size),
     char *(*strdup_func)(const char *s));
    int pmemblk_check(const char *path, size_t bsize);

    Error handling:

    "constchar*pmemblk_errormsg(void);

DESCRIPTION
===========

**libpmemblk** provides an array of blocks in *persistent memory* (pmem)
such that updates to a single block are atomic. This library is intended
for applications using direct access storage (DAX), which is storage
that supports load/store access without paging blocks from a block
storage device. Some types of *non-volatile memory DIMMs* (NVDIMMs)
provide this type of byte addressable access to storage. A *persistent
memory aware file system* is typically used to expose the direct access
to applications. Memory mapping a file from this type of file system
results in the load/store, non-paged access to pmem. **libpmemblk**
builds on this type of memory mapped file.

This library is for applications that need a potentially large array of
blocks, all the same size, where any given block is updated atomically
(the update cannot be *torn* by program interruption such as power
failures). This library builds on the low-level pmem support provided by
**libpmem**(3), handling the transactional update of the blocks,
flushing to persistence, and recovery for the application.

**libpmemblk** is one of a collection of persistent memory libraries
available, the others are:

> **libpmemobj**(3), a general use persistent memory API, providing
> memory allocation and transactional operations on variable-sized
> objects.
>
> **libpmemlog**(3), providing a pmem-resident log file.
>
> **libpmem**(3), low-level persistent memory support.

Under normal usage, **libpmemblk** will never print messages or
intentionally cause the process to exit. The only exception to this is
the debugging information, when enabled, as described under **DEBUGGING
AND ERROR HANDLING** below.

MOST COMMONLY USED FUNCTIONS
============================

To use the atomic block arrays supplied by **libpmemblk**, a *memory
pool* is first created. This is done with the **pmemblk\_create**()
function described in this section. The other functions described in
this section then operate on the resulting block memory pool.

Once created, the memory pool is represented by an opaque handle, of
type *PMEMblkpool \**, which is passed to most of the other functions in
this section. Internally, **libpmemblk** will use either
**pmem\_persist**() or **msync**(2) when it needs to flush changes,
depending on whether the memory pool appears to be persistent memory or
a regular file (see the **pmem\_is\_pmem**() function in **libpmem**(3)
for more information). There is no need for applications to flush
changes directly when using the block memory API provided by
**libpmemblk**.

**PMEMblkpool \*pmemblk\_open(const char \****path***, size\_t
***bsize***);**

> The **pmemblk\_open**() function opens an existing block memory pool,
> returning a memory pool handle used with most of the functions in this
> section. *path* must be an existing file containing a block memory
> pool as created by **pmemblk\_create**(). The application must have
> permission to open the file and memory map it with read/write
> permissions. If the *bsize* provided is non-zero, **pmemblk\_open**()
> will verify the given block size matches the block size used when the
> pool was created. Otherwise, **pmemblk\_open**() will open the pool
> without verification of the block size. The *bsize* can be determined
> using the **pmemblk\_bsize**() function. If an error prevents the pool
> from being opened, **pmemblk\_open**() returns NULL and sets errno
> appropriately. A block size mismatch with the *bsize* argument passed
> in results in errno being set to EINVAL.

**PMEMblkpool \*pmemblk\_create(const char \****path***, size\_t
***bsize***,**\
** size\_t ***poolsize***, mode\_t ***mode***);**

> The **pmemblk\_create**() function creates a block memory pool with
> the given total *poolsize* divided up into as many elements of size
> *bsize* as will fit in the pool. Since the transactional nature of a
> block memory pool requires some space overhead in the memory pool, the
> resulting number of available blocks is less than *poolsize / bsize*,
> and is made available to the caller via the **pmemblk\_nblock**()
> function described below. Given the specifics of the implementation,
> the number of available blocks for the user cannot be less than 256.
> This translates to at least 512 internal blocks. *path* specifies the
> name of the memory pool file to be created. *mode* specifies the
> permissions to use when creating the file as described by
> **creat**(2). The memory pool file is fully allocated to the size
> *poolsize* using **posix\_fallocate**(3). The caller may choose to
> take responsibility for creating the memory pool file by creating it
> before calling **pmemblk\_create**() and then specifying *poolsize* as
> zero. In this case **pmemblk\_create**() will take the pool size from
> the size of the existing file and will verify that the file appears to
> be empty by searching for any non-zero data in the pool header at the
> beginning of the file. The minimum file size allowed by the library
> for a block pool is defined in **\<libpmemblk.h\>** as
> **PMEMBLK\_MIN\_POOL**. *bsize* can be any non-zero value, however
> **libpmemblk** will silently round up the given size to
> **PMEMBLK\_MIN\_BLK**, as defined in **\<libpmemblk.h\>**.

Depending on the configuration of the system, the available space of
non-volatile memory space may be divided into multiple memory devices.
In such case, the maximum size of the pmemblk memory pool could be
limited by the capacity of a single memory device. The **libpmemblk**
allows building persistent memory resident array spanning multiple
memory devices by creation of persistent memory pools consisting of
multiple files, where each part of such a *pool set* may be stored on
different pmem-aware filesystem.

Creation of all the parts of the pool set can be done with the
**pmemblk\_create**() function. However, the recommended method for
creating pool sets is to do it by using the **pmempool**(1) utility.

When creating the pool set consisting of multiple files, the *path*
argument passed to **pmemblk\_create**() must point to the special *set*
file that defines the pool layout and the location of all the parts of
the pool set. The *poolsize* argument must be 0. The meaning of *layout*
and *mode* arguments doesn\'t change, except that the same *mode* is
used for creation of all the parts of the pool set. If the error
prevents any of the pool set files from being created,
**pmemblk\_create**() returns NULL and sets errno appropriately.

When opening the pool set consisting of multiple files, the *path*
argument passed to **pmemblk\_open**() must not point to the pmemblk
memory pool file, but to the same *set* file that was used for the pool
set creation. If an error prevents any of the pool set files from being
opened, or if the actual size of any file does not match the
corresponding part size defined in *set* file **pmemblk\_open**()
returns NULL and sets errno appropriately.

The set file is a plain text file, which must start with the line
containing a *PMEMPOOLSET* string, followed by the specification of all
the pool parts in the next lines. For each part, the file size and the
absolute path must be provided. The size has to be compliant with the
format specified in IEC 80000-13, IEEE 1541 or the Metric Interchange
Format. Standards accept SI units with obligatory B - kB, MB, GB, \...
(multiplier by 1000) and IEC units with optional \"iB\" - KiB, MiB, GiB,
\..., K, M, G, \... - (multiplier by 1024).

The minimum file size of each part of the pool set is the same as the
minimum size allowed for a block pool consisting of one file. It is
defined in **\<libpmemblk.h\>** as **PMEMBLK\_MIN\_POOL**. Lines
starting with \"\#\" character are ignored.

Here is the example \"myblkpool.set\" file:

    PMEMPOOLSET
    100G /mountpoint0/myfile.part0
    200G /mountpoint1/myfile.part1
    400G /mountpoint2/myfile.part2

The files in the set may be created by running the following command:

    pmempool create blk <bsize> myblkpool.set

**void pmemblk\_close(PMEMblkpool \****pbp***);**

> The **pmemblk\_close**() function closes the memory pool indicated by
> *pbp* and deletes the memory pool handle. The block memory pool itself
> lives on in the file that contains it and may be re-opened at a later
> time using **pmemblk\_open**() as described above.

**size\_t pmemblk\_bsize(PMEMblkpool \****pbp***);**

> The **pmemblk\_bsize**() function returns the block size of the
> specified block memory pool. It\'s the value which was passed as
> *bsize* to **pmemblk\_create**(). *pbp* must be a block memory pool
> handle as returned by **pmemblk\_open**() or **pmemblk\_create**().

**size\_t pmemblk\_nblock(PMEMblkpool \****pbp***);**

> The **pmemblk\_nblock**() function returns the usable space in the
> block memory pool, expressed as the number of blocks available. *pbp*
> must be a block memory pool handle as returned by **pmemblk\_open**()
> or **pmemblk\_create**().

**int pmemblk\_read(PMEMblkpool \****pbp***, void \****buf***, long long
***blockno***);**

> The **pmemblk\_read**() function reads a block from memory pool *pbp*,
> block number *blockno*, into the buffer *buf*. On success, zero is
> returned. On error, -1 is returned and errno is set. Reading a block
> that has never been written by **pmemblk\_write**() will return a
> block of zeroes.

**int pmemblk\_write(PMEMblkpool \****pbp***, const void \****buf***,**\
** long long ***blockno***);**

> The **pmemblk\_write**() function writes a block from *buf* to block
> number *blockno* in the memory pool *pbp*. The write is atomic with
> respect to other reads and writes. In addition, the write cannot be
> torn by program failure or system crash; on recovery the block is
> guaranteed to contain either the old data or the new data, never a
> mixture of both. On success, zero is returned. On error, -1 is
> returned and errno is set.

**int pmemblk\_set\_zero(PMEMblkpool \****pbp***, long long
***blockno***);**

> The **pmemblk\_set\_zero**() function writes zeros to block number
> *blockno* in memory pool *pbp*. Using this function is faster than
> actually writing a block of zeros since **libpmemblk** uses metadata
> to indicate the block should read back as zero. On success, zero is
> returned. On error, -1 is returned and errno is set.

**int pmemblk\_set\_error(PMEMblkpool \****pbp***, long long
***blockno***);**

> The **pmemblk\_set\_error**() function sets the error state for block
> number *blockno* in memory pool *pbp*. A block in the error state
> returns errno EIO when read. Writing the block clears the error state
> and returns the block to normal use. On success, zero is returned. On
> error, -1 is returned and errno is set.

LIBRARY API VERSIONING
======================

This section describes how the library API is versioned, allowing
applications to work with an evolving API.

**const char \*pmemblk\_check\_version(**\
** unsigned ***major\_required***,**\
** unsigned ***minor\_required***);**

> The **pmemblk\_check\_version**() function is used to see if the
> installed **libpmemblk** supports the version of the library API
> required by an application. The easiest way to do this is for the
> application to supply the compile-time version information, supplied
> by defines in **\<libpmemblk.h\>**, like this:
>
>     reason = pmemblk_check_version(PMEMBLK_MAJOR_VERSION,
>                                 PMEMBLK_MINOR_VERSION);
>     if (reason != NULL) {
>         /*  version check failed, reason string tells you why */
>     }
>
> Any mismatch in the major version number is considered a failure, but
> a library with a newer minor version number will pass this check since
> increasing minor versions imply backwards compatibility.
>
> An application can also check specifically for the existence of an
> interface by checking for the version where that interface was
> introduced. These versions are documented in this man page as follows:
> unless otherwise specified, all interfaces described here are
> available in version 1.0 of the library. Interfaces added after
> version 1.0 will contain the text *introduced in version x.y* in the
> section of this manual describing the feature.
>
> When the version check performed by **pmemblk\_check\_version**() is
> successful, the return value is NULL. Otherwise the return value is a
> static string describing the reason for failing the version check. The
> string returned by **pmemblk\_check\_version**() must not be modified
> or freed.

MANAGING LIBRARY BEHAVIOR
=========================

The library entry points described in this section are less commonly
used than the previous sections.

**void pmemblk\_set\_funcs(**\
** void \*(\****malloc\_func***)(size\_t ***size***),**\
** void (\****free\_func***)(void \****ptr***),**\
** void \*(\****realloc\_func***)(void \****ptr***, size\_t
***size***),**\
** char \*(\****strdup\_func***)(const char \****s***));**

> The **pmemblk\_set\_funcs**() function allows an application to
> override memory allocation calls used internally by **libpmemblk**.
> Passing in NULL for any of the handlers will cause the **libpmemblk**
> default function to be used. The library does not make heavy use of
> the system malloc functions, but it does allocate approximately 4-8
> kilobytes for each memory pool in use.

**int pmemblk\_check(const char \****path***, size\_t ***bsize***);**

> The **pmemblk\_check**() function performs a consistency check of the
> file indicated by *path* and returns 1 if the memory pool is found to
> be consistent. Any inconsistencies found will cause
> **pmemblk\_check**() to return 0, in which case the use of the file
> with **libpmemblk** will result in undefined behavior. The debug
> version of **libpmemblk** will provide additional details on
> inconsistencies when **PMEMBLK\_LOG\_LEVEL** is at least 1, as
> described in the **DEBUGGING AND ERROR HANDLING** section below. When
> *bsize* is non-zero **pmemblk\_check**() will compare it to the block
> size of the pool and return 0 when they don\'t match.
> **pmemblk\_check**() will return -1 and set errno if it cannot perform
> the consistency check due to other errors. **pmemblk\_check**() opens
> the given *path* read-only so it never makes any changes to the file.

DEBUGGING AND ERROR HANDLING
============================

Two versions of **libpmemblk** are typically available on a development
system. The normal version, accessed when a program is linked using the
**-lpmemblk** option, is optimized for performance. That version skips
checks that impact performance and never logs any trace information or
performs any run-time assertions. If an error is detected during the
call to **libpmemblk** function, an application may retrieve an error
message describing the reason of failure using the following function:

**\"const***char***\*pmemblk\_errormsg(void);**

> The **pmemblk\_errormsg**() function returns a pointer to a static
> buffer containing the last error message logged for current thread.
> The error message may include description of the corresponding error
> code (if errno was set), as returned by **strerror**(3). The error
> message buffer is thread-local; errors encountered in one thread do
> not affect its value in other threads. The buffer is never cleared by
> any library function; its content is significant only when the return
> value of the immediately preceding call to **libpmemblk** function
> indicated an error, or if errno was set. The application must not
> modify or free the error message string, but it may be modified by
> subsequent calls to other library functions.

A second version of **libpmemblk**, accessed when a program uses the
libraries under **/usr/lib/nvml\_debug**, contains run-time assertions
and trace points. The typical way to access the debug version is to set
the environment variable **LD\_LIBRARY\_PATH** to
**/usr/lib/nvml\_debug** or **/usr/lib64/nvml\_debug** depending on
where the debug libraries are installed on the system. The trace points
in the debug version of the library are enabled using the environment
variable **PMEMBLK\_LOG\_LEVEL**, which can be set to the following
values:

0.  This is the default level when **PMEMBLK\_LOG\_LEVEL** is not set.
    No log messages are emitted at this level.

1.  Additional details on any errors detected are logged (in addition to
    returning the errno-based errors as usual). The same information may
    be retrieved using **pmemblk\_errormsg**().

2.  A trace of basic operations is logged.

3.  This level enables a very verbose amount of function call tracing in
    the library.

4.  This level enables voluminous and fairly obscure tracing information
    that is likely only useful to the **libpmemblk** developers.

The environment variable **PMEMBLK\_LOG\_FILE** specifies a file name
where all logging information should be written. If the last character
in the name is \"-\", the PID of the current process will be appended to
the file name when the log file is created. If **PMEMBLK\_LOG\_FILE** is
not set, the logging output goes to stderr.

Setting the environment variable **PMEMBLK\_LOG\_LEVEL** has no effect
on the non-debug version of **libpmemblk**.

See also **libpmem**(3) to get information about other environment
variables affecting **libpmemblk** behavior.

EXAMPLES
========

The following example illustrates how the **libpmemblk** API is used.

    #include <stdio.h>
    #include <fcntl.h>
    #include <errno.h>
    #include <stdlib.h>
    #include <unistd.h>
    #include <string.h>
    #include <libpmemblk.h>

    /* size of the pmemblk pool -- 1 GB */
    #define POOL_SIZE ((size_t)(1 << 30))

    /* size of each element in the pmem pool */
    #define ELEMENT_SIZE 1024

    int
    main(int argc, char *argv[])
    {
        const char path[] = "/pmem-fs/myfile";
        PMEMblkpool *pbp;
        size_t nelements;
        char buf[ELEMENT_SIZE];

        /* create the pmemblk pool or open it if it already exists */
        pbp = pmemblk_create(path, ELEMENT_SIZE, POOL_SIZE, 0666);

        if (pbp == NULL)
            pbp = pmemblk_open(path, ELEMENT_SIZE);

        if (pbp == NULL) {
            perror(path);
            exit(1);
        }

        /* how many elements fit into the file? */
        nelements = pmemblk_nblock(pbp);
        printf("file holds %zu elements, nelements);

        /* store a block at index 5 */
        strcpy(buf, "hello, world");
        if (pmemblk_write(pbp, buf, 5) < 0) {
            perror("pmemblk_write");
            exit(1);
        }

        /* read the block at index 10 (reads as zeros initially) */
        if (pmemblk_read(pbp, buf, 10) < 0) {
            perror("pmemblk_read");
            exit(1);
        }

        /* zero out the block at index 5 */
        if (pmemblk_set_zero(pbp, 5) < 0) {
            perror("pmemblk_set_zero");
            exit(1);
        }

        /* ... */

        pmemblk_close(pbp);
    }

See http://pmem.io/nvml/libpmemblk for more examples using the
**libpmemblk** API.

BUGS
====

Unlike **libpmemobj**, data replication is not supported in
**libpmemblk**. Thus, it is not allowed to specify replica sections in
pool set files.

ACKNOWLEDGEMENTS
================

**libpmemblk** builds on the persistent memory programming model
recommended by the SNIA NVM Programming Technical Work Group:

> http://snia.org/nvmp

SEE ALSO
========

**mmap**(2), **munmap**(2), **msync**(2), **strerror**(3),
**libpmemobj**(3), **libpmemlog**(3), **libpmem**(3), **libvmem**(3) and
**http://pmem.io**.

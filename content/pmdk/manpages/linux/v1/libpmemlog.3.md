---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["libpmemlog.3.html"]
title: "libpmemlog | PMDK"
header: "pmemlog API"
---

NAME
====

libpmemlog - persistent memory resident log file

SYNOPSIS
========

    #include <libpmemlog.h>

    cc ... -lpmemlog -lpmem

    Most commonly used functions:

    PMEMlogpool *pmemlog_open(const char *path);
    PMEMlogpool *pmemlog_create(const char *path,
     size_t poolsize, mode_t mode);
    void pmemlog_close(PMEMlogpool *plp);
    size_t pmemlog_nbyte(PMEMlogpool *plp);
    int pmemlog_append(PMEMlogpool *plp, const void *buf, size_t count);
    int pmemlog_appendv(PMEMlogpool *plp,
     const struct iovec *iov, int iovcnt);
    long long pmemlog_tell(PMEMlogpool *plp);
    void pmemlog_rewind(PMEMlogpool *plp);
    void pmemlog_walk(PMEMlogpool *plp, size_t chunksize,
     int (*process_chunk)(const void *buf, size_t len, void *arg),
     void *arg);

    Library API versioning:

    const char *pmemlog_check_version(
     unsigned major_required,
     unsigned minor_required);

    Managing library behavior:

    void pmemlog_set_funcs(
     void *(*malloc_func)(size_t size),
     void (*free_func)(void *ptr),
     void *(*realloc_func)(void *ptr, size_t size),
     char *(*strdup_func)(const char *s));
    int pmemlog_check(const char *path);

    Error handling:

    "constchar*pmemlog_errormsg(void);

DESCRIPTION
===========

**libpmemlog** provides a log file in *persistent memory* (pmem) such
that additions to the log are appended atomically. This library is
intended for applications using direct access storage (DAX), which is
storage that supports load/store access without paging blocks from a
block storage device. Some types of *non-volatile memory DIMMs*
(NVDIMMs) provide this type of byte addressable access to storage. A
*persistent memory aware file system* is typically used to expose the
direct access to applications. Memory mapping a file from this type of
file system results in the load/store, non-paged access to pmem.
**libpmemlog** builds on this type of memory mapped file.

This library is for applications that need a persistent log file,
updated atomically (the updates cannot be *torn* by program interruption
such as power failures). This library builds on the low-level pmem
support provided by **libpmem**(3)**,** handling the transactional
update of the log, flushing to persistence, and recovery for the
application.

**libpmemlog** is one of a collection of persistent memory libraries
available, the others are:

> **libpmemobj**(3), a general use persistent memory API, providing
> memory allocation and transactional operations on variable-sized
> objects.
>
> **libpmemblk**(3), providing pmem-resident arrays of fixed-sized
> blocks with atomic updates.
>
> **libpmem**(3), low-level persistent memory support.

Under normal usage, **libpmemlog** will never print messages or
intentionally cause the process to exit. The only exception to this is
the debugging information, when enabled, as described under **DEBUGGING
AND ERROR HANDLING** below.

MOST COMMONLY USED FUNCTIONS
============================

To use the pmem-resident log file provided by **libpmemlog**, a *memory
pool* is first created. This is done with the **pmemlog\_create**()
function described in this section. The other functions described in
this section then operate on the resulting log memory pool.

Once created, the memory pool is represented by an opaque handle, of
type *PMEMlogpool \**, which is passed to most of the other functions in
this section. Internally, **libpmemlog** will use either
**pmem\_persist**() or **msync**(2) when it needs to flush changes,
depending on whether the memory pool appears to be persistent memory or
a regular file (see the **pmem\_is\_pmem**() function in **libpmem**(3)
for more information). There is no need for applications to flush
changes directly when using the log memory API provided by
**libpmemlog**.

**PMEMlogpool \*pmemlog\_open(const char \****path***);**

> The **pmemlog\_open**() function opens an existing log memory pool,
> returning a memory pool handle used with most of the functions in this
> section. *path* must be an existing file containing a log memory pool
> as created by **pmemlog\_create**(). The application must have
> permission to open the file and memory map it with read/write
> permissions. If an error prevents the pool from being opened,
> **pmemlog\_open**() returns NULL and sets errno appropriately.

**PMEMlogpool \*pmemlog\_create(const char \****path***,**\
** size\_t ***poolsize***, mode\_t ***mode***);**

> The **pmemlog\_create**() function creates a log memory pool with the
> given total *poolsize*. Since the transactional nature of a log memory
> pool requires some space overhead in the memory pool, the resulting
> available log size is less than *poolsize*, and is made available to
> the caller via the **pmemlog\_nbyte**() function described below.
> *path* specifies the name of the memory pool file to be created.
> *mode* specifies the permissions to use when creating the file as
> described by **creat**(2). The memory pool file is fully allocated to
> the size *poolsize* using **posix\_fallocate**(3). The caller may
> choose to take responsibility for creating the memory pool file by
> creating it before calling **pmemlog\_create**() and then specifying
> *poolsize* as zero. In this case **pmemlog\_create**() will take the
> pool size from the size of the existing file and will verify that the
> file appears to be empty by searching for any non-zero data in the
> pool header at the beginning of the file. The minimum file size
> allowed by the library for a log pool is defined in
> **\<libpmemlog.h\>** as **PMEMLOG\_MIN\_POOL**.

Depending on the configuration of the system, the available space of
non-volatile memory space may be divided into multiple memory devices.
In such case, the maximum size of the pmemlog memory pool could be
limited by the capacity of a single memory device. The **libpmemlog**
allows building persistent memory resident log spanning multiple memory
devices by creation of persistent memory pools consisting of multiple
files, where each part of such a *pool set* may be stored on different
pmem-aware filesystem.

Creation of all the parts of the pool set can be done with the
**pmemlog\_create**() function. However, the recommended method for
creating pool sets is to do it by using the **pmempool**(1) utility.

When creating the pool set consisting of multiple files, the *path*
argument passed to **pmemlog\_create**() must point to the special *set*
file that defines the pool layout and the location of all the parts of
the pool set. The *poolsize* argument must be 0. The meaning of *layout*
and *mode* arguments doesn\'t change, except that the same *mode* is
used for creation of all the parts of the pool set. If the error
prevents any of the pool set files from being created,
**pmemlog\_create**() returns NULL and sets errno appropriately.

When opening the pool set consisting of multiple files, the *path*
argument passed to **pmemlog\_open**() must not point to the pmemlog
memory pool file, but to the same *set* file that was used for the pool
set creation. If an error prevents any of the pool set files from being
opened, or if the actual size of any file does not match the
corresponding part size defined in *set* file **pmemlog\_open**()
returns NULL and sets errno appropriately.

The set file is a plain text file, which must start with the line
containing a *PMEMPOOLSET* string, followed by the specification of all
the pool parts in the next lines. For each part, the file size and the
absolute path must be provided. The minimum file size of each part of
the pool set is the same as the minimum size allowed for a log pool
consisting of one file. It is defined in **\<libpmemlog.h\>** as
**PMEMLOG\_MIN\_POOL**. Lines starting with \"\#\" character are
ignored.

Here is the example \"mylogpool.set\" file:

    PMEMPOOLSET
    100G /mountpoint0/myfile.part0
    200G /mountpoint1/myfile.part1
    400G /mountpoint2/myfile.part2

The files in the set may be created by running the following command:

    pmempool create log --from-set=mylogpool.set

**void pmemlog\_close(PMEMlogpool \****plp***);**

> The **pmemlog\_close**() function closes the memory pool indicated by
> *plp* and deletes the memory pool handle. The log memory pool itself
> lives on in the file that contains it and may be re-opened at a later
> time using **pmemlog\_open**() as described above.

**size\_t pmemlog\_nbyte(PMEMlogpool \****plp***);**

> The **pmemlog\_nbyte**() function returns the amount of usable space
> in the log *plp*. This function may be used on a log to determine how
> much usable space is available after **libpmemlog** has added its
> metadata to the memory pool.

**int pmemlog\_append(PMEMlogpool \****plp***, const void \****buf***,
size\_t ***count***);**

> The **pmemlog\_append**() function appends *count* bytes from *buf* to
> the current write offset in the log memory pool *plp*. Calling this
> function is analogous to appending to a file. The append is atomic and
> cannot be torn by a program failure or system crash. On success, zero
> is returned. On error, -1 is returned and errno is set.

**int pmemlog\_appendv(PMEMlogpool \****plp***,**\
** const struct iovec \****iov***, int ***iovcnt***);**

> The **pmemlog\_appendv**() function appends to the log *plp* just like
> **pmemlog\_append**() above, but this function takes a scatter/gather
> list in a manner similar to **writev**(2). In this case, the entire
> list of buffers is appended atomically, as if the buffers in *iov*
> were concatenated in order. On success, zero is returned. On error, -1
> is returned and errno is set.

> NOTE: Since **libpmemlog** is designed as a low-latency code path,
> many of the checks routinely done by the operating system for
> **writev**(2) are not practical in the library\'s implementation of
> **pmemlog\_appendv**(). No attempt is made to detect NULL or incorrect
> pointers, or illegal count values, for example.

**long long pmemlog\_tell(PMEMlogpool \****plp***);**

> The **pmemlog\_tell**() function returns the current write point for
> the log, expressed as a byte offset into the usable log space in the
> memory pool. This offset starts off as zero on a newly-created log,
> and is incremented by each successful append operation. This function
> can be used to determine how much data is currently in the log.

**void pmemlog\_rewind(PMEMlogpool \****plp***);**

> The **pmemlog\_rewind**() function resets the current write point for
> the log to zero. After this call, the next append adds to the
> beginning of the log.

**void pmemlog\_walk(PMEMlogpool
\****plp***\",***size\_t***chunksize***,*\
** int (\****process\_chunk***)(const void \****buf***, size\_t
***len***, void \****arg***),**\
** void \****arg***);**

> The **pmemlog\_walk**() function walks through the log *plp*, from
> beginning to end, calling the callback function *process\_chunk* for
> each *chunksize* block of data found. The argument *arg* is also
> passed to the callback to help avoid the need for global state. The
> *chunksize* argument is useful for logs with fixed-length records and
> may be specified as 0 to cause a single call to the callback with the
> entire log contents passed as the *buf* argument. The *len* argument
> tells the *process\_chunk* function how much data buf is holding. The
> callback function should return 1 if **pmemlog\_walk**() should
> continue walking through the log, or 0 to terminate the walk. The
> callback function is called while holding **libpmemlog** internal
> locks that make calls atomic, so the callback function must not try to
> append to the log itself or deadlock will occur.

LIBRARY API VERSIONING
======================

This section describes how the library API is versioned, allowing
applications to work with an evolving API.

**const char \*pmemlog\_check\_version(**\
** unsigned ***major\_required***,**\
** unsigned ***minor\_required***);**

> The **pmemlog\_check\_version**() function is used to see if the
> installed **libpmemlog** supports the version of the library API
> required by an application. The easiest way to do this is for the
> application to supply the compile-time version information, supplied
> by defines in **\<libpmemlog.h\>**, like this:
>
>     reason = pmemblk_check_version(PMEMLOG_MAJOR_VERSION,
>                                 PMEMLOG_MINOR_VERSION);
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
> When the version check performed by **pmemlog\_check\_version**() is
> successful, the return value is NULL. Otherwise the return value is a
> static string describing the reason for failing the version check. The
> string returned by **pmemlog\_check\_version**() must not be modified
> or freed.

MANAGING LIBRARY BEHAVIOR
=========================

The library entry points described in this section are less commonly
used than the previous sections.

**void pmemlog\_set\_funcs(**\
** void \*(\****malloc\_func***)(size\_t ***size***),**\
** void (\****free\_func***)(void \****ptr***),**\
** void \*(\****realloc\_func***)(void \****ptr***, size\_t
***size***),**\
** char \*(\****strdup\_func***)(const char \****s***));**

> The **pmemlog\_set\_funcs**() function allows an application to
> override memory allocation calls used internally by **libpmemlog**.
> Passing in NULL for any of the handlers will cause the **libpmemlog**
> default function to be used. The library does not make heavy use of
> the system malloc functions, but it does allocate approximately 4-8
> kilobytes for each memory pool in use.

**int pmemlog\_check(const char \****path***);**

> The **pmemlog\_check**() function performs a consistency check of the
> file indicated by *path* and returns 1 if the memory pool is found to
> be consistent. Any inconsistencies found will cause
> **pmemlog\_check**() to return 0, in which case the use of the file
> with **libpmemlog** will result in undefined behavior. The debug
> version of **libpmemlog** will provide additional details on
> inconsistencies when **PMEMLOG\_LOG\_LEVEL** is at least 1, as
> described in the **DEBUGGING AND ERROR HANDLING** section below.
> **pmemlog\_check**() will return -1 and set errno if it cannot perform
> the consistency check due to other errors. **pmemlog\_check**() opens
> the given *path* read-only so it never makes any changes to the file.

DEBUGGING AND ERROR HANDLING
============================

Two versions of **libpmemlog** are typically available on a development
system. The normal version, accessed when a program is linked using the
**-lpmemlog** option, is optimized for performance. That version skips
checks that impact performance and never logs any trace information or
performs any run-time assertions. If an error is detected during the
call to **libpmemlog** function, an application may retrieve an error
message describing the reason of failure using the following function:

**\"const***char***\*pmemlog\_errormsg(void);**

> The **pmemlog\_errormsg**() function returns a pointer to a static
> buffer containing the last error message logged for current thread.
> The error message may include description of the corresponding error
> code (if errno was set), as returned by **strerror**(3). The error
> message buffer is thread-local; errors encountered in one thread do
> not affect its value in other threads. The buffer is never cleared by
> any library function; its content is significant only when the return
> value of the immediately preceding call to **libpmemlog** function
> indicated an error, or if errno was set. The application must not
> modify or free the error message string, but it may be modified by
> subsequent calls to other library functions.

A second version of **libpmemlog**, accessed when a program uses the
libraries under **/usr/lib/nvml\_debug**, contains run-time assertions
and trace points. The typical way to access the debug version is to set
the environment variable **LD\_LIBRARY\_PATH** to
**/usr/lib/nvml\_debug** or **/usr/lib64/nvml\_debug** depending on
where the debug libraries are installed on the system. The trace points
in the debug version of the library are enabled using the environment
variable **PMEMLOG\_LOG\_LEVEL**, which can be set to the following
values:

0.  This is the default level when **PMEMLOG\_LOG\_LEVEL** is not set.
    No log messages are emitted at this level.

1.  Additional details on any errors detected are logged (in addition to
    returning the errno-based errors as usual). The same information may
    be retrieved using **pmemlog\_errormsg**().

2.  A trace of basic operations is logged.

3.  This level enables a very verbose amount of function call tracing in
    the library.

4.  This level enables voluminous and fairly obscure tracing information
    that is likely only useful to the **libpmemlog** developers.

The environment variable **PMEMLOG\_LOG\_FILE** specifies a file name
where all logging information should be written. If the last character
in the name is \"-\", the PID of the current process will be appended to
the file name when the log file is created. If **PMEMLOG\_LOG\_FILE** is
not set, the logging output goes to stderr.

Setting the environment variable **PMEMLOG\_LOG\_LEVEL** has no effect
on the non-debug version of **libpmemlog**.

EXAMPLES
========

The following example illustrates how the **libpmemlog** API is used.

    #include <stdio.h>
    #include <fcntl.h>
    #include <errno.h>
    #include <stdlib.h>
    #include <unistd.h>
    #include <string.h>
    #include <libpmemlog.h>

    /* size of the pmemlog pool -- 1 GB */
    #define POOL_SIZE ((size_t)(1 << 30))

    /*
     * printit -- log processing callback for use with pmemlog_walk()
     */
    int
    printit(const void *buf, size_t len, void *arg)
    {
        fwrite(buf, len, 1, stdout);
        return 0;
    }

    int
    main(int argc, char *argv[])
    {
        const char path[] = "/pmem-fs/myfile";
        PMEMlogpool *plp;
        size_t nbyte;
        char *str;

        /* create the pmemlog pool or open it if it already exists */
        plp = pmemlog_create(path, POOL_SIZE, 0666);

        if (plp == NULL)
            plp = pmemlog_open(path);

        if (plp == NULL) {
            perror(path);
            exit(1);
        }

        /* how many bytes does the log hold? */
        nbyte = pmemlog_nbyte(plp);
        printf("log holds %zu bytes, nbyte);

        /* append to the log... */
        str = "This is the first string appended;
        if (pmemlog_append(plp, str, strlen(str)) < 0) {
            perror("pmemlog_append");
            exit(1);
        }
        str = "This is the second string appended;
        if (pmemlog_append(plp, str, strlen(str)) < 0) {
            perror("pmemlog_append");
            exit(1);
        }

        /* print the log contents */
        printf("log contains:);
        pmemlog_walk(plp, 0, printit, NULL);

        pmemlog_close(plp);
    }

See http://pmem.io/nvml/libpmemlog for more examples using the
**libpmemlog** API.

BUGS
====

Unlike **libpmemobj**, data replication is not supported in
**libpmemlog**. Thus, it is not allowed to specify replica sections in
pool set files.

ACKNOWLEDGEMENTS
================

**libpmemlog** builds on the persistent memory programming model
recommended by the SNIA NVM Programming Technical Work Group:

> http://snia.org/nvmp

SEE ALSO
========

**mmap**(2), **munmap**(2), **msync**(2), **strerror**(3),
**libpmemobj**(3), **libpmemblk**(3), **libpmem**(3), **libvmem**(3) and
**http://pmem.io**.

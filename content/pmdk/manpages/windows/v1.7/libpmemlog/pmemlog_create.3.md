---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["pmemlog_create.3.html"]
title: "libpmemlog | PMDK"
header: "pmemlog API version 1.1"
---

[comment]: <> (Copyright 2017-2018, Intel Corporation)

[comment]: <> (Redistribution and use in source and binary forms, with or without)
[comment]: <> (modification, are permitted provided that the following conditions)
[comment]: <> (are met:)
[comment]: <> (    * Redistributions of source code must retain the above copyright)
[comment]: <> (      notice, this list of conditions and the following disclaimer.)
[comment]: <> (    * Redistributions in binary form must reproduce the above copyright)
[comment]: <> (      notice, this list of conditions and the following disclaimer in)
[comment]: <> (      the documentation and/or other materials provided with the)
[comment]: <> (      distribution.)
[comment]: <> (    * Neither the name of the copyright holder nor the names of its)
[comment]: <> (      contributors may be used to endorse or promote products derived)
[comment]: <> (      from this software without specific prior written permission.)

[comment]: <> (THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS)
[comment]: <> ("AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT)
[comment]: <> (LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR)
[comment]: <> (A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT)
[comment]: <> (OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,)
[comment]: <> (SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT)
[comment]: <> (LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,)
[comment]: <> (DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY)
[comment]: <> (THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT)
[comment]: <> ((INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE)
[comment]: <> (OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.)

[comment]: <> (pmemlog_create.3 -- man page for libpmemlog create, open, close and  validate)

[NAME](#name)<br />
[SYNOPSIS](#synopsis)<br />
[DESCRIPTION](#description)<br />
[RETURN VALUE](#return-value)<br />
[CAVEATS](#caveats)<br />
[SEE ALSO](#see-also)<br />


# NAME #

**pmemlog_createU**()/**pmemlog_createW**(), **pmemlog_openU**()/**pmemlog_openW**(),
**pmemlog_close**(), **pmemlog_checkU**()/**pmemlog_checkW**()
- create, open, close and validate persistent memory resident log file


# SYNOPSIS #

```c
#include <libpmemlog.h>

PMEMlogpool *pmemlog_openU(const char *path);
PMEMlogpool *pmemlog_openW(const wchar_t *path);
PMEMlogpool *pmemlog_createU(const char *path, size_t poolsize, mode_t mode);
PMEMlogpool *pmemlog_createW(const wchar_t *path, size_t poolsize, mode_t mode);
void pmemlog_close(PMEMlogpool *plp);
int pmemlog_checkU(const char *path);
int pmemlog_checkW(const wchar_t *path);
```


>NOTE: The PMDK API supports UNICODE. If the **PMDK_UTF8_API** macro is
defined, basic API functions are expanded to the UTF-8 API with postfix *U*.
Otherwise they are expanded to the UNICODE API with postfix *W*.


# DESCRIPTION #

The **pmemlog_createU**()/**pmemlog_createW**() function creates a log memory pool with the given
total *poolsize*. Since the transactional nature of a log memory pool requires
some space overhead in the memory pool, the resulting available log size is
less than *poolsize*, and is made available to the caller via the
**pmemlog_nbyte**(3) function. *path* specifies the name of the memory pool
file to be created. *mode* specifies the permissions to use when creating the
file as described by **creat**(2). The memory pool file is fully allocated
to the size *poolsize* using **posix_fallocate**(3).
The caller may choose to take responsibility for creating the memory pool file
by creating it before calling **pmemlog_createU**()/**pmemlog_createW**() and then specifying
*poolsize* as zero. In this case **pmemlog_createU**()/**pmemlog_createW**() will take the pool size
from the size of the existing file and will verify that the file appears to be
empty by searching for any non-zero data in the pool header at the beginning of
the file. The net pool size of a pool file is equal to the file size.
The minimum net pool size allowed by the library for a log pool
is defined in **\<libpmemlog.h\>** as **PMEMLOG_MIN_POOL**.

Depending on the configuration of the system, the available non-volatile
memory space may be divided into multiple memory devices.
In such case, the maximum size of the pmemlog memory pool
could be limited by the capacity of a single memory device.
**libpmemlog**(7) allows building persistent memory
resident logs spanning multiple memory devices by creation of
persistent memory pools consisting of multiple files, where each part of
such a *pool set* may be stored on a different memory device
or pmem-aware filesystem.

Creation of all the parts of the pool set can be done with **pmemlog_createU**()/**pmemlog_createW**();
however, the recommended method for creating pool sets is with the
**pmempool**(1) utility.

When creating a pool set consisting of multiple files, the *path* argument
passed to **pmemlog_createU**()/**pmemlog_createW**() must point to the special *set* file that defines
the pool layout and the location of all the parts of the pool set. The
*poolsize* argument must be 0. The meaning of the *mode* argument
does not change, except that the same *mode* is used for creation of all the
parts of the pool set.

The set file is a plain text file, the structure of which is described in
**poolset**(5).

The **pmemlog_openU**()/**pmemlog_openW**() function opens an existing log memory pool.
Similar to **pmemlog_createU**()/**pmemlog_createW**(), *path* must identify either an existing
log memory pool file, or the *set* file used to create a pool set.
The application must have permission to open the file and memory map the
file or pool set with read/write permissions.

Be aware that if the pool contains bad blocks inside, opening can be aborted
by the SIGBUS signal, because currently the pool is not checked against
bad blocks during opening. It can be turned on by setting the CHECK_BAD_BLOCKS
compat feature. For details see description of this feature
in **pmempool-feature**(1).

The **pmemlog_close**() function closes the memory pool indicated by *plp*
and deletes the memory pool handle. The log memory pool itself lives on in
the file that contains it and may be re-opened at a later time using
**pmemlog_openU**()/**pmemlog_openW**() as described above.

The **pmemlog_checkU**()/**pmemlog_checkW**() function performs a consistency check of the file
indicated by *path*. **pmemlog_checkU**()/**pmemlog_checkW**() opens the given *path* read-only so
it never makes any changes to the file. This function is not supported on
Device DAX.


# RETURN VALUE #

On success, **pmemlog_createU**()/**pmemlog_createW**() returns a *PMEMlogpool\** handle to the
memory pool that is used with most of the functions from **libpmemlog**(7).
If an error prevents any of the pool set files from being
created, it returns NULL and sets *errno* appropriately.

On success, **pmemlog_openU**()/**pmemlog_openW**() returns a *PMEMlogpool\** handle to the
memory pool that is used with most of the functions from **libpmemlog**(7).
If an error prevents the pool from being opened, or a pool set is being
opened and the actual size of any file does not match the corresponding part
size defined in the *set* file, **pmemlog_openU**()/**pmemlog_openW**() returns NULL and sets
*errno* appropriately.

The **pmemlog_close**() function returns no value.

The **pmemlog_checkU**()/**pmemlog_checkW**() function returns 1 if the persistent memory
resident log file is found to be consistent.
Any inconsistencies will cause **pmemlog_checkU**()/**pmemlog_checkW**() to return 0,
in which case the use of the file with **libpmemlog** will result
in undefined behavior. The debug version of **libpmemlog** will provide
additional details on inconsistencies when **PMEMLOG_LOG_LEVEL** is at least 1,
as described in the **DEBUGGING AND ERROR HANDLING** section in
**libpmemlog**(7). **pmemlog_checkU**()/**pmemlog_checkW**() will return -1 and set *errno* if it
cannot perform the consistency check due to other errors.


# CAVEATS #

Not all file systems support **posix_fallocate**(3). **pmemlog_createU**()/**pmemlog_createW**() will
fail if the underlying file system does not support **posix_fallocate**(3).


# SEE ALSO #

**pmempool**(1), **creat**(2), **posix_fallocate**(3),
**pmemlog_nbyte**(3), **poolset**(5), **libpmemlog**(7)
and **<http://pmem.io>**

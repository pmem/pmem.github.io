---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["libpmemobj.3.html"]
title: "libpmemobj | PMDK"
header: "pmemobj API"
---

NAME
====

libpmemobj - persistent memory transactional object store

SYNOPSIS
========

    #include <libpmemobj.h>

    cc -std=gnu99 ... -lpmemobj -lpmem

    Most commonly used functions:

    PMEMobjpool *pmemobj_open(const char *path, const char *layout);
    PMEMobjpool *pmemobj_create(const char *path, const char *layout,
     size_t poolsize, mode_t mode);
    void pmemobj_close(PMEMobjpool *pop);

    Low-level memory manipulation:

    void *pmemobj_memcpy_persist(PMEMobjpool *pop, void *dest,
     const void *src, size_t len);
    void *pmemobj_memset_persist(PMEMobjpool *pop, void *dest,
     int c, size_t len);
    void pmemobj_persist(PMEMobjpool *pop, const void *addr, size_t len);
    void pmemobj_flush(PMEMobjpool *pop, const void *addr, size_t len);
    void pmemobj_drain(PMEMobjpool *pop);

    Locking:

    void pmemobj_mutex_zero(PMEMobjpool *pop, PMEMmutex *mutexp);
    int pmemobj_mutex_lock(PMEMobjpool *pop, PMEMmutex *mutexp);
    int pmemobj_mutex_timedlock(PMEMobjpool *pop,
     PMEMmutex *restrict mutexp,
     const struct timespec *restrict abs_timeout);
    int pmemobj_mutex_trylock(PMEMobjpool *pop, PMEMmutex *mutexp);
    int pmemobj_mutex_unlock(PMEMobjpool *pop, PMEMmutex *mutexp);

    void pmemobj_rwlock_zero(PMEMobjpool *pop, PMEMrwlock *rwlockp);
    int pmemobj_rwlock_rdlock(PMEMobjpool *pop, PMEMrwlock *rwlockp);
    int pmemobj_rwlock_wrlock(PMEMobjpool *pop, PMEMrwlock *rwlockp);
    int pmemobj_rwlock_timedrdlock(PMEMobjpool *pop,
     PMEMrwlock *restrict rwlockp,
     const struct timespec *restrict abs_timeout);
    int pmemobj_rwlock_timedwrlock(PMEMobjpool *pop,
     PMEMrwlock *restrict rwlockp,
     const struct timespec *restrict abs_timeout);
    int pmemobj_rwlock_tryrdlock(PMEMobjpool *pop, PMEMrwlock *rwlockp);
    int pmemobj_rwlock_trywrlock(PMEMobjpool *pop, PMEMrwlock *rwlockp);
    int pmemobj_rwlock_unlock(PMEMobjpool *pop, PMEMrwlock *rwlockp);

    void pmemobj_cond_zero(PMEMobjpool *pop, PMEMcond *condp);
    int pmemobj_cond_broadcast(PMEMobjpool *pop, PMEMcond *condp);
    int pmemobj_cond_signal(PMEMobjpool *pop, PMEMcond *condp);
    int pmemobj_cond_timedwait(PMEMobjpool *pop, PMEMcond *restrict condp,
     PMEMmutex *restrict mutexp, const struct timespec *restrict abs_timeout);
    int pmemobj_cond_wait(PMEMobjpool *pop, PMEMcond *condp,
     PMEMmutex *restrict mutexp);

    Persistent object identifier:

    OID_IS_NULL(PMEMoid oid)
    OID_EQUALS(PMEMoid lhs, PMEMoid rhs)

    Type-safety:

    TOID(TYPE)
    TOID_DECLARE(TYPE, uint64_t type_num)
    TOID_DECLARE_ROOT(ROOT_TYPE)

    TOID_TYPE_NUM(TYPE)
    TOID_TYPE_NUM_OF(TOID oid)
    TOID_VALID(TOID oid)
    OID_INSTANCEOF(PMEMoid oid, TYPE)

    TOID_ASSIGN(TOID oid, VALUE)

    TOID_IS_NULL(TOID oid)
    TOID_EQUALS(TOID lhs, TOID rhs)
    TOID_TYPEOF(TOID oid)
    DIRECT_RW(TOID oid)
    DIRECT_RO(TOID oid)
    D_RW(TOID oid)
    D_RO(TOID oid)

    Layout declaration:

    POBJ_LAYOUT_BEGIN(layout)
    POBJ_LAYOUT_TOID(layout, TYPE)
    POBJ_LAYOUT_ROOT(layout, ROOT_TYPE)
    POBJ_LAYOUT_END(layout)
    POBJ_LAYOUT_NAME(layout)
    POBJ_LAYOUT_TYPES_NUM(layout)

    Non-transactional atomic allocations:

    typedef int (*pmemobj_constr)(PMEMobjpool *pop,
     void *ptr, void *arg");

    int pmemobj_alloc(PMEMobjpool *pop, PMEMoid *oidp, size_t size,
     uint64_t type_num, pmemobj_constr constructor, void *arg);
    int pmemobj_zalloc(PMEMobjpool *pop, PMEMoid *oidp, size_t size,
     uint64_t type_num);
    int pmemobj_realloc(PMEMobjpool *pop, PMEMoid *oidp, size_t size,
     uint64_t type_num);
    int pmemobj_zrealloc(PMEMobjpool *pop, PMEMoid *oidp, size_t size,
     uint64_t type_num);
    int pmemobj_strdup(PMEMobjpool *pop, PMEMoid *oidp, const char *s,
     uint64_t type_num);
    void pmemobj_free(PMEMoid *oidp);
    size_t pmemobj_alloc_usable_size(PMEMoid oid);
    PMEMobjpool *pmemobj_pool_by_oid(PMEMoid oid);
    PMEMobjpool *pmemobj_pool_by_ptr(const void *addr);
    void *pmemobj_direct(PMEMoid oid);
    uint64_t pmemobj_type_num(PMEMoid oid);

    POBJ_NEW(PMEMobjpool *pop, TOID *oidp, TYPE,
     pmemobj_constr constructor, void *arg)
    POBJ_ALLOC(PMEMobjpool *pop, TOID *oidp, TYPE, size_t size,
     pmemobj_constr constructor, void *arg)
    POBJ_ZNEW(PMEMobjpool *pop, TOID *oidp, TYPE)
    POBJ_ZALLOC(PMEMobjpool *pop, TOID *oidp, TYPE, size_t size)
    POBJ_REALLOC(PMEMobjpool *pop, TOID *oidp, TYPE, size_t size)
    POBJ_ZREALLOC(PMEMobjpool *pop, TOID *oidp, TYPE, size_t size)
    POBJ_FREE(TOID *oidp)

    Root object management:

    PMEMoid pmemobj_root(PMEMobjpool *pop, size_t size);
    PMEMoid pmemobj_root_construct(PMEMobjpool *pop, size_t size,
     pmemobj_constr constructor, void *arg);
    size_t pmemobj_root_size(PMEMobjpool *pop);

    POBJ_ROOT(PMEMobjpool *pop, TYPE)

    Object containers:

    PMEMoid pmemobj_first(PMEMobjpool *pop);
    PMEMoid pmemobj_next(PMEMoid oid);

    POBJ_FIRST_TYPE_NUM(PMEMobjpool *pop, uint64_t type_num)
    POBJ_FIRST(PMEMobjpool *pop, TYPE)
    POBJ_NEXT_TYPE_NUM(PMEMoid oid)
    POBJ_NEXT(TOID oid)

    POBJ_FOREACH(PMEMobjpool *pop, PMEMoid varoid)
    POBJ_FOREACH_SAFE(PMEMobjpool *pop, PMEMoid varoid, PMEMoid nvaroid)
    POBJ_FOREACH_TYPE(PMEMobjpool *pop, TOID var)
    POBJ_FOREACH_SAFE_TYPE(PMEMobjpool *pop, TOID var, TOID nvar)

    Non-transactional persistent atomic circular doubly-linked list:

    int pmemobj_list_insert(PMEMobjpool *pop, size_t pe_offset, void *head,
     PMEMoid dest, int before, PMEMoid oid);
    PMEMoid pmemobj_list_insert_new(PMEMobjpool *pop, size_t pe_offset, void *head,
     PMEMoid dest, int before, size_t size, uint64_t type_num,
     pmemobj_constr constructor, void *arg);
    int pmemobj_list_remove(PMEMobjpool *pop, size_t pe_offset, void *head,
     PMEMoid oid, int free);
    int pmemobj_list_move(PMEMobjpool *pop,
     size_t pe_old_offset, void *head_old,
     size_t pe_new_offset, void *head_new,
     PMEMoid dest, int before, PMEMoid oid);

    POBJ_LIST_ENTRY(TYPE)
    POBJ_LIST_HEAD(HEADNAME, TYPE)

    POBJ_LIST_FIRST(POBJ_LIST_HEAD *head)
    POBJ_LIST_LAST(POBJ_LIST_HEAD *head, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_EMPTY(POBJ_LIST_HEAD *head)
    POBJ_LIST_NEXT(TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_PREV(TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_DEST_HEAD
    POBJ_LIST_DEST_TAIL

    POBJ_LIST_FOREACH(TOID var, POBJ_LIST_HEAD *head, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_FOREACH_REVERSE(TOID var, POBJ_LIST_HEAD *head, POBJ_LIST_ENTRY FIELD)

    POBJ_LIST_INSERT_HEAD(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_INSERT_TAIL(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_INSERT_AFTER(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID listelm, TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_INSERT_BEFORE(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID listelm, TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_INSERT_NEW_HEAD(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     POBJ_LIST_ENTRY FIELD, size_t size,
     pmemobj_constr constructor, void *arg)
    POBJ_LIST_INSERT_NEW_TAIL(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     POBJ_LIST_ENTRY FIELD, size_t size,
     void (*constructor)(PMEMobjpool *pop, void *ptr, void *arg),
     void *arg)
    POBJ_LIST_INSERT_NEW_AFTER(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID listelm, POBJ_LIST_ENTRY FIELD, size_t size,
     pmemobj_constr constructor, void *arg)
    POBJ_LIST_INSERT_NEW_BEFORE(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID listelm, POBJ_LIST_ENTRY FIELD, size_t size,
     pmemobj_constr constructor, void *arg)
    POBJ_LIST_REMOVE(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_REMOVE_FREE(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     TOID elm, POBJ_LIST_ENTRY FIELD)
    POBJ_LIST_MOVE_ELEMENT_HEAD(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     POBJ_LIST_HEAD *head_new, TOID elm, POBJ_LIST_ENTRY FIELD,
     POBJ_LIST_ENTRY field_new)
    POBJ_LIST_MOVE_ELEMENT_TAIL(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     POBJ_LIST_HEAD *head_new, TOID elm, POBJ_LIST_ENTRY FIELD,
     POBJ_LIST_ENTRY field_new)
    POBJ_LIST_MOVE_ELEMENT_AFTER(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     POBJ_LIST_HEAD *head_new, TOID listelm, TOID elm,
     POBJ_LIST_ENTRY FIELD, POBJ_LIST_ENTRY field_new)
    POBJ_LIST_MOVE_ELEMENT_BEFORE(PMEMobjpool *pop, POBJ_LIST_HEAD *head,
     POBJ_LIST_HEAD *head_new, TOID listelm, TOID elm,
     POBJ_LIST_ENTRY FIELD, POBJ_LIST_ENTRY field_new)

    Transactional object manipulation:

    "enumtx_stagepmemobj_tx_stage(void);

    int pmemobj_tx_begin(PMEMobjpool *pop, jmp_buf *env, enum tx_lock, ...);
    int pmemobj_tx_lock(enum tx_lock lock_type, void *lockp);
    void pmemobj_tx_abort(int errnum);
    "voidpmemobj_tx_commit(void);
    "intpmemobj_tx_end(void);
    "intpmemobj_tx_errno(void);
    "voidpmemobj_tx_process(void);

    int pmemobj_tx_add_range(PMEMoid oid, uint64_t off, size_t size);
    int pmemobj_tx_add_range_direct(const void *ptr, size_t size);
    PMEMoid pmemobj_tx_alloc(size_t size, uint64_t type_num);
    PMEMoid pmemobj_tx_zalloc(size_t size, uint64_t type_num);
    PMEMoid pmemobj_tx_realloc(PMEMoid oid, size_t size, uint64_t type_num);
    PMEMoid pmemobj_tx_zrealloc(PMEMoid oid, size_t size, uint64_t type_num);
    PMEMoid pmemobj_tx_strdup(const char *s, uint64_t type_num);
    int pmemobj_tx_free(PMEMoid oid);

    TX_BEGIN_LOCK(PMEMobjpool *pop, ...)
    TX_BEGIN(PMEMobjpool *pop)
    TX_ONABORT
    TX_ONCOMMIT
    TX_FINALLY
    TX_END

    TX_ADD(TOID o)
    TX_ADD_FIELD(TOID o, FIELD)
    TX_ADD_DIRECT(TYPE *p)
    TX_ADD_FIELD_DIRECT(TYPE *p, FIELD)

    TX_NEW(TYPE)
    TX_ALLOC(TYPE, size_t size)
    TX_ZNEW(TYPE)
    TX_ZALLOC(TYPE, size_t size)
    TX_REALLOC(TOID o, size_t size)
    TX_ZREALLOC(TOID o, size_t size)
    TX_STRDUP(const char *s, uint64_t type_num)
    TX_FREE(TOID o)

    TX_SET(TOID o, FIELD, VALUE)
    TX_SET_DIRECT(TYPE *p, FIELD, VALUE)
    TX_MEMCPY(void *dest, const void *src, size_t num)
    TX_MEMSET(void *dest, int c, size_t num)

    Library API versioning:

    const char *pmemobj_check_version(
     unsigned major_required,
     unsigned minor_required);

    Managing library behavior:

    void pmemobj_set_funcs(
     void *(*malloc_func)(size_t size),
     void (*free_func)(void *ptr),
     void *(*realloc_func)(void *ptr, size_t size),
     char *(*strdup_func)(const char *s));
    int pmemobj_check(const char *path, const char *layout);

    Error handling:

    "constchar*pmemobj_errormsg(void);

DESCRIPTION
===========

**libpmemobj** provides a transactional object store in *persistent
memory* (pmem). This library is intended for applications using direct
access storage (DAX), which is storage that supports load/store access
without paging blocks from a block storage device. Some types of
*non-volatile memory DIMMs* (NVDIMMs) provide this type of byte
addressable access to storage. A *persistent memory aware file system*
is typically used to expose the direct access to applications. Memory
mapping a file from this type of file system results in the load/store,
non-paged access to pmem. **libpmemobj** builds on this type of memory
mapped file.

This library is for applications that need a transactions and persistent
memory management. The **libpmemobj** requires a **-std=gnu99**
compilation flag to build properly. This library builds on the low-level
pmem support provided by **libpmem**, handling the transactional
updates, flushing changes to persistence, and recovery for the
application.

**libpmemobj** is one of a collection of persistent memory libraries
available, the others are:

> **libpmemblk**(3), providing pmem-resident arrays of fixed-sized
> blocks with atomic updates.
>
> **libpmemlog**(3), providing a pmem-resident log file.
>
> **libpmem**(3), low-level persistent memory support.

Under normal usage, **libpmemobj** will never print messages or
intentionally cause the process to exit. The only exception to this is
the debugging information, when enabled, as described under **DEBUGGING
AND ERROR HANDLING** below.

MOST COMMONLY USED FUNCTIONS
============================

To use the pmem-resident transactional object store provided by
**libpmemobj**, a *memory pool* is first created. This is done with the
**pmemobj\_create**() function described in this section. The other
functions described in this section then operate on the resulting memory
pool.

Once created, the memory pool is represented by an opaque handle, of
type *PMEMobjpool \**, which is passed to most of the other functions in
this section. Internally, **libpmemobj** will use either
**pmem\_persist**() or **msync**(2) when it needs to flush changes,
depending on whether the memory pool appears to be persistent memory or
a regular file (see the **pmem\_is\_pmem**() function in **libpmem**(3)
for more information). There is no need for applications to flush
changes directly when using the obj memory API provided by
**libpmemobj**.

**PMEMobjpool \*pmemobj\_open(const char \****path***, const char
\****layout***);**

> The **pmemobj\_open**() function opens an existing object store memory
> pool, returning a memory pool handle used with most of the functions
> in this section. *path* must be an existing file containing a pmemobj
> memory pool as created by **pmemobj\_create**(). If *layout* is
> non-NULL, it is compared to the layout name provided to
> **pmemobj\_create**() when the pool was first created. This can be
> used to verify the layout of the pool matches what was expected. The
> application must have permission to open the file and memory map it
> with read/write permissions. If an error prevents the pool from being
> opened, or if the given *layout* does not match the pool\'s layout,
> **pmemobj\_open**() returns NULL and sets errno appropriately.

**PMEMobjpool \*pmemobj\_create(const char \****path***, const char
\****layout***,**\
** size\_t ***poolsize***, mode\_t ***mode***);**

> The **pmemobj\_create**() function creates a transactional object
> store with the given total *poolsize*. *path* specifies the name of
> the memory pool file to be created. *layout* specifies the
> application\'s layout type in the form of a string. The layout name is
> not interpreted by **libpmemobj**, but may be used as a check when
> **pmemobj\_open**() is called. The layout name, including the null
> termination, cannot be longer than **PMEMOBJ\_MAX\_LAYOUT** as defined
> in **\<libpmemobj.h\>**. It is allowed to pass NULL as *layout*, which
> is equivalent for using an empty string as a layout name. *mode*
> specifies the permissions to use when creating the file as described
> by **creat**(2). The memory pool file is fully allocated to the size
> *poolsize* using **posix\_fallocate**(3). The caller may choose to
> take responsibility for creating the memory pool file by creating it
> before calling **pmemobj\_create**() and then specifying *poolsize* as
> zero. In this case **pmemobj\_create**() will take the pool size from
> the size of the existing file and will verify that the file appears to
> be empty by searching for any non-zero data in the pool header at the
> beginning of the file. The minimum file size allowed by the library
> for a transactional object store is defined in **\<libpmemobj.h\>** as
> **PMEMOBJ\_MIN\_POOL**.

**void pmemobj\_close(PMEMobjpool \****pop***);**

> The **pmemobj\_close**() function closes the memory pool indicated by
> *pop* and deletes the memory pool handle. The object store itself
> lives on in the file that contains it and may be re-opened at a later
> time using **pmemobj\_open**() as described above.

LOW-LEVEL MEMORY MANIPULATION
=============================

The **libpmemobj** specific low-level memory manipulation functions
leverage the knowledge of the additional configuration options available
for **libpmemobj** pools, such as replication. They also take advantage
of the type of storage behind the pool and use appropriate flush/drain
functions. It is advised to use these functions in conjunction with
**libpmemobj** objects, instead of using low-level memory manipulations
functions from **libpmem**.

**void pmemobj\_persist(PMEMobjpool \****pop***, const void
\****addr***, size\_t ***len***);**

> Forces any changes in the range \[*addr*, *addr*+*len*) to be stored
> durably in persistent memory. Internally this may call either
> **pmem\_msync**() or **pmem\_persist**(). There are no alignment
> restrictions on the range described by *addr* and *len*, but
> **pmemobj\_persist**() may expand the range as necessary to meet
> platform alignment requirements.
>
> WARNING: Like **msync**(2), there is nothing atomic or transactional
> about this call. Any unwritten stores in the given range will be
> written, but some stores may have already been written by virtue of
> normal cache eviction/replacement policies. Correctly written code
> must not depend on stores waiting until **pmemobj\_persist**() is
> called to become persistent \-- they can become persistent at any time
> before **pmemobj\_persist**() is called.

**void pmemobj\_flush(PMEMobjpool \****pop***, const void \****addr***,
size\_t ***len***);**\
**void pmemobj\_drain(PMEMobjpool \****pop***);**

> These functions provide partial versions of the **pmemobj\_persist**()
> function described above. **pmemobj\_persist**() can be thought of as
> this:
>
>     void
>     pmemobj_persist(PMEMobjpool *pop, const void *addr, size_t len)
>     {
>         /* flush the processor caches */
>         pmemobj_flush(pop, addr, len);
>
>         /* wait for any pmem stores to drain from HW buffers */
>         pmemobj_drain(pop);
>     }
>
> These functions allow advanced programs to create their own variations
> of **pmemobj\_persist**(). For example, a program that needs to flush
> several discontiguous ranges can call **pmemobj\_flush**() for each
> range and then follow up by calling **pmemobj\_drain**() once. For
> more information on partial flushing operations see the **libpmem**
> manpage.

**void \*pmemobj\_memcpy\_persist(PMEMobjpool \****pop***, void
\****dest***,**\
** const void \****src***, size\_t ***len***);**\
**void \*pmemobj\_memset\_persist(PMEMobjpool \****pop***, void
\****dest***,**\
** int ***c***, size\_t ***len***);**

> The **pmemobj\_memcpy\_persist**(), and
> **pmemobj\_memset\_persist**(), functions provide the same memory
> copying as their namesakes **memcpy**(3), and **memset**(3), and
> ensure that the result has been flushed to persistence before
> returning. For example, the following code is functionally equivalent
> to **pmemobj\_memcpy\_persist**():
>
>     void *
>     pmemobj_memcpy_persist(PMEMobjpool *pop, void *dest,
>         const void *src, size_t len)
>     {
>         void *retval = memcpy(pop, dest, src, len);
>
>         pmemobj_persist(pop, dest, len);
>
>         return retval;
>     }

POOL SETS AND REPLICAS
======================

Depending on the configuration of the system, the available space of
non-volatile memory space may be divided into multiple memory devices.
In such case, the maximum size of the transactional object store could
be limited by the capacity of a single memory device. The **libpmemobj**
allows building transactional object stores spanning multiple memory
devices by creation of persistent memory pools consisting of multiple
files, where each part of such a *pool set* may be stored on different
pmem-aware filesystem.

To improve reliability and eliminate the single point of failure, all
the changes of the data stored in the persistent memory pool could be
also automatically written to local pool replicas, thereby providing a
backup for a persistent memory pool by producing a *mirrored pool set*.
In practice, the pool replicas may be considered binary copies of the
\"master\" pool set.

Creation of all the parts of the pool set and the associated replica
sets can be done with the **pmemobj\_create**() function or by using the
**pmempool**(1) utility.

When creating the pool set consisting of multiple files, or when
creating the replicated pool set, the *path* argument passed to
**pmemobj\_create**() must point to the special *set* file that defines
the pool layout and the location of all the parts of the pool set. The
*poolsize* argument must be 0. The meaning of *layout* and *mode*
arguments doesn\'t change, except that the same *mode* is used for
creation of all the parts of the pool set and replicas. If the error
prevents any of the pool set files from being created,
**pmemobj\_create**() returns NULL and sets errno appropriately.

When opening the pool set consisting of multiple files, or when opening
the replicated pool set, the *path* argument passed to
**pmemobj\_open**() must not point to the pmemobj memory pool file, but
to the same *set* file that was used for the pool set creation. If an
error prevents any of the pool set files from being opened, or if the
actual size of any file does not match the corresponding part size
defined in *set* file **pmemobj\_open**() returns NULL and sets errno
appropriately.

The set file is a plain text file, which must start with the line
containing a *PMEMPOOLSET* string, followed by the specification of all
the pool parts in the next lines. For each part, the file size and the
absolute path must be provided. The size has to be compliant with the
format specified in IEC 80000-13, IEEE 1541 or the Metric Interchange
Format. Standards accept SI units with obligatory B - kB, MB, GB, \...
(multiplier by 1000) and IEC units with optional \"iB\" - KiB, MiB, GiB,
\..., K, M, G, \... - (multiplier by 1024).

The minimum file size of each part of the pool set is the same as the
minimum size allowed for a transactional object store consisting of one
file. It is defined in **\<libpmemobj.h\>** as **PMEMOBJ\_MIN\_POOL**.
Sections defining the replica sets are optional. There could be multiple
replica sections and each must start with the line containing a
*REPLICA* string. Lines starting with \"\#\" character are ignored.

Here is the example \"myobjpool.set\" file:

    PMEMPOOLSET
    100G /mountpoint0/myfile.part0
    200G /mountpoint1/myfile.part1
    400G /mountpoint2/myfile.part2
    REPLICA
    500G /mountpoint3/mymirror.part0
    200G /mountpoint4/mymirror.part1

The files in the set may be created by running the following command:

    pmempool create --layout="mylayout" obj myobjpool.set

LOCKING
=======

**libpmemobj** provides several types of synchronization primitives,
designed so as to use them with persistent memory. The locks are not
dynamically allocated, but embedded in pmem-resident objects. For
performance reasons, they are also padded up to 64 bytes (cache line
size).

Pmem-aware locks implementation is based on the standard POSIX Thread
Library, as described in **pthread\_mutex**(3), **pthread\_rwlock**(3)
and **pthread\_cond**(3). They provide semantics similar to standard
**pthread** locks, except that they are considered initialized by
zeroing them. So allocating the locks with **pmemobj\_zalloc**() or
**pmemobj\_tx\_zalloc**() does not require another initialization step.

The fundamental property of pmem-aware locks is their automatic
reinitialization every time the persistent object store pool is open.
This way, all the pmem-aware locks may be considered initialized
(unlocked) right after opening the pool, regardless of their state at
the time the pool was closed for the last time.

Pmem-aware mutexes, read/write locks and condition variables must be
declared with one of the *PMEMmutex*, *PMEMrwlock*, or *PMEMcond* type
respectively.

**void pmemobj\_mutex\_zero(PMEMobjpool \****pop***, PMEMmutex
\****mutexp***);**

> The **pmemobj\_mutex\_zero**() function explicitly initializes
> pmem-aware mutex pointed by *mutexp* by zeroing it. Initialization is
> not necessary if the object containing the mutex has been allocated
> using one of **pmemobj\_zalloc**() or **pmemobj\_tx\_zalloc**()
> functions.

**int pmemobj\_mutex\_lock(PMEMobjpool \****pop***, PMEMmutex
\****mutexp***);**

> The **pmemobj\_mutex\_lock**() function locks pmem-aware mutex pointed
> by *mutexp*. If the mutex is already locked, the calling thread will
> block until the mutex becomes available. If this is the first use of
> the mutex since opening of the pool *pop*, the mutex is automatically
> reinitialized and then locked.

**int pmemobj\_mutex\_timedlock(PMEMobjpool \****pop***,**\
** PMEMmutex \*restrict ***mutexp***,**\
** const struct timespec \*restrict ***abs\_timeout***);**

> The **pmemobj\_mutex\_timedlock**() performs the same action as
> **pmemobj\_mutex\_lock**(), but will not wait beyond *abs\_timeout* to
> obtain the lock before returning.

**int pmemobj\_mutex\_trylock(PMEMobjpool \****pop***, PMEMmutex
\****mutexp***);**

> The **pmemobj\_mutex\_trylock**() function locks pmem-aware mutex
> pointed by *mutexp*. If the mutex is already locked,
> **pthread\_mutex\_trylock**() will not block waiting for the mutex,
> but will return an error condition. If this is the first use of the
> mutex since opening of the pool *pop* the mutex is automatically
> reinitialized and then locked.

**int pmemobj\_mutex\_unlock(PMEMobjpool \****pop***, PMEMmutex
\****mutexp***);**

> The **pmemobj\_mutex\_unlock**() function unlocks an acquired
> pmem-aware mutex pointed by *mutexp*. Undefined behavior follows if a
> thread tries to unlock a mutex that has not been locked by it, or if a
> thread tries to release a mutex that is already unlocked or not
> initialized.

**void pmemobj\_rwlock\_zero(PMEMobjpool \****pop***, PMEMrwlock
\****rwlockp***);**

> The **pmemobj\_rwlock\_zero**() function is used to explicitly
> initialize pmem-aware read/write lock pointed by *rwlockp* by zeroing
> it. Initialization is not necessary if the object containing the lock
> has been allocated using one of **pmemobj\_zalloc**() or
> **pmemobj\_tx\_zalloc**() functions.

**int pmemobj\_rwlock\_rdlock(PMEMobjpool \****pop***, PMEMrwlock
\****rwlockp***);**

> The **pmemobj\_rwlock\_rdlock**() function acquires a read lock on
> *rwlockp* provided that lock is not presently held for writing and no
> writer threads are presently blocked on the lock. If the read lock
> cannot be immediately acquired, the calling thread blocks until it can
> acquire the lock. If this is the first use of the lock since opening
> of the pool *pop*, the lock is automatically reinitialized and then
> acquired.

**int pmemobj\_rwlock\_timedrdlock(PMEMobjpool \****pop***,**\
** PMEMrwlock \*restrict ***rwlockp***,**\
** const struct timespec \*restrict ***abs\_timeout***);**

> The **pmemobj\_rwlock\_timedrdlock**() performs the same action, but
> will not wait beyond *abs\_timeout* to obtain the lock before
> returning.

A thread may hold multiple concurrent read locks. If so,
**pmemobj\_rwlock\_unlock**() must be called once for each lock
obtained.

The results of acquiring a read lock while the calling thread holds a
write lock are undefined.

**int pmemobj\_rwlock\_wrlock(PMEMobjpool \****pop***, PMEMrwlock
\****rwlockp***);**

> The **pmemobj\_rwlock\_wrlock**() function blocks until a write lock
> can be acquired against lock pointed by *rwlockp*. If this is the
> first use of the lock since opening of the pool *pop*, the lock is
> automatically reinitialized and then acquired.

**int pmemobj\_rwlock\_timedwrlock(PMEMobjpool \****pop***,**\
** PMEMrwlock \*restrict ***rwlockp***,**\
** const struct timespec \*restrict ***abs\_timeout***);**

> The **pmemobj\_rwlock\_timedwrlock**() performs the same action, but
> will not wait beyond *abs\_timeout* to obtain the lock before
> returning.

**int pmemobj\_rwlock\_tryrdlock(PMEMobjpool \****pop***, PMEMrwlock
\****rwlockp***);**

> The **pmemobj\_rwlock\_tryrdlock**() function performs the same action
> as **pmemobj\_rwlock\_rdlock**(), but does not block if the lock
> cannot be immediately obtained.
>
> The results are undefined if the calling thread already holds the lock
> at the time the call is made.

**int pmemobj\_rwlock\_trywrlock(PMEMobjpool \****pop***, PMEMrwlock
\****rwlockp***);**

> The **pmemobj\_rwlock\_trywrlock**() function performs the same action
> as **pmemobj\_rwlock\_wrlock**(), but does not block if the lock
> cannot be immediately obtained.
>
> The results are undefined if the calling thread already holds the lock
> at the time the call is made.

**int pmemobj\_rwlock\_unlock(PMEMobjpool \****pop***, PMEMrwlock
\****rwlockp***);**

> The **pmemobj\_rwlock\_unlock**() function is used to release the
> read/write lock previously obtained by **pmemobj\_rwlock\_rdlock**(),
> **pmemobj\_rwlock\_wrlock**(), **pthread\_rwlock\_tryrdlock**(), or
> **pmemobj\_rwlock\_trywrlock**().

**void pmemobj\_cond\_zero(PMEMobjpool \****pop***, PMEMcond
\****condp***);**

> The **pmemobj\_cond\_zero**() function explicitly initializes
> pmem-aware condition variable by zeroing it. Initialization is not
> necessary if the object containing the condition variable has been
> allocated using one of **pmemobj\_zalloc**() or
> **pmemobj\_tx\_zalloc**() functions.

**int pmemobj\_cond\_broadcast(PMEMobjpool \****pop***, PMEMcond
\****condp***);**

**int pmemobj\_cond\_signal(PMEMobjpool \****pop***, PMEMcond
\****condp***);**

> The difference between **pmemobj\_cond\_broadcast**() and
> **pmemobj\_cond\_signal**() is that the former unblocks all threads
> waiting for the condition variable, whereas the latter blocks only one
> waiting thread. If no threads are waiting on *cond*, neither function
> has any effect. If more than one thread is blocked on a condition
> variable, the used scheduling policy determines the order in which
> threads are unblocked. The same mutex used for waiting must be held
> while calling either function. Although neither function strictly
> enforces this requirement, undefined behavior may follow if the mutex
> is not held.

**int pmemobj\_cond\_timedwait(PMEMobjpool \****pop***, PMEMcond
\*restrict ***condp***,**\
** PMEMmutex \*restrict ***mutexp***,**\
** const struct timespec \*restrict ***abs\_timeout***);**

**int pmemobj\_cond\_wait(PMEMobjpool \****pop***, PMEMcond
\****condp***,**\
** PMEMmutex \*restrict ***mutexp***);**

> The **pmemobj\_cond\_timedwait**() and **pmemobj\_cond\_wait**()
> functions shall block on a condition variable. They shall be called
> with mutex locked by the calling thread or undefined behavior results.
> These functions atomically release mutex pointed by *mutexp* and cause
> the calling thread to block on the condition variable *cond*;
> atomically here means \"atomically with respect to access by another
> thread to the mutex and then the condition variable\". That is, if
> another thread is able to acquire the mutex after the about-to-block
> thread has released it, then a subsequent call to
> **pmemobj\_cond\_broadcast**() or **pmemobj\_cond\_signal**() in that
> thread shall behave as if it were issued after the about-to-block
> thread has blocked. Upon successful return, the mutex shall have been
> locked and shall be owned by the calling thread.

PERSISTENT OBJECTS
==================

Each object stored in persistent memory pool is represented by an object
handle of type *PMEMoid*. In practice, such a handle is a unique Object
IDentifier (OID) of a global scope, which means that two objects from
different pools may not have the same OID. The special *OID\_NULL* macro
defines a NULL-like handle that does not represent any object. The size
of a single object is limited by a *PMEMOBJ\_MAX\_ALLOC\_SIZE*. Thus an
allocation with requested size greater than this value will fail.

An OID cannot be considered as a direct pointer to an object. Each time
the program attempts to read or write object data, it must obtain the
current memory address of the object by converting its OID into the
pointer.

In contrast to the memory address, the OID value for given object does
not change during the life of an object (except for realloc operation),
and remains valid after closing and reopening the pool. For this reason,
if an object contains a reference to another persistent object -
necessary to build some kind of a linked data structure - it shall never
use memory address of an object, but its OID.

**void \*pmemobj\_direct(PMEMoid ***oid***);**

> The **pmemobj\_direct**() function returns a pointer to an object
> represented by *oid*. If OID\_NULL is passed as an argument, function
> returns NULL.

**uint64\_t pmemobj\_type\_num(PMEMoid ***oid***);**

> The **pmemobj\_type\_num**() function returns a type number of the
> object represented by *oid*.

**PMEMobjpool \*pmemobj\_pool\_by\_oid(PMEMoid ***oid***);**

> The **pmemobj\_pool\_by\_oid**() function returns a handle to the pool
> which contains the object represented by *oid*. If the pool is not
> open or OID\_NULL is passed as an argument, function returns NULL.

**PMEMobjpool \*pmemobj\_pool\_by\_ptr(const void \****addr***);**

> The **pmemobj\_pool\_by\_ptr**() function returns a handle to the pool
> which contains the address. If the address does not belong to any open
> pool, function returns NULL.

At the time of allocation (or reallocation), each object may be assigned
a number representing its type. Such a *type number* may be used to
arrange the persistent objects based on their actual user-defined
structure type, thus facilitating implementation of a simple run-time
type safety mechanism. It also allows to iterate through all the objects
of given type stored in the persistent memory pool. See **OBJECT
CONTAINERS** section for more details.

The **OID\_IS\_NULL** macro checks if given *PMEMoid* represents a NULL
object.

The **OID\_EQUALS** macro compares two *PMEMoid* objects.

TYPE-SAFETY
===========

Operating on untyped object handles, as well as on direct untyped object
pointers (void \*) may be confusing and error prone. To facilitate
implementation of type safety mechanism, **libpmemobj** defines a set of
macros that provide a static type enforcement, catching potential errors
at compile time. For example, a compile-time error is generated when an
attempt is made to assign a handle to an object of one type to the
object handle variable of another type of object.

**TOID\_DECLARE(***TYPE***, uint64\_t ***type\_num)*

> The **TOID\_DECLARE** macro declares a typed OID of user-defined type
> specified by argument *TYPE*, and with type number specified by
> argument *type\_num*.

**TOID\_DECLARE\_ROOT(***ROOT\_TYPE***)**

> The **TOID\_DECLARE\_ROOT** macro declares a typed OID of user-defined
> type specified by argument *ROOT\_TYPE*, and with type number for root
> object **POBJ\_ROOT\_TYPE\_NUM**.

**TOID(***TYPE***)**

> The **TOID** macro declares a handle to an object of type specified by
> argument *TYPE*, where *TYPE* is the name of a user-defined structure.
> The typed OID must be declared first using the **TOID\_DECLARE**,
> **TOID\_DECLARE\_ROOT**, **POBJ\_LAYOUT\_TOID** or
> **POBJ\_LAYOUT\_ROOT** macros.

**TOID\_TYPE\_NUM(***TYPE***)**

> The **TOID\_TYPE\_NUM** macro returns a type number of the type
> specified by argument *TYPE*.

**TOID\_TYPE\_NUM\_OF(TOID ***oid***\")**

> The **TOID\_TYPE\_NUM\_OF** macro returns a type number of the object
> specified by argument *oid*. The type number is read from the typed
> OID.

**TOID\_VALID(TOID ***oid***\")**

> The **TOID\_VALID** macro validates whether the type number stored in
> object\'s metadata is equal to the type number read from typed OID.

**OID\_INSTANCEOF(PMEMoid ***oid***, ***TYPE***)**

> The **OID\_INSTANCEOF** macro checks whether the *oid* is of the type
> specified by argument *TYPE*.

**TOID\_ASSIGN(TOID ***o***, ***VALUE***)**

> The **TOID\_ASSIGN** macro assigns an object handle specified by
> *VALUE* to the variable *o*.

**TOID\_IS\_NULL(TOID ***o***)**

> The **TOID\_IS\_NULL** macro evaluates to true if the object handle
> represented by argument *o* has OID\_NULL value.

**TOID\_EQUALS(TOID ***lhs***, TOID ***rhs***)**

> The **TOID\_EQUALS** macro evaluates to true if both *lhs* and *rhs*
> object handles are referencing the same persistent object.

**TOID\_TYPEOF(TOID ***o***)**

> The **TOID\_TYPEOF** macro returns a type of the object handle
> represented by argument *o*.

**DIRECT\_RW(TOID ***oid***)**

**D\_RW(TOID ***oid***)**

> The **DIRECT\_RW**() macro and its shortened form **D\_RW**() return a
> typed write pointer (TYPE \*) to an object represented by *oid*. If
> *oid* holds OID\_NULL value, the macro evaluates to NULL.

**DIRECT\_RO(TOID ***oid***)**

**D\_RO(TOID ***oid***)**

> The **DIRECT\_RO**() macro and its shortened form **D\_RO**() return a
> typed read-only (const) pointer (TYPE \*) to an object represented by
> *oid*. If *oid* holds OID\_NULL value, the macro evaluates to NULL.

LAYOUT DECLARATION
==================

The *libpmemobj* defines a set of macros for convenient declaration of
pool\'s layout. The declaration of layout consist of declaration of
number of used types. The declared types will be assigned consecutive
type numbers. Declared types may be used in conjunction with type safety
macros. Once created the layout declaration shall not be changed unless
the new types are added at the end of the existing layout declaration.
Modifying any of existing declaration may lead to changes in type
numbers of declared types which in consequence may cause data
corruption.

**POBJ\_LAYOUT\_BEGIN(***LAYOUT***)**

> The **POBJ\_LAYOUT\_BEGIN** macro indicates a begin of declaration of
> layout. The *LAYOUT* argument is a name of layout. This argument must
> be passed to all macros related to the declaration of layout.

**POBJ\_LAYOUT\_TOID(***LAYOUT***, ***TYPE***)**

> The **POBJ\_LAYOUT\_TOID** macro declares a typed OID for type passed
> as *TYPE* argument inside the declaration of layout. All types
> declared using this macro are assigned with consecutive type numbers.
> This macro must be used between the **POBJ\_LAYOUT\_BEGIN** and
> **POBJ\_LAYOUT\_END** macros, with the same name passed as *LAYOUT*
> argument.

**POBJ\_LAYOUT\_ROOT(***LAYOUT***, ***ROOT\_TYPE***)**

> The **POBJ\_LAYOUT\_ROOT** macro declares a typed OID for type passed
> as *ROOT\_TYPE* argument inside the declaration of layout. The typed
> OID will be assigned with type number for root object
> **POBJ\_ROOT\_TYPE\_NUM**.

**POBJ\_LAYOUT\_END(***LAYOUT***)**

> The **POBJ\_LAYOUT\_END** macro ends the declaration of layout.

**POBJ\_LAYOUT\_NAME(***LAYOUT***)**

> The **POBJ\_LAYOUT\_NAME** macro returns the name of layout as a
> NULL-terminated string.

**POBJ\_LAYOUT\_TYPES\_NUM(***LAYOUT***)**

> The **POBJ\_LAYOUT\_TYPES\_NUM** macro returns number of types
> declared using the **POBJ\_LAYOUT\_TOID** macro within the layout
> declaration.

This is an example of layout declaration:

    POBJ_LAYOUT_BEGIN(mylayout);
    POBJ_LAYOUT_ROOT(mylayout, struct root);
    POBJ_LAYOUT_TOID(mylayout, struct node);
    POBJ_LAYOUT_TOID(mylayout, struct foo);
    POBJ_LAYOUT_END(mylayout);

    struct root {
    	TOID(struct node) node;
    };

    struct node {
    	TOID(struct node) next;
    	TOID(struct foo) foo;
    };

The name of layout and the number of declared types can be retrieved
using the following code:

    const char *layout_name = POBJ_LAYOUT_NAME(mylayout);
    int num_of_types = POBJ_LAYOUT_TYPES_NUM(mylayout);

OBJECT CONTAINERS
=================

All the objects in the persistent memory pool are assigned a *type
number* and are accessible by it.

The *libpmemobj* provides a mechanism allowing to iterate through the
internal object collection, either looking for a specific object, or
performing a specific operation on each object of given type. Software
should not make any assumptions about the order of the objects in the
internal object containers.

**PMEMoid pmemobj\_first(PMEMobjpool \****pop***);**

> The **pmemobj\_first**() function returns the first object from the
> pool. If the pool is empty, OID\_NULL is returned.

**POBJ\_FIRST(PMEMobjpool \****pop***, ***TYPE***)**

> The **POBJ\_FIRST** macro returns the first object from the pool of
> the type specified by *TYPE*.

**POBJ\_FIRST\_TYPE\_NUM(PMEMobjpool \****pop***, uint64\_t
***type\_num***)**

> The **POBJ\_FIRST\_TYPE\_NUM** macro returns the first object from the
> pool of the type specified by *type\_num*.

**PMEMoid pmemobj\_next(PMEMoid ***oid***);**

> The **pmemobj\_next**() function returns the next object from the
> pool. If an object referenced by *oid* is the last object in the
> collection, or if the OID\_NULL is passed as an argument, function
> returns OID\_NULL.

**POBJ\_NEXT(TOID ***oid***)**

> The **POBJ\_NEXT** macro returns the next object of the same type as
> the object referenced by *oid*.

**POBJ\_NEXT\_TYPE\_NUM(PMEMoid ***oid***)**

> The **POBJ\_NEXT\_TYPE\_NUM** macro returns the next object of the
> same type as the object referenced by *oid*.

The following four macros provide more convenient way to iterate through
the internal collections, performing a specific operation on each
object.

**POBJ\_FOREACH(PMEMobjpool \****pop***, PMEMoid ***varoid***)**

> The **POBJ\_FOREACH**() macro allows to perform a specific operation
> on each allocated object stored in the persistent memory pool pointed
> by *pop*. It traverses the internal collection of all the objects,
> assigning a handle to each element in turn to *varoid* variable.

**POBJ\_FOREACH\_TYPE(PMEMobjpool \****pop***, TOID ***var***)**

> **POBJ\_FOREACH\_TYPE**() macro allows to perform a specific operation
> on each allocated object of the same type as object passed as *var*
> argument, stored in the persistent memory pool pointed by *pop*. It
> traverses the internal collection of all the objects of the specified
> type, assigning a handle to each element in turn to *var* variable.

**POBJ\_FOREACH\_SAFE(PMEMobjpool \****pop***, PMEMoid ***varoid***,**\
** PMEMoid ***nvaroid***)**

**POBJ\_FOREACH\_SAFE\_TYPE(PMEMobjpool \****pop***, TOID ***var***,
TOID ***nvar***)**

> The macros **POBJ\_FOREACH\_SAFE**() and
> **POBJ\_FOREACH\_SAFE\_TYPE**() work in a similar fashion as
> **POBJ\_FOREACH**() and **POBJ\_FOREACH\_TYPE**() except that prior to
> performing the operation on the object, they preserve a handle to the
> next object in the collection by assigning it to *nvaroid* or *nvar*
> variable. This allows safe deletion of selected objects while
> iterating through the collection.

ROOT OBJECT MANAGEMENT
======================

The root object of persistent memory pool is an entry point for all
other persistent objects allocated using the *libpmemobj* API. In other
words, every single object stored in persistent memory pool should have
the root object at the end of its reference path. It may be assumed that
for each persistent memory pool the root object always exists, and there
is exactly one root object in each pool.

**PMEMoid pmemobj\_root(PMEMobjpool \****pop***, size\_t ***size***);**

> The **pmemobj\_root**() function returns a handle to the root object
> associated with the persistent memory pool pointed by *pop*. If this
> is the first call to **pmemobj\_root**() and the root object does not
> exists yet, it is implicitly allocated in a thread-safe manner, so if
> the function is called by more than one thread simultaneously (with
> identical *size* value), the same root object handle is returned in
> all the threads.
>
> The size of the root object is guaranteed to be not less than the
> requested *size*. If the requested size is larger than the current
> size, the root object is automatically resized. In such case, the old
> data is preserved and the extra space is zeroed. The
> **pmemobj\_root**() function shall not fail, except for the case if
> the requested object size is larger than the maximum allocation size
> supported for given pool, or if there is not enough free space in the
> pool to satisfy the reallocation of the root object. In such case,
> OID\_NULL is returned.

**PMEMoid pmemobj\_root\_construct(PMEMobjpool \****pop***, size\_t
***size***,**\
** pmemobj\_constr ***constructor***, void \****arg***)**

> The **pmemobj\_root\_construct**() performs the same actions as the
> **pmemobj\_root**() function, but instead of zeroing the newly
> allocated object a *constructor* function is called. The constructor
> is also called on reallocations. If the constructor returns non-zero
> value the allocation is canceled, the **OID\_NULL** value is returned
> from the caller and errno is set to **ECANCELED .** The
> **pmemobj\_root\_size**() can be used in the constructor to check
> whether it\'s the first call to the constructor.

**POBJ\_ROOT(PMEMobjpool \****pop***, ***TYPE***)**

> The **POBJ\_ROOT** macro works the same way as the **pmemobj\_root**()
> function except it returns a typed OID of type *TYPE* instead of
> **PMEMoid**.

**size\_t pmemobj\_root\_size(PMEMobjpool \****pop***);**

> The **pmemobj\_root\_size**() function returns the current size of the
> root object associated with the persistent memory pool pointed by
> *pop*. The returned size is the largest value requested by any of the
> earlier **pmemobj\_root**() calls. 0 is returned if the root object
> has not been allocated yet.

NON-TRANSACTIONAL ATOMIC ALLOCATIONS
====================================

Functions described in this section provide the mechanism to allocate,
resize and free objects from the persistent memory pool in a thread-safe
and fail-safe manner. All the routines are atomic with respect to other
threads and any power-fail interruptions. If any of those operations is
torn by program failure or system crash; on recovery they are guaranteed
to be entirely completed or discarded, leaving the persistent memory
heap and internal object containers in a consistent state.

All these functions can be used outside transactions. Note that
operations performed using non-transactional API are considered durable
after completion, even if executed within the open transaction. Such
non-transactional changes will not be rolled-back if the transaction is
aborted or interrupted.

The allocations are always aligned to the cache-line boundary.

**typedef int (\****pmemobj\_constr***)(PMEMobjpool \****pop***,**\
** void \****ptr***, void \****arg***\");**

> The **pmemobj\_constr** type represents a constructor for atomic
> allocation from persistent memory heap associated with memory pool
> *pop*. The *ptr* is a pointer to allocating memory area and the *arg*
> is an user-defined argument passed to an appropriate function.

**int pmemobj\_alloc(PMEMobjpool \****pop***, PMEMoid \****oidp***,
size\_t ***size***,**\
** uint64\_t ***type\_num***, pmemobj\_constr ***constructor*** , void
\****arg***);**

> The **pmemobj\_alloc** function allocates a new object from the
> persistent memory heap associated with memory pool *pop*. The
> **PMEMoid** of allocated object is stored in *oidp*. If NULL is passed
> as *oidp*, then the newly allocated object may be accessed only by
> iterating objects in the object container associated with given
> *type\_num*, as described in **OBJECT CONTAINERS** section. If the
> *oidp* points to memory location from the **pmemobj** heap the *oidp*
> is modified atomically. Before returning, it calls the **constructor**
> function passing the pool handle *pop*, the pointer to the newly
> allocated object in *ptr* along with the *arg* argument. It is
> guaranteed that allocated object is either properly initialized, or if
> the allocation is interrupted before the constructor completes, the
> memory space reserved for the object is reclaimed. If the constructor
> returns non-zero value the allocation is canceled, the -1 value is
> returned from the caller and errno is set to **ECANCELED .** The
> *size* can be any non-zero value, however due to internal padding and
> object metadata, the actual size of the allocation will differ from
> the requested one by at least 64 bytes. For this reason, making the
> allocations of a size less than 64 bytes is extremely inefficient and
> discouraged. If *size* equals 0, then **pmemobj\_alloc**() returns
> non-zero value, sets the errno and leaves the *oidp* untouched. The
> allocated object is added to the internal container associated with
> given *type\_num*.

**int pmemobj\_zalloc(PMEMobjpool \****pop***, PMEMoid \****oidp***,
size\_t ***size***,**\
** uint64\_t ***type\_num***);**

> The **pmemobj\_zalloc**() function allocates a new zeroed object from
> the the persistent memory heap associated with memory pool *pop*. The
> **PMEMoid** of allocated object is stored in *oidp*. If NULL is passed
> as *oidp*, then the newly allocated object may be accessed only by
> iterating objects in the object container associated with given
> *type\_num*, as described in **OBJECT CONTAINERS** section. If the
> *oidp* points to memory location from the **pmemobj** heap the *oidp*
> is modified atomically. The *size* can be any non-zero value, however
> due to internal padding and object metadata, the actual size of the
> allocation will differ from the requested one by at least 64 bytes.
> For this reason, making the allocations of a size less than 64 bytes
> is extremely inefficient and discouraged. If *size* equals 0, then
> **pmemobj\_zalloc**() returns non-zero value, sets the errno and
> leaves the *oidp* untouched. The allocated object is added to the
> internal container associated with given *type\_num*.

**void pmemobj\_free(PMEMoid \****oidp***);**

> The **pmemobj\_free**() function provides the same semantics as
> **free**(3), but instead of the process heap supplied by the system,
> it operates on the persistent memory heap. It frees the memory space
> represented by *oidp*, which must have been returned by a previous
> call to **pmemobj\_alloc**(), **pmemobj\_zalloc**(),
> **pmemobj\_realloc**(), or **pmemobj\_zrealloc**(). If *oidp* is NULL
> or if it points to the root object\'s OID, behavior of the function is
> undefined. If it points to *OID\_NULL*, no operation is performed. It
> sets the *oidp* to *OID\_NULL* value after freeing the memory. If the
> *oidp* points to memory location from the **pmemobj** heap the *oidp*
> is changed atomically.

**int pmemobj\_realloc(PMEMobjpool \****pop***, PMEMoid \****oidp***,
size\_t ***size***,**\
** uint64\_t ***type\_num***);**

> The **pmemobj\_realloc**() function provide similar semantics to
> **realloc**(3), but operates on the persistent memory heap associated
> with memory pool *pop*. It changes the size of the object represented
> by *oidp*, to *size* bytes. The resized object is also added or moved
> to the internal container associated with given *type\_num*. The
> contents will be unchanged in the range from the start of the region
> up to the minimum of the old and new sizes. If the new size is larger
> than the old size, the added memory will *not* be initialized. If
> *oidp* is NULL or if it points to the root object\'s OID, behavior of
> the function is undefined. If it points to *OID\_NULL*, then the call
> is equivalent to **pmemobj\_alloc(***pop***, ***size***,
> ***type\_num***).** If *size* is equal to zero, and *oidp* is not
> *OID\_NULL*, then the call is equivalent to
> **pmemobj\_free(***oid***).** Unless *oidp* is *OID\_NULL*, it must
> have been returned by an earlier call to **pmemobj\_alloc**(),
> **pmemobj\_zalloc**(), **pmemobj\_realloc**(), or
> **pmemobj\_zrealloc**(). Note that the object handle value may change
> in result of reallocation. If the object was moved, a memory space
> represented by *oid* is reclaimed. If *oidp* points to memory location
> from the **pmemobj** heap the *oidp* is changed atomically. If
> **pmemobj\_realloc**() is unable to satisfy the allocation request, a
> non-zero value is returned and errno is set appropriately.

**int pmemobj\_zrealloc(PMEMobjpool \****pop***, PMEMoid \****oidp***,
size\_t ***size***,**\
** uint64\_t ***type\_num***);**

> The **pmemobj\_zrealloc**() function provide similar semantics to
> **realloc**(3), but operates on the persistent memory heap associated
> with memory pool *pop*. It changes the size of the object represented
> by *oid*, to *size* bytes. The resized object is also added or moved
> to the internal container associated with given *type\_num*. The
> contents will be unchanged in the range from the start of the region
> up to the minimum of the old and new sizes. If the new size is larger
> than the old size, the added memory will be zeroed. If *oidp* is NULL
> or if it points to the root object\'s OID, behavior of the function is
> undefined. If it points to *OID\_NULL*, then the call is equivalent to
> **pmemobj\_zalloc(***pop***, ***size***, ***type\_num***).** If *size*
> is equal to zero, and *oidp* doesn\'t point to *OID\_NULL*, then the
> call is equivalent to **pmemobj\_free(***pop***, ***oid***).** Unless
> *oidp* points to *OID\_NULL*, it must have been returned by an earlier
> call to **pmemobj\_alloc**(), **pmemobj\_zalloc**(),
> **pmemobj\_realloc**(), or **pmemobj\_zrealloc**(). Note that the
> object handle value may change in result of reallocation. If the
> object was moved, a memory space represented by *oidp* is reclaimed.
> If *oidp* points to memory location from the **pmemobj** heap the
> *oidp* is changed atomically. If **pmemobj\_zrealloc**() is unable to
> satisfy the allocation request, OID\_NULL is returned and errno is set
> appropriately.

**int pmemobj\_strdup(PMEMobjpool \****pop***, PMEMoid \****oidp***,
const char \****s***,**\
** uint64\_t ***type\_num***);**

> The **pmemobj\_strdup**() function provides the same semantics as
> **strdup**(3), but operates on the persistent memory heap associated
> with memory pool *pop*. It stores a handle to a new object in *oidp*
> which is a duplicate of the string *s*. If NULL is passed as *oidp*,
> then the newly allocated object may be accessed only by iterating
> objects in the object container associated with given *type\_num*, as
> described in **OBJECT CONTAINERS** section. If the *oidp* points to
> memory location from the **pmemobj** heap the *oidp* is changed
> atomically. The allocated string object is also added to the internal
> container associated with given *type\_num*. Memory for the new string
> is obtained with **pmemobj\_alloc**(), on the given memory pool, and
> can be freed with **pmemobj\_free**() on the same memory pool. If
> **pmemobj\_strdup**() is unable to satisfy the allocation request,
> OID\_NULL is returned and errno is set appropriately.

**size\_t pmemobj\_alloc\_usable\_size(PMEMoid ***oid***);**

> The **pmemobj\_alloc\_usable\_size**() function provides the same
> semantics as **malloc\_usable\_size**(3), but instead of the process
> heap supplied by the system, it operates on the persistent memory
> heap. It returns the number of usable bytes in the object represented
> by *oid*, a handle to an object allocated by **pmemobj\_alloc**() or a
> related function. If *oid* is OID\_NULL, 0 is returned.

**POBJ\_NEW(PMEMobjpool \****pop***, TOID \****oidp***, ***TYPE***,**\
** pmemobj\_constr ***constructor*** , void \****arg***)**

> The **POBJ\_NEW** macro is a wrapper around the **pmemobj\_alloc**()
> function which takes the type name **TYPE** and passes the size and
> type number to the **pmemobj\_alloc**() function from the typed OID.
> Instead of taking a pointer to **PMEMoid** it takes a pointer to typed
> OID of **TYPE**.

**POBJ\_ALLOC(PMEMobjpool \****pop***, TOID \****oidp***, ***TYPE***,
size\_t ***size***,**\
** pmemobj\_constr ***constructor*** , void \****arg***)**

> The **POBJ\_ALLOC** macro is a wrapper around the **pmemobj\_alloc**()
> function which takes the type name **TYPE**, the size of allocation
> *size* and passes the type number to the **pmemobj\_alloc**() function
> from the typed OID. Instead of taking a pointer to **PMEMoid** it
> takes a pointer to typed OID of **TYPE**.

**POBJ\_ZNEW(PMEMobjpool \****pop***, TOID \****oidp***, ***TYPE***)**

> The **POBJ\_ZNEW** macro is a wrapper around the **pmemobj\_zalloc**()
> function which takes the type name **TYPE** and passes the size and
> type number to the **pmemobj\_zalloc**() function from the typed OID.
> Instead of taking a pointer to **PMEMoid** it takes a pointer to typed
> OID of **TYPE**.

**POBJ\_ZALLOC(PMEMobjpool \****pop***, TOID \****oidp***, ***TYPE***,
size\_t ***size***)**

> The **POBJ\_ZALLOC** macro is a wrapper around the
> **pmemobj\_zalloc**() function which takes the type name **TYPE**, the
> size of allocation *size* and passes the type number to the
> **pmemobj\_zalloc**() function from the typed OID. Instead of taking a
> pointer to **PMEMoid** it takes a pointer to typed OID of **TYPE**.

**POBJ\_REALLOC(PMEMobjpool \****pop***, TOID \****oidp***, ***TYPE***,
size\_t ***size***)**

> The **POBJ\_REALLOC** macro is a wrapper around the
> **pmemobj\_realloc**() function which takes the type name **TYPE** and
> passes the type number to the **pmemobj\_realloc**() function from the
> typed OID. Instead of taking a pointer to **PMEMoid** it takes a
> pointer to typed OID of **TYPE**.

**POBJ\_ZREALLOC(PMEMobjpool \****pop***, TOID \****oidp***, ***TYPE***,
size\_t ***size***)**

> The **POBJ\_ZREALLOC** macro is a wrapper around the
> **pmemobj\_zrealloc**() function which takes the type name **TYPE**
> and passes the type number to the **pmemobj\_zrealloc**() function
> from the typed OID. Instead of taking a pointer to **PMEMoid** it
> takes a pointer to typed OID of **TYPE**.

**POBJ\_FREE(TOID \****oidp***)**

> The **POBJ\_FREE** macro is a wrapper around the **pmemobj\_free**()
> function which takes pointer to typed OID *oidp* as an argument
> instead of **PMEMoid**.

NON-TRANSACTIONAL PERSISTENT ATOMIC LISTS
=========================================

Besides the internal objects collections described in section **OBJECT
CONTAINERS** the **libpmemobj** provides a mechanism to organize
persistent objects in the user-defined persistent atomic circular doubly
linked lists. All the routines and macros operating on the persistent
lists provide atomicity with respect to any power-fail interruptions. If
any of those operations is torn by program failure or system crash; on
recovery they are guaranteed to be entirely completed or discarded,
leaving the lists, persistent memory heap and internal object containers
in a consistent state.

The persistent atomic circular doubly linked lists support the following
functionality:

-   Insertion of an object at the head of the list, or at the end of the
    list.

-   Insertion of an object before or after any element in the list.

-   Atomic allocation and insertion of a new object at the head of the
    list, or at the end of the list.

-   Atomic allocation and insertion of a new object before or after any
    element in the list.

-   Atomic moving of an element from one list to the specific location
    on another list.

-   Removal of any object in the list.

-   Atomic removal and freeing of any object in the list.

-   Forward or backward traversal through the list.

A list is headed by a *list\_head* structure containing a single object
handle of the first element on the list. The elements are doubly linked
so that an arbitrary element can be removed without a need to traverse
the list. New elements can be added to the list before or after an
existing element, at the head of the list, or at the end of the list. A
list may be traversed in either direction.

The user-defined structure of each element must contain a field of type
**list\_entry** holding the object handles to the previous and next
element on the list. Both the **list\_head** and the **list\_entry**
structures are declared in **\<libpmemobj.h\>**.

The functions below are intended to be used outside transactions -
transactional variants are described in section **TRANSACTIONAL OBJECT
MANIPULATION**. Note that operations performed using this
non-transactional API are independent from their transactional
counterparts. If any non-transactional allocations or list manipulations
are performed within an open transaction, the changes will not be
rolled-back if such a transaction is aborted or interrupted.

**int pmemobj\_list\_insert(PMEMobjpool \****pop***, size\_t
***pe\_offset***,**\
** void \****head***, PMEMoid ***dest***, int ***before***, PMEMoid
***oid***);**

> The **pmemobj\_list\_insert** function inserts an element represented
> by object handle *oid* into the list referenced by *head*. Depending
> on the value of flag *before*, the object is added before or after the
> element *dest*. If *dest* value is OID\_NULL, the object is inserted
> at the head or at the end of the list, depending on the *before* flag
> value. If value is non-zero the object is inserted at the head, if
> value is zero the object is inserted at the end of the list. The
> relevant values are available through **POBJ\_LIST\_DEST\_HEAD** and
> **POBJ\_LIST\_DEST\_TAIL** defines respectively. The argument
> *pe\_offset* declares an offset of the structure that connects the
> elements in the list. All the handles *head*, *dest* and *oid* must
> point to the objects allocated from the same memory pool *pop*. The
> *head* and *oid* cannot be OID\_NULL. On success, zero is returned. On
> error, -1 is returned and errno is set.

**PMEMoid pmemobj\_list\_insert\_new(PMEMobjpool \****pop***, size\_t
***pe\_offset***,**\
** void \****head***, PMEMoid ***dest***, int ***before***, size\_t
***size***,**\
** uint64\_t ***type\_num***, pmemobj\_constr ***constructor***, void
\****arg***);**

> The **pmemobj\_list\_insert\_new** function atomically allocates a new
> object of given *size* and type *type\_num* and inserts it into the
> list referenced by *head*. Depending on the value of *before* flag,
> the newly allocated object is added before or after the element
> *dest*. If *dest* value is OID\_NULL, the object is inserted at the
> head or at the end of the list, depending on the *before* flag value.
> If value is non-zero the object is inserted at the head, if value is
> zero the object is inserted at the end of the list. The relevant
> values are available through **POBJ\_LIST\_DEST\_HEAD** and
> **POBJ\_LIST\_DEST\_TAIL** defines respectively. The argument
> *pe\_offset* declares an offset of the structure that connects the
> elements in the list. All the handles *head*, *dest* must point to the
> objects allocated from the same memory pool *pop*. Before returning,
> it calls the **constructor** function passing the pool handle *pop*,
> the pointer to the newly allocated object in *ptr* along with the
> *arg* argument. It is guaranteed that allocated object is either
> properly initialized or, if the allocation is interrupted before the
> constructor completes, the memory space reserved for the object is
> reclaimed. If the constructor returns non-zero value the allocation is
> canceled, the -1 value is returned from the caller and errno is set to
> **ECANCELED .** The *head* cannot be OID\_NULL. The allocated object
> is also added to the internal container associated with given
> *type\_num*. as described in section **OBJECT CONTAINERS**. On
> success, it returns a handle to the newly allocated object. On error,
> OID\_NULL is returned and errno is set.

**int pmemobj\_list\_remove(PMEMobjpool \****pop***, size\_t
***pe\_offset***,**\
** void \****head***, PMEMoid ***oid***, int ***free***);**

> The **pmemobj\_list\_remove** function removes the object referenced
> by *oid* from the list pointed by *head*. If *free* flag is set, it
> also removes the object from the internal object container and frees
> the associated memory space. The argument *pe\_offset* declares an
> offset of the structure that connects the elements in the list. Both
> *head* and *oid* must point to the objects allocated from the same
> memory pool *pop* and cannot be OID\_NULL. On success, zero is
> returned. On error, -1 is returned and errno is set.

**int pmemobj\_list\_move(PMEMobjpool \****pop***,**\
** size\_t ***pe\_old\_offset***, void \****head\_old***,**\
** size\_t ***pe\_new\_offset***, void \****head\_new***,**\
** PMEMoid ***dest***, int ***before***, PMEMoid ***oid***);**

> The **pmemobj\_list\_move** function moves the object represented by
> *oid* from the list pointed by *head\_old* to the list pointed by
> *head\_new*. Depending on the value of flag *before*, the newly
> allocated object is added before or after the element *dest*. If
> *dest* value is OID\_NULL, the object is inserted at the head or at
> the end of the second list, depending on the *before* flag value. If
> value is non-zero the object is inserted at the head, if value is zero
> the object is inserted at the end of the list. The relevant values are
> available through **POBJ\_LIST\_DEST\_HEAD** and
> **POBJ\_LIST\_DEST\_TAIL** defines respectively. The arguments
> *pe\_old\_offset* and *pe\_new\_offset* declare the offsets of the
> structures that connects the elements in the old and new lists
> respectively. All the handles *head\_old*, *head\_new*, *dest* and
> *oid* must point to the objects allocated from the same memory pool
> *pop*. *head\_old*, *head\_new* and *oid* cannot be OID\_NULL. On
> success, zero is returned. On error, -1 is returned and errno is set.

TYPE-SAFE NON-TRANSACTIONAL PERSISTENT ATOMIC LISTS
===================================================

The following macros define and operate on a type-safe persistent atomic
circular doubly linked list data structure that consist of a set of
persistent objects of a well-known type. Unlike the functions described
in the previous section, these macros provide type enforcement by
requiring declaration of type of the objects stored in given list, and
not allowing mixing objects of different types in a single list.

The functionality and semantics of those macros is similar to circular
queues defined in **queue**(3).

The majority of the macros must specify the handle to the memory pool
*pop* and the name of the *field* in the user-defined structure, which
must be of type *POBJ\_LIST\_ENTRY* and is used to connect the elements
in the list.

A list is headed by a structure defined by the *POBJ\_LIST\_HEAD* macro.
This structure contains an object handle of the first element on the
list. The elements are doubly linked so that an arbitrary element can be
removed without a need to traverse the list. New elements can be added
to the list before or after an existing element, at the head of the
list, or at the end of the list. A list may be traversed in either
direction. A **POBJ\_LIST\_HEAD** structure is declared as follows:

    #define POBJ_LIST_HEAD(HEADNAME, TYPE)
    struct HEADNAME {
        TOID(TYPE) pe_first;
        PMEMmutex lock;
    };

In the macro definitions, *TYPE* is the name of a user-defined
structure, that must contain a field of type *POBJ\_LIST\_ENTRY*. The
argument *HEADNAME* is the name of a user-defined structure that must be
declared using the macro *POBJ\_LIST\_HEAD*. See the examples below for
further explanation of how these macros are used.

The macro **POBJ\_LIST\_ENTRY** declares a structure that connects the
elements in the list.

    #define POBJ_LIST_ENTRY(TYPE)
    struct {
        TOID(TYPE) pe_next;
        TOID(TYPE) pe_prev;
    };

**POBJ\_LIST\_FIRST(POBJ\_LIST\_HEAD \****head***)**

> The macro **POBJ\_LIST\_FIRST** returns the first element on the list
> referenced by *head*. If the list is empty OID\_NULL is returned.

**POBJ\_LIST\_LAST(POBJ\_LIST\_HEAD \****head***, POBJ\_LIST\_ENTRY
***FIELD***)**

> The macro **POBJ\_LIST\_LAST** returns the last element on the list
> referenced by *head*. If the list is empty OID\_NULL is returned.

**POBJ\_LIST\_EMPTY(POBJ\_LIST\_HEAD \****head***)**

> The macro **POBJ\_LIST\_EMPTY** evaluates to 1 if the list referenced
> by *head* is empty. Otherwise, 0 is returned.

**POBJ\_LIST\_NEXT(TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_NEXT** returns the element next to the element
> *elm*.

**POBJ\_LIST\_PREV(TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_PREV** returns the element preceding the
> element *elm*.

**POBJ\_LIST\_FOREACH(TOID ***var***, POBJ\_LIST\_HEAD \****head***,**\
** POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_FOREACH** traverses the list referenced by
> *head* assigning a handle to each element in turn to *var* variable.

**POBJ\_LIST\_FOREACH\_REVERSE(TOID ***var***, POBJ\_LIST\_HEAD
\****head***,**\
** POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_FOREACH\_REVERSE** traverses the list
> referenced by *head* in reverse order, assigning a handle to each
> element in turn to *var* variable. The *field* argument is the name of
> the field of type *POBJ\_LIST\_ENTRY* in the element structure.

**POBJ\_LIST\_INSERT\_HEAD(PMEMobjpool \****pop***, POBJ\_LIST\_HEAD
\****head***,**\
** TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_INSERT\_HEAD** inserts the element *elm* at
> the head of the list referenced by *head*.

**POBJ\_LIST\_INSERT\_TAIL(PMEMobjpool \****pop***, POBJ\_LIST\_HEAD
\****head***,**\
** TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_INSERT\_TAIL** inserts the element *elm* at
> the end of the list referenced by *head*.

**POBJ\_LIST\_INSERT\_AFTER(PMEMobjpool \****pop***, POBJ\_LIST\_HEAD
\****head***,**\
** TOID ***listelm***, TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_INSERT\_AFTER** inserts the element *elm* into
> the list referenced by *head* after the element *listelm*. If
> *listelm* value is TOID\_NULL, the object is inserted at the end of
> the list.

**POBJ\_LIST\_INSERT\_BEFORE(PMEMobjpool \****pop***, POBJ\_LIST\_HEAD
\****head***,**\
** TOID ***listelm***, TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_INSERT\_BEFORE** inserts the element *elm*
> into the list referenced by *head* before the element *listelm*. If
> *listelm* value is TOID\_NULL, the object is inserted at the head of
> the list.

**POBJ\_LIST\_INSERT\_NEW\_HEAD(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** POBJ\_LIST\_ENTRY ***FIELD***, size\_t ***size***,**\
** pmemobj\_constr ***constructor*** , void \****arg***)**

> The macro **POBJ\_LIST\_INSERT\_NEW\_HEAD** atomically allocates a new
> object of size *size* and inserts it at the head of the list
> referenced by *head*. The newly allocated object is also added to the
> internal object container associated with a type number which is
> retrieved from the typed OID of the first element on list.

**POBJ\_LIST\_INSERT\_NEW\_TAIL(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** POBJ\_LIST\_ENTRY ***FIELD***, size\_t ***size***,**\
** pmemobj\_constr ***constructor*** , void \****arg***)**

> The macro **POBJ\_LIST\_INSERT\_NEW\_TAIL** atomically allocates a new
> object of size *size* and inserts it at the tail of the list
> referenced by *head*. The newly allocated object is also added to the
> internal object container associated with with a type number which is
> retrieved from the typed OID of the first element on list.

**POBJ\_LIST\_INSERT\_NEW\_AFTER(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** TOID ***listelm***, POBJ\_LIST\_ENTRY ***FIELD***, size\_t
***size***,**\
** pmemobj\_constr ***constructor*** , void \****arg***)**

> The macro **POBJ\_LIST\_INSERT\_NEW\_AFTER** atomically allocates a
> new object of size *size* and inserts it into the list referenced by
> *head* after the element *listelm*. If *listelm* value is TOID\_NULL,
> the object is inserted at the end of the list. The newly allocated
> object is also added to the internal object container associated with
> with a type number which is retrieved from the typed OID of the first
> element on list.

**POBJ\_LIST\_INSERT\_NEW\_BEFORE(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** TOID ***listelm***, POBJ\_LIST\_ENTRY ***FIELD***, size\_t
***size***,**\
** pmemobj\_constr ***constructor*** , void \****arg***)**

> The macro **POBJ\_LIST\_INSERT\_NEW\_BEFORE** atomically allocates a
> new object of size *size* and inserts it into the list referenced by
> *head* before the element *listelm*. If *listelm* value is TOID\_NULL,
> the object is inserted at the head of the list. The newly allocated
> object is also added to the internal object container associated with
> with a type number which is retrieved from the typed OID of the first
> element on list.

**POBJ\_LIST\_REMOVE(PMEMobjpool \****pop***, POBJ\_LIST\_HEAD
\****head***,**\
** TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_REMOVE** removes the element *elm* from the
> list referenced by *head*.

**POBJ\_LIST\_REMOVE\_FREE(PMEMobjpool \****pop***, POBJ\_LIST\_HEAD
\****head***,**\
** TOID ***elm***, POBJ\_LIST\_ENTRY ***FIELD***)**

> The macro **POBJ\_LIST\_REMOVE\_FREE** removes the element *elm* from
> the list referenced by *head* and frees the memory space represented
> by this element.

**POBJ\_LIST\_MOVE\_ELEMENT\_HEAD(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** POBJ\_LIST\_HEAD \****head\_new***, TOID ***elm***, POBJ\_LIST\_ENTRY
***FIELD***,**\
** POBJ\_LIST\_ENTRY ***field\_new***)**

> The macro **POBJ\_LIST\_MOVE\_ELEMENT\_HEAD** moves the element *elm*
> from the list referenced by *head* to the head of the list
> *head\_new*. The *field* and *field\_new* arguments are the names of
> the fields of type *POBJ\_LIST\_ENTRY* in the element structure that
> are used to connect the elements in both lists.

**POBJ\_LIST\_MOVE\_ELEMENT\_TAIL(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** POBJ\_LIST\_HEAD \****head\_new***, TOID ***elm***, POBJ\_LIST\_ENTRY
***FIELD***,**\
** POBJ\_LIST\_ENTRY ***field\_new***)**

> The macro **POBJ\_LIST\_MOVE\_ELEMENT\_TAIL** moves the element *elm*
> from the list referenced by *head* to the end of the list *head\_new*.
> The *field* and *field\_new* arguments are the names of the fields of
> type *POBJ\_LIST\_ENTRY* in the element structure that are used to
> connect the elements in both lists.

**POBJ\_LIST\_MOVE\_ELEMENT\_AFTER(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** POBJ\_LIST\_HEAD \****head\_new***, TOID ***listelm***, TOID
***elm***,**\
** POBJ\_LIST\_ENTRY ***FIELD***, POBJ\_LIST\_ENTRY ***field\_new***)**

> The macro **POBJ\_LIST\_MOVE\_ELEMENT\_AFTER** atomically removes the
> element *elm* from the list referenced by *head* and inserts it into
> the list referenced by *head\_new* after the element *listelm*. If
> *listelm* value is TOID\_NULL, the object is inserted at the end of
> the list. The *field* and *field\_new* arguments are the names of the
> fields of type *POBJ\_LIST\_ENTRY* in the element structure that are
> used to connect the elements in both lists.

**POBJ\_LIST\_MOVE\_ELEMENT\_BEFORE(PMEMobjpool \****pop***,
POBJ\_LIST\_HEAD \****head***,**\
** POBJ\_LIST\_HEAD \****head\_new***, TOID ***listelm***, TOID
***elm***,**\
** POBJ\_LIST\_ENTRY ***FIELD***, POBJ\_LIST\_ENTRY ***field\_new***)**

> The macro **POBJ\_LIST\_MOVE\_ELEMENT\_BEFORE** atomically removes the
> element *elm* from the list referenced by *head* and inserts it into
> the list referenced by *head\_new* before the element *listelm*. If
> *listelm* value is TOID\_NULL, the object is inserted at the head of
> the list. The *field* and *field\_new* arguments are the names of the
> fields of type *POBJ\_LIST\_ENTRY* in the element structure that are
> used to connect the elements in both lists.

TRANSACTIONAL OBJECT MANIPULATION
=================================

The functions described in sections **NON-TRANSACTIONAL ATOMIC
ALLOCATIONS** and **NON-TRANSACTIONAL PERSISTENT ATOMIC LISTS** only
guarantee the atomicity in scope of a single operation on an object. In
case of more complex changes, involving multiple operations on an
object, or allocation and modification of multiple objects; data
consistency and fail-safety may be provided only by using *atomic
transactions*.

A transaction is defined as series of operations on persistent memory
objects that either all occur, or nothing occurs. In particular, if the
execution of a transaction is interrupted by a power failure or a system
crash, it is guaranteed that after system restart, all the changes made
as a part of the uncompleted transaction will be rolled-back, restoring
the consistent state of the memory pool from the moment when the
transaction was started.

Note that transactions do not provide the atomicity with respect to
other threads. All the modifications performed within the transactions
are immediately visible to other threads, and this is the responsibility
of the program to implement a proper thread synchronization mechanism.

Each transaction is visible only for the thread that started it. No
other threads can add operations, commit or abort the transaction
initiated by another thread. There may be multiple open transactions on
given memory pool at the same time, but only one transaction per thread.

Nested transactions are supported but flattened. Committing the nested
transaction does not commit the outer transaction, however errors in the
nested transaction are propagated up to the outer-most level, resulting
in the interruption of the entire transaction.

Please see the **CAVEATS** section for known limitations of the
transactional API.

**\"enum***tx\_stage***pmemobj\_tx\_stage(void);**

> The **pmemobj\_tx\_stage**() function returns the stage of the current
> transaction stage for a thread. Stages are changed only by the
> *pmemobj\_tx\_\** functions. The transaction stages are defined as
> follows:
>
> **TX\_STAGE\_NONE** - no open transaction in this thread
>
> **TX\_STAGE\_WORK** - transaction in progress
>
> **TX\_STAGE\_ONCOMMIT** - successfully committed
>
> **TX\_STAGE\_ONABORT** - starting the transaction failed or
> transaction aborted
>
> **TX\_STAGE\_FINALLY** - ready for clean up

**int pmemobj\_tx\_begin(PMEMobjpool \****pop***, jmp\_buf \****env***,
***\...***);**

> The **pmemobj\_tx\_begin**() function starts a new transaction in the
> current thread. If called within an open transaction, it starts a
> nested transaction. The caller may use *env* argument to provide a
> pointer to the information of a calling environment to be restored in
> case of transaction abort. This information must be filled by a
> caller, using **setjmp**(3) macro.
>
> Optionally, a list of pmem-resident locks may be provided as the last
> arguments. Each lock is specified by a pair of lock type (
> *TX\_LOCK\_MUTEX* or *TX\_LOCK\_RWLOCK*) and the pointer to the lock
> of type *PMEMmutex* or *PMEMrwlock* respectively. The list must be
> terminated with *TX\_LOCK\_NONE*. In case of rwlocks, a write lock is
> acquired. It is guaranteed that **pmemobj\_tx\_begin**() will grab all
> the locks prior to successful completion and they will be held by the
> current thread until the transaction is finished. Locks are taken in
> the order from left to right. To avoid deadlocks, user must take care
> about the proper order of locks.
>
> New transaction may be started only if the current stage is
> *TX\_STAGE\_NONE* or *TX\_STAGE\_WORK*. If successful, transaction
> stage changes to *TX\_STAGE\_WORK* and function returns zero.
> Otherwise, stage changes to *TX\_STAGE\_ONABORT* and an error number
> is returned.

**int pmemobj\_tx\_lock(enum tx\_lock ***lock\_type***, void
\****lockp***);**

> The **pmemobj\_tx\_lock**() function grabs a lock pointed by *lockp*
> and adds it to the current transaction. The lock type is specified by
> *lock\_type* ( *TX\_LOCK\_MUTEX* or *TX\_LOCK\_RWLOCK* ) and the
> pointer to the *lockp* of *PMEMmutex* or *PMEMrwlock* type. If
> successful, *lockp* is added to transaction, locked and function
> returns zero. Otherwise, stage changes to *TX\_STAGE\_ONABORT* and an
> error number is returned. In case of *PMEMrwlock* *lock\_type*
> function acquires a write lock. This function must be called during
> *TX\_STAGE\_WORK*.

**void pmemobj\_tx\_abort(int ***errnum***);**

> The **pmemobj\_tx\_abort**() aborts the current transaction and causes
> transition to *TX\_STAGE\_ONABORT*. This function must be called
> during *TX\_STAGE\_WORK*. If the passed *errnum* is equal to zero, it
> shall be set to *ECANCELED*.

**\"void***pmemobj\_tx\_commit(void);*

> The **pmemobj\_tx\_commit**() function commits the current open
> transaction and causes transition to *TX\_STAGE\_ONCOMMIT* stage. If
> called in context of the outer-most transaction, all the changes may
> be considered as durably written upon successful completion. This
> function must be called during *TX\_STAGE\_WORK*.

**\"int***pmemobj\_tx\_end(void);*

> The **pmemobj\_tx\_end**() function performs a clean up of a current
> transaction. If called in context of the outer-most transaction, it
> releases all the locks acquired by **pmemobj\_tx\_begin**() for outer
> and nested transactions. Then it causes the transition to
> *TX\_STAGE\_NONE*. In case of the nested transaction, it returns to
> the context of the outer transaction with *TX\_STAGE\_WORK* stage
> without releasing any locks. Must always be called for each
> **pmemobj\_tx\_begin**(), even if starting the transaction failed.
> This function must *not* be called during *TX\_STAGE\_WORK*. If
> transaction was successful, returns 0. Otherwise returns error code
> set by **pmemobj\_tx\_abort**(). Note that **pmemobj\_tx\_abort**()
> can be called internally by the library.

**\"int***pmemobj\_tx\_errno(void);*

> The **pmemobj\_tx\_errno**() function returns the error code of the
> last transaction.

**\"void***pmemobj\_tx\_process(void);*

> The **pmemobj\_tx\_process**() function performs the actions
> associated with current stage of the transaction, and makes the
> transition to the next stage. It must be called in transaction.
> Current stage must always be obtained by a call to
> **pmemobj\_tx\_stage**().

**int pmemobj\_tx\_add\_range(PMEMoid ***oid***, uint64\_t ***off***,
size\_t ***size***);**

> The **pmemobj\_tx\_add\_range**() takes a \"snapshot\" of the memory
> block of given *size*, located at given offset *off* in the object
> specified by *oid* and saves it to the undo log. The application is
> then free to directly modify the object in that memory range. In case
> of a failure or abort, all the changes within this range will be
> rolled-back. The supplied block of memory has to be within the pool
> registered in the transaction. If successful, returns zero. Otherwise,
> state changes to *TX\_STAGE\_ONABORT* and an error number is returned.
> This function must be called during *TX\_STAGE\_WORK*.

**int pmemobj\_tx\_add\_range\_direct(const void \****ptr***, size\_t
***size***);**

> The **pmemobj\_tx\_add\_range\_direct**() behaves the same as
> **pmemobj\_tx\_add\_range**() with the exception that it operates on
> virtual memory addresses and not persistent memory objects. It takes a
> \"snapshot\" of a persistent memory block of given *size*, located at
> the given address *ptr* in the virtual memory space and saves it to
> the undo log. The application is then free to directly modify the
> object in that memory range. In case of a failure or abort, all the
> changes within this range will be rolled-back. The supplied block of
> memory has to be within the pool registered in the transaction. If
> successful, returns zero. Otherwise, state changes to
> *TX\_STAGE\_ONABORT* and an error number is returned. This function
> must be called during *TX\_STAGE\_WORK*.

**PMEMoid pmemobj\_tx\_alloc(size\_t ***size***, uint64\_t
***type\_num***);**

> The **pmemobj\_tx\_alloc**() transactionally allocates a new object of
> given *size* and *type\_num*. In contrast to the non-transactional
> allocations, the objects are added to the internal object containers
> of given *type\_num* only after the transaction is committed, making
> the objects visible to the **POBJ\_FOREACH\_\*** macros. If
> successful, returns a handle to the newly allocated object. Otherwise,
> stage changes to *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and
> errno is set appropriately. If *size* equals 0, OID\_NULL is returned
> and errno is set appropriately. This function must be called during
> *TX\_STAGE\_WORK*.

**PMEMoid pmemobj\_tx\_zalloc(size\_t ***size***, uint64\_t
***type\_num***);**

> The pmemobj\_tx\_zalloc () function transactionally allocates new
> zeroed object of given *size* and *type\_num*. If successful, returns
> a handle to the newly allocated object. Otherwise, stage changes to
> *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and errno is set
> appropriately. If *size* equals 0, OID\_NULL is returned and errno is
> set appropriately. This function must be called during
> *TX\_STAGE\_WORK*.

**PMEMoid pmemobj\_tx\_realloc(PMEMoid ***oid***, size\_t ***size***,**\
** uint64\_t ***type\_num***);**

> The **pmemobj\_tx\_realloc**() function transactionally resizes an
> existing object to the given *size* and changes its type to
> *type\_num*. If *oid* is OID\_NULL, then the call is equivalent to
> **pmemobj\_tx\_alloc(***pop***, ***size***, ***type\_num***).** If
> *size* is equal to zero and *oid* is not OID\_NULL, then the call is
> equivalent to **pmemobj\_tx\_free(***oid***).** If the new size is
> larger than the old size, the added memory will *not* be initialized.
> If successful, returns a handle to the resized object. Otherwise,
> stage changes to *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and
> errno is set appropriately. Note that the object handle value may
> change in result of reallocation. This function must be called during
> *TX\_STAGE\_WORK*.

**PMEMoid pmemobj\_tx\_zrealloc(PMEMoid ***oid***, size\_t
***size***,**\
** uint64\_t ***type\_num***);**

> The **pmemobj\_tx\_zrealloc**() function transactionally resizes an
> existing object to the given *size* and changes its type to
> *type\_num*. If the new size is larger than the old size, the extended
> new space is zeroed. If successful, returns a handle to the resized
> object. Otherwise, stage changes to *TX\_STAGE\_ONABORT*, OID\_NULL is
> returned, and errno is set appropriately. Note that the object handle
> value may change in result of reallocation. This function must be
> called during *TX\_STAGE\_WORK*.

**PMEMoid pmemobj\_tx\_strdup(const char \****s***, uint64\_t
***type\_num***);**

> The **pmemobj\_tx\_strdup**() function transactionally allocates a new
> object containing a duplicate of the string *s* and assigns it a type
> *type\_num*. If successful, returns a handle to the newly allocated
> object. Otherwise, stage changes to *TX\_STAGE\_ONABORT*, OID\_NULL is
> returned, and errno is set appropriately. This function must be called
> during *TX\_STAGE\_WORK*.

**int pmemobj\_tx\_free(PMEMoid ***oid***);**

> The **pmemobj\_tx\_free**() function transactionally frees an existing
> object referenced by *oid*. If successful, returns zero. Otherwise,
> stage changes to *TX\_STAGE\_ONABORT* and an error number is returned.
> This function must be called during *TX\_STAGE\_WORK*.

In addition to the above API, the **libpmemobj** offers a more intuitive
method of building transactions using a set of macros described below.
When using macros, the complete transaction flow looks like this:

    TX_BEGIN(Pop) {
    	/* the actual transaction code goes here... */
    } TX_ONCOMMIT {
    	/*
    	 * optional - executed only if the above block
    	 * successfully completes
    	 */
    } TX_ONABORT {
    	/*
    	 * optional - executed only if starting the transaction fails,
    	 * or if transaction is aborted by an error or a call to
    	 * pmemobj_tx_abort()
    	 */
    } TX_FINALLY {
    	/*
    	 * optional - if exists, it is executed after
    	 * TX_ONCOMMIT or TX_ONABORT block
    	 */
    } TX_END /* mandatory */

**TX\_BEGIN\_LOCK(PMEMobjpool \****pop***, ***\...***)**

**TX\_BEGIN(PMEMobjpool \****pop***)**

> The **TX\_BEGIN\_LOCK**() and **TX\_BEGIN**() macros start a new
> transaction in the same way as **pmemobj\_tx\_begin**(), except that
> instead of the environment buffer provided by a caller, they set up
> the local *jmp\_buf* buffer and use it to catch the transaction abort.
> The **TX\_BEGIN**() macro may be used in case when there is no need to
> grab any locks prior to starting a transaction (like for a
> single-threaded program). Each of those macros shall be followed by a
> block of code with all the operations that are to be performed
> atomically.

**TX\_ONABORT**

> The **TX\_ONABORT** macro starts a block of code that will be executed
> only if starting the transaction fails due to an error in
> **pmemobj\_tx\_begin**(), or if the transaction is aborted. This block
> is optional, but in practice it should not be omitted. If it\'s
> desirable to crash the application when transaction aborts and
> there\'s no **TX\_ONABORT** section, application can define
> **POBJ\_TX\_CRASH\_ON\_NO\_ONABORT** macro before inclusion of
> **\<libpmemobj.h\>**. It provides default **TX\_ONABORT** section
> which just calls **abort**(3).

**TX\_ONCOMMIT**

> The **TX\_ONCOMMIT** macro starts a block of code that will be
> executed only if the transaction is successfully committed, which
> means that the execution of code in **TX\_BEGIN** block has not been
> interrupted by an error or by a call to **pmemobj\_tx\_abort**(). This
> block is optional.

**TX\_FINALLY**

> The **TX\_FINALLY** macro starts a block of code that will be executed
> regardless of whether the transaction is committed or aborted. This
> block is optional.

**TX\_END**

> The **TX\_END** macro cleans up and closes the transaction started by
> **TX\_BEGIN**() or **TX\_BEGIN\_LOCK**() macro. It is mandatory to
> terminate each transaction with this macro. If the transaction was
> aborted, errno is set appropriately.

Similarly to the macros controlling the transaction flow, the
**libpmemobj** defines a set of macros that simplify the transactional
operations on persistent objects. Note that those macros operate on
typed object handles, thus eliminating the need to specify the size of
the object, or the size and offset of the field in the user-defined
structure that is to be modified.

**TX\_ADD\_FIELD(TOID ***o***, ***FIELD***)**

> The **TX\_ADD\_FIELD**() macro saves in the undo log the current value
> of given *FIELD* of the object referenced by a handle *o*. The
> application is then free to directly modify the specified *FIELD*. In
> case of a failure or abort, the saved value will be restored.

**TX\_ADD(TOID ***o***)**

> The **TX\_ADD**() macro takes a \"snapshot\" of the entire object
> referenced by object handle *o* and saves it in the undo log. The
> object size is determined from its *TYPE*. The application is then
> free to directly modify the object. In case of a failure or abort, all
> the changes within the object will be rolled-back.

**TX\_ADD\_FIELD\_DIRECT(TYPE \****p***, ***FIELD***)**

> The **TX\_ADD\_FIELD\_DIRECT**() macro saves in the undo log the
> current value of given *FIELD* of the object referenced by (direct)
> pointer *p*. The application is then free to directly modify the
> specified *FIELD*. In case of a failure or abort, the saved value will
> be restored.

**TX\_ADD\_DIRECT(TYPE \****p***)**

> The **TX\_ADD\_DIRECT**() macro takes a \"snapshot\" of the entire
> object referenced by (direct) pointer *p* and saves it in the undo
> log. The object size is determined from its *TYPE*. The application is
> then free to directly modify the object. In case of a failure or
> abort, all the changes within the object will be rolled-back.

**TX\_SET(TOID ***o***, ***FIELD***, ***VALUE***)**

> The **TX\_SET** macro saves in the undo log the current value of given
> *FIELD* of the object referenced by a handle *o*, and then set its new
> *VALUE*. In case of a failure or abort, the saved value will be
> restored.

**TX\_SET\_DIRECT(TYPE \****p***, ***FIELD***, ***VALUE***)**

> The **TX\_SET\_DIRECT** macro saves in the undo log the current value
> of given *FIELD* of the object referenced by (direct) pointer *p*, and
> then set its new *VALUE*. In case of a failure or abort, the saved
> value will be restored.

**TX\_MEMCPY(void \****dest***, const void \****src***, size\_t
***num***)**

> The **TX\_MEMCPY** macro saves in the undo log the current content of
> *dest* buffer and then overwrites the first *num* bytes of its memory
> area with the data copied from the buffer pointed by *src*. In case of
> a failure or abort, the saved value will be restored.

**TX\_MEMSET(void \****dest***, int ***c***, size\_t ***num***)**

> The **TX\_MEMSET** macro saves in the undo log the current content of
> *dest* buffer and then fills the first *num* bytes of its memory area
> with the constant byte *c*. In case of a failure or abort, the saved
> value will be restored.

**TX\_NEW(***TYPE***)**

> The **TX\_NEW**() macro transactionally allocates a new object of
> given *TYPE* and assigns it a type number read from the typed OID. The
> allocation size is determined from the size of the user-defined
> structure *TYPE*. If successful and called during *TX\_STAGE\_WORK* it
> returns a handle to the newly allocated object. Otherwise, stage
> changes to *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and errno is
> set appropriately.

**TX\_ALLOC(***TYPE***, size\_t ***size***\")**

> The **TX\_ALLOC**() macro transactionally allocates a new object of
> given *TYPE* and assigns it a type number read from the typed OID. The
> allocation size is passed by *size* parameter. If successful and
> called during *TX\_STAGE\_WORK* it returns a handle to the newly
> allocated object. Otherwise, stage changes to *TX\_STAGE\_ONABORT*,
> OID\_NULL is returned, and errno is set appropriately.

**TX\_ZNEW(***TYPE***)**

> The **TX\_ZNEW**() macro transactionally allocates a new zeroed object
> of given *TYPE* and assigns it a type number read from the typed OID.
> The allocation size is determined from the size of the user-defined
> structure *TYPE*. If successful and called during *TX\_STAGE\_WORK* it
> returns a handle to the newly allocated object. Otherwise, stage
> changes to *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and errno is
> set appropriately.

**TX\_ZALLOC(***TYPE***)**

> The **TX\_ZALLOC**() macro transactionally allocates a new zeroed
> object of given *TYPE* and assigns it a type number read from the
> typed OID. The allocation size is passed by *size* argument. If
> successful and called during *TX\_STAGE\_WORK* it returns a handle to
> the newly allocated object. Otherwise, stage changes to
> *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and errno is set
> appropriately.

**TX\_REALLOC(TOID ***o***, size\_t ***size***)**

> The **TX\_REALLOC**() macro transactionally resizes an existing object
> referenced by a handle *o* to the given *size*. If successful and
> called during *TX\_STAGE\_WORK* it returns a handle to the reallocated
> object. Otherwise, stage changes to *TX\_STAGE\_ONABORT*, OID\_NULL is
> returned, and errno is set appropriately.

**TX\_ZREALLOC(TOID ***o***, size\_t ***size***)**

> The **TX\_ZREALLOC**() macro transactionally resizes an existing
> object referenced by a handle *o* to the given *size*. If the new size
> is larger than the old size, the extended new space is zeroed. If
> successful and called during *TX\_STAGE\_WORK* it returns a handle to
> the reallocated object. Otherwise, stage changes to
> *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and errno is set
> appropriately.

**TX\_STRDUP(const char \****s***, uint64\_t ***type\_num***)**

> The **TX\_STRDUP**() macro transactionally allocates a new object
> containing a duplicate of the string *s* and assigns it a type
> *type\_num*. If successful and called during *TX\_STAGE\_WORK* it
> returns a handle to the newly allocated object. Otherwise, stage
> changes to *TX\_STAGE\_ONABORT*, OID\_NULL is returned, and errno is
> set appropriately.

**TX\_FREE(TOID ***o***)**

> The **TX\_FREE**() transactionally frees the memory space represented
> by an object handle *o*. If *o* is OID\_NULL, no operation is
> performed. If successful and called during *TX\_STAGE\_WORK* it
> returns zero. Otherwise, stage changes to *TX\_STAGE\_ONABORT* and an
> error number is returned.

CAVEATS
=======

The transaction flow control is governed by the
**setjmp**(3)/**longjmp**(3) macros and they are used in both the macro
and function flavors of the API. The transaction will longjmp on
transaction abort. This has one major drawback which is described in the
ISO C standard subsection 7.13.2.1. It says that **the values of objects
of automatic storage duration that are local to** **the function
containing the setjmp invocation that do not have** **volatile-qualified
type and have been changed between the setjmp invocation** **and longjmp
call are indeterminate.**

The following example illustrates the issue described above.

    int *bad_example_1 = NULL;
    int *bad_example_2 = NULL;
    int *bad_example_3 = NULL;
    volatile int *good_example = NULL;

    TX_BEGIN(Pop) {
            bad_example_1 = malloc(...);
            bad_example_2 = malloc(...);
            bad_example_3 = malloc(...);
            good_example = malloc(...);
            ...
            pmemobj_tx_abort(EINVAL); /* manual or library abort called here */
    } TX_ONCOMMIT {
            /*
             * This section is longjmp-safe
             */
    } TX_ONABORT {
            /*
             * This section is not longjmp-safe
             */

             free(bad_example_1); /* undefined behavior */
             free(good_example); /* OK */
    } TX_FINALLY {
            /*
             * This section is not longjmp-safe on transaction abort only
             */

             free(bad_example_2); /* undefined behavior */
    } TX_END

    free(bad_example_3); /* undefined behavior */

Objects which are not volatile-qualified, are of automatic storage
duration and have been changed between the invocations of **setjmp**(3)
and **longjmp**(3) (that also means within the work section of the
transaction after TX\_BEGIN) should not be used after a transaction
abort or should be used with utmost care. This also includes code after
the **TX\_END** macro.

**Libpmemobj** is not cancellation-safe. The pool will never be
corrupted because of canceled thread, but other threads may stall
waiting on locks taken by that thread. If application wants to use
**pthread\_cancel**(3), it must disable cancellation before calling
**libpmemobj** APIs (see **pthread\_setcancelstate**(3) with
**PTHREAD\_CANCEL\_DISABLE**) and re-enable it after. Deferring
cancellation ( **pthread\_setcanceltype**(3) with
**PTHREAD\_CANCEL\_DEFERRED**) is not safe enough, because
**libpmemobj** internally may call functions that are specified as
cancellation points in POSIX API.

LIBRARY API VERSIONING
======================

This section describes how the library API is versioned, allowing
applications to work with an evolving API.

**const char \*pmemobj\_check\_version(**\
** unsigned ***major\_required***,**\
** unsigned ***minor\_required***);**

> The **pmemobj\_check\_version**() function is used to see if the
> installed **libpmemobj** supports the version of the library API
> required by an application. The easiest way to do this is for the
> application to supply the compile-time version information, supplied
> by defines in **\<libpmemobj.h\>**, like this:
>
>     reason = pmemobj_check_version(PMEMOBJ_MAJOR_VERSION,
>                                 PMEMOBJ_MINOR_VERSION);
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
> When the version check performed by **pmemobj\_check\_version**() is
> successful, the return value is NULL. Otherwise the return value is a
> static string describing the reason for failing the version check. The
> string returned by **pmemobj\_check\_version**() must not be modified
> or freed.

MANAGING LIBRARY BEHAVIOR
=========================

The library entry points described in this section are less commonly
used than the previous sections.

**void pmemobj\_set\_funcs(**\
** void \*(\****malloc\_func***)(size\_t ***size***),**\
** void (\****free\_func***)(void \****ptr***),**\
** void \*(\****realloc\_func***)(void \****ptr***, size\_t
***size***),**\
** char \*(\****strdup\_func***)(const char \****s***));**

> The **pmemobj\_set\_funcs**() function allows an application to
> override memory allocation calls used internally by **libpmemobj**.
> Passing in NULL for any of the handlers will cause the **libpmemobj**
> default function to be used. The library does not make heavy use of
> the system malloc functions, but it does allocate approximately 4-8
> kilobytes for each memory pool in use.

**int pmemobj\_check(const char \****path***, const char
\****layout***);**

> The **pmemobj\_check**() function performs a consistency check of the
> file indicated by *path* and returns 1 if the memory pool is found to
> be consistent. Any inconsistencies found will cause
> **pmemobj\_check**() to return 0, in which case the use of the file
> with **libpmemobj** will result in undefined behavior. The debug
> version of **libpmemobj** will provide additional details on
> inconsistencies when **PMEMOBJ\_LOG\_LEVEL** is at least 1, as
> described in the **DEBUGGING AND ERROR HANDLING** section below.
> **pmemobj\_check**() will return -1 and set errno if it cannot perform
> the consistency check due to other errors. **pmemobj\_check**() opens
> the given *path* read-only so it never makes any changes to the file.

DEBUGGING AND ERROR HANDLING
============================

Two versions of **libpmemobj** are typically available on a development
system. The normal version, accessed when a program is linked using the
**-lpmemobj** option, is optimized for performance. That version skips
checks that impact performance and never logs any trace information or
performs any run-time assertions. If an error is detected during the
call to **libpmemobj** function, an application may retrieve an error
message describing the reason of failure using the following function:

**\"const***char***\*pmemobj\_errormsg(void);**

> The **pmemobj\_errormsg**() function returns a pointer to a static
> buffer containing the last error message logged for current thread.
> The error message may include description of the corresponding error
> code (if errno was set), as returned by **strerror**(3). The error
> message buffer is thread-local; errors encountered in one thread do
> not affect its value in other threads. The buffer is never cleared by
> any library function; its content is significant only when the return
> value of the immediately preceding call to **libpmemobj** function
> indicated an error, or if errno was set. The application must not
> modify or free the error message string, but it may be modified by
> subsequent calls to other library functions.

A second version of **libpmemobj**, accessed when a program uses the
libraries under **/usr/lib/nvml\_debug**, contains run-time assertions
and trace points. The typical way to access the debug version is to set
the environment variable **LD\_LIBRARY\_PATH** to
**/usr/lib/nvml\_debug** or **/usr/lib64/nvml\_debug** depending on
where the debug libraries are installed on the system. The trace points
in the debug version of the library are enabled using the environment
variable **PMEMOBJ\_LOG\_LEVEL**, which can be set to the following
values:

0.  This is the default level when **PMEMOBJ\_LOG\_LEVEL** is not set.
    No log messages are emitted at this level.

1.  Additional details on any errors detected are logged (in addition to
    returning the errno-based errors as usual). The same information may
    be retrieved using **pmemobj\_errormsg**().

2.  A trace of basic operations is logged.

3.  This level enables a very verbose amount of function call tracing in
    the library.

4.  This level enables voluminous and fairly obscure tracing information
    that is likely only useful to the **libpmemobj** developers.

The environment variable **PMEMOBJ\_LOG\_FILE** specifies a file name
where all logging information should be written. If the last character
in the name is \"-\", the PID of the current process will be appended to
the file name when the log file is created. If **PMEMOBJ\_LOG\_FILE** is
not set, the logging output goes to stderr.

Setting the environment variable **PMEMOBJ\_LOG\_LEVEL** has no effect
on the non-debug version of **libpmemobj**.

See also **libpmem**(3) to get information about other environment
variables affecting **libpmemobj** behavior.

EXAMPLES
========

See http://pmem.io/nvml/libpmemobj for examples using the **libpmemobj**
API.

ACKNOWLEDGEMENTS
================

**libpmemobj** builds on the persistent memory programming model
recommended by the SNIA NVM Programming Technical Work Group:

> http://snia.org/nvmp

SEE ALSO
========

**mmap**(2), **munmap**(2), **msync**(2), **pthread\_mutex**(3),
**pthread\_rwlock**(3), **pthread\_cond**(3), **strerror**(3),
**libpmemblk**(3), **libpmemlog**(3), **libpmem**(3), **libvmem**(3) and
**http://pmem.io**.

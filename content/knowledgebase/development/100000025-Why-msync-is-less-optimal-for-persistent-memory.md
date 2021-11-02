---
title: Why msync() is less optimal for persistent memory
description: ''
layout: doc
categories: [development]
tags: [persistent memory, pmem, msync]
author: Steve Scargall
docid: 100000025
creation_date: 2020-03-24
modified_date: 
---

From the msync(2) man page:

msync()  flushes  changes  made  to  the  in-core  copy of a file that was mapped into memory using mmap(2) back to the filesystem.  Without use of this call, there is no guarantee that changes are written back before munmap(2) is  called. To  be more precise, the part of the file that corresponds to the memory area starting at addr and having length length is updated.

So why is msync() not primarily used to flush dirty pages to persistent memory?

There are three areas where the overhead of msync() can make a difference for persistent memory:

1. Small flushes get rounded up to large flushes.  For example, imagine you are implementing a btree where you are updating pointers during inserts, rebalancing, etc.  Each time you want to ensure an update has been made persistent, you flush some small ranges, like a few cache lines.  Each of those flushes gets rounded up to a page since that's the smallest granularity msync can handle.  The impact is not just the extra flushes, but also the side effect of those flushes which invalidates those extra cache lines, forcing cache misses when they are next accessed.

2. There is no way to tell msync() that non-temporal stores were used for parts of the range.  In the [Persistent Memory Development Kit](https://pmem.io/pmdk/), you'll see lots of places where it uses non-temporal (NT) stores for performance. A non-temporal store bypasses the CPU caches since the data is not expected to be accessed any time soon.  For the kind of fine-grained changes described in point 1, PMDK knows that only normal stores need flushing, and any ranges where NT stores were used can be considered persistent.  Note that even without PMDK in the picture, libc's memcpy() will sometimes use NT stores for large ranges.  You have no visibility into that, so you must call msync() to make changes persistent, even when there's no flush required due to NT stores.

3. The overhead of kernel system calls.  msync() can take locks, which can end up serializing highly multithreaded code.  If you use libpmem, or a higher-level library that uses it, from the PMDK,  the loop done by pmem_persist() within will be a shorter overall code path.  This is even more true on future platforms where CPU caches can be considered persistent.  PMDK applications will detect these platforms and skip the flushes, but msync() is still a trip through the kernel.

Of course, it depends on the use case.  For flushing large ranges where the kernel entry/exit is a very small portion of the code, and assuming no lock contention and no NT stores, pmem_persist() and msync() will perform about the same.  And also please remember that you can only use pmem_persist() when pmem_is_pmem returns true, meaning the file system has agreed to make it safe by granting mmap() with the MAP_SYNC flag.


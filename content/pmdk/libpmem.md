---
title: "libpmem | PMDK"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libpmem"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---
### The libpmem library

**libpmem** provides low level persistent memory support.
In particular, support for the persistent memory instructions
for flushing changes to pmem is provided.

This library is provided for software which tracks every store
to pmem and needs to flush those changes to durability.  Most
developers will find higher level libraries like
[libpmemobj](../libpmemobj) to be much more convenient.

>NOTE:
Support for **Windows** and **FreeBSD** are deprecated since **PMDK 1.13.0** release
and was removed in the **PMDK 2.0.0** release.

Man pages that contains a list of the **Linux** interfaces provided:

* Man page for <a href="../manpages/linux/master/libpmem/libpmem.7.html">libpmem current master</a>

Man pages that contains a list of the **Windows** interfaces provided:

* Man page for <a href="../manpages/windows/master/libpmem/libpmem.7.html">libpmem current master</a>

### libpmem Examples

#### The Basics

If you've decided to handle persistent memory allocation
and consistency across program interruption yourself, you will
find the functions in libpmem useful.  It is important to
understand that programming to raw pmem means you must create
your own transactions or convince yourself you don't care if
a system or program crash leaves your pmem files in an inconsistent
state.  Libraries like [libpmemobj](../libpmemobj) provide transactional
interfaces by building on these libpmem functions, but the interfaces
in libpmem are **non-transactional**.

To illustrate the basics, let's walk through the man page example first:

{{< highlight c "linenos=true,hl_lines=9,linenostart=8" >}}
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <stdio.h>
#include <errno.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <libpmem.h>
{{< /highlight >}}

The example starts, as shown above, by including the necessary
headers.  Line 16 (the highlighted line) shows the header file
you need to include to use libpmem: `libpmem.h`.

{{< highlight c "linenos=true,hl_lines=2,linenostart=22" >}}
/* using 4k of pmem for this example */
#define	PMEM_LEN 4096

#define PATH "/pmem-fs/myfile"
{{< /highlight >}}

For this simple example, we're just going to hard code a pmem file
size of 4 kilobytes.

{{< highlight c "linenos=true,hl_lines=9,linenostart=27" >}}
int
main(int argc, char *argv[])
{
	char *pmemaddr;
	size_t mapped_len;
	int is_pmem;

	/* create a pmem file and memory map it */
	if ((pmemaddr = pmem_map_file(PATH, PMEM_LEN, PMEM_FILE_CREATE,
				0666, &mapped_len, &is_pmem)) == NULL) {
		perror("pmem_map_file");
		exit(1);
	}
{{< /highlight >}}

The lines above create the file under specified path, with the
size of 4k, and map the file into memory. This illustrates one of
the helper functions in libpmem: `pmem_map_file()` which takes a path
of a file and desired size. It calls `mmap(2)` to memory map the
entire file.  Calling `mmap()` directly will work just fine --
the main advantage of `pmem_map_file()` is that it tries to find an
address where mapping is likely to use large page mappings,
for better performance when using large ranges of pmem.

Since the system calls for memory mapping persistent memory
are the same as the POSIX calls for memory mapping any file,
you may want to write your code to run correctly when given
either a pmem file or a file on a traditional file system.
For many decades it has been the case that changes written
to a memory mapped range of a file may not be persistent until
flushed to the media.  One common way to do this is using
the POSIX call `msync(2)`.  If you write your program to memory
map a file and use `msync()` every time you want to flush the
changes media, it will work correctly for pmem as well as files
on a traditional file system.  However, you may find your program
performs better if you detect pmem explicitly and use libpmem
to flush changes in that case.  `pmem_map_file` returns that
information (via `is_pmem` parameter).  It's also possible to
explicitly call `pmem_is_pmem(pmemaddr, mapped_len)` if needed.

The libpmem function `pmem_is_pmem()` can be used to determine
if the memory in the given range is really persistent memory or if
it is just a memory mapped file on a traditional file system.  Using
this call in your program will allow you to decide what to do when
given a non-pmem file.  Your program could decide to print an error
message and exit (for example: "ERROR: This program only works on pmem").
But it seems more likely you will want to save the value of `is_pmem`,
and then use that flag to decide what to do when flushing changes to
persistence as later in this example program.

{{< highlight c "linenos=true,hl_lines=2,linenostart=41" >}}
    /* store a string to the persistent memory */
    strcpy(pmemaddr, "hello, persistent memory");
{{< /highlight >}}

The novel thing about pmem is you can copy to it directly, like any
memory.  The `strcpy()` call shown on line 42 above is just the usual
_libc_ function that stores a string to memory.  If this example program
were to be interrupted either during or just after the `strcpy()` call,
you can't be sure which parts of the string made it all the way to the
media.  It might be none of the string, all of the string, or somewhere
in-between.  In addition, there's no guarantee the string will make it to
the media in the order it was stored!  For longer ranges, it is just as
likely that portions copied later make it to the media before earlier
portions.  (So don't write code like the example above and then expect
to check for zeros to see how much of the string was written.)

How can a string get stored in seemingly random order?
The reason is that until a flush function like `msync()`
has returned successfully, the normal cache pressure that happens
on an active system can push changes out to the media at any time
in any order.  Most processors have barrier instructions (like
`SFENCE` on the Intel platform) but those instructions deal with
ordering in the visibility of stores to other threads, not with
the order that changes reach persistence.  The only barriers for
flushing to persistence are functions like `msync()` or
`pmem_persist()` as shown below.

{{< highlight c "linenos=true,hl_lines=3,linenostart=44" >}}
    /* flush above strcpy to persistence */
    if (is_pmem)
        pmem_persist(pmemaddr, mapped_len);
    else
        pmem_msync(pmemaddr, mapped_len);
{{< /highlight >}}

As shown above, this example uses the `is_pmem` flag saved from the
previous call to `pmem_map_file()`.  This is the recommended way to
use this information rather than calling `pmem_is_pmem()` each time
you want to make changes durable.  That's because `pmem_is_pmem()`
can have a high overhead, having to search through data structures to
ensure the entire range is really persistent memory.

For true pmem, the highlighted line 46 above is the most optimal way
to flush changes to persistence.  `pmem_persist()` will, if possible,
perform the flush directly from user space, without calling into the
OS.  This is made possible on the Intel platform using instructions like
`CLWB` and `CLFLUSHOPT` which are
[described in Intel's manuals](https://software.intel.com/sites/default/files/managed/0d/53/319433-022.pdf).
Of course you are free to use these instructions directly in your
program, but the program will crash with an _undefined opcode_ if
you try to use the instructions on a platform that doesn't support
them.  This is where libpmem helps you out, by checking the platform
capabilities on start-up and choosing the best instructions for each
operation it supports.

The above example also uses `pmem_msync()` for the non-pmem case
instead of calling `msync(2)` directly.  For convenience, the
`pmem_msync()` call is a small wrapper around `msync()` that ensures
the arguments are aligned, as requirement of POSIX.

Buildable source for the
[libpmem manpage.c](https://github.com/pmem/pmdk/tree/master/src/examples/libpmem)
example above is available in the PMDK repository.

#### Copying to Persistent Memory

Another feature of libpmem is a set of routines for optimally copying
to persistent memory.  These functions perform the same functions as
the _libc_ functions `memcpy()`, `memset()`, and `memmove()`, but they
are optimized for copying to pmem.  On the Intel platform, this is done
using the _non-temporal_ store instructions which bypass the processor
caches (eliminating the need to flush that portion of the data path).

The first copy example, called *simple_copy*, illustrates how
`pmem_memcpy()` is used.

{{< highlight c "linenos=true,hl_lines=10,linenostart=58" >}}
	/* read up to BUF_LEN from srcfd */
	if ((cc = read(srcfd, buf, BUF_LEN)) < 0) {
		pmem_unmap(pmemaddr, mapped_len);
		perror("read");
		exit(1);
	}

	/* write it to the pmem */
	if (is_pmem) {
		pmem_memcpy_persist(pmemaddr, buf, cc);
	} else {
		memcpy(pmemaddr, buf, cc);
		pmem_msync(pmemaddr, cc);
	}
{{< /highlight >}}

The highlighted line, line 67 above, shows how `pmem_memcpy_persist()` is
used just like `memcpy(3)` except that when the destination is pmem,
libpmem handles flushing the data to persistence as part of the copy.
Please note: `pmem_memcpy_persist()` is an alias for `pmem_memcpy()`
with flags equal to 0.

Buildable source for the
[libpmem simple_copy.c](https://github.com/pmem/pmdk/tree/master/src/examples/libpmem)
example above is available in the PMDK repository.

#### Separating the Flush Steps

There are two steps in flushing to persistence.  The first
step is to flush the processor caches, or bypass them entirely
as explained in the previous example.  The second step is to
wait for any hardware buffers to drain, to ensure writes have
reached the media.  These steps are performed together when
`pmem_persist()` is called, or they can be called individually
by calling `pmem_flush()` for the first step and `pmem_drain()`
for the second.  Note that either of these steps may be
unnecessary on a given platform, and the library knows how
to check for that and do the right thing.  For example, on
Intel platforms with eADR, `pmem_flush()` is an empty function.

When does it make sense to break flushing into steps?  This example,
called *full_copy* illustrates one reason you might do this.  Since
the example copies data using multiple calls to `memcpy()`, it
uses the version of libpmem copy that only performs the flush, postponing
the final drain step to the end.  This works because unlike the flush
step, the drain step does not take an address range -- it is a system-wide
drain operation so can happen at the end of the loop that copies
individual blocks of data.

{{< highlight c "linenos=true,hl_lines=12 22,linenostart=29" >}}
/*
 * do_copy_to_pmem -- copy to pmem, postponing drain step until the end
 */
static void
do_copy_to_pmem(char *pmemaddr, int srcfd, off_t len)
{
	char buf[BUF_LEN];
	int cc;

	/* copy the file, saving the last flush step to the end */
	while ((cc = read(srcfd, buf, BUF_LEN)) > 0) {
		pmem_memcpy_nodrain(pmemaddr, buf, cc);
		pmemaddr += cc;
	}

	if (cc < 0) {
		perror("read");
		exit(1);
	}

	/* perform final flush step */
	pmem_drain();
}
{{< /highlight >}}

As each block is copied, line 40 in the above example copies a block of
data to pmem, effectively flushing it from the processor caches.  But
rather than waiting for the hardware queues to drain each time, that
step is saved until the end, as shown on line 50 above.

Buildable source for the
[libpmem full_copy.c](https://github.com/pmem/pmdk/tree/master/src/examples/libpmem)
example above is available in the PMDK repository.

---
title: "libpmemblk | PMDK"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libpmemblk"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Discontinuation of the project
The **libpmemblk** project will no longer be maintained by Intel.
- Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases,
or updates, to this project.
- Intel no longer accepts patches to this project.
- If you have an ongoing need to use this project, are interested in independently developing it, or would like to
maintain patches for the open source software community, please create your own fork of this project.
- You will find more information [here](/blog/2022/11/update-on-pmdk-and-our-long-term-support-strategy/).

### The libpmemblk library

**libpmemblk** implements a pmem-resident array of blocks,
all the same size, where a block is updated atomically with
respect to power failure or program interruption (no torn
blocks).

This library is provided for cases requiring large arrays
of objects at least 512 bytes each.  Most
developers will find higher level libraries like
[libpmemobj](../libpmemobj) to be more generally useful.

Man pages that contains a list of the **Linux** interfaces provided:

* Man page for <a href="../manpages/linux/master/libpmemblk/libpmemblk.7.html">libpmemblk current master</a>


Man pages that contains a list of the **Windows** interfaces provided:

* Man page for <a href="../manpages/windows/master/libpmemblk/libpmemblk.7.html">libpmemblk current master</a>

# NOTE #

> NOTICE:
The **libpmemblk** library is deprecated since PMDK 1.13.0 release
and was removed in the PMDK 2.0.0 release.

### libpmemblk Examples

{{< highlight c "linenos=true,hl_lines=7,linenostart=37" >}}
#include <stdio.h>
#include <fcntl.h>
#include <errno.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <libpmemblk.h>

/* size of the pmemblk pool -- 1 GB */
#define	POOL_SIZE ((off_t)(1 << 30))

/* size of each element in the pmem pool */
#define	ELEMENT_SIZE 1024

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
	printf("file holds %zu elements\n", nelements);

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
{{< /highlight >}}
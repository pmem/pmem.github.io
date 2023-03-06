---
title: "librpmem | PMDK"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "librpmem"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Discontinuation of the project
The **librpmem** library will no longer be maintained by Intel.
- Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases,
or updates, to this project.
- Intel no longer accepts patches to this project.
- If you have an ongoing need to use this project, are interested in independently developing it, or would like to
maintain patches for the open source software community, please create your own fork of this project.
- You will find more information [here](/blog/2022/11/update-on-pmdk-and-our-long-term-support-strategy/).

### The librpmem library

> **Note:** The alternative solution for accessing remote persistent memory is implemented by [librpma](/librpma).

> **Note:** This is a **deprecated** API and should not be used in production environments.

The last PMDK release, which contains this library is [stable-1.12](https://github.com/pmem/pmdk/tree/stable-1.12).

**librpmem** provides low-level support for remote access to
*persistent memory* (pmem) utilizing RDMA-capable RNICs. The library can be
used to replicate remotely a memory region over RDMA protocol.
It utilizes appropriate persistency mechanism based on remote node's platform
capabilities. The **librpmem** utilizes the **ssh** client to authenticate
a user on remote node and for encryption of connection's out-of-band
configuration data. See **SSH** section for details.

This library is for applications that use remote persistent memory directly,
without the help of any library-supplied transactions or memory
allocation. Higher-level libraries that build on **libpmem** are
available and are recommended for most applications, see:

Man pages that contains a list of the **Linux** interfaces provided:
* Man page for [librpmem in the latest stable release](https://github.com/pmem/pmdk/tree/stable-1.12/doc/librpmem)

### The rpmemd utility

> **Note:** This is a **deprecated** utility and should not be used in production environments.

The **rpmemd** process is executed on target node by **librpmem** library over
**ssh**(1) and facilitates access to persistent memory over RDMA.

See the rpmemd man page documentation and examples
[for the latest stable release](../manpages/linux/v1.12/rpmemd/rpmemd.1.html)
or browse older versions there.

### librpmem Examples

{{< highlight c "linenos=true,hl_lines=7,linenostart=36">}}
#include <assert.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

#include <librpmem.h>

#define POOL_SIZE	(32 * 1024 * 1024)
#define NLANES		4

int
main(int argc, char *argv[])
{
	int ret;
	unsigned nlanes = NLANES;

	/* allocate a page size aligned local memory pool */
	long pagesize = sysconf(_SC_PAGESIZE);
	void *pool;
	ret = posix_memalign(&pool, pagesize, POOL_SIZE);
	if (ret) {
		fprintf(stderr, "posix_memaling: %s\n", strerror(ret));
		return 1;
	}
	assert(pool != NULL);

	/* fill pool_attributes */
	struct rpmem_pool_attr pool_attr;
	memset(&pool_attr, 0, sizeof(pool_attr));

	/* create a remote pool */
	RPMEMpool *rpp = rpmem_create("localhost", "pool.set",
		pool, POOL_SIZE, &nlanes, &pool_attr);
	if (!rpp) {
		fprintf(stderr, "rpmem_create: %s\n", rpmem_errormsg());
		return 1;
	}

	/* store data in the local pool */
	memset(pool, 0, POOL_SIZE);

	/* make local data persistent on the target node */
	ret = rpmem_persist(rpp, 0, POOL_SIZE, 0);
	if (ret) {
		fprintf(stderr, "rpmem_persist: %s\n", rpmem_errormsg());
		return 1;
	}

	/* close the remote pool */
	ret = rpmem_close(rpp);
	if (ret) {
		fprintf(stderr, "rpmem_close: %s\n", rpmem_errormsg());
		return 1;
	}

	/* release the local memory pool */
	free(pool);

	return 0;
}
{{< /highlight >}}

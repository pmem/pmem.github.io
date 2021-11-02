---
title: "libvmem | PMDK"
url: "/vmem/libvmem"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libvmem"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---
### The libvmem library

**libvmem** supports the traditional _malloc_/_free_
interfaces on a memory mapped file.  This allows the
use of persistent memory as volatile memory, for cases
where the pool of persistent memory is useful to an
application, but when the application doesn't need
it to be persistent.

>**Note:**
Since persistent memory support
has been integrated into [libmemkind](https://github.com/memkind/memkind),
that library is the **recommended** choice for any new volatile usages,
since it combines support for multiple types of volatile memory into
a single, convenient API.

Man pages that contains a list of the **Linux** interfaces provided:

* Man page for <a href="../manpages/linux/master/libvmem/libvmem.7.html">libvmem current master</a>


Man pages that contains a list of the **Windows** interfaces provided:

* Man page for <a href="../manpages/windows/master/libvmem/libvmem.7.html">libvmem current master</a>

### libvmem Examples

#### More Detail Coming Soon

{{< highlight c "linenos=true,hl_lines=4,linenostart=37">}}

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <libvmem.h>

int
main(int argc, char *argv[])
{
	VMEM *vmp;
	char *ptr;

	/* create minimum size pool of memory */
	if ((vmp = vmem_create("/pmem-fs",
					VMEM_MIN_POOL)) == NULL) {
		perror("vmem_create");
		exit(1);
	}

	if ((ptr = vmem_malloc(vmp, 100)) == NULL) {
		perror("vmem_malloc");
		exit(1);
	}

	strcpy(ptr, "hello, world");

	/* give the memory back */
	vmem_free(vmp, ptr);

	/* ... */
}
{{< /highlight >}}
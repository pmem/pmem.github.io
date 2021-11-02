---
title: "libvmmalloc | PMDK"
url: "/vmem/libvmmalloc"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libvmmalloc"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---
### The libvmmalloc library

**libvmmalloc** interposes the traditional _malloc_/_free_ interfaces and,
in a way fully transparent to the program, substitutes the system heap
with a volatile memory pool built on memory-mapped file.  Such memory pool
works in a similar manner as the memory pools provided by **libvmem**,
except that it is created and destroyed automatically for each process
that uses **libvmmalloc**.

The typical usage of **libvmmalloc** is to load it before all other libraries
by setting the environment variable **LD_PRELOAD**.

Man pages that contains a list of the **Linux** interfaces provided:

* Man page for <a href="../manpages/linux/master/libvmmalloc/libvmmalloc.7.html">libvmmalloc current master</a>


### libvmmalloc Examples

#### More Detail Coming Soon

{{< highlight c "linenos=true,hl_lines=4,linenostart=37">}}
export VMMALLOC_POOL_SIZE=$((16*1024*1024))
export VMMALLOC_POOL_DIR="/pmem-fs"

LD_PRELOAD=libvmmalloc.so.1 grep "pmem" /proc/mounts
{{< /highlight >}}
---
title: "libpmemobj | PMDK"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libpmemobj"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Discontinuation of the project
The **libpmemobj** project will no longer be maintained by Intel.
- Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases,
or updates, to this project.
- Intel no longer accepts patches to this project.
- If you have an ongoing need to use this project, are interested in independently developing it, or would like to
maintain patches for the open source software community, please create your own fork of this project.
- You will find more information [here](/blog/2022/11/update-on-pmdk-and-our-long-term-support-strategy/).

### The libpmemobj library

**libpmemobj** turns a persistent memory file into a
flexible object store, supporting transactions, memory
management, locking, lists, and a number of other features.

Man pages that contains a list of the **Linux** interfaces provided:

* Man page for <a href="../manpages/linux/master/libpmemobj/libpmemobj.7.html">libpmemobj current master</a>


Man pages that contains a list of the **Windows** interfaces provided:

* Man page for <a href="../manpages/windows/master/libpmemobj/libpmemobj.7.html">libpmemobj current master</a>

### libpmemobj Examples

The following series of blog articles provides a tutorial introduction
to **libpmemobj**:

* [Part 0 - new programming model](/2015/06/12/pmem-model.html)
* [Part 1 - accessing the persistent memory](/2015/06/13/accessing-pmem.html)
* [Part 2 - transactions](/2015/06/15/transactions.html)
* [Part 3 - types](/2015/06/16/types.html)
* [Part 4 - transactional dynamic memory allocation](/2015/06/17/tx-alloc.html)
* [Part 5 - atomic dynamic memory allocation](/2015/06/18/ntx-alloc.html)
* [Part 6 - threading](/2015/06/18/threads.html)
* [Part 7 - persistent lists](/2015/06/19/lists.html)
---
title: "libpmempool | PMDK"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libpmempool"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---
### The libpmempool library

**libpmempool** provides a set of utilities for management, diagnostics and
repair of persistent memory pools.
By pool in this context we mean pmemobj pool, pmemblk pool, pmemlog pool or
BTT layout, independent of the underlying storage.
The **libpmempool** is for applications that need high reliability or built-in
troubleshooting. It may be useful for testing and debugging purposes also.

>NOTE:
Support for **Windows** and **FreeBSD** are deprecated since **PMDK 1.13.0** release
and was removed in the **PMDK 2.0.0** release.

Each of the following man pages contain an example and a list of the OS-specific interfaces:
* Man page for [Linux libpmempool(7) current master](../manpages/linux/master/libpmempool/libpmempool.7.html)
* Man page for [Windows libpmempool(7) current master](../manpages/windows/master/libpmempool/libpmempool.7.html)

For up-to-date **libpmempool** example and its building steps, please see GitHub repository
["examples" directory](https://github.com/pmem/pmdk/tree/master/src/examples/libpmempool).

Please note:
> If you are rather looking for a standalone tool instead of a library, see [**pmempool**](/pmdk/pmempool/).
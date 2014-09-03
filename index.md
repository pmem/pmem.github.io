---
title: Home
---

This site is focused on making _persistent memory programming_ easier.
The current focus is on the Linux NVM Library, which is
a library (set of libraries, actually) designed to provide some
useful APIs for server applications wanting to use persistent memory.
You can [read more about the NVM Library](/nvml/) or
[go directly to the source](https://github.com/pmem/nvml/).
Contributions are welcome!

**Note: The NVM Library is still under development and is not
yet ready for production use.**

The Linux NVM Library builds on the **Direct Access** (DAX) changes
under development in Linux.  Check out the
[PRD repo](https://github.com/01org/prd) for the latest snapshot of this work.

#### What Is It?

For many years computer applications organize their data between two
tiers: memory and storage.  We believe the emerging _persistent memory_
technologies introduce a third tier.  Persistent memory (or _pmem_
for short) is accessed like volatile memory, using processor load
and store instructions, but it retains its contents across power loss
like storage.

This project focuses specifically on how persistent memory is exposed
to server-class applications which will explicitly manage the placement
of data among the three tiers (volatile memory, persistent memory, and
storage).  This project is not looking at client or mobile uses of
persistent memory, which are more likely to be transparent to the
applications (at least, we're not focusing of that yet).  The first
focus is to expose the capabilities of this new technology and provide
software to help applications exploit it.

#### More Information

See our [blog entries](/blog/) for more information, especially
the entry on the
[overall architecture]({% post_url 2014-08-27-crawl-walk-run %})
for persistent memory programming and the entry containing
[references]({% post_url 2014-08-26-references %}) to more information.

Your questions, comments, and contributions are welcome!  Join our
[Google Group](http://groups.google.com/group/pmem) find us on
IRC on the **#pmem** channel on [OFTC](http://www.oftc.net).

---
title: Home
---

#### The [Persistent Memory Development Kit](/pmdk/)

The **Persistent Memory Development Kit** ([**PMDK**](/pmdk/)),
formerly known as [**NVML**]({% post_url 2017-12-11-NVML-is-now-PMDK %}),
is a growing collection of libraries (currently ten of them) which have been
developed for various use cases, tuned, validated to production quality,
and thoroughly documented.  These libraries build on the
**Direct Access** (**DAX**) feature available in both Linux and Windows,
which allows applications direct load/store access to persistent memory by
memory-mapping files on a persistent memory aware file system.
PMDK also includes a collection of tools, examples, and tutorials
on persistent memory programming.

* [read more about PMDK](/pmdk/)
* [go directly to the source on GitHub](https://github.com/pmem/pmdk/)

To learn more about the latest
persistent memory developments for Linux, go to the
[Persistent Memory Wiki](https://nvdimm.wiki.kernel.org).
If you want to learn more about how DAX is made available in Windows,
check out this
[Channel9 video](https://channel9.msdn.com/Events/Build/2016/P470).

#### What Is Persistent Memory?

For many years computer applications organized their data between two
tiers: memory and storage.  The emerging _persistent memory_
technologies introduce a third tier.  Persistent memory (or _pmem_
for short) is accessed like volatile memory, using processor load
and store instructions, but it retains its contents across power loss
like storage.

This site focuses specifically on how persistent memory is exposed
to applications which explicitly manage the placement
of data among the three tiers (volatile memory, persistent memory, and
storage).

#### More Information

See our [blog entries](/blog/) for more information.  If you're just
starting out, you might find the entry on the
[overall architecture]({% post_url 2014-08-27-crawl-walk-run %})
useful.

The [Documents](/documents/) page contains links to additional
reading material.

Your questions, comments, and contributions are welcome!  Join our
[Google Group](http://groups.google.com/group/pmem) find us on
IRC on the **#pmem** channel on [OFTC](http://www.oftc.net).

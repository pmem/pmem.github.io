---
title: Home
---

This site is dedicated to persistent memory programming.  If you're just
getting started, head to the [Documentation Area](https://docs.pmem.io)
for links to background information, a Getting Started Guide, and lots
of additional information.

Here are some of the top links to related information:

* The [Persistent Memory Development Kit](/pmdk/)
* Information on the [PMDK 1.5 release]({% post_url 2018-10-22-release-1-5 %})
* The [Persistent Memory Summit 2018](https://www.snia.org/pm-summit)
* The [Intel Developer Zone for persistent memory](https://software.intel.com/en-us/persistent-memory)


#### What Is Persistent Memory?

The term _persistent memory_ is used to describe technologies which
allow programs to access data as memory, directly byte-addressable,
while the contents are non-volatile, preserved across power cycles.  It
has aspects that are like memory, and aspects that are like storage, but
it doesn't typically replace either memory or storage.  Instead, persistent
memory is a third tier, used in conjunction with memory and storage.

With this new ingrediant, systems containing persistent memory can
outperform legacy configurations, providing faster start-up times,
faster access to large in-memory datasets, and often improved total cost of
ownership.


#### The [Persistent Memory Development Kit](/pmdk/)

The **Persistent Memory Development Kit** ([PMDK][pmdk]),
is a growing collection of libraries which have been
developed for various use cases, tuned, validated to production quality,
and thoroughly documented.  These libraries build on the
**Direct Access** (**DAX**) feature available in both Linux and Windows,
which allows applications direct load/store access to persistent memory by
memory-mapping files on a persistent memory aware file system.
PMDK also includes a collection of tools, examples, and tutorials
on persistent memory programming.

* [read more about PMDK][pmdk]
* [go directly to the source on GitHub](https://github.com/pmem/pmdk/)

PMDK is vendor-neutral, started by Intel,
motivated by the introduction of [Optane DC persistent
memory](https://www.intel.com/content/www/us/en/architecture-and-technology/optane-dc-persistent-memory.html).
PMDK will work with any persistent
memory that provides the [SNIA NVM Programming Model](https://www.snia.org/sites/default/files/technical_work/final/NVMProgrammingModel_v1.2.pdf).
It is open source and welcomes community contributions.


#### More Information

The [Documents](/documents/) page contains links to additional
reading material.

Your questions, comments, and contributions are welcome!  Join our
[Google Group](http://groups.google.com/group/pmem) find us on
IRC on the **#pmem** channel on [OFTC](http://www.oftc.net).

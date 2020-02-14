---
title: Home
---

<table style="border:4px; border-style:solid; border-color:#3498DB; padding: 1em;">
<tr><td>
<a href="book/"><img src="assets/book.jpg" style="float:left"></a>
The <a href="book/">Programming Persistent Memory book</a> is now available!
This is a great resource, whether you're just learning about persistent memory
or you want to deep dive into the programming details.
You can read the book on-line <b>for free!</b></td></tr></table>

This site is dedicated to persistent memory programming.  If you're just
getting started, head to the [Documentation Area](https://docs.pmem.io)
for links to background information, a Getting Started Guide, and lots
of additional information.

Here are some of the top links to related information:

* [Persistent Memory Development Kit](/pmdk/)
* [Persistent Memory Summit](https://www.snia.org/pm-summit)
* [Intel Developer Zone for persistent memory](https://software.intel.com/en-us/persistent-memory)
* [PIRL Conference](https://pirl.nvsl.io/program/) (Persistent Programming In Real Life)


#### What Is Persistent Memory?

The term _persistent memory_ is used to describe technologies which
allow programs to access data as memory, directly byte-addressable,
while the contents are non-volatile, preserved across power cycles.  It
has aspects that are like memory, and aspects that are like storage, but
it doesn't typically replace either memory or storage.  Instead, persistent
memory is a third tier, used in conjunction with memory and storage.

With this new ingredient, systems containing persistent memory can
outperform legacy configurations, providing faster start-up times,
faster access to large in-memory datasets, and often improved total cost of
ownership.


#### The [Persistent Memory Development Kit](/pmdk/)

The **Persistent Memory Development Kit**, [PMDK](/pmdk/),
is a growing collection of libraries which have been
developed for various use cases, tuned, validated to production quality,
and thoroughly documented.  These libraries build on the
**Direct Access** (DAX) feature available in both Linux and Windows,
which allows applications direct load/store access to persistent memory by
memory-mapping files on a persistent memory aware file system.
PMDK also includes a collection of tools, examples, and tutorials
on persistent memory programming.

* [read more about PMDK](/pmdk/)
* [go directly to the source on GitHub](/repoindex)

PMDK is vendor-neutral, started by Intel,
motivated by the introduction of [Optane DC persistent
memory](https://www.intel.com/content/www/us/en/architecture-and-technology/optane-dc-persistent-memory.html).
PMDK will work with any persistent
memory that provides the [SNIA NVM Programming Model](https://www.snia.org/sites/default/files/technical_work/final/NVMProgrammingModel_v1.2.pdf).
It is open source and welcomes community contributions.


#### More Information

The [Documents](https://docs.pmem.io) page contains more information.

Your questions, comments, and contributions are welcome!  Join our
[Google Group](https://groups.google.com/group/pmem), find us on
IRC on the **#pmem** channel on [OFTC](https://www.oftc.net), or
join our **#pmem** Slack channel using this
[invite link](https://join.slack.com/t/pmem-io/shared_invite/enQtNzU4MzQ2Mzk3MDQwLWQ1YThmODVmMGFkZWI0YTdhODg4ODVhODdhYjg3NmE4N2ViZGI5NTRmZTBiNDYyOGJjYTIyNmZjYzQxODcwNDg).

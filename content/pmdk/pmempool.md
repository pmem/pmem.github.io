---
title: "pmempool | PMDK"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "pmempool"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---
### The pmempool utility

**pmempool** is a standalone utility for management and off-line analysis
of persistent memory pools. It works for both the *single-file* pools and
for *pool set files*. Below you can find a list of available commands.

See the [pmempool man page](../manpages/linux/master/pmempool/pmempool.1.html)
for current master documentation and examples or see older versions:

* [pmempool version 1.11](../manpages/linux/v1.11/pmempool/pmempool.1.html)
* [pmempool version 1.10](../manpages/linux/v1.10/pmempool/pmempool.1.html)
* [pmempool version 1.9](../manpages/linux/v1.9/pmempool/pmempool.1.html)
* [pmempool version 1.8](../manpages/linux/v1.8/pmempool/pmempool.1.html)
* [pmempool version 1.7](../manpages/linux/v1.7/pmempool/pmempool.1.html)
* [pmempool version 1.6](../manpages/linux/v1.6/pmempool/pmempool.1.html)
* [pmempool version 1.5](../manpages/linux/v1.5/pmempool/pmempool.1.html)
* [pmempool version 1.4](../manpages/linux/v1.4/pmempool/pmempool.1.html)
* [pmempool version 1.3](../manpages/linux/v1.3/pmempool/pmempool.1.html)
* [pmempool version 1.2](../manpages/linux/v1.2/pmempool/pmempool.1.html)
* [pmempool version 1.1](../manpages/linux/v1.1/pmempool/pmempool.1.html)
* [pmempool version 1.0](../manpages/linux/v1.0/pmempool/pmempool.1.html)

### create

The **pmempool create** command creates a persistent memory pool of specified
type and parameters. Creating the persistent memory pool is possible using
the NVM library APIs, however using the **pmempool** utility for creating the
pool files may simplify the user application.

See the [pmempool create man page](../manpages/linux/master/pmempool/pmempool-create.1.html)
for current documentation and examples or see older versions:

* [pmempool-create version 1.11](../manpages/linux/v1.11/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.10](../manpages/linux/v1.10/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.9](../manpages/linux/v1.9/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.8](../manpages/linux/v1.8/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.7](../manpages/linux/v1.7/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.6](../manpages/linux/v1.6/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.5](../manpages/linux/v1.5/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.4](../manpages/linux/v1.4/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.3](../manpages/linux/v1.3/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.2](../manpages/linux/v1.2/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.1](../manpages/linux/v1.1/pmempool/pmempool-create.1.html)
* [pmempool-create version 1.0](../manpages/linux/v1.0/pmempool/pmempool-create.1.html)

### info

The **pmempool info** command performs an off-line analysis of a persistent memory
pool. The **pmempool info** command prints all persistent data
structures and evaluates some basic statistics of the pool. By default the
**pmempool info** prints the *pool header* and *pool descriptor* data
structures, but by adding command line arguments it is possible to print
additional information about the pool.

See the [pmempool info man page](../manpages/linux/master/pmempool/pmempool-info.1.html)
for current master documentation and examples or see older versions:

* [pmempool-info version 1.11](../manpages/linux/v1.11/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.10](../manpages/linux/v1.10/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.9](../manpages/linux/v1.9/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.8](../manpages/linux/v1.8/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.7](../manpages/linux/v1.7/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.6](../manpages/linux/v1.6/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.5](../manpages/linux/v1.5/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.4](../manpages/linux/v1.4/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.3](../manpages/linux/v1.3/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.2](../manpages/linux/v1.2/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.1](../manpages/linux/v1.1/pmempool/pmempool-info.1.html)
* [pmempool-info version 1.0](../manpages/linux/v1.0/pmempool/pmempool-info.1.html)

### dump

The **pmempool dump** command dumps the user data stored in a persistent memory
pool file. This command currently works only for the **pmemblk** and
**pmemlog** pool types. It is possible to pick the data dump format as well as
the range of desired data.

See the [pmempool dump man page](../manpages/linux/master/pmempool/pmempool-dump.1.html)
for current master documentation and examples or see older versions:

* [pmempool-dump version 1.11](../manpages/linux/v1.11/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.10](../manpages/linux/v1.10/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.9](../manpages/linux/v1.9/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.8](../manpages/linux/v1.8/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.7](../manpages/linux/v1.7/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.6](../manpages/linux/v1.6/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.5](../manpages/linux/v1.5/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.4](../manpages/linux/v1.4/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.3](../manpages/linux/v1.3/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.2](../manpages/linux/v1.2/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.1](../manpages/linux/v1.1/pmempool/pmempool-dump.1.html)
* [pmempool-dump version 1.0](../manpages/linux/v1.0/pmempool/pmempool-dump.1.html)

### check

The **pmempool check** command checks consistency of the persistent memory pool.
It prints information about errors found. This command is able to repair
a broken pool in some cases. Currently it works only for the **pmemblk** and
**pmemlog** pool types but the support for **pmemobj** pool type will be
available in the near feature.

See the [pmempool check man page](../manpages/linux/master/pmempool/pmempool-check.1.html)
for current master documentation and examples or see older version:

* [pmempool-check version 1.11](../manpages/linux/v1.11/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.10](../manpages/linux/v1.10/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.9](../manpages/linux/v1.9/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.8](../manpages/linux/v1.8/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.7](../manpages/linux/v1.7/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.6](../manpages/linux/v1.6/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.5](../manpages/linux/v1.5/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.4](../manpages/linux/v1.4/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.3](../manpages/linux/v1.3/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.2](../manpages/linux/v1.2/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.1](../manpages/linux/v1.1/pmempool/pmempool-check.1.html)
* [pmempool-check version 1.0](../manpages/linux/v1.0/pmempool/pmempool-check.1.html)

### rm

The **pmempool rm** is a simple replacement for the system **rm** command for
the persistent memory pools. The command may be useful for removing the
*pool set files* because it removes all part files specified in the
configuration file. For *single-file* pools it works almost the same
as the standard system **rm** command.

See the [pmempool rm man page](../manpages/linux/master/pmempool/pmempool-rm.1.html)
for current master documentation and examples or see older version:

* [pmempool-rm version 1.11](../manpages/linux/v1.11/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.10](../manpages/linux/v1.10/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.9](../manpages/linux/v1.9/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.8](../manpages/linux/v1.8/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.7](../manpages/linux/v1.7/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.6](../manpages/linux/v1.6/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.5](../manpages/linux/v1.5/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.4](../manpages/linux/v1.4/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.3](../manpages/linux/v1.3/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.2](../manpages/linux/v1.2/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.1](../manpages/linux/v1.1/pmempool/pmempool-rm.1.html)
* [pmempool-rm version 1.0](../manpages/linux/v1.0/pmempool/pmempool-rm.1.html)

### convert

The **pmempool convert** command performs conversion of the specified pool
from the old layout versions to the newest one supported by this tool.
Currently only **pmemobj** pools are supported.

Starting from PMDK 1.5 **pmempool convert** is a thin wrapper around a tool
called **pmdk-convert**. This tool handles all versions of PMDK pools and is
the recommended conversion mechanism.

See the [pmdk-convert man page](../../pmdk-convert/manpages/master/pmdk-convert.1.html) for the current master documentation or see the older version:

* [pmdk-convert version 1.5](../../pmdk-convert/manpages/v1.5/pmdk-convert.1.html)

For documentation of **pmempool convert** for older PMDK versions see:

* [pmempool-convert version 1.11](../manpages/linux/v1.11/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.10](../manpages/linux/v1.10/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.9](../manpages/linux/v1.9/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.8](../manpages/linux/v1.8/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.7](../manpages/linux/v1.7/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.6](../manpages/linux/v1.6/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.5](../manpages/linux/v1.5/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.4](../manpages/linux/v1.4/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.3](../manpages/linux/v1.3/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.2](../manpages/linux/v1.2/pmempool/pmempool-convert.1.html)
* [pmempool-convert version 1.1](../manpages/linux/v1.1/pmempool/pmempool-convert.1.html)

### sync

The **pmempool sync** command synchronizes data between replicas within
a poolset. It checks if metadata of all replicas in a poolset
are consistent, i.e. all parts are healthy, and if any of them is not,
the corrupted or missing parts are recreated and filled with data from one of
the healthy replicas.

See the [pmempool sync man page](../manpages/linux/master/pmempool/pmempool-sync.1.htmll)
for current master documentation and examples or see older version:

* [pmempool-sync version 1.11](../manpages/linux/v1.11/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.10](../manpages/linux/v1.10/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.9](../manpages/linux/v1.9/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.8](../manpages/linux/v1.8/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.7](../manpages/linux/v1.7/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.6](../manpages/linux/v1.6/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.5](../manpages/linux/v1.5/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.4](../manpages/linux/v1.4/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.3](../manpages/linux/v1.3/pmempool/pmempool-sync.1.html)
* [pmempool-sync version 1.2](../manpages/linux/v1.2/pmempool/pmempool-sync.1.html)

### transform

The **pmempool transform** command modifies internal structure of a poolset
defined by one poolset file, according to a structure described in
another poolset file.

See the [pmempool transform man page](../manpages/linux/master/pmempool/pmempool-transform.1.html)
for current master documentation and examples or see older version:

* [pmempool-transform version 1.11](../manpages/linux/v1.11/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.10](../manpages/linux/v1.10/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.9](../manpages/linux/v1.9/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.8](../manpages/linux/v1.8/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.7](../manpages/linux/v1.7/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.6](../manpages/linux/v1.6/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.5](../manpages/linux/v1.5/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.4](../manpages/linux/v1.4/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.3](../manpages/linux/v1.3/pmempool/pmempool-transform.1.html)
* [pmempool-transform version 1.2](../manpages/linux/v1.2/pmempool/pmempool-transform.1.html)

### feature

The **pmempool feature** command enables / disables or query pool set features.

See the [pmempool feature man page](../manpages/linux/master/pmempool/pmempool-feature.1.html)
for current master documentation and examples.
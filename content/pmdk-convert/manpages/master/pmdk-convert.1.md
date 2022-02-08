---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["pmdk-convert.1.html"]
title: "pmempool convert | PMDK"
header: "pmem Tools version 1.5"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2016-2017, Intel Corporation)

[comment]: <> (pmdk-convert.1 -- man page for pmdk-convert)

[NAME](#name)<br />
[SYNOPSIS](#synopsis)<br />
[DESCRIPTION](#description)<br />
[EXAMPLE](#example)<br />
[SEE ALSO](#see-also)<br />


# NAME #

**pmdk-convert** - upgrade pool files layout version


# SYNOPSIS #

``` bash
$ pmdk-convert <file>
```


# DESCRIPTION #

The **pmdk-convert** performs a conversion of the specified pool to the newest
layout supported by this tool. Currently only **libpmemobj**(7) pools are supported.

The conversion process is not fail-safe - power interruption may damage the
pool. It is advised to have a backup of the pool before conversion.

This tool doesn't support remote replicas. Before a conversion all remote replicas
have to be removed from the pool by **pmempool transform** command.

##### Options: #####

`-V, --version`

Display version information and exit.

`-h, --help`

Display help and the list of supported layouts and corresponding PMDK versions.

`-f, --from=pmdk-version`

Convert from specified PMDK version. This option is exclusive with -F option.

`-F, --from-layout=version`

Convert from specified layout version. This option is exclusive with -f option.

`-t, --to=version`

convert to specified PMDK version. This option is exclusive with -T option.

`-T, --to-layout=version`

Convert to specified layout version. This option is exclusive with -t option.

`-X, --force-yes=[question]`
reply positively to specified question
possible questions:
- fail-safety
- 1.2-pmemmutex


# EXAMPLE #

``` bash
$ pmempool convert pool.obj
```

Updates pool.obj to the latest layout version.

``` bash
$ pmempool convert pool.obj --from=1.2 --to=1.4
```

Updates pool.obj from PMDK 1.2 to PMDK 1.4


# SEE ALSO #

**pmempool**(1), **libpmemblk**(7), **libpmemlog**(7),
**libpmemobj**(7), **libpmempool**(7) and **<https://pmem.io>**

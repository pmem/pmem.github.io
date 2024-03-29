---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["pmempool-dump.1.html"]
title: "pmempool | PMDK"
header: "pmem Tools"
---

NAME
====

pmempool-dump - Dump user data from specified pool

SYNOPSIS
========

**pmempool dump** \[\<options\>\] \<file\>

DESCRIPTION
===========

The **pmempool** invoked with **dump** command dumps user data from
specified pool file. The output format may be either binary or
hexadecimal.

By default the output format is hexadecimal.

By default data is dumped to standard output. It is possible to dump
data to other file by specifying **-o** option. In this case data will
be appended to this file.

Using **-r** option you can specify number of blocks/bytes/data chunks
using special text format. See **RANGE** section for details.

Available options:
------------------

**-b, \--binary**

> Dump data in binary format.

**-r, \--range** \<range\>

> Range of pool file to dump. This may be number of blocks for **blk**
> pool type or either number of bytes or number of data chunks for
> **log** pool type.

**-c, \--chunk** \<size\>

> Size of chunk for **log** pool type. See **pmemlog\_walk**() in
> **libpmemlog(3)** for details.

**-o, \--output** \<file\>

> Name of output file.

**-h, \--help**

> Display help message and exit.

RANGE
=====

Using **-r, \--range** option it is possible to dump only a range of
user data. This section describes valid format of *\<range\>* string.

You can specify multiple ranges separated by commas.

**\<first\>-\<last\>**

> All blocks/bytes/data chunks from **\<first\>** to **\<last\>** will
> be dumped.

**-\<last\>**

> All blocks/bytes/data chunks up to **\<last\>** will be dumped.

**\<first\>-**

> All blocks/bytes/data chunks starting from **\<first\>** will be
> dumped.

**\<number\>**

> Only **\<number\>** block/byte/data chunk will be dumped.

EXAMPLES
========

pmempool dump pool.bin

:   \# Dump user data from pool.bin file to standard output

pmempool dump -o output.bin -r1,10-100 pool\_blk.bin

:   \# Dump block number 1 and blocks from 10 to 100 from pool\_blk.bin
    containing pmem blk pool to output.bin file

pmempool dump -r 1K-2K pool.bin

:   \# Dump data form 1K to 2K from pool.bin file.

SEE ALSO
========

**libpmemblk(3) libpmemlog(3) pmempool(1)**

PMEMPOOL
========

Part of the **pmempool(1)** suite.

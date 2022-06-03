---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["pmempool-convert.1.html"]
title: "pmempool | PMDK"
header: "pmem Tools"
---

NAME
====

pmempool-convert - Convert pool files from old layout versions to the
newest one.

SYNOPSIS
========

**pmempool convert** \<file\>

DESCRIPTION
===========

The **pmempool** invoked with the **convert** command performs a
conversion of the specified pool to the newest layout supported by this
tool. Currently only **libpmemobj(3)** pools are supported. It is
advised to have a backup of the pool before conversion. The conversion
process is not fail-safe - power interruption may damage the pool.

EXAMPLES
========

pmempool convert pool.obj

:   \# Updates pool.obj to the latest layout version.

SEE ALSO
========

**pmempool(1) pmempool-info(1) libpmemobj(3)**

PMEMPOOL
========

Part of the **pmempool(1)** suite.

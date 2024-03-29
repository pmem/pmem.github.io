---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_peer_cfg_new.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.12.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_peer\_cfg\_new** - create a new peer configuration object

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_peer_cfg;
          int rpma_peer_cfg_new(struct rpma_peer_cfg **pcfg_ptr);

DESCRIPTION
===========

**rpma\_peer\_cfg\_new**() creates a new peer configuration object.

RETURN VALUE
============

The **rpma\_peer\_cfg\_new**() function returns 0 on success or a
negative error code on failure. **rpm\_peer\_cfg\_new**() does not set
\*pcfg\_ptr value on failure.

ERRORS
======

**rpma\_peer\_cfg\_new**() can fail with the following errors:

-   RPMA\_E\_INVAL - pcfg\_ptr is NULL

-   RPMA\_E\_NOMEM - out of memory

SEE ALSO
========

**rpma\_conn\_apply\_remote\_peer\_cfg**(3),
**rpma\_peer\_cfg\_delete**(3),
**rpma\_peer\_cfg\_from\_descriptor**(3),
**rpma\_peer\_cfg\_get\_descriptor**(3),
**rpma\_peer\_cfg\_get\_descriptor\_size**(3),
**rpma\_peer\_cfg\_get\_direct\_write\_to\_pmem**(3),
**rpma\_peer\_cfg\_set\_direct\_write\_to\_pmem**(3), **librpma**(7) and
https://pmem.io/rpma/

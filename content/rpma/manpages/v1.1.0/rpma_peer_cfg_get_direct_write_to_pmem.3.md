---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_peer_cfg_get_direct_write_to_pmem.3.html"]
title: "librpma | PMDK"
header: "librpma API version 1.1.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_peer\_cfg\_get\_direct\_write\_to\_pmem** - check direct write
to PMEM support

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_peer_cfg;
          int rpma_peer_cfg_get_direct_write_to_pmem(
                          const struct rpma_peer_cfg *pcfg, bool *supported);

DESCRIPTION
===========

**rpma\_peer\_cfg\_get\_direct\_write\_to\_pmem**() checks the support
of the direct write to PMEM.

RETURN VALUE
============

The **rpma\_peer\_cfg\_get\_direct\_write\_to\_pmem**() function returns
0 on success or a negative error code on failure.

ERRORS
======

**rpma\_peer\_cfg\_get\_direct\_write\_to\_pmem**() can fail with the
following error:

-   RPMA\_E\_INVAL - pcfg or supported are NULL

SEE ALSO
========

**rpma\_peer\_cfg\_from\_descriptor**(3), **rpma\_peer\_cfg\_new**(3),
**rpma\_peer\_cfg\_set\_direct\_write\_to\_pmem**(3), **librpma**(7) and
https://pmem.io/rpma/

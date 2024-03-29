---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_conn_cfg_set_sq_size.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.14.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_conn\_cfg\_set\_sq\_size** - set SQ size for the connection

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_conn_cfg;
          int rpma_conn_cfg_set_sq_size(struct rpma_conn_cfg *cfg,
                          uint32_t sq_size);

DESCRIPTION
===========

**rpma\_conn\_cfg\_set\_sq\_size**() sets the SQ size for the
connection. If this function is not called, the sq\_size has the default
value (10) set by **rpma\_conn\_cfg\_new**(3).

RETURN VALUE
============

The **rpma\_conn\_cfg\_set\_sq\_size**() function returns 0 on success
or a negative error code on failure.

ERRORS
======

**rpma\_conn\_cfg\_set\_sq\_size**() can fail with the following error:

-   RPMA\_E\_INVAL - cfg is NULL

SEE ALSO
========

**rpma\_conn\_cfg\_new**(3), **rpma\_conn\_cfg\_get\_sq\_size**(3),
**librpma**(7) and https://pmem.io/rpma/

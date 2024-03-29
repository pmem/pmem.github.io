---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_conn_delete.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.9.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020, Intel Corporation)

NAME
====

**rpma\_conn\_delete** - delete already closed connection

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_conn;
          int rpma_conn_delete(struct rpma_conn **conn_ptr);

DESCRIPTION
===========

**rpma\_conn\_delete**() deletes already closed connection.

RETURN VALUE
============

The **rpma\_conn\_delete**() function returns 0 on success or a negative
error code on failure. **rpma\_conn\_delete**() sets \*conn\_ptr value
to NULL on success and on failure.

ERRORS
======

**rpma\_conn\_delete**() can fail with the following errors:

-   RPMA\_E\_INVAL - conn\_ptr is NULL

-   RPMA\_E\_PROVIDER - **ibv\_destroy\_cq**() or
    **rdma\_destroy\_id**() failed

SEE ALSO
========

**rpma\_conn\_disconnect**(3), **rpma\_conn\_req\_connect**(3),
**librpma**(7) and https://pmem.io/rpma/

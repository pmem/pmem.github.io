---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_conn_disconnect.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.14.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_conn\_disconnect** - tear the connection down

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_conn;
          int rpma_conn_disconnect(struct rpma_conn *conn);

DESCRIPTION
===========

**rpma\_conn\_disconnect**() tears the connection down.

-   It may initiate disconnecting the connection. In this case, the end
    of disconnecting is signalled by the RPMA\_CONN\_CLOSED event via
    **rpma\_conn\_next\_event**() or

-   It may be called after receiving the RPMA\_CONN\_CLOSED event. In
    this case, the disconnection is done when
    **rpma\_conn\_disconnect**() returns with success.

RETURN VALUE
============

The **rpma\_conn\_disconnect**() function returns 0 on success or a
negative error code on failure.

ERRORS
======

**rpma\_conn\_disconnect**() can fail with the following errors:

-   RPMA\_E\_INVAL - conn is NULL

-   RPMA\_E\_PROVIDER - **rdma\_disconnect**() failed

SEE ALSO
========

**rpma\_conn\_delete**(3), **rpma\_conn\_next\_event**(3),
**rpma\_conn\_req\_connect**(3), **librpma**(7) and
https://pmem.io/rpma/

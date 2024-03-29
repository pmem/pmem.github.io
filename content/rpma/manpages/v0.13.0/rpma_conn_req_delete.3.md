---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_conn_req_delete.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.13.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_conn\_req\_delete** - delete the connection requests

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_conn_req;
          int rpma_conn_req_delete(struct rpma_conn_req **req_ptr);

DESCRIPTION
===========

**rpma\_conn\_req\_delete**() deletes the connection requests both
incoming and outgoing.

RETURN VALUE
============

The **rpma\_conn\_req\_delete**() function returns 0 on success or a
negative error code on failure. **rpma\_conn\_req\_delete**() sets
\*req\_ptr value to NULL on success and on failure.

ERRORS
======

**rpma\_conn\_req\_delete**() can fail with the following errors:

-   RPMA\_E\_INVAL - req\_ptr is NULL

-   RPMA\_E\_PROVIDER

    -   **rdma\_destroy\_qp**(3) or **ibv\_destroy\_cq**(3) failed

    -   **rdma\_reject**(3) or **rdma\_ack\_cm\_event**(3) failed
        (passive side only)

    -   **rdma\_destroy\_id**(3) failed (active side only)

SEE ALSO
========

**rpma\_conn\_req\_new**(3), **librpma**(7) and https://pmem.io/rpma/

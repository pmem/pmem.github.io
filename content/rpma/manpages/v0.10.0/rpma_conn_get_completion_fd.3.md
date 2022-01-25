---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_conn_get_completion_fd.3.html"]
title: "librpma | PMDK"
header: "rpma API version 0.10.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020, Intel Corporation)

NAME
====

**rpma\_conn\_get\_completion\_fd** - get the completion file descriptor
(deprecated)

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_conn;
          int rpma_conn_get_completion_fd(const struct rpma_conn *conn, int *fd);

DESCRIPTION
===========

**rpma\_conn\_get\_completion\_fd**() gets the completion file
descriptor of the connection. It is the same file descriptor as the one
returned by the **rpma\_cq\_get\_fd**(3) for the connection\'s main CQ
available via **rpma\_conn\_get\_cq**(3).

RETURN VALUE
============

The **rpma\_conn\_get\_completion\_fd**() function returns 0 on success
or a negative error code on failure.
**rpma\_conn\_get\_completion\_fd**() does not set \*fd value on
failure.

ERRORS
======

**rpma\_conn\_get\_completion\_fd**() can fail with the following error:

-   RPMA\_E\_INVAL - conn or fd is NULL

DEPRECATED
==========

Please use **rpma\_conn\_get\_cq**(3) and **rpma\_cq\_get\_fd**(3)
instead. This is an example snippet of code using the old API:

            int ret;
            int fd;

            ret = rpma_conn_get_completion_fd(conn, &fd);
            if (ret) { error_handling_code() }

            ret = rpma_conn_completion_wait(conn);
            if (ret) { error_handling_code() }

            struct rpma_completion cmpl;
            ret = rpma_conn_completion_get(conn, &cmpl);
            if (ret) { error_handling_code() }

The above snippet should be replaced with the following one using the
new API:

            rpma_cq *cq;
            if (rpma_conn_get_cq(conn, &cq)) { error_handling_code() }

            ret = rpma_cq_get_fd(cq, &fd);
            if (ret) { error_handling_code() }

            ret = rpma_cq_wait(cq);
            if (ret) { error_handling_code() }

            struct rpma_completion cmpl;

            ret = rpma_cq_get_completion(cq, &cmpl);
            if (ret) { error_handling_code() }

SEE ALSO
========

**rpma\_conn\_completion\_get**(3), **rpma\_conn\_completion\_wait**(3),
**rpma\_conn\_req\_connect**(3), **librpma**(7) and
https://pmem.io/rpma/

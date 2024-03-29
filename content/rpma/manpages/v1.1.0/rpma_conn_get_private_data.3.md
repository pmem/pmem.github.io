---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_conn_get_private_data.3.html"]
title: "librpma | PMDK"
header: "librpma API version 1.1.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_conn\_get\_private\_data** - get a pointer to the connection\'s
private data

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_conn;
          struct rpma_conn_private_data;
          int rpma_conn_get_private_data(const struct rpma_conn *conn,
                          struct rpma_conn_private_data *pdata);

DESCRIPTION
===========

**rpma\_conn\_get\_private\_data**() obtains the pointer to the private
data given by the other side of the connection.

RETURN VALUE
============

The **rpma\_conn\_get\_private\_data**() function returns 0 on success
or a negative error code on failure.
**rpma\_conn\_get\_private\_data**() does not set \*pdata value on
failure.

ERRORS
======

**rpma\_conn\_get\_private\_data**() can fail with the following error:

-   RPMA\_E\_INVAL - conn or pdata is NULL

SEE ALSO
========

**rpma\_conn\_req\_connect**(3), **librpma**(7) and
https://pmem.io/rpma/

---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_mr_dereg.3.html"]
title: "librpma | PMDK"
header: "librpma API version 1.1.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_mr\_dereg** - delete a local memory registration object

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_mr_local;
          int rpma_mr_dereg(struct rpma_mr_local **mr_ptr);

DESCRIPTION
===========

**rpma\_mr\_dereg**() deregisters a memory region and deletes a local
memory registration object.

RETURN VALUE
============

The **rpma\_mr\_dereg**() function returns 0 on success or a negative
error code on failure. **rpma\_mr\_dereg**() does not set \*mr\_ptr
value to NULL on failure.

ERRORS
======

**rpma\_mr\_dereg**() can fail with the following errors:

-   RPMA\_E\_INVAL - mr\_ptr is NULL

-   RPMA\_E\_PROVIDER - memory deregistration failed

SEE ALSO
========

**rpma\_mr\_reg**(3), **librpma**(7) and https://pmem.io/rpma/

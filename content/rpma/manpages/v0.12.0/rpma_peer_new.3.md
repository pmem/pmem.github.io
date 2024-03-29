---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_peer_new.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.12.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_peer\_new** - create a peer object

SYNOPSIS
========

          #include <librpma.h>

          struct ibv_context;
          struct rpma_peer;
          int rpma_peer_new(struct ibv_context *ibv_ctx,
                          struct rpma_peer **peer_ptr);

DESCRIPTION
===========

**rpma\_peer\_new**() creates a new peer object.

RETURN VALUE
============

The **rpma\_peer\_new**() function returns 0 on success or a negative
error code on failure. **rpma\_peer\_new**() does not set \*peer\_ptr
value on failure.

ERRORS
======

**rpma\_peer\_new**() can fail with the following errors:

-   RPMA\_E\_INVAL - ibv\_ctx or peer\_ptr is NULL

-   RPMA\_E\_NOMEM - creating a verbs protection domain failed with
    ENOMEM.

-   RPMA\_E\_PROVIDER - creating a verbs protection domain failed with
    error other than ENOMEM.

-   RPMA\_E\_UNKNOWN - creating a verbs protection domain failed without
    error value.

-   RPMA\_E\_NOMEM - out of memory

SEE ALSO
========

**rpma\_conn\_req\_new**(3), **rpma\_ep\_listen**(3),
**rpma\_mr\_reg**(3), **rpma\_peer\_delete**(3),
**rpma\_utils\_get\_ibv\_context**(3), **librpma**(7) and
https://pmem.io/rpma/

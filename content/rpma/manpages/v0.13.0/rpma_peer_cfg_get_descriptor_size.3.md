---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_peer_cfg_get_descriptor_size.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.13.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_peer\_cfg\_get\_descriptor\_size** - get size of the peer cfg
descriptor

SYNOPSIS
========

          #include <librpma.h>

          struct rpma_peer_cfg;
          int rpma_peer_cfg_get_descriptor_size(const struct rpma_peer_cfg *pcfg,
                          size_t *desc_size);

DESCRIPTION
===========

**rpma\_peer\_cfg\_get\_descriptor\_size**() gets size of the peer
configuration descriptor.

RETURN VALUE
============

The **rpma\_peer\_cfg\_get\_descriptor\_size**() function returns 0 on
success or a negative error code on failure.

ERRORS
======

**rpma\_peer\_cfg\_get\_descriptor\_size**() can fail with the following
error:

-   RPMA\_E\_INVAL - pcfg or desc\_size is NULL

SEE ALSO
========

**rpma\_peer\_cfg\_get\_descriptor**(3), **rpma\_peer\_cfg\_new**(3),
**librpma**(7) and https://pmem.io/rpma/

---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_log_get_threshold.3.html"]
title: "librpma | PMDK"
header: "librpma API version 0.11.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2022, Intel Corporation)

NAME
====

**rpma\_log\_get\_threshold** - get the logging threshold level

SYNOPSIS
========

          #include <librpma.h>

          int rpma_log_get_threshold(enum rpma_log_threshold threshold,
                          enum rpma_log_level *level);

DESCRIPTION
===========

**rpma\_log\_get\_threshold**() gets the current *level* of the
*threshold*. See **rpma\_log\_set\_threshold**(3) for available
thresholds and levels.

RETURN VALUE
============

**rpma\_log\_get\_threshold**() function returns 0 on success or a
negative error code on failure.

ERRORS
======

**rpma\_log\_get\_threshold**() can fail with the following errors:

-   RPMA\_E\_INVAL - *threshold* is not RPMA\_LOG\_THRESHOLD nor
    RPMA\_LOG\_THRESHOLD\_AUX

-   RPMA\_E\_INVAL - \**level* is NULL

SEE ALSO
========

**rpma\_log\_set\_function**(3), **rpma\_log\_set\_threshold**(3),
**librpma**(7) and https://pmem.io/rpma/

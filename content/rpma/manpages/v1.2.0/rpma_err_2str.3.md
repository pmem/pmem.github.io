---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_err_2str.3.html"]
title: "librpma | PMDK"
header: "librpma API version 1.2.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2023, Intel Corporation)

# NAME

**rpma_err_2str** - convert RPMA error code to a string

# SYNOPSIS

          #include <librpma.h>

          const char *rpma_err_2str(int ret);

# DESCRIPTION

**rpma_err_2str**() returns the const string representation of RPMA
error codes.

# ERRORS

**rpma_err_2str**() can not fail.

# SEE ALSO

**librpma**(7) and https://pmem.io/rpma/

---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_conn_cfg_set_compl_channel.3.html"]
title: "librpma | PMDK"
header: "librpma API version 1.3.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2023, Intel Corporation)

# NAME

**rpma_conn_cfg_set_compl_channel** - set if the completion event
channel can be shared by CQ and RCQ

# SYNOPSIS

          #include <librpma.h>

          struct rpma_conn_cfg;
          int rpma_conn_cfg_set_compl_channel(struct rpma_conn_cfg *cfg, bool shared);

# DESCRIPTION

**rpma_conn_cfg_set_compl_channel**() sets if the completion event
channel can be shared by CQ and RCQ or not. The completion event channel
is not shared by CQ and RCQ by default. See **rpma_conn_cfg_new**(3) for
details.

# RETURN VALUE

The **rpma_conn_cfg_set_compl_channel**() function returns 0 on success
or a negative error code on failure.

# ERRORS

**rpma_conn_cfg_set_compl_channel**() can fail with the following error:

-   RPMA_E\_INVAL - cfg is NULL

# SEE ALSO

**rpma_conn_cfg_new**(3), **rpma_conn_wait**(3),
**rpma_conn_cfg_get_compl_channel**(3), **librpma**(7) and
https://pmem.io/rpma/

---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["rpma_atomic_write.3.html"]
title: "librpma | PMDK"
header: "librpma API version 1.2.0"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2023, Intel Corporation)

# NAME

**rpma_atomic_write \-- initiate the atomic 8 bytes write operation**

# SYNOPSIS

          #include <librpma.h>

          struct rpma_conn;
          struct rpma_mr_remote;
          int rpma_atomic_write(struct rpma_conn *conn,
                          struct rpma_mr_remote *dst, size_t dst_offset,
                          const char src[8], int flags, const void *op_context);

# DESCRIPTION

**rpma_atomic_write**() initiates the atomic 8 bytes write operation
(transferring data from the local memory to the remote memory). The
atomic write operation allows transferring exactly 8 bytes of data and
storing them atomically in the remote memory.

The attribute flags set the completion notification indicator:

-   RPMA_F\_COMPLETION_ON_ERROR - generate the completion on error

-   RPMA_F\_COMPLETION_ALWAYS - generate the completion regardless of
    result of the operation.

op_context is returned in the wr_id field of the completion (struct
ibv_wc).

# RETURN VALUE

The **rpma_atomic_write**() function returns 0 on success or a negative
error code on failure.

# ERRORS

**rpma_atomic_write**() can fail with the following errors:

-   RPMA_E\_INVAL - conn, dst or src is NULL

-   RPMA_E\_INVAL - dst_offset is not aligned to 8 bytes

-   RPMA_E\_INVAL - flags are not set (flags == 0)

-   RPMA_E\_PROVIDER - **ibv_post_send**(3) failed

# SEE ALSO

**rpma_conn_req_connect**(3), **rpma_mr_reg**(3),
**rpma_mr_remote_from_descriptor**(3), **librpma**(7) and
https://pmem.io/rpma/

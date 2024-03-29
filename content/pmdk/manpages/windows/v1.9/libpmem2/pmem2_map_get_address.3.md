---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["pmem2_map_get_address.3.html"]
title: "libpmem2 | PMDK"
header: "pmem2 API version 1.0"
---

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2019, Intel Corporation)

[comment]: <> (pmem2_map_get_address.3 -- man page for libpmem2 mapping operations)

[NAME](#name)<br />
[SYNOPSIS](#synopsis)<br />
[DESCRIPTION](#description)<br />
[RETURN VALUE](#return-value)<br />
[SEE ALSO](#see-also)<br />

# NAME #

**pmem2_map_get_address**() - reads mapping address

# SYNOPSIS #

```c
#include <libpmem2.h>

void *pmem2_map_get_address(struct pmem2_map *map);
```

# DESCRIPTION #

The **pmem2_map_get_address**() function reads address of the created mapping.
The *map* parameter points to the structure describing mapping created using
the **pmem2_map**(3) function.

# RETURN VALUE #

The **pmem2_map_get_address**() function returns a pointer to the mapped area.

# SEE ALSO #

**pmem2_map**(3), **libpmem2**(7) and **<https://pmem.io>**

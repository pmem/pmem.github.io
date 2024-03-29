---
draft: false
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["pmemset_part_new.3.html"]
title: "libpmemset | PMDK"
header: "pmemset API version 1.0"
---

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2020-2021, Intel Corporation)

[comment]: <> (pmemset_part_new.3 -- man page for libpmemset pmemset_part_new operation)

[NAME](#name)<br />
[SYNOPSIS](#synopsis)<br />
[DESCRIPTION](#description)<br />
[RETURN VALUE](#return-value)<br />
[ERRORS](#errors)<br />
[SEE ALSO](#see-also)<br />

# NAME #

**pmemset_part_new**(), **pmemset_part_delete**() - create and delete structure
for a part object

# SYNOPSIS #

```c
#include <libpmemset.h>

struct pmemset;
struct pmemset_part;
struct pmemset_source;
int pmemset_part_new(struct pmemset_part **part, struct pmemset *set,
		struct pmemset_source *src, size_t offset, size_t length);
int pmemset_part_delete(struct pmemset_part **part);
```

# DESCRIPTION #

The **pmemset_part_new**() creates new part based on the set specified in the *\*set* pointer.
This function requires a data source *source*.

For the operation to succeed the *src* structure must be created from a valid data source.
See **pmemset_source_from_file**(3) and **pmemset_source_from_pmem2**(3) for possible sources.

If the **pmemset_part_new**() function succeeds in creating a new part it
instantiates a new *struct pmemset_part** object describing the part. The pointer
to this newly created object is stored in the user-provided variable passed
via the *part* pointer. If the mapping fails the variable pointed by *part*
will contain a NULL value and appropriate error value will be returned.
For a list of possible return values please see [RETURN VALUE](#return-value).

The **pmemset_part_delete**() function frees *\*part* returned by **pmemset_part_new**()
and sets *\*part* to NULL.

# RETURN VALUE #

The **pmemset_part_new**() function returns 0 on success
or a negative error code on failure.

The **pmemset_part_delete**() function always returns 0.

# ERRORS #

The **pmemset_part_new**() can fail with the following errors:

* **PMEMSET_E_INVALID_PMEM2_SOURCE** - *pmem2_source* set in the *src* structure
is invalid.

* **PMEMSET_E_INVALID_SOURCE_PATH** - the path to the file set in the provided *src*
structure points to invalid file.

* **PMEMSET_E_INVALID_SOURCE_TYPE** - the source type in the provided *src* isn't recognized.

* **-ENOMEM** in case of insufficient memory to allocate an instance
of *struct pmemset_part*.

It can also return **-EACCES**, **-EFAULT**, **-ELOOP**, **-ENAMETOOLONG**,
**-ENOMEM**, **-ENOTDIR**, **-EOVERFLOW** from the underlying **stat**(2) function.

# SEE ALSO #

**stat**(2), **pmemset_source_from_pmem2**(), **pmemset_source_from_file**(3),
**libpmemset**(7) and **<http://pmem.io>**

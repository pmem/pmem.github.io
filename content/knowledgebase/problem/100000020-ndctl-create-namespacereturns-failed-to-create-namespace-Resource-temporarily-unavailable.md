---
title: Executing "ndctl create-namespace" returns "failed to create namespace. Resource temporarily unavailable"
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ndctl, create]
author: Steve Scargall
docid: 100000020
creation_date: 2020-03-11
modified_date:
---

# Applies To

- Linux
- ndctl utility

# Issue

Creating a new namespace using 'ndctl create-namespace', may return the following error:

```
# ndctl create-namespace -r region0
failed to create namespace: Resource temporarily unavailable
```

# Cause

There are many potential causes including:

- There's no available capacity within the region because one or more namespaces exist and have consumed all the space.
- The region is disabled.
- There's an issue with the labels for the NVDIMMs belonging to the region.

# Solution

Use the `-v` option to print more information to help identify the cause. A debug version of `ndctl` may be required to get useful information. See [Installing NDCTL](https://github.com/sscargal/pmem-docs-ndctl-user-guide/blob/master/installing-ndctl.md) for instructions to build `ndctl` with debug options from source code.

For a scenario where there's no space left within the region, a message similar to the following will be shown:

```
# ndctl create-namespace -r region0 -v
...
namespace_create:772: region0: insufficient capacity size: 0 avail: 0
failed to create namespace: Resource temporarily unavailable
```

When a region is disabled, the following will be shown:

```
# ndctl create-namespace -r region0 -v
...
validate_namespace_options:473: region0: disabled, skipping...
failed to create namespace: Resource temporarily unavailable
```

Identifying label issues requires more investigation. Start with [Validating Labels]().

The `Resource temporarily unavailable` message improvements is resolved in ndctl v66 or later. See https://github.com/pmem/ndctl/issues/67

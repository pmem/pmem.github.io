---
title: Executing "ndctl destroy-namespace" returns "Error. namespace0.0 is active, specify --force for re-configuration"
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ndctl, destroy]
author: Steve Scargall
docid: 100000019
creation_date: 2020-03-11
modified_date: 2020-03-11
---

# Applies To

- Linux
- ndctl utility

# Issue

Destroying a namespace mode may fail with the following error:

```
# ndctl destroy-namespace namespace0.0
  Error: namespace0.0 is active, specify --force for re-configuration
```

Changing a namespace mode may fail with the following error:

```
# ndctl create-namespace -e namespace0.0 -m fsdax
  Error: namespace0.0 is active, specify --force for re-configuration

failed to reconfigure namespace: Device or resource busy
```

# Cause

The error indicates the namespace is currently **active** and potentially mounted (FSDAX) or in use, so the operation is not permitted.

# Solution

1) Verify the current namespace mode using `ndctl list -N` to show all namespaces or filter the result by specifying namespace name using `ndctl list -N namespace{X.Y}`, eg:

```
# ndctl list -N
{
  "dev":"namespace0.0",
  "mode":"raw",
  "size":134217728000,
  "uuid":"0a66b46c-5146-45bb-a0f1-320f28db4d40",
  "sector_size":512,
  "blockdev":"pmem0",
  "numa_node":0
}
```

If the namespace mode is 'fsdax', verify any associated filesystems are unmounted.

If the namespace mode is 'devdax', verify any application using the device is stopped.



The 'fuser' command on Linux can be used to identify any running processes with the /dev/dax* or /dev/pmem* devices open.

2) Either disable the namespace prior to deleting it or changing the mode or use the `-f`, `--force` options to override the active status checks.

To change the mode of an active namespace without disabling it first, use the `-f`, `--force` option:

```
# ndctl create-namespace -f -e namespace0.0 -m fsdax
{
  "dev":"namespace0.0",
  "mode":"fsdax",
  "map":"dev",
  "size":"123.04 GiB (132.12 GB)",
  "uuid":"1cf25e33-8dfa-4ad2-b409-e236d598bc1e",
  "raw_uuid":"9368c235-26d3-4220-9048-e85f3ccd8469",
  "sector_size":512,
  "blockdev":"pmem0",
  "numa_node":0
}
```

To delete a namespace without disabling it first, use the `-f`, `--force` option:

```
# ndctl destroy-namespace -f namespace0.0
destroyed 1 namespace
```

Alternatively, disable the namespace then perform the destroy/mode change operation. Note: Changing a namespace mode automatically activates it.

To change the namespace mode from 'raw' to 'fsdax':

```
# ndctl list -N
{
  "dev":"namespace0.0",
  "mode":"raw",
  "size":134217728000,
  "uuid":"ce15f005-d3bd-4a94-8565-55be77bed6f6",
  "sector_size":512,
  "blockdev":"pmem0",
  "numa_node":0
}

# ndctl disable-namespace namespace0.0
disabled 1 namespace

# ndctl create-namespace -e namespace0.0 -m fsdax
{
  "dev":"namespace0.0",
  "mode":"fsdax",
  "map":"dev",
  "size":"123.04 GiB (132.12 GB)",
  "uuid":"f45a6725-b80e-4174-a366-b07c91e66a58",
  "raw_uuid":"ee359fec-0a83-436a-91b6-44e3f45b04e0",
  "sector_size":512,
  "blockdev":"pmem0",
  "numa_node":0
}
```

To destroy a namespace:

```
# ndctl list -N
{
  "dev":"namespace0.0",
  "mode":"fsdax",
  "map":"dev",
  "size":132118478848,
  "uuid":"f45a6725-b80e-4174-a366-b07c91e66a58",
  "raw_uuid":"ee359fec-0a83-436a-91b6-44e3f45b04e0",
  "sector_size":512,
  "blockdev":"pmem0",
  "numa_node":0
}

# ndctl disable-namespace namespace0.0
disabled 1 namespace

# ndctl destroy-namespace namespace0.0
destroyed 1 namespace
```

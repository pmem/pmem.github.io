---
title: Executing "ndctl disable-dimm nmem0" returns "nmem0 is active, skipping.."
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ndctl]
author: Steve Scargall
docid: 100000017
creation_date: 2020-03-12
modified_date: 
---

# Applies To

- Linux
- ndctl utility
- Systems with Persistent Memory

# Issue

When disabling an persistent memory device (nmem), a notice is displayed indicating it is active, eg:

```
# ndctl disable-dimm nmem0
nmem0 is active, skipping...
disabled 0 nmem
```

# Cause

The message indicates there's at least one active/enabled Region and/or Namespace using the NVDIMM.

# Solution

All active/enabled Regions and Namespaces must be destroyed an/or disabled prior to disabling the dimm.

1) List the current configuration (Namespaces, Regions, DIMMs):

```
# ndctl list -NRD
```

2) Verify no fsdax or devdax namespaces are mounted or in-use by running applications:

```
// Check for mounted file systems using the pmem devices
# df -h

// Check for any running processes that are currently accessing the namespaces
# fuser /dev/pmem0
```

3) Destroy or disable the namespace(s)

```
# ndctl destroy-namespace namespace0.0
```

4) Disable the regions used by the persistent memory decvice(s) that needs to be disabled:

```
# ndctl disable-region region0
```

5) Disable the persistent memory device (nmem)

```
# ndctl disable-dimm nmem0
```


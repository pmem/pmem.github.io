---
title: Executing "ndctl sanitize-dimm" returns "security operation not supported"
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ndctl, sanitize, secure erase]
author: Steve Scargall
docid: 100000023
creation_date: 2020-03-11
modified_date:
---
# Applies To

- Linux
- ndctl utility
- Secure Erase/Crypto Erase

# Issue

The 'ndctl sanitize-dimm' command will securely erase the persistent data on one or more persistent memory devices. When executing the command, the following error is returned:

```
# ndctl sanitize-dimm all
  Error: nmem11: security operation not supported
  Error: nmem10: security operation not supported
  [...snip...]
```

# Cause

To use this feature, you need a Linux Kernel v5.0.0 and ndctl version 64 or later. The feature is not implemented in earlier releases which is why the 'security operation not supported' is returned.

Details can be found here:

- [https://docs.pmem.io/ndctl-user-guide/managing-nvdimm-security](https://docs.pmem.io/ndctl-user-guide/managing-nvdimm-security)
- [https://github.com/pmem/ndctl/releases/tag/v64](https://github.com/pmem/ndctl/releases/tag/v64)

# Solution

The secure erase operation can be initiated using the following methods, subject to vendor implementation:

- Certain server OEMs provide a secure erase option in the BIOS
- The ipmctl.efi utility for the UEFI shell may be available from your server OEM
- Use 'ndctl sanitize' on Linux using Kernel 5.0.0 or later with ndctl v64 or later.


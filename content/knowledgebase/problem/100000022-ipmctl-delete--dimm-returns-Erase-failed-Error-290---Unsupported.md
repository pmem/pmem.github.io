---
title: Executing "ipmctl delete -dimm" returns "Erase failed. Error (290) - Unsupported"
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ipmctl, sanitize, secure erase]
author: Steve Scargall
docid: 100000022
creation_date: 2020-03-11
modified_date: 2020-03-11
---

# Applies To

- Linux
- ipmctl utility
- Secure Erase/Crypto Erase

# Issue

The `ipmctl delete -dimm` command will securely erase the persistent data on one or more persistent memory devices. When executing the command, the following error is returned:

```
# ipmctl delete -dimm
Erasing DIMM 0x0001.
Do you want to continue? [y/n] y
Erase failed: Error (290) - Unsupported
```

# Cause

This command is subject to Operating System Vendor (OSV) support. It will return "Not Supported" or "Unsupported" if the security feature is not implemented. 

More details can be found in the ipmctl-erase-device-data(1) man page.

# Solution

The secure erase operation can be initiated using the following methods, subject to vendor implementation:

- Certain server OEMs provide a secure erase option in the BIOS
- The ipmctl.efi utility for the UEFI shell may be available from your server OEM. 
- Use `ndctl sanitize` on Linux using Kernel 5.0.0 or later with ndctl v64 or later.

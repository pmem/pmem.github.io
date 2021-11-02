---
title: Executing "daxctl offline-memory daxX.Y" returns "Failed to offline"
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ndctl, daxctl, system-ram]
author: Steve Scargall
docid: 100000026
creation_date: 2020-04-09
modified_date: 
---
# Applies To

- Linux
- Linux Kernel v5.1 or later
- daxctl utility
- DAX System-RAM Feature

# Issue

When attempting to offline a 'system-ram' device, daxctl returns an error similar to the following:

```
# daxctl offline-memory dax0.0
libdaxctl: offline_one_memblock: dax0.0: Failed to offline /sys/devices/system/node/node2/memory851/state: Device or resource busy
dax0.0: failed to offline memory: Device or resource busy
error offlining memory: Device or resource busy
```

# Cause

One or more processes are using memory from the system-ram device, causing the 'Device or resource busy' error.

# Solution

Use the `fuser` command to confirm if any processes are using the /dev/daxX.Y device. The following example shows many processes are still using the dax device:

```
# fuser -c /dev/dax0.0
/dev/dax0.0:             1   491rc  1576  1598  1914  2232  2234  2262  2267  2271  2272  2276  2277  2278  2281  2282  2290  2298  2308  2351  2369  2370  2371  2671  2674  2692  2695  2697  2698  2707  2981  2982 10018 10023 10025 10030

// Display process and owner
# fuser -cu /dev/dax0.0
/dev/dax0.0:             1(root)   491rc(root)  1576(root)  1598(root)  1914(root)  2232(root)  2234(root)  2262(root)  2267(root)  2271(root)  2272(root)  2276(root)  2277(root)  2278(root)  2281(root)  2282(root)  2290(dbus)  2298(root)  2308(dbus)  2351(polkitd)  2369(root)  2370(root)  2371(root)  2671(root)  2674(root)  2692(root)  2695(tomcat)  2697(root)  2698(root)  2707(root)  2981(dnsmasq)  2982(root) 10018(root) 10023(root) 10025(root) 10030(root)

```

Stop the application processes and try again, or schedule a system reboot.


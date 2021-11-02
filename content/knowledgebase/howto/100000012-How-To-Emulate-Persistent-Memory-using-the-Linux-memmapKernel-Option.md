---
title: How To Emulate Persistent Memory using the Linux "memmap" Kernel Option
description: ''
subtitle: 
layout: doc
categories: [howto]
tags: [linux, kernel, memmap, emulate, persistent memory, pmem]
author: Steve Scargall
docid: 100000012
creation_date: 2019-10-31
modified_date: 
---

### Applies To

This document applies to Linux distributions and versions that support persistent memory.

## Introduction

The Linux `pmem` driver allows application developers to begin developing persistent memory enabled applications using memory mapped files residing on Direct Access Filesystems (DAX) such as EXT4 and XFS.  A `memmap` kernel option was added that supports reserving one or more ranges of unassigned memory for use with emulated persistent memory.   The `memmap` parameter documentation can be found at [https://www.kernel.org/doc/Documentation/admin-guide/kernel-parameters.txt](https://www.kernel.org/doc/Documentation/admin-guide/kernel-parameters.txt).  This feature was available in the Linux Kernel v4.0.  Kernel v4.15 introduced performance improvements and is recommended for production environments.

On systems that do not have any physical persistent memory installed, emulating persistent memory allows for code development and functional testing.  This approach is not recommended for storing critical data, performance testing, or benchmarking since it uses volatile DRAM.  Any data written to the emulated persistent memory will be lost when the host reboots.

The memmap kernel option uses the format of `memmap=nn[KMG]!ss[KMG]` ; where `nn` is the size of the region to reserve, `ss` is the starting offset, and `[KMG]` specifies the size in Kilobytes, Megabytes, or Gigabytes.  The region of memory to be reserved is from ss to ss+nn.  The memmap configuration option is passed to the Kernel using GRUB.   Changing GRUB menu entries and kernel arguments varies between Linux distributions and potentially between versions of the same operating system distribution.  Instructions for some of the common Linux distros can be found below.  Refer to the documentation of the Linux distro and version being used for more information.

### Fedora

Create a new memory mapping of 4GB starting at the 12GB boundary (ie from 12GB to 16GB)
```
$ grubby --args="memmap=4G\!12G" --update-kernel=ALL
```

Reboot the host
```
$ sudo systemctl reboot
```



### Ubuntu

Create a new memory mapping of 4GB starting at the 12GB boundary (ie from 12GB to 16GB)

```
$ sudo vi /etc/default/grub
```

Add or edit the "GRUB_CMDLINE_LINUX" entry to include the mapping, eg:
```
GRUB_CMDLINE_LINUX="memmap=4G!12G"
```

Update grub and reboot
```
$ sudo update-grub2
$ sudo systemctl reboot
```



### RHEL & CentOS

Create a new memory mapping of 4GB starting at the 12GB boundary (ie from 12GB to 16GB)

```
$ sudo vi /etc/default/grubGRUB_CMDLINE_LINUX="memmap=4G!12G"
```

Update the grub config 
```
$ sudo grub2-mkconfig -o /boot/efi/EFI/centos/grub.cfg
```

**Note:** If more than one persistent memory namespace is required, specify a memmap entry for each namespace.  For example, `memmap=2G!12G memmap=2G!14G` will create two 2GB namespaces, one using the 12GB-14GB memory address offset and the second using 14GB-16GB.

After the host has been rebooted, a new `/dev/pmem{N}` device should exist, one for each memmap region specified in the GRUB config.  These can be shown using `ls /dev/pmem*`. Naming convention starts at `/dev/pmem0` and increments for each device.  The `/dev/pmem{N}` devices can be used to create a DAX filesystem.

Create and mount a filesystem using /dev/pmem device(s), then verify the `dax` flag is set for the mount point to confirm the DAX feature is enabled.  The following shows how to create and mount an EXT4 or XFS filesystem.


### EXT4

```
$ sudo mkfs.ext4 /dev/pmem0
$ sudo mkdir /pmem
$ sudo mount -o dax /dev/pmem0 /pmem
$ sudo mount -v | grep /pmem
/dev/pmem0 on /pmem type ext4 (rw,relatime,seclabel,dax,data=ordered)
```

### XFS

```
$ sudo mkfs.xfs /dev/pmem0
$ sudo mkdir /pmem
$ sudo mount -o dax /dev/pmem0 /pmem
$ sudo mount -v | grep /pmem
/dev/pmem0 on /pmem type xfs (rw,relatime,seclabel,attr2,dax,inode64,noquota)
```



## How To Choose the Correct memmap Option for Your System

When selecting values for the `memmap` kernel parameter, consideration that the start and end addresses represent usable RAM must be made. Using or overlapping with reserved memory can result in corruption or undefined behavior. This information is easily available in the e820 table, available via dmesg.

The following shows an example server with 16GiB of memory with "usable" memory between 4GiB (0x100000000) and ~16GiB (0x3ffffffff):

```
$ dmesg | grep BIOS-e820
[    0.000000] BIOS-e820: [mem 0x0000000000000000-0x000000000009fbff] usable
[    0.000000] BIOS-e820: [mem 0x000000000009fc00-0x000000000009ffff] reserved
[    0.000000] BIOS-e820: [mem 0x00000000000f0000-0x00000000000fffff] reserved
[    0.000000] BIOS-e820: [mem 0x0000000000100000-0x00000000bffdffff] usable
[    0.000000] BIOS-e820: [mem 0x00000000bffe0000-0x00000000bfffffff] reserved
[    0.000000] BIOS-e820: [mem 0x00000000feffc000-0x00000000feffffff] reserved
[    0.000000] BIOS-e820: [mem 0x00000000fffc0000-0x00000000ffffffff] reserved
[    0.000000] BIOS-e820: [mem 0x0000000100000000-0x00000003ffffffff] usable
```

To reserve the 12GiB usable space between 4GiB and 16GiB as emulated persistent memory, the syntax for this reservation will be as follows:

```
memmap=12G!4G
```

After rebooting a new user defined e820 table entry shows the range is now "persistent (type 12)":

```
$ dmesg | grep user
[    0.000000] user: [mem 0x0000000000000000-0x000000000009fbff] usable
[    0.000000] user: [mem 0x000000000009fc00-0x000000000009ffff] reserved
[    0.000000] user: [mem 0x00000000000f0000-0x00000000000fffff] reserved
[    0.000000] user: [mem 0x0000000000100000-0x00000000bffdffff] usable
[    0.000000] user: [mem 0x00000000bffe0000-0x00000000bfffffff] reserved
[    0.000000] user: [mem 0x00000000feffc000-0x00000000feffffff] reserved
[    0.000000] user: [mem 0x00000000fffc0000-0x00000000ffffffff] reserved
[    0.000000] user: [mem 0x0000000100000000-0x00000003ffffffff] persistent (type 12)
```

The `fdisk` or `lsblk` utilities can be used to show the capacity, eg:

```
# sudo fdisk -l /dev/pmem0 
Disk /dev/pmem0: 12 GiB,  12884901888 bytes, 25165824 sectors
Units: sectors of 1 * 512 = 512 bytes
Sector size (logical/physical): 512 bytes / 4096 bytes
I/O size (minimum/optimal): 4096 bytes / 4096 bytes
```

```
$ sudo lsblk /dev/pmem0
NAME  MAJ:MIN RM SIZE RO TYPE MOUNTPOINT
pmem0 259:0    0  12G  0 disk /pmem
```

**Note:** Most Linux distributions ship with Kernel Address Space Layout Randomization (KASLR) enabled. This is defined by `CONFIG_RANDOMIZE_BASE`.  When enabled, the Kernel may potentially use memory previously reserved for persistent memory without warning, resulting in corruption or undefined behavior.  It is recommended to disable KASLR on systems with 16GiB or less.  Refer to your Linux distribution documentation for details as the procedure varies per distro.  



## Summary

This article demonstrated how to emulate persistent memory using the Linux "memmap" kernel option on systems that do not have persistent memory physically installed.  This emulation technique allows application developers to begin programming and testing code without needing access to physical persistent memory.
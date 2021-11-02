---
title: Mounting a DAX XFS file system can return "wrong fs type, bad option, bad superblock, missing code page or helper program, or other error"
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, dax, xfs, mkfs, fsck, xfs_repair]
author: Steve Scargall
docid: 100000005
creation_date: 2020-03-12
modified_date: 
---

# Applies To

- Linux Kernel 5.3.x or later
- XFS file system
- xfsprogs5.1 or later
- DAX (Direct Access)
- mount(1) command

# Issue

After creating an XFS file system on an fsdax persistent memory namespace, mounting the file system returns the following error.

```
// Create an fsdax namespace using ndctl
# ndctl create-namespace --region 0 --mode fsdax
{
  "dev":"namespace0.0",
  "mode":"fsdax",
  "map":"dev",
  "size":"1488.37 GiB (1598.13 GB)",
  "uuid":"2ced2361-1c0a-424e-939e-9fa7b5644deb",
  "sector_size":512,
  "align":2097152,
  "blockdev":"pmem0"
}

// Create an XFS file system
# mkfs.xfs /dev/pmem0
meta-data=/dev/pmem0             isize=512    agcount=4, agsize=97542016 blks
         =                       sectsz=4096  attr=2, projid32bit=1
         =                       crc=1        finobt=1, sparse=1, rmapbt=0
         =                       reflink=1
data     =                       bsize=4096   blocks=390168064, imaxpct=5
         =                       sunit=0      swidth=0 blks
naming   =version 2              bsize=4096   ascii-ci=0, ftype=1
log      =internal log           bsize=4096   blocks=190511, version=2
         =                       sectsz=4096  sunit=1 blks, lazy-count=1
realtime =none                   extsz=4096   blocks=0, rtextents=0

// Attempt to mount the file system using the '-o dax' option
# mkdir /pmemfs0
# mount -o dax /dev/pmem0 /pmemfs0
mount: /pmemfs0: wrong fs type, bad option, bad superblock on /dev/pmem0, missing codepage or helper program, or other error.
```

A file system check (fsck/xfs_repair) returns no issues with the superblock or file system metadata:

```
# xfs_repair /dev/pmem0
Phase 1 - find and verify superblock...
Phase 2 - using internal log
        - zero log...
        - scan filesystem freespace and inode maps...
        - found root inode chunk
Phase 3 - for each AG...
        - scan and clear agi unlinked lists...
        - process known inodes and perform inode discovery...
        - agno = 0
        - agno = 1
        - agno = 2
        - agno = 3
        - process newly discovered inodes...
Phase 4 - check for duplicate blocks...
        - setting up duplicate extent list...
        - check for inodes claiming duplicate blocks...
        - agno = 0
        - agno = 1
        - agno = 3
        - agno = 2
Phase 5 - rebuild AG headers and trees...
        - reset superblock...
Phase 6 - check inode connectivity...
        - resetting contents of realtime bitmap and summary inodes
        - traversing filesystem ...
        - traversal finished ...
        - moving disconnected inodes to lost+found ...
Phase 7 - verify and correct link counts...
done
```

Removing the 'dax' option allows the mount to succeed without error

```
# mount /dev/pmem0 /pmemfs0

// Verify the mount was successful
# df -h /pmemfs0
Filesystem      Size  Used Avail Use% Mounted on
/dev/pmem0      1.5T  1.6G  1.5T   1% /pmemfs0
```



# Cause

Starting with xfsprogs version 5.1, the default is to create XFS file systems with the 'reflink' option enabled. Previously it was disabled by default. The reflink and dax options are mutually exclusive which causes the mount to fail. 

A 'DAX and reflink cannot be used together!' error can be seen in dmesg when the mount command fails:

```
# dmesg -T | tail
[758549.018746] pmem0: detected capacity change from 0 to 1598128390144
[758619.823070] XFS (pmem0): DAX enabled. Warning: EXPERIMENTAL, use at your own risk
[758619.823870] XFS (pmem0): DAX and reflink cannot be used together!
```



# Workaround

The EXT4 file system can be used as an alternative because it does not implement the reflink feature but does support DAX.

For XFS, explicitly disable the reflink feature at file system creation time if dax is required:

```
# mkfs.xfs -m reflink=0 -f /dev/pmem0
meta-data=/dev/pmem0             isize=512    agcount=4, agsize=97542016 blks
         =                       sectsz=4096  attr=2, projid32bit=1
         =                       crc=1        finobt=1, sparse=1, rmapbt=0
         =                       reflink=0
data     =                       bsize=4096   blocks=390168064, imaxpct=5
         =                       sunit=0      swidth=0 blks
naming   =version 2              bsize=4096   ascii-ci=0, ftype=1
log      =internal log           bsize=4096   blocks=190511, version=2
         =                       sectsz=4096  sunit=1 blks, lazy-count=1
realtime =none                   extsz=4096   blocks=0, rtextents=0

// Mount the file system with the 'dax' option
# mount -o dax /dev/pmem0 /pmemfs0

// Verify the mount was successful
# df -h /pmemfs0
Filesystem      Size  Used Avail Use% Mounted on
/dev/pmem0      1.5T  1.6G  1.5T   1% /pmemfs0
```



# Solution

See Workaround for details.


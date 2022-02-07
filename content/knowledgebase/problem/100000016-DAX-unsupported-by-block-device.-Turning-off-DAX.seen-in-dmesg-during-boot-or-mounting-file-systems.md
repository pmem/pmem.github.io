---

title: dmesg reports "DAX unsupported by block device. Turning off DAX." during boot or mounting file systems
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, dax, mount]
author: Steve Scargall
docid: 100000016
creation_date: 2020-03-12
modified_date: 
---

# Applies To

- Linux

- XFS or EXT4 File Systems

- Systems with Persistent Memory

  

# Issue

During system boot or mounting of an EXT4 or XFS filesystem, the following message may be seen in dmesg:

```
[125.755367] XFS (pmem0): DAX enabled. Warning: EXPERIMENTAL, use at your own risk[125.763736] XFS (pmem0): DAX unsupported by block device. Turning off DAX.
```

The filesystem should still mount, but it will not have the 'dax' (Direct Access) flag set as shown by the mount command:

```
$ mount | grep pmem0/dev/pmem0 on /mnt/pmem0 type xfs (rw,relatime,attr2,inode64,noquota)
```



# Solution

Check the namespace mode using `ndctl list -Nu`. In the following example the mode is 'raw':

```
# ndctl list -Nu{
"namespaces":[
    {
       "dev":"namespace0.0",
       "mode":"raw",
       "size":"704.00 MiB (738.20 MB)",
       "blockdev":"pmem0",
       "numa_node":0
    }
  ]
}
```

From the `ndctl-create-namespace` man page, 'raw' mode does not support DAX (Direct Access).

```
       -m, --mode=
           -   "raw": expose the namespace capacity directly with limitations. Neither a raw pmem namespace nor raw blk
           namespace support sector atomicity by default (see "sector" mode below). A raw pmem namespace may have limited
           to no dax support depending the kernel. In other words operations like direct-I/O targeting a dax buffer may
           fail for a pmem namespace in raw mode or indirect through a page-cache buffer. See "fsdax" and "devdax" mode
           for dax operation.
```

The namespace needs to be changed to either 'fsdax' or 'devdax' using the following:

**WARNING:** Changing the namespace mode will destroy any existing data. Backup all data before changing the mode.

```
$ sudo ndctl create-namespace -f -e namespace0.0 --mode=fsdax
{  
	"dev":"namespace0.0",
    "mode":"memory",
    "size":"690.00 MiB (723.52 MB)",
    "uuid":"d1cc8945-7bf7-4b57-83b5-0a79565e9c15",
    "blockdev":"pmem0",
    "numa_node":0
}
```

The filesystem needs to be re-created, mounted with the 'dax' option, then any data restored:

```
$ sudo mkfs.xfs /dev/pmem0$ sudo mount -o dax /dev/pmem0 /mnt/pmem0
$ mount | grep pmem0/dev/pmem0 on /mnt/pmem0 type xfs (rw,relatime,attr2,dax,inode64,noquota)
```

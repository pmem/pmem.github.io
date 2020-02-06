---
title: Using Persistent Memory Devices with the Linux Device Mapper
author: sscargal
layout: post
identifier: linux-pmem-dev-mapper
---


<br/>
#### Introduction

X86/X64 systems do not typically interleave Persistent Memory Devices (also referred to as 'modules' or 'DIMMs') across sockets, so a two-socket system will have two separate interleave sets.  To use these interleave sets as a single device requires using a software device mapper or volume manager.  

This article focuses on using the 'striped' (**dm-stripe**) and 'linear' (**dm-linear**) target drivers with persistent memory devices to create virtual devices on which direct access (DAX) enabled filesystems can be created.  Both XFS and EXT4 have native DAX support.

The Linux device mapper is a framework provided by the kernel for mapping physical block devices onto higher-level virtual block devices.  The device mapper works by passing data from a virtual block device, which is provided by the device mapper itself, to another block device.  Several mapping targets exist - cache, crypt, delay, era, error, flakey, linear, mirror, multipath, raid, snapshot, striped, thin, and zero.  

Support for persistent memory devices and emulation of devices is present in Kernel v4.0 or later.  Kernel v4.2 or newer has the feature is enabled by default.  Kernel v4.15 or newer is recommended for production as it has performance improvements.

DAX support is a feature of the individual device-mapper target driver.  Not all target drivers have or require DAX support.  Both the 'linear' and 'stripe' target drivers have the DAX feature.  The downside of these configurations is when a single device fails, access to the data also fails.  Applications will need to be designed to handle all failure conditions.  On the plus side, creating virtual devices allows for more flexible configuration options.  DAX has not been added to the 'raid' module due to metadata overhead, IO to page alignment requirements, and performance reasons.  Data can be protected and replicated using 'replication pool sets' which you can read more about in '[An Introduction to Replication](https://pmem.io/2015/11/23/replication-intro.html)'.

The `dmsetup` utility is a low-level tool used to create and manage devices.  Linux Volume Manager (LVM) commands also allow the creation of logical volumes using all DAX capable devices, such as pmem.  The logical volume inherits DAX features if created using DAX devices.  Once a logical volume is set to DAX capable, the volume may not be extended with non-DAX capable devices.

The rest of this article assumes either physical or emulated persistent memory devices exist and are accessible via /dev/pmem{N}.  Refer to [How To Emulate Persistent Memory](https://pmem.io/2016/02/22/pm-emulation.html) for instructions.


<br/>
#### IO Alignment Considerations

Traditional storage devices such as Hard Disk Drives, SSD's, NVMe, and SAN LUNs present storage as blocks.  A block is an addressable unit of storage measured in bytes.  The traditional block size used by hard disks is 512 bytes.  Newer devices commonly use 4KiB or 8KiB physical block sizes, but may also choose to present logical/emulated 512 bytes blocks.

Persistent Memory devices are accessible via the Virtual Memory System.  Therefore, IO should be aligned using the systems Page Size(s).   The Memory Management Unit (MMU) located on the CPU determines what page sizes are possible.  

Linux supports two page sizes:

- Default Page Size; is commonly 4KiB by default on all architectures.  Linux often refers to these as a Page Table Entry (PTE). 
- [Huge Pages](https://www.kernel.org/doc/Documentation/vm/hugetlbpage.txt); requires Kernel support having configured `CONFIG_HUBETLB_PAGE` and `CONFIG_HUGETLBFS`.  Often referred to as the 'Page Middle Directory (PMD)', huge pages are commonly 2MiB in size.

More information can be found in "[Chapter 3  Page Table Management](https://www.kernel.org/doc/gorman/html/understand/understand006.html)" of Mel Gorman's book "[Understanding the Linux Virtual Memory Manager](https://www.kernel.org/doc/gorman/html/understand/)".

The page size is a compromise between memory usage and speed.

- A larger page size means more waste when a page is partially used.
- A smaller page size with a large memory capacity means more kernel memory for the page tables since there's a large number of page table entries.
- A smaller page size could require more time spent in page table traversal, particularly if there's a high Translation Lookaside Buffer (TLB) miss count.

The capacity difference between DDR and Persistent Memory Modules is considerable.  Using smaller pages on a system with terabytes of memory could negatively impact performance for the reasons described above.

The systems default page size can be found by querying its configuration using the `getconf`command: 

```
$ getconf PAGE_SIZE
4096
```

or

```
$ getconf PAGESIZE
4096
```

**NOTE:** The above units are bytes. 4096 bytes == 4 Kilobytes (4 KiB)

To verify the system currently has HugePage support, `cat /proc/meminfo|grep -i hugepage` will return information similar to the following:

```
.....
HugePages_Total: uuu
HugePages_Free:  vvv
HugePages_Rsvd:  www
HugePages_Surp:  xxx
Hugepagesize:    yyy kB
Hugetlb:         zzz kB
.....
```

Depending on the processor, there are at least two different huge page sizes on the x86_64 architecture: 2MiB and 1GiB. If the CPU supports 2MiB pages, it has the `PSE` cpuinfo flag, for 1GiB it has the `PDPE1GB` flag. `/proc/cpuinfo` shows whether the two flags are set.

If this commands returns a non-empty string, 2MiB pages are supported.

```
$ grep pse /proc/cpuinfo | uniq
flags           : [...] pse [...]
```

If this commands returns a non-empty string, 1GiB pages are supported.

```
$ grep pdpe1gb /proc/cpuinfo | uniq
flags           : [...] pdpe1gb [...]
```


<br/>
#### Verifying IO Alignment

For a DAX filesystem to be able to use 2 MiB hugepages several things have to happen:

- The mmap() mapping has to be at least 2 MiB in size.
- The filesystem block allocation has to be at least 2 MiB in size.
- The filesystem block allocation has to have the same alignment as our mmap().

The first requirement is trivial to control since the size of the mapping relates to the size of the persistent memory pool file(s).  Both EXT4 and XFS each have support for requesting specific filesystem block allocation alignment and size.  This feature was introduced in support of RAID, but can be used equally well for DAX filesystems.  Finally, controlling the starting alignment is achieved by ensuring the start of the filesystem is 2MB aligned.  

The procedure to ensure DAX filesystems use PMDs is shown below as an example.  It needs to be executed once the dm-linear or dm-stripe has been configured.

1. Verify the namespace is in 'fsdax' mode. 

```
$ ndctl list -u
[
  {
    "dev":"namespace1.0",
    "mode":"fsdax",
    "size":"3.93 GiB (4.22 GB)",
    "uuid":"9b8d6eeb-547c-4865-8213-746c6b20bc9c",
    "raw_uuid":"e84e23f4-cab8-4afe-9fbf-6176e37095b1",
    "sector_size":512,
    "blockdev":"pmem1",
    "numa_node":0
  },
  {
    "dev":"namespace0.0",
    "mode":"fsdax",
    "size":"3.93 GiB (4.22 GB)",
    "uuid":"76a114c5-b7c0-48f7-8fe8-d09702d8b1b1",
    "raw_uuid":"2ef21ac8-e22d-4b8e-8cf6-fc0fcbf6258a",
    "sector_size":512,
    "blockdev":"pmem0",
    "numa_node":0
  }
]
```

If the namespace is not in 'fsdax' mode, use the following to switch modes.

```
$ sudo ndctl create-namespace -f -e namespace0.0 --mode=fsdax
$ sudo ndctl create-namespace -f -e namespace1.0 --mode=fsdax
```

**Note**: This will destroy all data within the namespace so backup any existing data before switching modes.



2. Verify the persistent memory block device starts at a 2 MiB aligned physical address.

This is important because when we ask the filesystem for 2 MiB aligned and sized block allocations it will provide those block allocations relative to the beginning of its block device. If the filesystem is built on top of a namespace whose data starts at a 1 MiB aligned offset, for example, a block allocation that is 2 MiB aligned from the point of view of the filesystem will still be only 1 MiB aligned from DAX's point of view. This will cause DAX to fall back to 4 KiB page faults.

Use `/proc/iomem` to verify the starting address of the namespace, eg:

```
$ cat /proc/iomem
...
140000000-23fdfffff : Persistent Memory
  140000000-23fdfffff : namespace0.0
23fe00000-33fbfffff : Persistent Memory
  23fe00000-33fbfffff : namespace1.0
```

Both namespaces are 2MiB (0x200000) aligned since namespace0.0 starts at 0x140000000 (5GiB) and namespace1.0 starts at 0x23fe00000 (~9GiB)

When creating filesystems using the namespaces, it's important to maintain the 2MiB alignment (4096 sectors).  Depending upon the VTOC type, fdisk creates 1MiB alignment (2048 sectors).  For a non-device mapped /dev/pmem0 a partition aligned at the 2MiB boundary can be created using the following: 

```
$ fdisk /dev/pmem0

Welcome to fdisk (util-linux 2.32).
Changes will remain in memory only, until you decide to write them.
Be careful before using the write command.

Device does not contain a recognized partition table.

Command (m for help): g
Created a new GPT disklabel (GUID: 10B97DA8-F537-6748-9E6F-ED66BBF7A047).

Command (m for help): n
Partition number (1-128, default 1):
First sector (2048-8249310, default 2048): 4096
Last sector, +sectors or +size{K,M,G,T,P} (4096-8249310, default 8249310):

Created a new partition 1 of type 'Linux filesystem' and of size 4 GiB.

Command (m for help): w
The partition table has been altered.
Calling ioctl() to re-read partition table.
Syncing disks.


$ fdisk -l /dev/pmem0
Disk /dev/pmem0: 4 GiB, 4223664128 bytes, 8249344 sectors
Units: sectors of 1 * 512 = 512 bytes
Sector size (logical/physical): 512 bytes / 4096 bytes
I/O size (minimum/optimal): 4096 bytes / 4096 bytes
Disklabel type: gpt
Disk identifier: 10B97DA8-F537-6748-9E6F-ED66BBF7A047

Device       Start     End Sectors Size Type
/dev/pmem0p1  4096 8249310 8245215   4G Linux filesystem
```



3. Create an XFS or EXT4 filesystem.  The commands below show how this can be achieved.  See the `mkfs.xfs` and `mkfs.ext4` man pages for more information.

EXT4:

```
$ mkfs.ext4 -b 4096 -E stride=512 -F /dev/pmem0
$ mount /dev/pmem0 /mnt/dax
```

XFS:

```
$ mkfs.xfs -f -d su=2m,sw=1 /dev/pmem0
$ mount /dev/pmem0 /mnt/dax
$ xfs_io -c "extsize 2m" /mnt/dax
```



4. [Optional] Watch IO allocations.  Without enabling filesystem debug options, it is possible to confirm the filesystem is allocating in 2MiB blocks using FTrace:

```
$ cd /sys/kernel/debug/tracing
$ echo 1 > events/fs_dax/dax_pmd_fault_done/enable 
```

Run test which faults in filesystem DAX mappings, eg:

```
$ fallocate --length 1G /mnt/dax/data
```

Look for **dax_pmd_fault_done** events in `/sys/kernel/debug/tracing/trace` to see if the allocations were successful.  An event that successfully faulted in a filesystem DAX PMD looks like this:

```
big-1434  [008] ....  1502.341229: dax_pmd_fault_done: dev 259:0 ino 0xc shared 
WRITE|ALLOW_RETRY|KILLABLE|USER address 0x10505000 vm_start 0x10200000 vm_end 
0x10600000 pgoff 0x305 max_pgoff 0x1400 NOPAGE
```

If the entry ends in **NOPAGE**, this means the fault succeeded and didn't return a page cache page, which is expected for DAX.  A 2 MiB fault that failed and fell back to 4 KiB DAX faults will instead look like this:

```
small-1431  [008] ....  1499.402672: dax_pmd_fault_done: dev 259:0 ino 0xc shared
WRITE|ALLOW_RETRY|KILLABLE|USER address 0x10420000 vm_start 0x10200000 vm_end
0x10500000 pgoff 0x220 max_pgoff 0x3ffff FALLBACK
```

You can see that this fault resulted in a fallback to 4 KiB faults via the **FALLBACK** return code at the end of the line. The rest of the data in this line can help you determine why the fallback happened.  In this example an intentional mmap() smaller than 2 MiB was created.  vm_end (0x10500000) - vm_start (0x10420000) == 0xE0000 (896 KiB).

To disable tracing run `echo 0 > events/fs_dax/dax_pmd_fault_done/enable `.


<br/>
#### Creating dm-linear Devices

See [Documentation/device-mapper/linear.txt](https://www.kernel.org/doc/Documentation/device-mapper/linear.txt) for parameters and usage.  

Device-Mapper's "linear" target maps a linear range of the Device-Mapper device onto a linear range of another device.  For example, if two 512GiB devices are linearly mapped, the resulting virtual virtual device is 1TiB.

**Note:** If the physical devices have already been configured within interleaved sets, dm-stripe devices could potentially stripe across Non-Uniform Memory Architecture Nodes (NUMA Nodes).

For this example, two `pmem` devices will be used to create a larger mapped device

![Figure 1](/assets/device-mapper_fig1.png)

Identify the /dev/pmem* devices to use

```
$ lsblk /dev/pmem*
NAME  MAJ:MIN RM SIZE RO TYPE MOUNTPOINT
pmem0 259:0    0   4G  0 disk
pmem1 259:1    0   4G  0 disk
```

The following creates `'linear-pmem` devices by concatenating `/dev/pmem0` and `/dev/pmem1`

```
$ echo -e "0 `blockdev --getsz /dev/pmem0` linear /dev/pmem0 0 "\\n"`blockdev --getsz /dev/pmem0` `blockdev --getsz /dev/pmem1` linear /dev/pmem1 0" | sudo dmsetup create linear-pmem
```

This results in the following

```
$ dmsetup ls --tree
linear-pmem (253:2)
 ├─ (259:1)
 └─ (259:0)

$ lsblk /dev/pmem*
NAME          MAJ:MIN RM SIZE RO TYPE MOUNTPOINT
pmem0         259:0    0   4G  0 disk
└─linear-pmem 253:2    0   8G  0 dm
pmem1         259:1    0   4G  0 disk
└─linear-pmem 253:2    0   8G  0 dm

$ lsblk /dev/mapper/linear-pmem
NAME        MAJ:MIN RM SIZE RO TYPE MOUNTPOINT
linear-pmem 253:2    0   8G  0 dm
```

Create a partition aligned with a 2MiB boundary, if 2MiB alignment is required.

```
$ fdisk /dev/mapper/linear-pmem

Welcome to fdisk (util-linux 2.32).
Changes will remain in memory only, until you decide to write them.
Be careful before using the write command.


Command (m for help): g
Created a new GPT disklabel (GUID: C0589C46-0330-7349-941B-905B72BD21A5).

Command (m for help): n
Partition number (1-128, default 1):
First sector (2048-16498654, default 2048): 4096
Last sector, +sectors or +size{K,M,G,T,P} (4096-16498654, default 16498654):

Created a new partition 1 of type 'Linux filesystem' and of size 7.9 GiB.

Command (m for help): w
The partition table has been altered.
Syncing disks.

```

A DAX filesystem can now be created using the `/dev/mapper/linear-pmem` device

``` $ sudo mkfs.ext4 -b 4096 -E stride=512 -F /dev/mapper/linear-pmem``` 

Mount the filesystem using the `-o dax` flag

```
$ sudo mkdir /pmem
$ mount -o dax /dev/mapper/linear-pmem /pmem
$ df -h /pmem
Filesystem               Size  Used Avail Use% Mounted on
/dev/mapper/linear-pmem  7.7G   36M  7.3G   1% /pmem
```


<br/>
#### Creating dm-striped Devices

See [Documentation/device-mapper/striped.txt](https://www.kernel.org/doc/Documentation/device-mapper/striped.txt) for parameters and usage.

Device-Mapper's "striped" target is used to create a striped (i.e. RAID-0) device across one or more underlying devices. Data is written in "chunks", with consecutive chunks rotating among the underlying devices. The "chunk" size should match the page size discussed in the "[IO Alignment Considerations](#io-alignment-considerations)" section above.  This can potentially provide improved I/O throughput by utilizing several physical devices in parallel.

**Note:** If the physical devices have already been configured within interleaved sets, dm-stripe devices could potentially stripe across Non-Uniform Memory Architecture Nodes (NUMA Nodes).

If the HugePage size (2 MiB) is used as the 'chunk size', it'll end up using PMDs for optimal efficiency and performance.  Unlike dm-raid*, dm-striped doesn't have an option for a separate metadata device, so the alignment will always work out.

![Figure 2](/assets/device-mapper_fig1.png)

Identify the /dev/pmem* devices to use

```
$ lsblk /dev/pmem*
NAME  MAJ:MIN RM SIZE RO TYPE MOUNTPOINT
pmem0 259:0    0   4G  0 disk
pmem1 259:1    0   4G  0 disk
```

The following creates `'striped-pmem` devices by striping `/dev/pmem0` and `/dev/pmem1` using a 2MiB chunk size, specified as multiples of 512b blocks (4096 x 512 byte == 2MiB).

```
$ echo -e "0 $(( `blockdev --getsz /dev/pmem0` + `blockdev --getsz /dev/pmem0` )) striped 2 4096 /dev/pmem0 0 /dev/pmem1 0" | sudo dmsetup create striped-pmem
```

This results in the following

```
$ dmsetup ls --tree
striped-pmem (253:2)
 ├─ (259:1)
 └─ (259:0)

$ lsblk /dev/pmem*
NAME           MAJ:MIN RM SIZE RO TYPE MOUNTPOINT
pmem0          259:0    0   4G  0 disk
└─striped-pmem 253:2    0   8G  0 dm
pmem1          259:1    0   4G  0 disk
└─striped-pmem 253:2    0   8G  0 dm

$ lsblk /dev/mapper/striped-pmem
NAME         MAJ:MIN RM SIZE RO TYPE MOUNTPOINT
striped-pmem 253:2    0   8G  0 dm
```

Create a partition aligned with a 2MiB boundary, if 2MiB alignment is required.

```
$ fdisk /dev/mapper/striped-pmem

Welcome to fdisk (util-linux 2.32).
Changes will remain in memory only, until you decide to write them.
Be careful before using the write command.

Device does not contain a recognized partition table.
Created a new DOS disklabel with disk identifier 0x071faf01.

Command (m for help): g
Created a new GPT disklabel (GUID: 19899535-EA45-8B4D-BC9D-6A5C922C8595).
The old ext4 signature will be removed by a write command.

Command (m for help): n
Partition number (1-128, default 1):
First sector (8192-16498654, default 8192):
Last sector, +sectors or +size{K,M,G,T,P} (8192-16498654, default 16498654):

Created a new partition 1 of type 'Linux filesystem' and of size 7.9 GiB.

Command (m for help): w
The partition table has been altered.
Failed to add partition 1 to system: Invalid argument

The kernel still uses the old partitions. The new table will be used at the next reboot.
Syncing disks.

```

A DAX filesystem can now be created using the `/dev/mapper/striped-pmem` device

``` $ sudo mkfs.ext4 -b 4096 -E stride=512 -F /dev/mapper/striped-pmem```

Mount the filesystem using the `-o dax` flag

```
$ sudo mkdir /pmem
$ mount -o dax /dev/mapper/striped-pmem /pmem
$ # df -h /pmem
Filesystem                Size  Used Avail Use% Mounted on
/dev/mapper/striped-pmem  7.9G   36M  7.4G   1% /pmem
```


<br/>
#### Using Other dm-* Devices

In the introduction, we stated the 'raid' and other modules does not implement DAX.  `dmsetup` does not validate or prevent creating RAID device mappings using persistent memory devices.  However when attempting to mount the resulting virtual device using the `-o dax` option, a warning is recorded to dmesg and the DAX feature is disabled.  Therefore it it not recommended to use the 'raid' device-mapper module with persistent memory devices if the applications need DAX support.  

The following example shows the expected warning if a non-DAX enabled device mapper module is used to mount a filesystem with the `-o dax` flag:

Create a raid mapping

```
$ dmsetup ...
```

Mount the new device using the '-o dax' flag:

```
$ mount -o dax /dev/mapper/raid-pmem /pmem
```

A filesystem with DAX disabled will not have the `dax` flag listed using `mount -v`, eg:

```
$ mount -v | grep /pmem
/dev/pmem0 on /pmem type ext4 (rw,relatime,seclabel)
```

Additionally, dmesg may also report a "DAX unsupported by block device. Turning off DAX" warning, eg:

```
$ tail -f /var/log/mesages | grep EXT4-fs
EXT4-fs (raid-pmem): mounted filesystem with ordered data mode. Opts: (null)
EXT4-fs (raid-pmem): DAX enabled. Warning: EXPERIMENTAL, use at your own risk
EXT4-fs (raid-pmem): DAX unsupported by block device. Turning off DAX.
EXT4-fs (raid-pmem): mounted filesystem with ordered data mode. Opts: dax
```


<br/>
#### Persistent Configuration Across System Reboots

In both the dm-linear and dm-stripe examples above, the configuration will not persist across system reboots because neither solution has any metadata to save.  A script executed at boot time is required to reinstate the configuration each time.  The UUID of the persistent memory namespaces will not change, but the `/dev/pmem{N}` could.  The `create-pmem-dev-links-by-uuid` script provided below uses the `ndctl`  utility to gather the uuid for each pmem device and creates symbolic links from /dev/disk/by-uuid to the appropriate `/dev/pmem{N}`.  The `dmsetup` command uses the `/dev/disk/by-uuid/{uuid}` convention rather than `/dev/pmem{N}`.  Using UUIDs guarantees the correct device(s) are used in the correct order to avoid data corruption.  

The `create-pmem-dev-links-by-uuid` service maintains the device links and the `pmem-dev-mapper` service creates the devices and mounts the filesystem.

```
--- create-pmem-dev-links-by-uuid ---
#!/bin/bash

# Usage:
# ./create-pmem-dev-links-by-uuid
#
# This script is expected to be executed from systemd to extract
# the UUID and Device Name (blockdev) from the persistent memory namespaces.
# It then creates symbolic links from /dev/disk/by-uuid/<uuid> to the respective
# /dev/pmem{N} device.
#
# Using the UUID's, device-mapper can then use the correct devices each time.
# This is required because the /dev/pmem{N} may change at boot time without
# warning.  Currently the persistent memory namespace uuid is not presented
# through the sysfs and udev drivers so do not appear under /dev/disk/by-uuid/.
#

# Global Variables
NDCTLCMD=ndctl
JQCMD=jq

# Try to locate the ndctl(1) utility within the users $PATH
if [ ! -x "$(command -v ${NDCTLCMD})" ]; then
    cat << EOF
        Error: ndctl is not installed.
        Please install from your Operating Systems repository
          or download it from https://github.com/pmem/ndctl.

        $ sudo dnf install ndctl

        If the utility is installed, it cannot be found in $PATH.
        Please update your PATH environment.
EOF
    exit 1
fi

# Try to locate the jq(1) utility within the users $PATH
if [ ! -x "$(command -v ${JQCMD})" ]; then
    cat << EOF
        Error: jq is not installed.
        Please install jq from your Operating Systems repository.

        $ sudo dnf install jq

        If the utility is installed, it cannot be found in $PATH.
        Please update your PATH environment.
EOF
    exit 1
fi

####################
# Create Sym Links #
####################
# Process the output from 'ndctl list -N' using jq to extract the "uuid" 
# and "blockdev" elements. "uuid" is used as the key and "blockdev"
# is the value

declare -A uuid_dev_lst=()
while read -r uuid dev
do
    uuid_dev_lst["$uuid"]="$dev"
done < <(${NDCTLCMD} list -N | ${JQCMD} -r '.[] | "\(.uuid) \(.blockdev)"')

# Walk the list, remove any old sym links, then create new sym links
if [ ${#array[@]} -ge 0 ]
then
    for uuid in "${!uuid_dev_lst[@]}"
    do
        rm -f /dev/disk/by-uuid/${uuid} > /dev/null 2>&1
        ln -s ../../${uuid_dev_lst[$uuid]} /dev/disk/by-uuid/${uuid}
    done
else
    echo "No valid fsdax namespaces found!"
fi
--- end ---
```
<br/>
```
--- pmem-dev-mapper ---
#!/bin/bash

# Usage:
#  ./pmem-dev-mapper
#
# This script is expected to be executed from systemd to create and
# mount device-mapped devices.
#

#################
# Device-Mapper #
#################

# The following creates a striped-pmem device using two devices:
# pmem0(af66dc0f-e3ac-4fbe-a854-438674eec3c0)
# pmem1(cccd2ba4-0f4d-4ff5-9415-c8364d5e2a98)

echo -e "0 $(( `blockdev --getsz /dev/disk/by-uuid/af66dc0f-e3ac-4fbe-a854-438674eec3c0` + `blockdev --getsz /dev/disk/by-uuid/af66dc0f-e3ac-4fbe-a854-438674eec3c0` )) striped 2 4096 /dev/disk/by-uuid/af66dc0f-e3ac-4fbe-a854-438674eec3c0 0 /dev/disk/by-uuid/cccd2ba4-0f4d-4ff5-9415-c8364d5e2a98 0" | sudo dmsetup create striped-pmem

#####################
# Mount Filesystems #
#####################

# The following mounts the 'striped-pmem' device to /pmem

mount -o dax /dev/mapper/striped-pmem /pmem
--- end ---
```

**Note:** The above has been tested on Fedora 27 and Fedora 28.

<br/>

##### Creating Custom systemd Services

Use the following procedure to create a custom systemd services to execute the `create-pmem-dev-links-by-uuid` and `pmem-dev-mapper` scripts at boot time.  Refer to the Fedora '[Understanding and administering systemd](https://docs.fedoraproject.org/quick-docs/en-US/understanding-and-administering-systemd.html)' Documentation for full details.

1. Create an `/opt/pmem` directory, then save the `create-pmem-dev-links-by-uuid` and `pmem-dev-mapper` scripts to `/opt/pmem/`.

   ```
   # mkdir -p /opt/pmem
   ```

2. Make the scripts executable

   ```
   # chmod +x /opt/pmem/*
   ```

3. Create and edit the new `pmem-uuid-dev-links.service` systemd service configuration file: 

   ```
   # vi /etc/systemd/system/pmem-uuid-dev-links.service
   ```

4. Insert the following:

   ```
   [Unit]
   Description=Create Persistent Memory UUID Device Links
   
   [Service]
   Type=simple
   ExecStart=/opt/pmem/create-pmem-dev-links-by-uuid
   
   [Install]
   WantedBy=multi-user.target
   ```

5. Create and edit the new `pmem-dev-mapper` systemd service configuration file:

   ```
   # vi /etc/systemd/system/pmem-dev-mapper.service
   ```

6. Insert the following:

   ```
   [Unit]
   Description=Create Persistent Memory Device Mapper Devices and the Mount Filesystems
   Requires=pmem-uuid-dev-links
   
   [Service]
   Type=simple
   ExecStart=/opt/pmem/pmem-dev-mapper
   
   [Install]
   WantedBy=multi-user.target
   ```

7. Add execute permissions to the service files

   ```
   # chmod +x /etc/systemd/system/pmem-uuid-dev-links.service \
   /etc/systemd/system/pmem-dev-mapper.service
   ```

8. Start and Enable the services

   ```
   # systemctl start pmem-dev-mapper
   # systemctl enable pmem-uuid-dev-links pmem-dev-mapper
   ```

9. Check the status of the service to ensure the service is running: 

   ```
   $ systemctl status pmem-uuid-dev-links pmem-dev-mapper
   ● pmem-uuid-dev-links.service - Create Persistent Memory UUID Device Links
      Loaded: loaded (/etc/systemd/system/pmem-uuid-dev-links.service; enabled; vendor preset: disabled)
      Active: inactive (dead)
   
   ● pmem-dev-mapper.service - Create Persistent Memory Device Mapper Devices and the Mount Filesystems
      Loaded: loaded (/etc/systemd/system/pmem-dev-mapper.service; enabled; vendor preset: disabled)
      Active: inactive (dead) since Tue 2018-06-05 17:07:43 MDT; 4s ago
     Process: 6294 ExecStart=/opt/pmem/pmem-dev-mapper (code=exited, status=0/SUCCESS)
    Main PID: 6294 (code=exited, status=0/SUCCESS)
   
   ```

   

<br/>

#### Summary

This article has shown how to use the Linux Device Mapper with Persistent Memory Devices (Modules) to create more complex configurations suitable for application requirements.  It describes and demonstrates how to use 2MiB HugePages to improve IO performance with large amounts of persistent memory.


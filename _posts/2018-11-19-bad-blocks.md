---
title: Bad blocks
author: ldorau
layout: post
identifier: bad_blocks
---

### Introduction

Storage devices can contain uncorrectable media errors often called
"bad blocks". A bad block is a part of a storage media that is either
inaccessible or unwritable due to a permanent physical damage.
In case of memory mapped I/O, if a process tries to access (read or write)
the corrupted block, it will be terminated by the SIGBUS signal.

### Handling bad blocks in PMDK libraries

PMDK libraries can handle bad blocks if the `CHECK_BAD_BLOCKS`
compat feature is turned on. Currently (PMDK v1.5) it is disabled by default
because it requires super user privileges. It can be turned on using
[pmempool-feature](#pmempool-feature).

If the `CHECK_BAD_BLOCKS` compat feature is turned on, several features
are available:
* pool is checked if it contains bad blocks during opening and during creating
  (when the pool is created using an already existing zeroed file(s)),
* `pmempool-info` prints out information about bad blocks in the pool
* `pmempool-check` checks if the pool contains bad blocks
* `pmempool-sync` tries to fix bad blocks in libpmemobj pool using its replicas

### Pmempool-feature

Using `pmempool-feature` one can enable or disable the `CHECK_BAD_BLOCKS`
compat feature:

```
	$ pmempool feature --enable CHECK_BAD_BLOCKS
	$ pmempool feature --disable CHECK_BAD_BLOCKS
```

The `CHECK_BAD_BLOCKS` compat feature enables checking and fixing bad blocks.
Currently (Linux kernel v4.19, libndctl v62) these operations require
read access to the following resource files (containing physical addresses)
of NVDIMM devices which only the super user can read by default:
```
	/sys/bus/nd/devices/ndbus*/region*/resource
	/sys/bus/nd/devices/ndbus*/region*/dax*/resource
	/sys/bus/nd/devices/ndbus*/region*/pfn*/resource
	/sys/bus/nd/devices/ndbus*/region*/namespace*/resource
```


### Opening and creating a pool

If the `CHECK_BAD_BLOCKS` compat feature is enabled, every pool is checked
if it contains bad blocks during opening and during creating
(when the pool is created using an already existing zeroed file(s)).
If it does then opening/creating fails.

If the `CHECK_BAD_BLOCKS` compat feature is enabled and the user does not have
enough permissions (see [pmempool-feature](#pmempool-feature)) to be able
to check if the pool contains bad blocks then opening/creating fails either.


### Pmempool-info

If the `CHECK_BAD_BLOCKS` compat feature is enabled or ```--bad-blocks=yes```
option is used then `pmempool-info` prints out information about bad blocks
in the pool, for example:
```
$ pmempool info --bad-blocks=yes ./poolset.file
Poolset structure:
Number of replicas       : 1
Replica 0 (master) - local, 1 part(s):
part 0:
path                     : /dev/dax1.0
type                     : device dax
size                     : 62922752
alignment                : 4096
bad blocks:
        offset          length
        11              1
[...]
```


### Pmempool-check

If the `CHECK_BAD_BLOCKS` compat feature is enabled, `pmempool-check` checks
if the pool contains bad blocks, for example:
```
$ pmempool check ./poolset.file
poolset contains bad blocks, use 'pmempool info --bad-blocks=yes' to print or 'pmempool sync --bad-blocks' to clear them
./poolset.file: cannot repair
```


### Pmempool-sync

Attention: this feature is available **only for libpmemobj** pools.

If the `CHECK_BAD_BLOCKS` compat feature is enabled or ```--bad-blocks```
option is used then `pmempool-sync` tries to fix bad blocks in the libpmemobj
pool using its replicas:

```
$ pmempool sync --bad-blocks ./poolset.file
./poolset.file: synchronized
```

Synchronization can fail if a part of the pool has uncorrectable errors
in all replicas:

```
$ pmempool sync --bad-blocks ./poolset.file
error: failed to synchronize: a part of the pool has uncorrectable errors in all replicas
error: Invalid argument
```

Fixing bad blocks causes creating or reading special recovery files.
When bad blocks are detected, special recovery files have to be created
in order to fix them safely. A separate recovery file is created per each part
of the pool. The recovery files are created in the same directory
as the poolset file, using the following name pattern:

```<poolset-file-name>_r<replica-number>_p<part-number>_badblocks.txt```

for example:

```poolset-file-name_r0_p0_badblocks.txt```

for part #0 of replica #0. These recovery files are automatically removed
if the sync operation finishes successfully.

If the last sync operation was interrupted and not finished correctly
(eg. the application crashed) and the bad blocks fixing procedure was
in progress, the bad block recovery files may be left over. In such case
bad blocks might have been cleared and zeroed, but the correct data from these
blocks was not recovered (not copied from a healthy replica), so the recovery
files **MUST NOT** be deleted manually, because it would cause a data loss.
Pmempool-sync should be run again with the ```--bad-blocks``` option.
It will finish the previously interrupted sync operation and copy correct data
to zeroed bad blocks using the left-over bad block recovery files
(the bad blocks will be read from the saved recovery files). Pmempool will
delete the recovery files automatically at the end of the sync operation.

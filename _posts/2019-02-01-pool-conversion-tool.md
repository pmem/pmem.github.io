---
title: Pool conversion tool
author: lplewa
layout: post
identifier: pmdk-convert
---

## Introduction

When we published the first PMDK stable release, we committed to maintaining
stable on-media layout. This means that all future PMDK
changes have to be backward compatible. Unfortunately, we weren't successful
in adhering to the strict requirements which would be needed to maintain
compatibility, mostly because we made changes whose benefit far outweighed
the costs. For this reason, we created the `pmempool convert` command. This tool was used to convert
pools which were created with old PMDK versions to the newer on-media layout.
In 1.5 release this functionality was refactored and moved to a separate tool
called [**pmdk-convert**](https://pmem.io/pmdk-convert/manpages/v1.5/pmdk-convert.1.html).

## Layout versions vs. PMDK releases

The on-media layout of libpmemobj pools has changed few times since the
initial release. The mapping between the layout version and corresponding PMDK
version can be found in the following table:

|              PMDK version | 1.0 | 1.1 | 1.2 | 1.3 | 1.4 | 1.5 |
|--------------------------:|:---:|:---:|:---:|:---:|:---:|:---:|
| Libpmemobj layout version |  v1 |  v2 |  v3 |  v4 |  v4 |  v5 |

The on-media layout of the pools created by other PMDK's libraries
(libpmemblk, libpmemlog) has not changed since initial release, so the
conversion of these pools was never needed.

### How is the conversion performed?

Conversion of pools consists of two phases:
- **Recovery** - if the pool was not closed gracefully,
all transaction logs are processed by a recovery algorithm compatible with the
current on-media layout.
- **Layout changes** - All on-media layout changes are performed, and the
layout version is incremented.

### How to convert a pool

> Conversion is not fail-safe, and it cannot
> be reverted. Before conversion, a backup of the pool should be created.

Pool conversion can be performed using `pmdk-convert` tool:

```bash
$ pmdk-convert path/to/the/pool
This tool will update the pool to the specified layout version.
This process is NOT fail-safe.
Proceed only if the pool has been backed up or
the risks are fully understood and acceptable.
Hit Ctrl-C now if you want to stop or Enter to continue.

Starting the conversion from v4 (PMDK 1.3, PMDK 1.4) to v5 (PMDK 1.5)
Converting from v4 (PMDK 1.3, PMDK 1.4) to v5 (PMDK 1.5)... Done
```

As the conversion process is not reversible, the tool asks for a confirmation.
This question might be inconvenient in scripts, so it can be skipped
by -X fail-safety option.

```bash
$ pmdk-convert path/to/the/pool/ -X fail-safety
This tool will update the pool to the specified layout version.
This process is NOT fail-safe.
Proceed only if the pool has been backed up or
the risks are fully understood and acceptable.
Starting the conversion from v4 (PMDK 1.3, PMDK 1.4) to v5 (PMDK 1.5)
Converting from v4 (PMDK 1.3, PMDK 1.4) to v5 (PMDK 1.5)... Done
```

There is also another question which can be asked during the conversion from
a pool created in PMDK 1.1

```bash
$ pmdk-convert tests/pool
This tool will update the pool to the specified layout version.
This process is NOT fail-safe.
Proceed only if the pool has been backed up or
the risks are fully understood and acceptable.
Starting conversion from v2 (PMDK 1.1) to v5 (PMDK 1.5)
Converting from v2 (PMDK 1.1) to v3 (PMDK 1.2)...
**The conversion to 1.2 can only be made automatically if the PMEMmutex,
PMEMrwlock and PMEMcond types are not used in the pool or all of the variables
of those three types are aligned to 8 bytes. Proceed only if you are sure that
the above is true for this pool.
convert the pool? [y/N] y**
Done
Converting from v3 (PMDK 1.2) to v4 (PMDK 1.3, PMDK 1.4)... Done
Converting from v4 (PMDK 1.3, PMDK 1.4) to v5 (PMDK 1.5)... Done
```
this question can be automatically answered with `-X 1.2-pmemmutex` option.

More information about this question can be found in 1.2 release notes and in
the following [issue](https://github.com/pmem/issues/issues/358),

### Conversion of the pool with remote replicas

During the conversion with remote replica the following error is printed:
```bash
$ pmdk-convert ./poolset
Remote replication is not supported.
Please use pmempool transform to remove remote replica
and then use pmdk-convert.
```
pmdk-convert does not support conversion of pools with remote replicas.
To convert such pools, the following workaround must be used.

1. A remote replica needs to be removed by [pmempool transform command](http://pmem.io/pmdk/manpages/linux/master/pmempool/pmempool-transform.1.html).
Before calling pmempool transform, you have to prepare a new poolset file
without a remote replica.
```bash
$ cat tests/poolset
PMEMPOOLSET
20M /mnt/mem/pool
REPLICA user@localhost remotepool.set
$ cat poolset2
PMEMPOOLSET
20M /mnt/mem/pool
$ pmempool transform poolset poolset2
```

2. When a remote replica is removed, the pool conversion can be
performed as usual:
```bash
$ pmdk-convert tests/poolset2 -X fail-safety
This tool will update the pool to the specified layout version.
This process is NOT fail-safe.
Proceed only if the pool has been backed up or
the risks are fully understood and acceptable.
Starting conversion from v4 (PMDK 1.3, PMDK 1.4) to v5 (PMDK 1.5)
Converting from v4 (PMDK 1.3, PMDK 1.4) to v5 (PMDK 1.5)... Done
```

3. When the conversion is done, the remote replica can be added again by
pmempool transform command:
```bash
$ pmempool transform poolset2 poolset
```

### pmempool features
In 1.5 release we introduced an extra features flags. After upgrading pmdk
(and pool conversion for libpmemobj pools), they can be turned on for
existing pools. Detailed information about feature flags can be found in
the [pool features](https://pmem.io/2018/12/05/pool-features.html) blog post.

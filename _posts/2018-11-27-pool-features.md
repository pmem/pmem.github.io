---
title: Pool features
author: jmmichal
layout: post
identifier: pool_features
---

# Introduction

Since the very first release, PMDK pools had internal feature flags. They were
mostly a hidden implementation detail. The more observant users might have
noticed pool features listing in [pmempool info][pmempool-info.1] output,
but that's about it.

[Release 1.5][release-1.5] introduced a set of new feature flags.
And since it's imperative that system administrators have the ability to manage
enabled features, we've added functionality that helps with that:

* `pmempool` tool [commands for managing features][pmempool-feature.1]
* *libpmempool* [API for managing features][pmempool-feature.3]
* CTL namespace for
[setting a `SHUTDOWN_STATE` initial value](#enabling-feature-at-creation-time)

# Taxonomy of pool features

Any one of the toggleable features can be disabled or enabled for the given pool
and the support for those features might vary depending on the library version.
All features are divided into two groups:

* compatible features:
	* `CHECK_BAD_BLOCKS`
* incompatible features:
	* `SINGLEHDR`
	* `CKSUM_2K`
	* `SHUTDOWN_STATE`

Resulting in the following alternatives:

- if a feature is disabled, it's disabled (obviously)
- if a feautre is enabled, and supprted by the library, it's enabled
- if a feature is enabled, and NOT supported, there are two things that might happen:
	- if it's a compatible feature, it's disabled
	- if it's a incompatible feature, the pool can NOT be opened

## CKSUM_2K dependant features

The first 4K of a pool is reserved for a pool header. The pool header is
checksummed to ensure its consistency. If `CKSUM_2K` feature is enabled, only
first 2K of the pool header is checksummed otherwise a whole 4K. This feature
gives the library more flexibility which is needed for features that require
extra pool header capacity. Currently, that's only `SHUTDOWN_STATE` feature.

Some features will depend on the extra flexibility provided by `CKSUM_2K` feature.
This means that it will need to be enabled prior to enabling those features and
those features will need to be disabled prior to disabling `CKSUM_2K` feature.

# Feature toggling and querying

Toggling and querying features for already existing and NOT opened pools can
be performed using the `pmempool` tool:

```bash
$ pmempool feature --enable SHUTDOWN_STATE /path/to/poolset.file # to enable

$ pmempool feature --disable SHUTDOWN_STATE /path/to/poolset.file # to disable

$ pmempool feature --query SHUTDOWN_STATE /path/to/poolset.file # to query
0 # query result: 0 == disabled, 1 == enabled
```

or using *libpmempool* API calls:

```c
const char *path = "/path/to/poolset.file";
enum pmempool_feature f = SHUTDOWN_STATE;
unsigned flags = 0; /* only currently supported value */
int result = 0;

/* to enable */
result = pmempool_feature_enable(path, f, flags);
if (result < 0) {
	fprintf(stderr, "pmempool_feature_enable failed with errno: %d", errno);
}

/* to disable */	
result = pmempool_feature_disable(path, f, flags);
if (result < 0) {
	fprintf(stderr, "pmempool_feature_disable failed with errno: %d", errno);
}

/* to query */
result = pmempool_feature_query(path, f, flags);
if (result < 0) {
	fprintf(stderr, "pmempool_feature_query failed with errno: %d", errno);
} else {
	printf("pmempool_feature_query results is %s", result == 0 ? "disabled" : "enabled");
}
```

**Disclaimers**:

- toggling features is not supported for poolsets with remote replicas
- `SINGLEHDR` feature is read-only (enabling or disabling it will fail)

## Enabling feature at creation time

Some features might be disabled by default. To enable them at creation time,
use appropriate query from the [CTL namespace][pmemobj_ctl_get.3].
An example using **pmem_ctl**(5) environment variable:

```bash
$ PMEMOBJ_CONF="sds.at_create=1" pmempool create obj /path/to/poolset.file
$ pmempool feature --query SHUTDOWN_STATE /path/to/poolset.file
1
```

An example using **pmemobj_ctl_set**(3) API:

```c
const char path[] = "/path/to/poolset.file";

/* force-enable SDS feature during pool creation*/
int sds_write_value = 1;
pmemobj_ctl_set(NULL, "sds.at_create", &sds_write_value);

/* create pool and use it */
PMEMobjpool *pop = pmemobj_create(path, LAYOUT_NAME, POOL_SIZE, 0644);
/* ... */
pmemobj_close(pop);

/* check SDS feature */
int sds_read_value = pmempool_feature_query(path, PMEMPOOL_FEAT_SHUTDOWN_STATE, 0);
/* sds_write_value == sds_read_value */
````

[release-1.5]: http://pmem.io/2018/10/22/release-1-5.html
[RAS]: http://pmem.io/2018/10/22/release-1-5.html#reliability-availability-and-serviceability-ras
[pmempool-info.1]: http://pmem.io/pmdk/manpages/linux/master/pmempool/pmempool-info.1.html
[pmempool-feature.1]: http://pmem.io/pmdk/manpages/linux/master/pmempool/pmempool-feature.1.html
[pmempool-feature.3]: http://pmem.io/pmdk/manpages/linux/master/libpmempool/pmempool_feature_query.3
[pmemobj_ctl_get.3]: http://pmem.io/pmdk/manpages/linux/master/libpmemobj/pmemobj_ctl_get.3

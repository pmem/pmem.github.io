---
title: Pool features
author: jmmichal
layout: post
identifier: pool_features
---

# Introduction

From the beginning of persistent memory pools existence pools have an internal state called features. It was not very useful for the end user to know what features are enabled in his pools so it was an implementation detail. The more vigilant user may notice pool features was listed in the [pmempool info][pmempool-info.1] output but not much more.

Starting from [release 1.5][release-1.5] set of new features have been introduced. It is **crucial for data safety** to know if e.g. [Reliability, Availability and Serviceability \(RAS\)][RAS] features are enabled in the pool or not. To meet the need bunch of new possibilities are now available:

* `pmempool` tool [commands for managing features][pmempool-feature.1]
* *libpmempool* [API for managing features][pmempool-feature.3]
* CTL namespace for [setting a `SHUTDOWN_STATE` initial value](#set-shutdown_state-initial-value)

# The features taxonomy

Any feature can be disabled or enabled in the pool and supported or not supported in the used version of the PMDK library. All features are divided into three groups:

* compatible features:
	* `CHECK_BAD_BLOCKS`
* incompatible features:
	* `SINGLEHDR`
	* `CKSUM_2K`
	* `SHUTDOWN_STATE`
* read-only compatible features:
	* \(empty group\)

This partition gives the following matrix:

<table>
	<thead>
		<tr>
			<th colspan="2" rowspan="2">pool feature</th>
			<th colspan="2">PMDK library support</th>
		</tr>
		<tr>
			<th>yes</th>
			<th>no</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th rowspan="2">compat</th>
			<th>enabled</th>
			<td>(1)</td>
			<td>the pool can be opened and used but the feature has no effect</td>
		</tr>
		<tr>
			<th>disabled</th>
			<td>(2)</td>
			<td>(2)</td>
		</tr>
		<tr>
			<th rowspan="2">incompat</th>
			<th>enabled</th>
			<td>(1)</td>
			<td>the pool can <strong>NOT</strong> be opened and used because PMDK has to support the feature to handle the pool properly</td>
		</tr>
		<tr>
			<th>disabled</th>
			<td>(2)</td>
			<td>(2)</td>
		</tr>
		<tr>
			<th rowspan="2">ro&nbsp;compat</th>
			<th>enabled</th>
			<td>(1)</td>
			<td>the pool can be opened but it is opened in the <strong>read-only mode</strong></td>
		</tr>
		<tr>
			<th>disabled</th>
			<td>(2)</td>
			<td>(2)</td>
		</tr>
	</tbody>
</table>

- (1) If the feature is enabled and supported by the used PMDK library it has effect as described in [the documentation][pmempool-feature.3].
- (2) If the feature is disabled it does not matter if it is supported or not. It has no effect.

## `CKSUM_2K` dependant features

An initial 4K of a pool is reserved for a pool header. The pool header is checksummed to control its consistency. If `CKSUM_2K` feature is enabled only first 2K of the pool header is checksummed otherwise a whole 4K. When only a first 2K of a pool header is checksummed the other 2K can be used used to store more dynamically changing data without affecting pool header consistency. Currently, the only feature which makes use of this extra space is `SHUTDOWN_STATE` feature.

If the **F** feature requires to store additional information in the non-checksummed 2K of a pool header it requires to enable `CKSUM_2K` feature before enabling the **F** feature. On the other hand, before `CKSUM_2K` feature is disabled it is required to disable the **F** feature if the **F** feature requires to store additional information in the non-checksummed 2K of a pool header.

# Feature toggling and querying

Toggling and querying features for already existing and **NOT** opened pools can be performed using the `pmempool` tool:

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
- `SINGLEHDR` can only be queried because it has to be explicitly written in a [poolset file][poolset.5]

## Set `SHUTDOWN_STATE` initial value

Every feature has its initial value either enabled or disabled. If it is required to change the feature value after a pool is created it can be done as it is described in [Feature toggling and querying](#feature-toggling-and-querying). But sometimes it is useful to change an initial feature value to have enabled or disabled feature from the get-go.

It can be done for `SHUTDOWN_STATE` feature using `sds.at_create` [CTL namespace][pmemobj_ctl_get.3].

[release-1.5]: http://pmem.io/2018/10/22/release-1-5.html
[RAS]: http://pmem.io/2018/10/22/release-1-5.html#reliability-availability-and-serviceability-ras
[pmempool-info.1]: http://pmem.io/pmdk/manpages/linux/master/pmempool/pmempool-info.1.html
[pmempool-feature.1]: http://pmem.io/pmdk/manpages/linux/master/pmempool/pmempool-feature.1.html
[pmempool-feature.3]: http://pmem.io/pmdk/manpages/linux/master/libpmempool/pmempool_feature_query.3
[poolset.5]: http://pmem.io/pmdk/manpages/linux/master/poolset/poolset.5
[pmemobj_ctl_get.3]: http://pmem.io/pmdk/manpages/linux/master/libpmemobj/pmemobj_ctl_get.3

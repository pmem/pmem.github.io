---
title: "pmemkv-java | PMemKV"
draft: false
slider_enable: true
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "pmemkv-java"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Discontinuation of the project
The **pmemkv-java** library will no longer be maintained by Intel.
- Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases,
or updates, to this project.
- Intel no longer accepts patches to this project.
- If you have an ongoing need to use this project, are interested in independently developing it, or would like to
maintain patches for the open source software community, please create your own fork of this project.
- You will find more information [here](/blog/2022/11/update-on-pmdk-and-our-long-term-support-strategy/).

### pmemkv-java

Java bindings for **pmemkv**, using Java Native Interface.

<a href="/pmemkv">pmemkv</a> is a local/embedded key-value datastore optimized for persistent memory.

Current code of pmemkv-java can be accessed on <a href="https://github.com/pmem/pmemkv-java">github page</a>.

The API of pmemkv-java binding is documented in the following docs:

* [master](master/html/index.html) - it implements additional API of libpmemkv (and requires min. version of 1.4)
* [v1.2](v1.2/html/index.html) - it implements additional API of libpmemkv (and requires min. version of 1.4)
* [v1.1](v1.1/html/index.html) - it is functionally equivalent to libpmemkv 1.0
* [v1.0](v1.0/html/index.html) - it is functionally equivalent to libpmemkv 1.0

### Releases’ support status

Currently all branches/releases are fully supported. Latest releases can be seen on the <a href="https://github.com/pmem/pmemkv-java/releases">"releases" tab on the Github page</a>.

| Version branch | First release date | Last patch release | Maintenance status |
| -------------- | ------------------ | ------------------ | ------------------ |
| stable-1.2 | Jul 02, 2021 | N/A | Full |
| stable-1.1 | Jun 08, 2021 | N/A | Full |
| stable-1.0 | Jun 30, 2021 | 1.0.1 (Mar 12, 2021) | Full |

Possible statuses:
1. Full maintenance:
	* All/most of bugs fixed (if possible),
	* Patch releases issued based on a number of fixes and their severity,
	* At least one release at the end of the maintenance period,
	* Full support for at least a year since the initial release.
2. Limited scope:
	* Only critical bugs (security, data integrity, etc.) will be backported,
	* Patch versions will be released when needed (based on severity of found issues),
	* Branch will remain in "limited maintenance" status based on the original release availability in popular distros,
3. EOL:
	* No support,
	* No bug fixes,
	* No official releases.

### Blog entries

The following blog articles relates to pmemkv-java:

* [API overview of pmemkv-java binding](/2020/10/30/pmemkv-java-binding.html) - based on v1.0.0 implementation


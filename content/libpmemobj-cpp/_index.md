---
title: "C++ Bindings"
draft: false
slider_enable: true
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libpmemobj-cpp"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Discontinuation of the project
The **libpmemobj-cpp** project will no longer be maintained by Intel.
- Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases,
or updates, to this project.
- Intel no longer accepts patches to this project.
- If you have an ongoing need to use this project, are interested in independently developing it, or would like to
maintain patches for the open source software community, please create your own fork of this project.
- You will find more information [here](/blog/2022/11/update-on-pmdk-and-our-long-term-support-strategy/).

### The C++ bindings to libpmemobj

**libpmemobj-cpp** provides a less error prone version of [libpmemobj](/pmdk/libpmemobj/)
through the implementation of a pmem-resident property, persistent pointers,
scoped and closure transactions, locking primitives and many others.

Doxygen documentation is available:

* for the current [**master**](master/doxygen/index.html)
* for the latest stable branch: [**v1.13**](v1.13/doxygen/index.html)

For older documentation [see below](#older-documentation).

### Blog entries

The following series of blog articles provides a tutorial introduction
to the **C++ bindings**:

* [Part 0 - Introduction](/2016/01/12/cpp-01.html)
* [Part 1 - Pmem Resident Property](/2016/01/12/cpp-02.html)
* [Part 2 - Persistent Smart Pointer](/2016/01/12/cpp-03.html)
* [Part 3 - Persistent Queue Example](/2016/01/12/cpp-04.html)
* [Part 4 - Pool Handle Wrapper](/2016/05/10/cpp-05.html)
* [Part 5 - make_persistent](/2016/05/19/cpp-06.html)
* [Part 6 - Transactions](/2016/05/25/cpp-07.html)
* [Part 7 - Synchronization Primitives](/2016/05/31/cpp-08.html)
* [Part 8 - Converting Existing Applications](/2016/06/02/cpp-ctree-conversion.html)

There are also another blog posts regarding **C++ bindings**:
* [Modeling strings with libpmemobj C++ bindings](/2017/01/23/cpp-strings.html)
* [Using Standard Library Containers with Persistent Memory](/2017/07/10/cpp-containers.html)
* [C++ persistent containers - array](/2018/11/02/cpp-array.html)
* [C++ persistent containers](/2018/11/20/cpp-persistent-containers.html)
* [C++ persistent containers - vector](/2019/02/20/cpp-vector.html)
* [C++ standard limitations and Persistent Memory](/2019/10/04/cpp-limitations.html)
* [Concurrency considerations in libpmemobj-cpp](/2021/09/17/concurrency.html)
* [Libpmemobj-cpp - lessons learned](/2021/09/30/cpp-lessons-learned.html)


### Releases' support status

Only some of the latest branches/releases are fully supported. The most recent releases can be found
on the ["releases" tab on the GitHub page](https://github.com/pmem/libpmemobj-cpp/releases).

| Version branch | First release date | Last patch release | Maintenance status |
| -------------- | ------------------ | ------------------ | ------------------ |
| stable-1.13 | Jul 27, 2021 | N/A | EOL |
| stable-1.12 | Feb 15, 2021 | N/A | EOL |
| stable-1.11 | Sep 30, 2020 | N/A | EOL |
| stable-1.10 | May 28, 2020 | N/A | EOL |
| stable-1.9 | Jan 31, 2020 | N/A | EOL |
| stable-1.8 | Oct 03, 2019 | 1.8.2 (Aug 01, 2022) | EOL |
| stable-1.7 | Jun 26, 2019 | 1.7.1 (Jul 29, 2022) | EOL |
| stable-1.6 | Mar 15, 2019 | 1.6.1 (Jul 06, 2021) | EOL |
| stable-1.5 | Oct 26, 2018 | 1.5.2 (Jun 28, 2021) | EOL |

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

### Older documentation

Older branches docs:
* for [stable-1.12](v1.12/doxygen/index.html)
* for [stable-1.11](v1.11/doxygen/index.html)
* for [stable-1.10](v1.10/doxygen/index.html)
* for [stable-1.9](v1.9/doxygen/index.html)

### Archived documentation

* for version [1.8.2](v1.8/doxygen/index.html)
* for version [1.7.1](v1.7/doxygen/index.html)
* for version [1.6.1](v1.6/doxygen/index.html)
* for version [1.5.2](v1.5/doxygen/index.html)
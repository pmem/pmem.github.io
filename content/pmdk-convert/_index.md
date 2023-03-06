---
title: "pmdk-convert"
draft: false
slider_enable: true
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "pmdk-convert"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### ⚠️ Discontinuation of the project
The **pmdk-convert** project will no longer be maintained by Intel.
- Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases,
or updates, to this project.
- Intel no longer accepts patches to this project.
- If you have an ongoing need to use this project, are interested in independently developing it, or would like to
maintain patches for the open source software community, please create your own fork of this project.
- You will find more information [here](https://pmem.io/blog/2022/11/update-on-pmdk-and-our-long-term-support-strategy/).

### pmdk-convert: PMDK pool conversion tool

The **pmdk-convert** performs a conversion of the specified pool to the newest layout supported by this tool. Currently only **libpmemobj**(7) pools are supported.

The conversion process is not fail-safe - power interruption may damage the pool. It is advised to have a backup of the pool before conversion.

This tool doesn’t support remote replicas. Before a conversion all remote replicas have to be removed from the pool by **pmempool transform** command.

See the <a href="manpages/master/pmdk-convert.1.html">pmdk-convert man page</a> for current master documentation and examples.

See the [blog post about pool conversion](/blog/2019/02/pool-conversion-tool) for detailed description of the process.

### Building The Source

Requirements:

* cmake >= 3.3

On Windows:

* [Windows SDK](https://developer.microsoft.com/en-us/windows/downloads/windows-10-sdk) >= 10.0.16299

In pmdk-convert directory:

``` sh
$ mkdir build
$ cd build
```

And then:

#### On RPM-based Linux distros (Fedora, openSUSE, RHEL, SLES)

``` sh
$ cmake .. -DCMAKE_INSTALL_PREFIX=/usr -DCPACK_GENERATOR=rpm
$ make package
$ sudo rpm -i pmdk-convert*.rpm
```

#### On DEB-based Linux distros (Debian, Ubuntu)

``` sh
$ cmake .. -DCMAKE_INSTALL_PREFIX=/usr -DCPACK_GENERATOR=deb
$ make package
$ sudo dpkg -i pmdk-convert*.deb
```

#### On other Linux distros

``` sh
$ cmake .. -DCMAKE_INSTALL_PREFIX=/home/user/pmdk-convert-bin
$ make
$ make install
```

#### On Windows
``` powershell
PS> cmake .. -G "Visual Studio 14 2015 Win64"
PS> msbuild build/ALL_BUILD.vcxproj
```

To build pmdk-convert on Windows 8 you have to specify your SDK version in the cmake command, e.g.

``` powershell
PS> cmake .. -G "Visual Studio 14 2015 Win64" -DCMAKE_SYSTEM_VERSION="10.0.26624"
```
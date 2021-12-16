---
title: ndctl returns "error while loading shared libraries. libjson-c.so.2. cannot open shared object file. No such file or directory"
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ndctl, library]
author: Steve Scargall
docid: 100000018
creation_date: 2020-03-11
modified_date: 
---

# Applies To

- Linux
- ndctl utility

# Issue

Executing the `ndctl` utility, with or without commands or options, returns the following missing library error:

```
# ndctl --versionndctl: error while loading shared libraries: libjson-c.so.2: cannot open shared obj
```



# Cause

The issue could be caused by one of the following issues:

- The json-c package is not installed
- A version mis-match between the `json-c` and `ndctl`.  More recent versions of json-c deliver `libjson-c.so.4` rather than `libjson-c.so.2`. 
- The json-c library is installed in a non-default location and the LD_LIBRARY_PATH environment variable needs to be updated.

Verify which libraries are missing from `ndctl` using the `ldd` utility and identifying libraries that are 'not found':

```
# ldd `which ndctl`
        linux-vdso.so.1 (0x00007ffc677c4000)
        libndctl.so.6 => /lib64/libndctl.so.6 (0x00007f31fca49000)
        libdaxctl.so.1 => /lib64/libdaxctl.so.1 (0x00007f31fc843000)
        libuuid.so.1 => /lib64/libuuid.so.1 (0x00007f31fc63c000)
        libjson-c.so.2 => not found
        libc.so.6 => /lib64/libc.so.6 (0x00007f31fc27d000)
        libudev.so.1 => /lib64/libudev.so.1 (0x00007f31fc057000)
        [...]
```

# Solution

Verify that the json-c package is installed. For example, on Fedora use `dnf info --installed json-c`. If it is installed, information about the package will be displayed, eg:

```
# sudo dnf info --installed json-c
Installed Packages
Name         : json-c
Version      : 0.13.1
Release      : 2.fc28
Arch         : x86_64
Size         : 65 k
Source       : json-c-0.13.1-2.fc28.src.rpm
Repo         : @System
From repo    : updates
Summary      : JSON implementation in C
URL          : https://github.com/json-c/json-c
License      : MIT
Description  : JSON-C implements a reference counting object model that allows you
             : to easily construct JSON objects in C, output them as JSON formatted
             : strings and parse JSON formatted strings back into the C representation
             : of JSON objects.  It aims to conform to RFC 7159.
```

If the package is not installed, a message similar to the following will be returned:

```
# sudo dnf info --installed json-c
Error: No matching Packages to list
```

To install the json-c package if it is missing, use

```
# sudo dnf install json-c
```

To query the json-c package to identify the library version use the following:

```
# dnf repoquery -l json-c | grep libjson-c.so
Last metadata expiration check: 2:07:37 ago on Fri 06 Jul 2018 06:18:47 AM MDT.
/usr/lib64/libjson-c.so.4
/usr/lib64/libjson-c.so.4.0.0
/usr/lib64/libjson-c.so.4
/usr/lib64/libjson-c.so.4.0.0
/usr/lib/libjson-c.so.4
/usr/lib/libjson-c.so.4.0.0
/usr/lib/libjson-c.so.4
/usr/lib/libjson-c.so.4.0.0
```

Verify the LD_LIBRARY_PATH includes the location of libjson-c.so.*. Note: `/usr/lib` and `/usr/lib64` are automatically included.

```
# echo $LD_LIBRARY_PATH
/usr/local/lib:/usr/local/lib64
```

If the package and LD_LIBRARY_PATH are correct, the version of ndctl will need to be updated. Using `ndctl --version` won't work and will simply return "*ndctl: error while loading shared libraries: libjson-c.so.2: cannot open shared object file: No such file or directory*".

If the `ndctl` utility was installed using the ndctl package from the operating system's repository, update the package to the latest version. On Fedora:

```
# sudo dnf update -y ndctl
```

If the latest version within the package repository is old with no new versions available, download, compile, and install from source code. Detailed instructions can be found in the [Installing NDCTL](https://github.com/sscargal/pmem-docs-ndctl-user-guide/tree/bad2d0a9e4528c1cc9d2aa6271f8a427dad45420/getting-started-guide/installing-ndctl.md) chapter.

If the `ndctl` utility was previously compiled and installed using source code, download the latest version from the [ndctl GitHub repository](https://github.com/pmem/ndctl), compile, and install. Detailed instructions can be found in the [Installing NDCTL](https://github.com/sscargal/pmem-docs-ndctl-user-guide/tree/bad2d0a9e4528c1cc9d2aa6271f8a427dad45420/getting-started-guide/installing-ndctl.md) chapter.

---
title: Executing 'man -k ndctl' returns "ndctl. nothing appropriate."
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, ndctl, man]
author: Steve Scargall
docid: 100000021
creation_date: 2020-03-11
modified_date: 
---

# Applies To

- Linux
- man pages

# Issue

When searching the short descriptions and manual page for a *keyword*, the man utility may return an empty set, for example:

```
# man -k ndctl
ndctl: nothing appropriate.
```

# Cause

If you built ndctl from source, the man page indexes may not have been automatically generated for you. Additionally, if you installed ndctl to a non-default location, your $MANPATH shell environment variable may not been updated to point to to the new man page locations.

# Solution

1) Verify the current system global search paths for manual pages

```
# manpath -g
/usr/man:/usr/share/man:/usr/local/man:/usr/local/share/man:/usr/X11R6/man:/opt/man
```

2) Identify if these paths are overridden by a local MANPATH setting, for example the following shows no additional search paths

```
# echo $MANPATH
#
```

3) If the install location of ndctl is not included in the above output, add the path to your MANPATH shell environment, otherwise skip this step:

```
# export MANPATH=$MANPATH:<path_to_ndctl_man_pages>
```

4) Manually scan and build the man page indexes

```
# sudo mandb -c
```

This command may take a few minutes depending on the number of search paths and man pages it has to index. The output is verbose as it scans and indexes. Once complete, it will provide a summary:

```
...
118 man subdirectories contained newer manual pages.
10550 manual pages were added.
0 stray cats were added.
```

5) If the ndctl man pages were scanned and indexed, `man -k ndctl` will now work as expected, eg:

```
# man -k ndctl
ndctl (1)            - Manage "libnvdimm" subsystem devices (Non-volatile Memory)
ndctl-check-labels (1) - determine if the given dimms have a valid namespace index block
ndctl-check-namespace (1) - check namespace metadata consistency
ndctl-clear-errors (1) - clear all errors (badblocks) on the given namespace
...
```

---
title: How To Install NDCTL and DAXCTL Packages on Ubuntu
description: ''
subtitle: 
layout: doc
categories: [howto]
tags: [howto, setup, install, configure, ndctl, daxctl]
date: 2019-06-18
creation_date: 2019-06-18
modified_date:
docid: 100000015
author: Steve Scargall
---

### Applies To

This document applies to Ubuntu 18.04 (Bionic Beaver) or later running Linux Kernel 4.2 or later.

## Introduction

The `ndctl` utility is used to manage persistent memory devices within the system while the `daxctl` utility managed device-dax instances.  `libndctl` is required for several Persistent Memory Development Kit (PMDK) and `ipmctl` features if compiling from source.  If `ndctl` is not available, the PMDK may not build all components and features.  This page describes how to install `ndctl` and `daxctl` using the Linux package repository.

Both `ndctl` and `daxctl` are Linux only utilities.  

## Installing NDCTL Packages

1. Query the repository to identify if ndctl is delivered:

   ```$ sudo apt search ndctl```

2. Install the ndctl package.  This automatically installs `daxctl` and `libndctl`.

   `$ sudo apt-get install ndctl`

3. Done!

## Summary

This article demonstrated how to install ndctl, daxctl, and libndctl on a system running Ubuntu 18.04 or later.




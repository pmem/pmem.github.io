---
title: How To Install NDCTL and DAXCTL Packages on Red Hat Enterprise Linux (RHEL) and CentOS 7
description: ''
subtitle: 
layout: doc
categories: [howto]
tags: [setup, install, configure, ndctl, daxctl]
date: 2019-06-18
docid: 100000013
creation_date: 2019-06-18
modified_date:
author: Steve Scargall
---

## Applies To

This document applies to RHEL/CentOS 7.5 or later running Linux Kernel 3.10.0 or later.

## Introduction

The `ndctl` utility is used to manage persistent memory devices within the system while the `daxctl` utility managed device-dax instances.  `libndctl` is required for several Persistent Memory Developer Kit (PMDK) and `ipmctl` features if compiling from source.  If `ndctl` is not available, the PMDK may not build all components and features.  This page describes how to install `ndctl` and `daxctl` using the Linux package repository.

Both `ndctl` and `daxctl` are Linux only utilities.  

## Installing NDCTL Packages

1. Query the repository to identify if ndctl is delivered:

   ```$ sudo apt search ndctl```

2. Install the ndctl package.  This automatically installs `daxctl` and `libndctl`.

   `$ sudo apt-get install ndctl`

3. Done!

## Summary

This article demonstrated how to install ndctl, daxctl, and libndctl on a system running Red Hat Enterprise Linux (RHEL) and CentOS 7.5 or later.




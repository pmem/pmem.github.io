---
title: What programming languages does PMDK (Persistent Memory Development Kit) support?
description: ''
subtitle: 
layout: doc
categories: [faq]
tags: [pmdk, programming, languages]
author: Steve Scargall
docid: 100000011
creation_date: 2019-10-31
modified_date: 
---

# Answer

The [Persistent Memory Development Kit (PMDK)](http://pmem.io/pmdk/) core libraries provide C APIs.  Libraries such as [libpmemobj](https://pmem.io/pmdk/libpmemobj/) provide C++ APIs. Higher level libraries such as [libpmemkv](https://github.com/pmem/pmemkv), a key-value store for persistent memory, support C, C++, Javascript, Java, Ruby, and Python.
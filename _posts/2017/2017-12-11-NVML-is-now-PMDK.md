---
title: Announcing the Persistent Memory Development Kit
author: andyrudoff
layout: post
---

This is to announce a name change: The NVML project is now known as
[**PMDK**](/pmdk/), the Persistent Memory Development Kit.

#### Why the name change?

The old name, NVML, made it sound like the project produced a single library
that applied to Non-Volatile Memory.  In reality, the project currently
supports **ten libraries**, targeted at various use cases for persistent
memory, along with language support for
**C**, **C++**, **Java**, and **Python**,
tools like the **pmemcheck**
plug-in for valgrind, and an increasing body of documentation, code examples,
tutorials, and [blog entries](/blog/).  The libraries are tuned and validated
to **production quality** and issued with a license that allows their use in
both open- and closed-source products.  And the project continues to grow as we
learn about new use cases.  So the term _Development Kit_ seems much more
appropriate.  The new name, [PMDK](/pmdk/), also follows the pattern of similar
development kits like the Data Plane Development Kit ([DPDK](https://dpdk.org))
and the Storage Performance Development Kit ([SPDK](https://spdk.io)).

#### What is the impact of the change?

The impact is almost entirely isolated to documentation, where we’re replacing
the name NVML with PMDK in man pages, examples, URLs, etc.  C++ code using
libpmemobj will require a small change since the string `nvml`
was used as a namespace.  This has been fixed to be the more descriptive
namespace `pmem` and our documentation and example code has been
changed to reflect this.

Note that any clones of the NVML source tree will continue to work, since
GitHub will map the old repo location to the
[new PMDK repo location](https://github.com/pmem/pmdk).

#### What is the future direction for PMDK?

Our future plans have not changed: we will continue to develop, tune, and
validate additional libraries and tools for the Persistent Memory Development
Kit.  We continue to welcome questions, comments, feature requests, and
community contributions.

---
title: Introducing pmemkv
author: RobDickinson
layout: post
---

We've blogged before about
[building](http://pmem.io/2015/07/31/diy-kvstore.html) and
[optimizing](http://pmem.io/2015/09/10/kvstore2.html)
key-value stores for persistent memory, and we're excited to
put these ideas to the test in a more formal way.

Our new **[pmemkv](https://github.com/pmem/pmemkv)** project is
an open-source key-value store that is optimized
for read-heavy workloads on persistent memory.
Compared with key-value stores based on the
[LSM algorithm](https://en.wikipedia.org/wiki/Log-structured_merge-tree),
pmemkv offers higher read performance and lower write amplification.
But our intent is not to deter use of LSM, only to expand the choices
developers and architects have for aligning workloads to backing stores.

Internally [pmemkv](https://github.com/pmem/pmemkv) uses a B+ tree where 
inner nodes are kept in DRAM and leaf nodes are exclusively stored in 
persistent memory. Mixing types of memory allows pmemkv to offer both
per-operation consistency and good performance. The diagram below shows
a simple example of what pmemkv looks like internally.

![pmemkv internals]({{site.url}}/assets/pmemkv1.png)

Obviously [pmemkv](https://github.com/pmem/pmemkv) is still in its early
stages and is not yet ready for production use, but a good
[issue backlog](https://github.com/pmem/pmemkv/issues)
has been started to capture what we intend to do next.

Like NVML itself, [pmemkv](https://github.com/pmem/pmemkv) is
open-source software (under BSD license) and community contributions
are welcomed!

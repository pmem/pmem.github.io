---
# Blog post title
title: 'Multi-level vmemcache'

# Blog post creation date
date: 2019-06-12T19:55:17-07:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: ''

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ''

# Blog post thumbnail
# eg: image: "/images/my_blog_thumbnail.png"
image: ''

# Blog post author
author: 'kilobyte'

# Categories to which this blog post belongs
blogs: ['libvmemcache']

tags: []

# Redirects from old URL
aliases: ['/2019/06/12/two-level-vmemcache.html']

# Blog post type
type: 'post'
---

### Introduction

**vmemcache** which we have
[recently described](/blog/2019/05/libvmemcache-buffer-based-lru-cache)
performs close to optimum when either all keys are approximately equally
likely to be queried, or when all key:value pairs fit completely into the
fastest form of memory you are willing to use. But, in many workloads, some
keys are “hot” and queried over and over again, while the rest, “cold”, may
comfortably reside on slower medium. This calls for multiple linked instances
of **vmemcache**, each residing in a different tier.

When the set of “hot” vs “cold” keys is known beforehand, you can preload
them into such **vmemcache** instances. However, if the cache is not static,
technique from this post may be of use to you.

### Tiers

While **vmemcache** has been optimized for byte-addressable storage (DRAM or
Intel® Optane™ DC Persistent Memory), it can also work on block-based media¹,
no matter how it is accessed (NVDIMM-F, NVMe, SATA, network,
[IPoAC](https://en.wikipedia.org/wiki/IP_over_Avian_Carriers)). This allows a
mix of different kinds to be used by the same program. In particular,
there's no need to limit yourself to just two tiers, allowing setups such
as:

    DRAM → pmem → SSD → HDD → ...

### Callbacks

The two callbacks offered by **vmemcache** can be used here: **on_miss** can
first check in a colder tier and promote that entry, while **on_evict** may
salvage the entry that would be deleted, saving it instead in the colder
cache.

With such a setup, all operations from the rest of your program touch just
the top level cache. They let the callbacks cascade misses down the cache
tiers, and have old entries degrade on their own via **vmemcache**'s LRU
eviction policy.

As the code needed to implement a multi-level cache is quite simple (such as
the version below) and can be altered for your particular scheme,
**libvmemcache** doesn't currently provide a ready-to-use version. You can
use something like:

```c
static void
evict_demote(VMEMcache *cache, const void *key, size_t key_size, void *arg)
{
	VMEMcache *colder = (VMEMcache *)arg;

	size_t vsize;
	if (vmemcache_get(cache, key, key_size, NULL, 0, 0, &vsize))
		return;
	void *buf = malloc(vsize);
	if (!buf)
		return;
	if (vmemcache_get(cache, key, key_size, buf, vsize, 0, NULL) == vsize)
		vmemcache_put(colder, key, key_size, buf, vsize);
	free(buf);
}
```

There's no real opportunity to tune demotes — at least unless you have
multiple sibling caches at a lower tier (multiple disks not combined in a
RAID at operating system level, etc) or other complex setup.

```c
static void
miss_promote(VMEMcache *cache, const void *key, size_t key_size, void *arg)
{
	VMEMcache *colder = (VMEMcache *)arg;

	size_t vsize;
	if (vmemcache_get(colder, key, key_size, NULL, 0, 0, &vsize))
		return;
	void *buf = malloc(vsize);
	if (!buf)
		return;
	if (vmemcache_get(colder, key, key_size, buf, vsize, 0, NULL) == vsize) {
		if (!vmemcache_put(cache, key, key_size, buf, vsize))
			vmemcache_evict(colder, key, key_size);
	}
	free(buf);
}
```

To the contrary, there are multiple decisions for promote-on-miss. First,
you need to handle complete misses — when no tier of the cache has the data,
it will be necessary to produce it. Alternatively, allow the failure,
to have **vmemcache** return no data, possibly to have it filled back later.

Second, you need to decide whether to put the new item into the top or the
bottom layer. Counterintuitively, for many key distributions, it is better
to consider new data cold — a key that's used once won't evict any data
that's more useful. Only if that key proves its worth by getting queried
again, it will be moved upwards. On the other hand, a cache with a better
reuse-to-evict ratio will instead benefit from assuming that new data is
hot.

To install the callbacks, you do:

```c
	vmemcache_callback_on_evict(dram, evict_demote, pmem);
	vmemcache_callback_on_miss(dram, miss_promote, pmem);
```

### Caveats

Using a multi-level cache is not always a good idea. When the keys are
about as likely to be queried, moving them around the cache tiers leads to
thrashing that wastes time for no benefit. You may want to hold an
arbitrary set of keys in one cache and others in the other, without
evictions or migrations.

Moving data around also increases latency — this might be pointless for data
which is going to be used just once.

### Working code

A complete example, with more detailed comments and with some support code,
can be found in **libvmemcache** repository,
[here](https://github.com/pmem/vmemcache/blob/master/tests/twolevel.c).

[1]. It is strongly recommended to set the block size to 4096 bytes or a
multiple, when using block-based media.

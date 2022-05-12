---
# Blog post title
title: 'An introduction to replication'

# Blog post creation date
date: 2015-11-23T19:55:17-07:00

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
author: 'tomaszkapela'

# Categories to which this blog post belongs
blogs: ['replication']

tags: []

# Redirects from old URL
aliases: ['/2015/11/23/replication-intro.html']

# Blog post type
type: 'post'
---

Replication is a means for raising the reliability of your _pmemobj_ based applications. You can basically think of it as RAID 1 within _PMDK_. What happens is, when you write to your pool using the **pmemobj\_\*** (memcpy, persist, and so on) primitives, it gets copied to your replicas. Yes, you can have more than one replica. In fact you can have as many as you want, but you have to keep in mind the performance penalty.

Replication, although not directly, is related to pool sets. This is a simple concept which I will try to briefly explain.

### Replication and pool sets

Imagine you want to create a really big _pmemobj_ pool, so big that it exceeds the capacity of a single non-volatile memory device. However, you have more than one of those and would like to leverage that fact. Well, now you can. Taken from the _libpmemobj_ manual: _"The libpmemobj allows building transactional object stores spanning multiple memory devices by creation of persistent memory pools consisting of multiple files, where each part of such a pool set may be stored on different pmem-aware filesystem"_. That just about sums it up.

As you might have noticed, I specifically mention _pmemobj_ pools. That is because we only support this feature for _libpmemobj_. There is in fact no technical obstacle keeping us from supporting this feature for the other libraries in _PMDK_, but we decided to do it one step at a time. One other constraint is that for now we only envision local replication. What I mean is not that you cannot do remote replication, just that we don't have any native support in the library itself. If you have a remote filesystem that supports the [mmap()][b4af9cfb] syscall, it **should** be OK to put replicas there. If you have a setup like that and experience issues using replication, let us know in our [issues][6534d9c4] section.

Replicas can also be made of multiple files, just like your primary pool. These two features in combination give you a lot of leeway in the way you compose and backup your _pmemobj_ pools. These are two powerful concepts.

### How to set-up replication

There are two ways of setting up your replica/pool set. As you might expect, one is the easy and preferred way and one is the opposite. You can do it using the **pmemobj_create()** function. You have to keep in mind that it has to point to a well formed _set_ file (more on that later) and that the _poolsize_ argument must be equal to 0. Other than that it is a standard **pmemobj_create()** call. The _mode_ parameter applies to all the files created from the _set_ file (both the primary set and the replicas). This a perfectly valid approach, but we prefer doing this using the [pmempool][6d977c8e] tool. In fact this is the preferred way of doing administrative tasks on any type of pool. It's less error prone and less of a hassle to create/manage/debug your pools. I suggest you get acquainted with it. Also, expect a blog entry on it, once the features it supports and the tool itself stabilize.

As I mentioned before, to create a set/replica you need a _.set_ file. The way this file is composed is subject to change (especially if we implement support for some kind of remote replication) so I urge you to look at our [manpages][1d90594e]. Let's take a look at an example.

{{< highlight sh linenos >}}
PMEMPOOLSET

# first set/replica foo

100G /mountpoint0/foo.part0
200G /mountpoint1/foo.part1
400G /mountpoint2/foo.part2
REPLICA

# second set/replica bar

500G /mountpoint3/bar.part0
200G /mountpoint4/bar.part1
REPLICA

# third set/replica baz

800G /mountpoint5/baz.part0
{{< /highlight >}}

This represents a 700GB pool set and two replicas. As you probably noticed, the number of files in the replicas, as well as the cumulative size of each part do not have to match. The library will chose the size of the smallest set as the actual pool size. All of the replicas are binary copies and are interchangeable. This is however not hassle-free and should be done by an external tool - _pmempool_ (it does not have support for this yet, but we plan to implement it). The first line of the set file has to be _PMEMPOOLSET_ - don't try to put a comment there.

Once you have the set file ready and have appropriate permissions to all the mountpoints, run the **pmempool** tool to create all the necessary files.

{{< highlight sh linenos >}}
pmempool create --layout="mylayout" obj myobjpool.set
{{< /highlight >}}

If pmempool does not report an error, you're good to go, to do a **pmemobj_open** on the set file and use your pool.

### How does it perform?

Pool replication is a very neat feature, but it shouldn't be abused. Adding replicas has a quite substantial, unavoidable performance penalty. Let me show you exactly what I mean. I ran the _pmembench_map, pmemobx_tx and pmalloc_ benchmarks from the _PMDK_ tree to see what replication does to performance. These are pretty much the standard benchmarks made to work with my custom set with replication. Do not look at the absolute values, but the difference between the number of operations per second as a function of replicas. Depending on the algorithm used, the overhead is different and not always a show-stopper. Our current implementation of replication basically does a binary copy of the original pool, hence the possible differences.

![map_insert](/images/posts/map_insert_repl.png)

![pmemobj_tx_alloc](/images/posts/pmemobj_tx_alloc_repl.png)

![pmalloc](/images/posts/pmalloc_repl.png)

### Further plans

For now, we envision adding more administrative functions to the pmempool tool. We would like to enable adding/removing replicas to an active set, changing the partitioning between files. For now, this is not supported and you have to start the new set from scratch each time you decide to make a change. Another thing worth considering is an interactive _.set_ file creator.

We plan on supporting basic 1:1 remote replication using standard network transports. The design of the remote replication will be such that existing PMDK based applications that utilize 1:1 local replication can also utilize 1:1 remote replication without changes to the application.

[6534d9c4]: https://github.com/pmem/issues/issues 'pmem-issues'
[b4af9cfb]: https://linux.die.net/man/2/mmap 'mmap'
[6d977c8e]: /pmdk/pmempool/ 'pmempool'
[1d90594e]: /pmdk/libpmemobj/libpmemobj.3.html 'pmemobj-manpages'

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

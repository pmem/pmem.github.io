---
# Blog post title
title: 'Transactional key-value store using libpmemobj - DIY'

# Blog post creation date
date: 2015-07-31T19:55:17-07:00

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
author: 'pbalcer'

# Categories to which this blog post belongs
blogs: ['kvstore']

tags: []

# Redirects from old URL
aliases: ['/2015/07/31/diy-kvstore.html']

# Blog post type
type: 'post'
---

Our library often gets compared to NoSQL databases because it stores things on
storage in unstructured manner. Which is true, but, when you think about it,
the pmemobj library is not technically a database, but can be used to implement
one - like the MySQL storage engine example. In this post I'll describe an
example implementation of transactional kv-store, that has two different
backends, which I'll then compare. To make things more interesting, it's not
going to be your typical kv-store, since the data structure behind it won't
be a hashmap. Instead, I've selected two different tree algorithms, namely
b-tree and crit-bit tree.

### The API design & features

For simplicity sake, the types of our key and value will be `uint64_t` and
`PMEMoid` respectively. All of our functions will work inside and outside a
pmemobj transaction. The user will be able to start transaction, do a bulk
insert and then commit that transaction. The user will be also able to insert
various types into the same collection and then leverage the built-in type
information of `PMEMoid` using `OID_INSTANCEOF` macro. Also, since our entire
library is implicitly persistent, the store will never lose data. And all of
this comes at almost no implementation cost, since we will rely heavily on the
libpmemobj.

The header file is available
[here](https://github.com/pmem/pmdk/blob/master/src/examples/libpmemobj/map/map.h).
It's pretty simple, but extending it should be fairly easy.

### Implementation

As I already mentioned, there will be 2 backends with different algorithms.
Those data structures are general knowledge and I won't describe them here. The
implementation itself doesn't not differ too much from what a volatile one would
look like. I suspect that if one removed all the pmemobj specific stuff both
trees would probably work fine as a volatile structures.

#### [Crit-bit Tree](https://cr.yp.to/critbit.html)

This is a very simple to implement data structure whose insert operation, once
the destination node of the new entry is found, does exactly **one** store to
modify the tree. There are no rotations and fancy complicated algorithms going
on here. Why is this important? There's potentially less data in the undo log of
the transaction, one could even write non-transactional version of this data
structure. The remove operation is similarly straightforward. However, it's
important to note that internal nodes do not hold values and there can be a lot
of internal nodes when dealing with non-sparse collection of keys.

The complete implementation can be found
[here](https://github.com/pmem/pmdk/tree/master/src/examples/libpmemobj/tree_map/ctree_map.c).

#### [B-Tree](https://en.wikipedia.org/wiki/B-tree)

A far more popular structure, that, in contrast to the crit-bit, does store
values on internal nodes, and what's more - it stores N of them. Meaning that
it will take far less allocations to store the same amount of entries. This
comes at a cost of considerably more complicated algorithm and far heavier
usage of transactions. And this is exactly why I've chosen B-Trees.

The implementation is available
[here](https://github.com/pmem/pmdk/tree/master/src/examples/libpmemobj/tree_map/btree_map.c).

### The end result

What I've ended up with is a sorted map collection interface with two different
implementations. The API supplements the libpmemobj and integrates with it
seamlessly.
```c++
struct store_item {
    /* data */
};

struct my_root {
    TOID(struct tree_map) map;
};

...

/* create a new collection */
TX_BEGIN(pop) {
    tree_map_new(pop, &D_RW(root)->map);

    /*
     * We don't want an empty collection, so let's insert
     * a few new entries in the same transaction.
     */
    TOID(struct store_item) n;

    n = TX_NEW(struct store_item);
    tree_map_insert(pop, D_RO(root)->map, 5, n.oid);

    n = TX_NEW(struct store_item);
    tree_map_insert(pop, D_RO(root)->map, 10, n.oid);

} TX_END

/* the backend is a tree, and so the collection is sorted */
tree_map_foreach(D_RO(root)->map, /* ... */);

/* all functions can be used outside of a transaction as well */
PMEMoid oid = tree*map_remove(pop, D_RO(root)->map, 5);
/* the object type can be verified */
assert(OID_INSTANCEOF(oid, struct store_item));

tree_map_delete(pop, &D_RW(root)->map);
```

So a fairly standard kv-store. There's a more complete example
[here](https://github.com/pmem/pmdk/blob/master/src/examples/libpmemobj/map/data_store.c).

### Performance

All tests were ran on a ramdisk with [ext4 + DAX](https://github.com/01org/prd)
filesystem. The numbers are only good for comparison between the two data
structures. If you want more meaningful data run the benchmarks for yourself.
Not to mention my measurements weren't very scientific. The order of B-Tree I've
selected is 8.

The first scenario is inserting 1 mln entries with random keys:

| Structure | Outer TX | Time    |
| :-------- | :------- | :------ |
| B-Tree    | Yes      | 2.588s  |
| B-Tree    | No       | 9.812s  |
| Crit-bit  | Yes      | 17.707s |
| Crit-bit  | No       | 17.145s |

So, inserting elements into B-Tree takes significantly less time - this is
because it does far less allocations. But more interestingly, when inserting
all of the entries in a single transaction the performance is way better
for B-Tree. It does make sense, when you think about it for a while. The
benchmark program allocated an entire tree and all of its elements in a single
transaction. This means that there's no reason to keep the undo log for any
modifications - because an abort of such transaction means simply discarding
all of the allocated objects - we didn't modify any existing data. So why the
crit-bit didn't benefit as much? The crit-bit uses `pmemobj_tx_add_range_direct`
that cannot verify if the `PMEMoid` it operates on was allocated in the same
transaction, and thus always creates the (useless) undo log entries. This is one
of the API functions we want to optimize later on, so anomalies like these won't
happen. Another reason why the crit-bit performs worse than B-Tree is because
it creates many very tiny undo log entries (to change a single PMEMoid) which
wastes a lot of memory and time on allocating. This inspired an optimization
proposal to create an undo log cache which will be preallocated for each
transaction and will store undo log entries whose size doesn't exceed a yet to
be determined threshold, this should cut the number of allocations performed by
c-tree in half.

Next scenario is removal of 1 mln entries with random keys:

| Structure | Outer TX | Time    |
| :-------- | :------- | :------ |
| B-Tree    | Yes      | 13.874s |
| B-Tree    | No       | 22.540s |
| Crit-bit  | Yes      | 27.629s |
| Crit-bit  | No       | 24.268s |

And again we see similar pattern emerging, for pretty much the same reasons.

I still believe crit-bit is capable of outperforming B-Tree, but that would
require a slightly less-readable implementation that does not allocate leaf
nodes but instead treats empty internal nodes as leafs, which should bring
quite substantial reduction in allocations. And also, we need to optimize the
`pmemobj_tx_add_range_direct` function to perform similarly to the
`pmemobj_tx_add_range`. I plan on revisiting this topic once we finish
optimizing the library functions, including the allocator.

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

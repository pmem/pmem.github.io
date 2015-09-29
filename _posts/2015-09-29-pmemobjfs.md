---
title: pmemobjfs - The simple FUSE based on libpmemobj
author: plebioda
layout: post
---

### How to use it
The sources of the **pmemobjfs** file system are available
[here](https://github.com/pmem/nvml/tree/master/src/examples/libpmemobj/pmemobjfs). Please refer
to [README](https://github.com/pmem/nvml/blob/master/src/examples/libpmemobj/pmemobjfs/README)
file for instructions on how to create a file system layout and mount it.

**NOTE:** This is just an example implementation of file system in user space using
the **libpmemobj** library and it is **not** considered to be *production quality*.
**Please do not use this file system to store your data you care about
because it may be lost.**

### Layout
The definition of **libpmemobj** layout looks like this:

{% highlight C linenos %}
typedef uint8_t objfs_block_t;

POBJ_LAYOUT_BEGIN(pmemobjfs);
POBJ_LAYOUT_ROOT(pmemobjfs, struct objfs_super);
POBJ_LAYOUT_TOID(pmemobjfs, struct objfs_inode);
POBJ_LAYOUT_TOID(pmemobjfs, struct objfs_dir_entry);
POBJ_LAYOUT_TOID(pmemobjfs, objfs_block_t);
POBJ_LAYOUT_TOID(pmemobjfs, char);
POBJ_LAYOUT_END(pmemobjfs);
{% endhighlight %}

It consists of a *root object* and four *typed OIDs*. The `objfs_block_t` is
a typedef for the `uint8_t` type in order to bind an unique type number for
this data structure. The *typed OID* for a `char` is required in order to
allocate a fixed-length string from **pmemobj** pool. The rest of data
structures are described in details in the following chapters.

### Data structures

#### Superblock

The main data structure of the **pmemobjfs** is the `struct objfs_super` which
plays a role of a super-block in traditional file systems:

{% highlight C linenos %}
struct objfs_super {
	TOID(struct objfs_inode) root_inode;	/* root dir inode */
	TOID(struct tree_map) opened;		/* map of opened files / dirs */
	uint64_t block_size;			/* size of data block */
};
{% endhighlight %}

The `root_inode` field holds the inode object of the root directory which is
created during creation of the file system layout.

The `block_size` field holds the size of data block which the files content and
directory entries are stored in.

The `opened` field is a [tree map]({% post_url 2015-07-31-diy-kvstore %})
of opened inodes. This map is required for handling the **unlink** operation on
opened files.

#### Inode

The next important data structure used by the **pmemobjfs** is the
`struct objfs_inode` which represents a file system object.
{% highlight C linenos %}
struct objfs_inode {
	uint64_t size;	/* size of file */
	uint64_t flags;	/* file flags */
	uint64_t dev;	/* device info */
	uint32_t ctime;	/* time of last status change */
	uint32_t mtime; /* time of last modification */
	uint32_t atime; /* time of last access */
	uint32_t uid;	/* user ID */
	uint32_t gid;	/* group ID */
	uint32_t ref;	/* reference counter */
	union {
		struct objfs_file file; /* file specific data */
		struct objfs_dir dir;	/* directory specific data */
		struct objfs_symlink symlink; /* symlink specific data */
	} d;
};
{% endhighlight %}

It contains basic attributes of an object:

* file type and permissions flags,
* major and minor device numbers,
* time of last status change,
* time of last modification,
* time of last access (currently this field is not updated),
* user ID of owner,
* group ID of owner,
* number of references.

The inode may represent a file, directory or a symbolic link. It contains a
separate structures for each inode type which holds essential information about
the specific type of inode:

##### Directory:
The data specific for directory object contains a doubly-linked list of
directory entries.

{% highlight C linenos %}
struct objfs_dir {
	PDLL_HEAD(struct objfs_dir_entry) entries; /* directory entries */
};
{% endhighlight %}

##### File:

The data specific for file object contains a
[tree map]({% post_url 2015-07-31-diy-kvstore %}) of blocks. The map key consist
of block number and the value contains a **PMEMoid** to the data block.

{% highlight C linenos %}
struct objfs_file {
	TOID(struct tree_map) blocks;	/* blocks map */
};
{% endhighlight %}

##### Symbolic link:

The data specific for symbolic link contains a length of link and the link data.
{% highlight C linenos %}
struct objfs_symlink {
	uint64_t len;	/* length of symbolic link */
	TOID(char) name; /* symbolic link data */
};
{% endhighlight %}

#### Directory entry

The `struct objfs_dir_entry` represents a directory entry. It contains a
persistent pointers to the neighbours, a pointer to corresponding inode and
a name:

{% highlight C linenos %}
struct objfs_dir_entry {
	PDLL_ENTRY(struct objfs_dir_entry) pdll; /* list entry */
	TOID(struct objfs_inode) inode;	/* pointer to inode */
	char name[];			/* name */
};
{% endhighlight %}

The maximum length of the name of a directory entry is forced by the block size
specified when creating a file system. It is equal to
`block_size - sizeof (struct objfs_dir_entry)`.

### Operations

All operations which modifies the file system structure are performed within a
transaction, which protects the **pmemobjfs** layout from being broken if power
failure occurred during any operation.

In this chapter I would like to describe in details some of the most important
operations performed on the file system.

**NOTE:** In current implementation it is recommended to mount the **pmemobjfs**
with the *-s* option. In this case the **FUSE** works in single-threaded mode
and there is no need for synchronization mechanisms. 

#### Creating file system layout

To create the **pmemobjfs** layout you can use the `mkfs.pmemobjfs` command:

```
mkfs.pmemobjfs -s <size> -b <block size> /mnt/pmem/pmemobjfs.obj
```

By default it creates a file system layout with the minimal size required for
**pmemobj** pool and with block size equal to `512 - 64`. The default value for
block size is chosen to such value in order to minimize the internal
fragmentation of allocated blocks. We must keep in mind the fact that in current
implementation the allocation and out-of-band headers are kept in one cache
line before the allocation. Although the default value is chosen with respect
to the internal layout of the **pmemobj** pool, it is not required to keep it in
mind when creating the file system. An arbitrary value specified for the block
size is valid and the **pmemobjfs** will work properly.

The file system layout is created within a transaction. The following listing
shows the most important parts of the routing for creating the **pmemobjfs**
layout:

{% highlight C linenos %}
...
objfs->pop = pmemobj_create(fname,
			POBJ_LAYOUT_NAME(pmemobjfs), size, mode);
...
TOID(struct objfs_super) super = POBJ_ROOT(objfs->pop, struct objfs_super);
...
TX_BEGIN(objfs->pop) {
	TX_ADD(super);

	/* create an opened files map */
	tree_map_new(objfs->pop, &D_RW(super)->opened);

	/* create root inode, inherit uid and gid from current user */
	D_RW(super)->root_inode =
		pmemobjfs_new_dir(objfs, TOID_NULL(struct objfs_inode),
				"/", root_flags, uid, gid);

	D_RW(super)->block_size = bsize;
} TX_ONABORT {
	fprintf(stderr, "error: creating pmemobjfs aborted\n");
	ret = (-ECANCELED);
} TX_END
...
pmemobj_close(objfs->pop);
...
{% endhighlight %}

At the beginning the **pmemobj** pool is created with specified name of layout,
size and mode. Next the *root object* is allocated when calling the `POBJ_ROOT`
macro for the first time. According to the documentation we can be sure the
root object is zeroed. Next the *root object* is initialized within a
transaction. The *tree map* is created for opened inodes, the root inode is
created and the block size is stored. Due to the fact that all operations
are performed within the transaction we can be sure that either the
*root object* will be filled up entirely or won't be at all. At the very end the
**pmemobj** pool is closed and as a result we have a **pmemobjfs** file system
layout initialized.

#### Creating new directory

The following listing presents the most important operations performed when
creating new directory on **pmemobjfs** file system:

{% highlight C linenos %}
...
TX_BEGIN(objfs->pop) {
	TOID(struct objfs_inode) new_inode =
		pmemobjfs_new_dir(objfs, inode, name, flags, uid, gid);

	TOID(struct objfs_dir_entry) entry =
		pmemobjfs_dir_entry_alloc(objfs, name, new_inode);

	pmemobjfs_add_dir_entry(objfs, inode, entry);

	TX_ADD_FIELD(inode, mtime);
	D_RW(inode)->mtime = time(NULL);
} TX_ONABORT {
	ret = (-ECANCELED);
} TX_END
...
{% endhighlight %}

After beginning a new transaction the new directory is allocated and
initialized. After creating the inode with new directory, the
`struct objfs_dir_entry` is allocated with the specified name and associated
newly created inode. The new directory entry is then added to the current
directory's doubly-linked list of entries and modification time is updated.

The `pmemobjfs_new_dir` function is presented on the following listing:

{% highlight C linenos %}
TX_BEGIN(objfs->pop) {
	inode = pmemobjfs_inode_alloc(objfs, flags, uid, gid, 0);

	pmemobjfs_inode_init_dir(objfs, inode);

	/* add . and .. to new directory */
	TOID(struct objfs_dir_entry) dot =
		pmemobjfs_dir_entry_alloc(objfs, ".", inode);
	TOID(struct objfs_dir_entry) dotdot =
		pmemobjfs_dir_entry_alloc(objfs, "..", parent);

	pmemobjfs_add_dir_entry(objfs, inode, dot);
	pmemobjfs_add_dir_entry(objfs, inode, dotdot);
} TX_ONABORT {
	inode = TOID_NULL(struct objfs_inode);
} TX_END

return inode;
{% endhighlight %}

First of all the new inode is allocated with specified permissions and
ownership and the directory specific data of inode is initialized.
Next the current and parent directory entries are allocated and added to the
newly created directory. Everything is done within a transaction. In this case
the transaction will be nested because this function is called from inside
other transaction, but according to the **libpmemobj** documentation if the
outer transaction aborts all changes made within a nested transaction will be
rolled back as well so we do not need to worry about committing the nested
transaction before committing the outermost one.

#### Allocating file blocks

The next interesting operation is allocating the file blocks. The following
listing shows how it is implemented:

{% highlight C linenos %}
TX_BEGIN(objfs->pop) {
	/* allocate blocks from requested range */
	uint64_t b_off = offset / objfs->block_size;
	uint64_t e_off = (offset + size) / objfs->block_size;
	for (uint64_t off = b_off; off <= e_off; off += 1)
		pmemobjfs_file_get_block_for_write(objfs, inode, off);

	time_t t = time(NULL);
	/* update modification time */
	TX_ADD_FIELD(inode, mtime);
	D_RW(inode)->mtime = t;

	/* update status change time */
	TX_ADD_FIELD(inode, ctime);
	D_RW(inode)->ctime = t;

	/* update inode size */
	D_RW(inode)->size = offset + size;
	TX_ADD_FIELD(inode, size);
} TX_ONABORT {
	ret = (-ECANCELED);
} TX_END
{% endhighlight %}

The most important function is `pmemobjfs_file_get_block_for_write` which
either allocates new block or returns previously allocated block. In the latter
case the previously allocated block is added to the transaction's undo log in
order to track all file's modifications. The following listing shows the
implementation of this function:

{% highlight C linenos %}
TOID(objfs_block_t) block =
	pmemobjfs_file_get_block(objfs, inode, offset);
if (TOID_IS_NULL(block)) {
	TX_BEGIN(objfs->pop) {
		block = TX_ALLOC(objfs_block_t,
				objfs->block_size);
		tree_map_insert(objfs->pop, D_RW(inode)->file.blocks,
				GET_KEY(offset), block.oid);
	} TX_ONABORT {
		block = TOID_NULL(objfs_block_t);
	} TX_END
} else {
	TX_ADD(block);
}

return block;
{% endhighlight %}

The `pmemobjfs_file_get_block` function returns a block at given offset or
returns `OID_NULL` if the block is missing.
The `pmemobjfs_file_get_block_for_write` and `pmemobjfs_file_get_block`
functions are used in **write** and **read** operations respectively when operating
on file's data.

#### Unlinking inode

The **unlink** operation utilizes two interesting mechanisms implemented with
the **pmemobjfs**. The first one is the inode's reference counter which is
increased each time the given inode is referenced by other data structure.
The inode is freed when the reference counter is equal to zero. The functions
which operates on inode's reference counter are `pmemobjfs_inode_get` and
`pmemobjfs_inode_put`. 

The **unlink** operation is really simple:

{% highlight C linenos %}
TX_BEGIN(objfs->pop) {
	pmemobjfs_remove_dir_entry(objfs, inode, entry);

	TX_ADD_FIELD(inode, size);
	D_RW(inode)->size--;
} TX_ONABORT {
	ret = (-ECANCELED);
} TX_END
{% endhighlight %}

All the work is performed by the `pmemobjfs_remove_dir_entry` function:

{% highlight C linenos %}
TX_BEGIN(objfs->pop) {
	pmemobjfs_inode_put(objfs, D_RO(entry)->inode);

	PDLL_REMOVE(D_RW(inode)->dir.entries, entry, pdll);

	pmemobjfs_dir_entry_free(objfs, entry);
} TX_END
{% endhighlight %}

The reference counter is decreased and the directory entry is removed from
the doubly-linked list of current directory and freed. The inode is freed if the
reference counter becomes zero after calling the `pmemobjfs_inode_put` function.

In case of unlinking an opened file the inode will not be freed immediately
because the *open* operation increases the inode's reference counter and
adds the inode to the *tree map* of opened inodes:

{% highlight C linenos %}
TX_BEGIN(objfs->pop) {
	/* insert inode to opened inodes map */
	tree_map_insert(objfs->pop, D_RW(super)->opened,
			inode.oid.off, inode.oid);
	/* hold inode */
	pmemobjfs_inode_get(objfs, inode);
} TX_ONABORT {
	ret = (-ECANCELED);
} TX_END
{% endhighlight %}

Using those two mechanism it is really simple to implement the **unlink**
operations with respect to opened files or directories and creating hard links.

Please note that hard links are not implemented currently due to some problems
with the *FUSE* kernel module which cause the appropriate callback function
is not called.

### Transactions

The **pmemobjfs** provides a feature of creating transactions. The current
implementation is limited to creating a single transaction at a time for the
whole file system, but this feature could be extended to more transactions, for
specified directories or files. The transaction is controlled via the ioctl
calls. For simplicity there have been developed three simple commands which do
the required work:

{% highlight sh %}
pmemobjfs.tx_begin
pmemobjfs.tx_abort
pmemobjfs.tx_end
{% endhighlight %}

For the above commands the path to the **pmemobjfs** mount point or any other
directory must be given. After beginning the transaction all modifications
performed on the file system files, directories or links are tracked by the
**libpmemobj** transactions. It tracks all changes of attributes and data.
They are made persistent after calling the `pmemobjfs.tx_commit` command.
All changes are visible immediately to the user but can be rolled back simply by
calling the `pmemobjfs.tx_abort` command. The transaction can be aborted
implicitly if any exceptional situation occurred like for example out of memory
error when allocating file block.

**NOTE:** Aborting the transaction when other process is still working on
the file system may lead to undefined behavior. For example if a new file
was created within a transaction and the transaction is aborted while some
other process is writing to the file leads to undefined behavior.

### Performance results

In this section I would like to present some performance tests results executed
using the **fio** utility with the following configuration file:

{% highlight INI %}
[job1]
ioengine=sync
runtime=60
time_based=1
filesize=128M
bs=448
rw=randrw
{% endhighlight %}

The block size value has been chosen in order to minimize internal
fragmentation on **pmemobjfs** file system.

The tests were run on **Fedora 22** distribution, kernel version 4.2.0
with **DAX** support and on the **pmem** block device.

The tests were run on the following file systems:

* ext4 + dax
* fusexmp_fh + ext4 + dax
* pmemobjfs
* pmemobjfs (NTB)

The *pmemobjfs (NTB)* is a **pmemobjfs** version without tracking file blocks
(PMEMOBJFS_TRACK_BLOCKS=0).
The *fusexmp_fh* is a file system which redirects all operations to the
root file system. It is available in the [FUSE](http://sourceforge.net/projects/fuse/)
examples.

The results are presented in the following table:

|            FS          |READ BW [KB/s]|WRITE BW [KB/s]|
|------------------------|--------------|---------------|
|ext4 + dax              |    232030    |    231333     |
|fusexmp_fh + ext4 + dax |     28687    |     28602     |
|pmemobjfs               |     29120    |     29034     |
|pmemobjfs (NTB)         |     30112    |     30023     |

The results shows quite huge overhead from the **FUSE** itself, but it shows
that **pmemobjfs** has slightly better performance than the **fusexmp_fh**
example file system which is quite good information for us :).

### Summary
The **pmemobjfs** example shows how the **libpmemobj** API works in a real
application. It can be used to run some performance tests using well known
file system test suites. If you have any questions or ideas for improvement
of the **pmemobjfs** please feel free to join a discussion on our
[Google Group](http://groups.google.com/group/pmem).


---
# Blog post title
title: 'An introduction to pmemobj (part 3) - types'

# Blog post creation date
date: 2015-06-16T19:55:17-07:00

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
blogs: ['libpmemobj']

tags: []

# Redirects from old URL
aliases: ['/2015/06/16/types.html']

# Blog post type
type: 'post'
---

In all of the previous post the code snippets and examples had persistent pointers (PMEMoid) without any type information - they were simple C structures. Very early in the development of the library we discovered that using something like that was extremely error-prone and generally difficult. That's why [considerable effort](/blog/2015/06/type-safety-macros-in-libpmemobj/) was put into encapsulating the PMEMoids with type-safe container. The end result can be compared with how `shared_ptr` and the like are done in C++11. All posts after this one will solely use the type-safety features.

### Layout declaration

All persistent memory programs that use pmemobj should have a clearly defined memory layout, preferably in its own file. To provide run- and compile- time type-safety the use of special macros is required in addition to declaring structures. For example, a layout for our string storing example would look like this:

{{< highlight C "linenos=table" >}}
POBJ_LAYOUT_BEGIN(string_store);
POBJ_LAYOUT_ROOT(string_store, struct my_root);
POBJ_LAYOUT_END(string_store);

#define MAX_BUF_LEN 10
struct my_root {
char buf[MAX_BUF_LEN];
};
{{< /highlight >}}

Thanks to this you can now use typed persistent pointers in your code. The `string_store` in this code is just a name. When creating or opening a pool with certain layout we recommend using `POBJ_LAYOUT_NAME` macro, like so:

{{< highlight C "linenos=table" >}}
pmemobj_create(path, POBJ_LAYOUT_NAME(string_store), PMEMOBJ_MIN_POOL, 0666);
...
pmemobj_open(path, POBJ_LAYOUT_NAME(string_store));
{{< /highlight >}}

If you find all of this confusing, please read [this](/blog/2015/06/type-safety-macros-in-libpmemobj) first - it's an in-depth explanation of the subject matter.

### Typed persistent pointer

Instead of PMEMoids for all of the pointers, you should now use the following construct:

{{< highlight C "linenos=table" >}}
TOID(struct my_root) root;
{{< /highlight >}}

To dereference this you no longer have to use another variable in conjunction with `pmemobj_direct`, a preferred way is to use `D_RW` for writing and `D_RO` for reading. Like this:

{{< highlight C "linenos=table" >}}
if (D_RO(root)->buf[0] != 0)
D_RW(root)->buf[0] = 0;
{{< /highlight >}}

Most IDEs correctly evaluate those macros and automatic code completion for types works.

### PMEMoid and TOID operations

Generally, two kinds of type-safety macros are distinguished: those that operate on raw `PMEMoid` - prefixed with `OID_`, and those that operate on typed `TOID` - prefixed with `TOID_`. All of the `pmemobj_` functions take only raw PMEMoids as arguments. We generally recommend using only macros, but if you ever need to 'cast' TOID to PMEMoid, you can do it like so:

{{< highlight C "linenos=table" >}}
TOID(struct foo) data;
pmemobj_direct(data.oid);
{{< /highlight >}}

All of the macros that are not prefixed with either `TOID_` or `OID_` generally take typed pointers and return them as their result (like the `POBJ_ROOT` macro).

### Run-time type-safety

Each type in a layout is internally assigned a unique number that can be then used for verification. For instance, an update to existing software may have changed the layout like so:

{{< highlight C "linenos=table" >}}
struct my_root_v1 {
TOID(struct foo) data;
}
{{< /highlight >}}

---

{{< highlight C "linenos=table" >}}
struct my_root_v2 {
TOID(struct bar) data;
}
{{< /highlight >}}

To check whether your version of the layout corresponds with the existing objects, you can use following expression:

{{< highlight C "linenos=table" >}}
if (TOID*VALID(D_RO(root)->data)) {
/* can use the data ptr safely _/
} else {
/_ declared type doesn't match the object \_/
}
{{< /highlight >}}

You can also rely on the embedded type number if you are unsure of the object type, like so:

{{< highlight C "linenos=table" >}}
PMEMoid data;
TOID(struct foo) foo;
TOID(struct bar) bar;
if (OID*INSTANCEOF(data, struct foo)) {
TOID_ASSIGN(foo, data);
} else if (OID_INSTANCEOF(data, struct bar)) {
TOID_ASSIGN(bar, data);
} else {
/* error \_/
}
{{< /highlight >}}

Similarities to high-level languages are not accidental.

### Example

This is the last time we are going to modify the string store example. The `layout.h` modifications can be seen above. First, let's start with the root object. Instead of first using the `pmemobj_root` function and then `pmemobj_direct` for the actual pointer, we can use the following line:

{{< highlight C "linenos=table" >}}
TOID(struct my_root) root = POBJ_ROOT(pop, struct my_root);
{{< /highlight >}}

Remember how I promised that the code will get even shorter? Here you go, `writer.c`:

{{< highlight C "linenos=table" >}}
TX_BEGIN(pop) {
TX_MEMCPY(D_RW(root)->buf, buf, strlen(buf));
} TX_END
{{< /highlight >}}

Because we don't have the `rootp` anymore, this one also becomes simpler, `reader.c`:

{{< highlight C "linenos=table" >}}
printf("%s\n", D_RO(root)->buf);
{{< /highlight >}}

As always, the example is available in the [repository](https://github.com/pmem/pmdk/tree/master/src/examples/libpmemobj).

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

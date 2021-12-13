---
# Blog post title
title: 'An introduction to pmemobj (part 4) - transactional dynamic memory allocation'

# Blog post creation date
date: 2015-06-17T19:55:17-07:00

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
blogs: ['pmemobj']

tags: []

# Redirects from old URL
aliases: ['/2015/06/17/tx-alloc.html']

# Blog post type
type: 'post'
---

This is a topic I intentionally avoided not to introduce too much complexity too fast. The pmemobj library contains an implemented from scratch memory allocator, that was designed with persistent memory in mind. There are two separate APIs: non-transactional and transactional.

### Transactional allocations

Let's start with a simple snippet of volatile code:

{{< highlight C "linenos=table" >}}
struct rectangle {
int a;
int b;
};

int area*calc(const struct rectangle \_rect) {
return rect->a * rect->b;
}

...
struct rectangle *rect = malloc(sizeof *rect);
if (rect == NULL) return;
rect->a = 5;
rect->b = 10;
int p = area*calc(rect);
/* busy work \_/
free(rect):
{{< /highlight >}}

You should know how to modify this code for persistent memory by now, with two exceptions - `malloc` and `free`. Let's start by declaring the layout:

{{< highlight C "linenos=table" >}}
/_ struct rectangle doesn't change _/

struct my_root {
TOID(struct rectangle) rect;
};

POBJ_LAYOUT_BEGIN(rect_calc);
POBJ_LAYOUT_ROOT(rect_calc, struct my_root);
POBJ_LAYOUT_TOID(rect_calc, struct rectangle);
POBJ_LAYOUT_END(rect_calc);
{{< /highlight >}}

Notice two different macros for the root object and all other structures.

The `area_calc` function has to change to use a persistent pointer:

{{< highlight C "linenos=table" >}}
int area_calc(const TOID(struct rectangle) rect) {
return D_RO(rect)->a \* D_RO(rect)->b;
}
{{< /highlight >}}

The `const` qualifier in front of the TOID means that you are not allowed to use `D_RW` on this object, it won't compile.

The rectangle object will be allocated and initialized in a transaction. And because this is persistent memory, you can't allocate objects without having some way of getting to them after application restart - for this purpose we will use the `rect` variable of the root object.

{{< highlight C "linenos=table" >}}
TOID(struct my*root) root = POBJ_ROOT(pop);
TX_BEGIN(pop) {
TX_ADD(root); /* we are going to operate on the root object \_/
TOID(struct rectangle) rect = TX_NEW(struct rectangle);
D_RW(rect)->x = 5;
D_RW(rect)->y = 10;
D_RW(root)->rect = rect;
} TX_END

int p = area*calc(D_RO(root)->rect);
/* busy work \_/
{{< /highlight >}}

There is only one new thing here, the `TX_NEW` macro. It simply allocates the memory block with `sizeof(T)` bytes and returns a `TOID(T)` - so you can assign it only to the correct type. If you want to specify the size of the object yourself (for arrays and things) you can use `TX_ALLOC` and for zeroed memory you can use the `Z` prefixed variants. It's also important to note that all new objects are automatically persisted on commit - don't call pmemobj_persist on it yourself - as a general rule you don't need to persist any memory inside a transaction. You might also not recognize the `TX_ADD`, but it's just `pmemobj_add_range` is disguise. After we do our busy work with this object we want to deallocate it, here's how to do it:

{{< highlight C "linenos=table" >}}
TX_BEGIN(pop) {
TX_ADD(root);
TX_FREE(D_RW(root)->rect);
D_RW(root)->rect = TOID_NULL(struct rectangle);
} TX_END
{{< /highlight >}}

This also has to be inside a transaction because it's a two-step operation. It's highly unlikely you will ever want to leave a freed pointer with its old value, that's why you have to remember about the NULL assignment. And yes, it's a typed NULL ;) There are two alternatives:

{{< highlight C "linenos=table" >}}
D_RW(root)->rect.oid = OID_NULL;
{{< /highlight >}}
or

{{< highlight C "linenos=table" >}}
TOID_ASSIGN(D_RW(root)->rect, OID_NULL);
{{< /highlight >}}

There is no functional difference, so the choice boils down to personal preference.

The usage of transactional allocations resembles the way you would normally write programs, but adds overhead of tracking all the changes. In the next part of the tutorial we will learn how to avoid that overhead with the non-transactional API. For now, as a homework, try creating an actual application based on the rectangle example and play around with it for a while, exploring the transactional API.

### For keen readers

If you have been reading carefully, you should be able tell why the following function is not optimal:

{{< highlight C "linenos=table" >}}
void rectangle_modify(TOID(struct rectangle) rect, int new_a, int new_b) {
TX_BEGIN(pop) {
TX_SET(rect, a, new_a);
TX_SET(rect, b, new_b);
} TX_END
}
{{< /highlight >}}

Try writing your own, better, version of this function. Don't hesitate to look into the `libpmemobj.h` header to see how the `TX_SET` macro expands.

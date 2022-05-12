---
# Blog post title
title: 'An introduction to pmemobj (part 7) - persistent lists'

# Blog post creation date
date: 2015-06-19T19:55:17-07:00

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
aliases: ['/2015/06/19/lists.html']

# Blog post type
type: 'post'
---

The pmemobj library provides non-transactional persistent atomic circular doubly-linked lists (or NTPACDLL for short) API with an interface familiar to anyone who have ever included `sys/queue.h` header file - it's in fact so similar that I considered not writing this post at all, you can just search the web for `CIRCLEQ` example.

Fun fact: The exact same list code is used internally by libpmemobj in the transaction undo log implementation.

### Declaring a list

Let's start by defining the structure of the thing we want on the list:

{{< highlight C "linenos=table" >}}
struct note {
time_t date_created;
POBJ_LIST_ENTRY(struct note) notes;
char msg[];
};
{{< /highlight >}}

It's a pmem notepad :) All of the notes are going to be stored in the root object:

{{< highlight C "linenos=table" >}}
struct my_root {
POBJ_LIST_HEAD(plist, struct note) head;
};
{{< /highlight >}}

The head of this list does not have to be initialized (maybe you have noticed that this is a recurring theme throughout the library, not initializing things that is).

### Inserting new list entries

You can either insert an existing object to a list (`POBJ_LIST_INSERT`) or allocate directly to the list (`POBJ_LIST_INSERT_NEW`). You can also insert the entries anywhere in the list you want thanks to the self-explanatory `_HEAD`, `_TAIL`, `_AFTER` and `_BEFORE` macro variants.

Continuing our example, we want to add a new entry to the head of the list:

{{< highlight C "linenos=table" >}}
int note_construct(PMEMobjpool *pop, void *ptr, void *arg) {
struct note *n = ptr;
char \*msg = arg;

    pmemobj_memcpy_persist(pop, n->msg, msg, strlen(msg));

    time(&n->date_created);
    pmemobj_persist(pop, &n->date_created, sizeof time_t);

    return 0;

}

void create_note(char \*msg) {
TOID(struct my_root) root = POBJ_ROOT(pop, struct root);

    size_t nlen = strlen(msg);
    POBJ_LIST_INSERT_NEW_HEAD(pop, &D_RW(root)->head, notes,
    			  sizeof(struct note) + nlen,
    			  note_construct, msg);

}
{{< /highlight >}}

Quite a lot happening here. You should remember the constructor from the non-transactional allocation API. Also worth noting is the allocation of flexible array in the `struct note`.

### Accessing list entries

Reading things from the list is done using: `POBJ_LIST_FIRST`, `POBJ_LIST_LAST`, `POBJ_LIST_EMPTY`, `POBJ_LIST_NEXT` and `POBJ_LIST_PREV`. They pretty much do what it says in the name.

Let's write functions to read our notes:

{{< highlight C "linenos=table" >}}
static TOID(struct note) current_note;

void note_read_init() {
TOID(struct my_root) root = POBJ_ROOT(pop, struct root);

    current_note = POBJ_LIST_FIRST(&D_RO(root)->head);

}

void note_read_next() {
current_note = POBJ_LIST_NEXT(current_note, notes);
}

void note_read_prev() {
current_note = POBJ_LIST_PREV(current_note, notes);
}

void note_print(const TOID(struct note) note) {
printf("Created at %s: \n %s\n",
ctime(D_RO(note)->date_created),
D_RO(note)->msg);
}
{{< /highlight >}}

I'll leave it up to the reader to correctly use these functions in a CLI/GUI.

### Iterating through the list

You can iterate a list in a similar fashion it's done for internal collections. There are two macros: `POBJ_LIST_FOREACH` and `POBJ_LIST_FOREACH_REVERSE`. No magic here.

Reading all the notes:

{{< highlight C "linenos=table" >}}
void note_print_all() {
TOID(struct my_root) root = POBJ_ROOT(pop, struct root);

    TOID(struct note) iter;
    POBJ_LIST_FOREACH(iter, &D_RO(root)->head, notes) {
    	note_print(iter);
    }

}
{{< /highlight >}}

### Removing entries

Just like you can insert an existing object or a new one, you can just remove entry from the list (`POBJ_LIST_REMOVE`) or remove and free it (`POBJ_LIST_REMOVE_FREE`).

Removing current note:

{{< highlight C "linenos=table" >}}
void note_remove() {
TOID(struct my_root) root = POBJ_ROOT(pop, struct root);

    POBJ_LIST_REMOVE_FREE(pop, &D_RO(root)->head,
    		      current_note, notes);

}
{{< /highlight >}}

### Moving entries between lists

This is something new, `POBJ_LIST_MOVE_ELEMENT` allows you to move an object from one list to another. A good use case example can be found in the [PI](https://en.wikipedia.org/wiki/Leibniz_formula_for_%CF%80) code [here](https://github.com/pmem/pmdk/tree/master/src/examples/libpmemobj). It's used there as a task queue and once worker thread completes a task is then moved from **todo** list to **done** list.

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK](/blog/2017/12/announcing-the-persistent-memory-development-kit).]

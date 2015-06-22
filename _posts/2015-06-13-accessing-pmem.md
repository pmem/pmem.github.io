---
title: An introduction to pmemobj (part 1) - accessing the persistent memory
author: pbalcer
layout: post
---

In the previous post, you learned a little bit about the general concept of the persistent memory programming model, now it's time to start the coding ;)

### Memory pools

If you've read the [NVML overview]({% post_url 2014-09-01-nvm-library-overview %}) you know that persistent memory is exposed by the OS as memory-mapped files, we call them pools. 

The pmemobj library provides an interface to easily manage those pools, so that you don't have to manually create the files or `mmap` them. Creating a pool is done using the `pmemobj_create` API function, which takes the usual parameters you would expect for a function creating a file plus a `layout`, which is a string of your choosing that identifies the pool. It is required that the `layout` you pass to `pmemobj_open` matches the one the pool was created with. As with any other OS resource, you have to release the pool using `pmemobj_close` when the persistent memory pool is no longer needed, usually at the end of the application. To verify the integrity of the pool there's a `pmemobj_check` function that verifies if all the required metadata is consistent.

### Persistent pointers

Now that we have the memory region mapped, how can one access it? Let's think about regular pointers for a second. Boiling it down to the very basics, a pointer is a number of bytes between the start of the virtual address space to the beginning of the thing it points to. And now to translate this to persistent memory. Note that you can have more than one pool open in one application, the persistent pointer is twice the size of a regular pointer and contains the offset from the start of the pool (not the VAS) and unique id of the pool. The structure itself looks like this:

	typedef struct pmemoid {
		uint64_t pool_uuid_lo;
		uint64_t off;
	} PMEMoid;

If you know the virtual address the pool is mapped at, a simple addition can be performed to get the **direct** pointer, like this: `(void *)((uint64_t)pool + oid.off)` and this is exactly what the `pmemobj_direct` does, it takes the `PMEMoid` (persistent pointer) and turns it into a regular one that can be dereferenced. The pool id is used to figure out where the pool is currently mapped (because the actual address of the memory mapped region can be different each time you start your application). How exactly does the *figuring out* work? All open pools are stored in a [cuckoo hash table](http://en.wikipedia.org/wiki/Cuckoo_hashing) with 2 hashing functions, so it means that when you call `pmemobj_direct` a maximum of two table lookups will happen to locate the pool address. 

### The root object

Think about following scenario:

	- allocate a block of persistent memory (let's assume a malloc-like interface)
	- write a string to it
	- close the application

How do you locate the pointer which contains your string? The data you want will be somewhere in the pool, but apart from scanning the entire file for matching characters you can't really find it. You could, for example, pick a random offset into the pool at treat it as a known address. But that would be wrong, like writing randomly in the virtual address space wrong - it would most likely unintentionally overwrite something. The known location you can always look for in the memory pool is the root object. It's the anchor to which all the memory structures can be attached. In a case where all you really need is one, not dynamically changing, data structure you can just solely rely on the root object. The `size` in the `pmemobj_root` function is the size of the structure you want as root object, so typically you might want to write something like this:

	PMEMoid root = pmemobj_root(pop, sizeof (struct my_root));

The root object is initially zeroed, so there is no need to worry about initialization. Also, if you want to resize your object, you are free to do so just by passing different size to the function - so when you add a new variable to your structure there's no need to worry. The new region will also be initially zeroed. Keep in mind that the root object is allocated from the pool and when an in-place reallocation is impossible a new object will be created with a different offset, so don't store the root persistent pointer anywhere without **really** thinking it through.

### Safely storing data
All of the previous information was about *where* to store data, it's time to learn *how*. Consider the following example:

	1: void set_name(const char *my_name) {
	2: 	memcpy(root->name, my_name, strlen(my_name));
	3: }	

This would be a perfectly valid code if the `root` variable were volatile, but if it's persistent the outcome of this function is not deterministic. Remember that the memory survives application crashes. When creating programs that write to persistent memory we have to be extra careful to make sure that the application is always in a state we can recognize and use, regardless of the exact moment is was interrupted - don't assume that your application will always gracefully exit, that might be the 99% case but when something unexpected happens you may end up with unrecoverable application state and lose all your data. Ok, let's get back to the code. Let's assume that we can recognize a zeroed state of the `root->name` variable, so if the application crashes before the `memcpy` started all is good. What happens when the application crashes somewhere in the middle of the copying? Well, if your name is Brianna, the actually stored value may be Brian - which is perfectly valid, however not what we wanted. Surely after the copying has finished the program can be interrupted without issues? Well... no. You also have to consider CPU caches and the order in which they are flushed. In this case, your name might become *anna* if the *Bri* part happens to be on a different cacheline that didn't get flushed in time. So, how to fix all that?

	1: void set_name(const char *my_name) {
	2: 	root->length = strlen(my_name);
	3: 	pmemobj_persist(&root->length, sizeof (root->length));
	4: 	pmemobj_memcpy_persist(root->name, my_name, root->length);
	5: }

Notice that here we store the length of the buffer before copying, so when reading we can double-check if the name is correct. The `_persist` suffixed functions make sure that the range of memory they operate on is flushed from the CPU and safely stored on the medium, whatever that might be. So, at line 4 we are 100% sure that the `root->length` contains what we want. The pmemobj library has way more convenient methods of doing this, like transactions, but knowing the basics can help in understanding the more advanced techniques. But no need to worry - I'll write the exact same example in at least two different ways later on in the series.

The fundamental principle is that, on the current hardware architecture, only 8 bytes of memory can be written in an **atomic** way. So something like this is correct:

	root->u64var = 123;
	pmemobj_persist(&root->u64var, 8);

But following is not:

	root->u64var = 123;
	root->u32var = 321;
	pmemobj_persist(&root->u64var, 12);

And that's the gist of the persistent memory programming.

### Example
Now that we have learned some valuable knowledge, let's put it to use. Remember the string example I've talked previously? Seems like a good point to start. As a reminder: we will write 2 applications, one that writes a string to memory and one that reads that exact same string - but only if it was properly written.

For both of the programs, we will need this set of includes:

	#include <stdio.h>
	#include <string.h>
	#include <libpmemobj.h>
	#include "layout.h"

As a general rule you don't need **libpmem** when using **libpmemobj**, the latter provides all the required functionality. The `layout.h` file has the declaration of stuff we will need for both `.c` files:
	
	#define LAYOUT_NAME "intro_0" /* will use this in create and open */
	#define MAX_BUF_LEN 10 /* maximum length of our buffer */
	
	struct my_root {
		size_t len; /* = strlen(buf) */
		char buf[MAX_BUF_LEN];
	};

First, we will create `writer.c` which will do the first part of the work:

	int main(int argc, char *argv[])
	{
		PMEMobjpool *pop = pmemobj_create(argv[1], LAYOUT_NAME, PMEMOBJ_MIN_POOL, 0666);
		if (pop == NULL) {
			perror("pmemobj_create");
			return 1;
		}

		...

		pmemobj_close(pop);
		return 0;
	}

Here we create the pool file with the name from the first argument. Don't forget to use proper file mode in `pmemobj_create` or you will end up with pool you cannot open or modify.

	PMEMoid root = pmemobj_root(pop, sizeof (struct my_root));
	struct my_root *rootp = pmemobj_direct(root);

Next we request the root object and translate it to a usable, direct pointer. Because this is done just after creating the pool we can be sure that the `struct my_root` pointed to by `root` is zeroed.

	char buf[MAX_BUF_LEN];
	scanf("%9s", buf);
	
We read maximum of 9 bytes to the temporary buffer.

	root->len = strlen(buf);
	pmemobj_persist(pop, &rootp->len, sizeof (rootp->len));
	pmemobj_memcpy_persist(pop, rootp->buf, my_buf, rootp->len);

And we write this buffer to persistence. This snippet should be clear as day by now. 

It's time for the `reader.c`, the code is very similar up to the scanf line. 

	int main(int argc, char *argv[])
	{
		PMEMobjpool *pop = pmemobj_open(argv[1], LAYOUT_NAME);
		if (pop == NULL) {
			perror("pmemobj_open");
			return 1;
		}
	
		PMEMoid root = pmemobj_root(pop, sizeof (struct my_root));
		struct my_root *rootp = pmemobj_direct(root);
	
		...
	
		pmemobj_close(pop);
	
		return 0;
	}

This time when we open the pool, the root object will not be zeroed - it will contain whatever string the writer was tasked with storing. So, to read it:

	if (root->len == strlen(rootp->buf))
		printf("%s\n", rootp->buf);

You should now be able to compile both applications and verify that they do what was advertised. If you want to check that it works for all the error-cases, we have a [tool](https://github.com/pmem/valgrind) for that, but it's a topic for a completely different tutorial ;)

The complete source code for this example (and more) can be found in [our repository](https://github.com/pmem/nvml/tree/master/src/examples/libpmemobj).


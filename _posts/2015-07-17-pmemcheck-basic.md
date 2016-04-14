---
title: An introduction to pmemcheck (part 1) - basics
author: tomaszkapela
layout: post
identifier: pmemcheck_01
---

As you probably noticed from the previous posts, persistent memory programming isn't really that easy. There are a couple of things you have to consider - data consistency being the most important one. The contemporary x86_64 architecture supports at most 8-byte atomic stores. You probably know by now, that by atomic I mean non-torn and not thread-safe. This means that you can be confident that you will not get 4 out of the 8 bytes with the new value and the rest will not be updated. However, there aren't many real-life programs that think in 8 byte chunks of data. Therefore [NVML][c5f40b9e] resorts to **non-trivial tricks** to ensure larger portions of memory can be updated in a failsafe manner.

Since our libraries are quite complicated, we needed a way to check for possible issues with persistent memory usage. As the persistent memory programming concept is fairly new, there aren't any free, widely available tools, which could help us with this task. This is why we came up with the idea to write one ourselves. We ruled out static analysis, because keeping track of pointers to persistent memory would be really tedious (we don't have any language additions for them). This leaves us with dynamic, runtime analysis. After some pathfinding we decided to use Valgrind, mostly because of its popularity and familiarity within the C/C++ developer community. Another very important factor was that Valgrind enables us to recognize and instrument at instruction level. As such we plan to add automatic support for flushes and memory barriers (PCOMMIT included) - at least for the x86_64 architecture. This is in short how the **pmemcheck** side-project started.

We wanted the tool to be as flexible as possible, so that it could be used with the current and future implementations of different libraries using persistent memory. Because of this, the tool relies heavily on information from the instrumented application. The information is fed through a built-in Valgrind mechanism ([CRM][5428585d]) with a set of predefined macros. I won't go into details of the implementation as it is available on github and you can take a look at both the [documentation][d324cfe0] and the [code][655a3db3] (hopefully contribute or make feature requests). The idea is: we track all stores you make to persistent memory and inform you of possible memory violations. Let's dive into some simple examples, because that's usually the easier way to learn things.

### Basic usage

Because our tool is still a prototype, it is only available on [github][655a3db3]. Once you get the tree, you have to manually build and install it. If you have any trouble installing, look at either the provided github page or the official [Valgrind homepage][b4b537ec] for more info. If you still experience trouble building and installing pmemcheck, either contact me @tomaszkapela or file an [issue][41493750]. Once that is done, to run an application under pmemcheck type:

{% highlight sh %}
$ valgrind --tool=pmemcheck [valgrind options] <your_app> [your_app options]
{% endhighlight %}

The most basic example you can imagine is acquiring a persistent memory region and writing to it. As pmemcheck doesn't really care how you acquire it or where it really is (frankly it doesn't even care if it really is persistent memory - as you will see in the example), you have to tell it explicitly which regions to track using the `VALGRIND_PMC_REGISTER_PMEM_MAPPING(addr, size)` macro. To sum up, in the most simplistic form this could look like:

{% highlight C linenos %}
#include <valgrind/pmemcheck.h>

int main(int argc, char** argv)
{
	int ip;
	VALGRIND_PMC_REGISTER_PMEM_MAPPING(&ip, sizeof (ip));
	ip = 5;
	VALGRIND_PMC_REMOVE_PMEM_MAPPING(&ip, sizeof (ip));
}
{% endhighlight %}

This example has one major error. The store at `ip = 5;` isn't made persistent and pmemcheck will tell you about it. If the executed binary has debug symbols available, you will get a nice stacktrace for each issue. The output looks like the following:

	Number of stores not made persistent: 1
	Stores not made persistent properly:
	[0]    at 0x400794: main (example.c:7)
		   Address: 0xfff000124	size: 4	state: DIRTY
	Total memory not made persistent: 4

As you can see, the output is fairly straightforward. The tool tells you that in function `main` in file `example.c` at line 7, you made a store of size 4 (bytes, obviously) and at program exit the state of the store is `DIRTY`. The last line is the total number of bytes that were not made persistent properly. Here I should probably explain what it means to make something persistent. Taken from pmemcheck's documentation: *To make the data persistent, preferably in a failsafe manner, you have to do a STORE->FLUSH->SFENCE->PCOMMIT->SFENCE sequence*. Now that we know what we did wrong, let's go back and fix the example.

{% highlight C linenos %}
	#include <valgrind/pmemcheck.h>

	int main(int argc, char** argv)
	{
		int ip;
		VALGRIND_PMC_REGISTER_PMEM_MAPPING(&ip, sizeof (ip));
		ip = 5;
		/* tell valgrind these flushing steps occurred */
		VALGRIND_PMC_DO_FLUSH(&ip, sizeof (ip));
		VALGRIND_PMC_DO_FENCE;
		VALGRIND_PMC_DO_COMMIT;
		VALGRIND_PMC_DO_FENCE;
		VALGRIND_PMC_REMOVE_PMEM_MAPPING(&ip, sizeof (ip));
	}
{% endhighlight %}

Now the output looks more or less like this:

	Number of stores not made persistent: 0

Yay! We assigned a value to a stack variable and made sure it stays there, sort of. Firstly, without a fair amount of [magic][e0997ea1], the stack will not be persistent memory. Secondly, we only informed pmemcheck that the data was made persistent. The macros defined in `pmemcheck.h`, and I couldn't stress this more, **DO NOT** affect your code in any way. In this example there is no code related to persistence. This is where libraries such as [NVML][c5f40b9e] come in. They do all the magic for you. Once again revisiting the example:

{% highlight C linenos %}
#include <valgrind/pmemcheck.h>
#include <libpmem.h>

int main(int argc, char** argv)
{
	int ip;
	VALGRIND_PMC_REGISTER_PMEM_MAPPING(&ip, sizeof (ip));
	ip = 5;
	pmem_persist(&ip, sizeof (ip));
	VALGRIND_PMC_REMOVE_PMEM_MAPPING(&ip, sizeof (ip));
}
{% endhighlight %}

A lot tidier, don't you think? And it has the added bonus of doing the actual persisting. NVML has full support for pmemcheck and does all the work for you behind the scenes. It will register the persistent memory region on each pool create/open and do the persisting, not to mention the other features such as transactions in pmemobj. By the way, pmemcheck supports transactions as well, but that's a topic for a different blog post.

### Advanced features

Pmemcheck does more than just check for non-persistent stores. Depending on the options you run pmemcheck with it can look out for memory overwrites, redundant flushes or unnecessary flushes. These are either data consistency or performance related issues. Let's look at overwrites first, as they are a potential data corruption source and should be carefully analyzed.

#### Multiple overwrites

Pmemcheck reports a potential data overwrite when you do a store to the same persistent memory area multiple times, before they become persistent. For pmemcheck to report multiple stores, you have to run the tool with the `--mult-stores=yes` option. Let's look at an example to clarify this issue.

{% highlight C linenos %}
#include <valgrind/pmemcheck.h>

int main(int argc, char** argv)
{
	int ip;
	VALGRIND_PMC_REGISTER_PMEM_MAPPING(&ip, sizeof (ip));
	ip = 5;
	ip = 4;
	VALGRIND_PMC_REMOVE_PMEM_MAPPING(&ip, sizeof (ip));
}
{% endhighlight %}

You can see that the value of `ip` got modified again before it was made persistent. This means, that in case of failure, you can get either value. The problem goes however a little deeper - why would you overwrite `ip` in the first place? That is something you would do with volatile memory and it means you should probably take it outside of persistent memory. Pmemcheck reports this issue in the following way:

	Number of overwritten stores: 1
	Overwritten stores before they were made persistent:
	[0]    at 0x400794: main (example.c:7)
	       Address: 0xfff000104	size: 4	state: DIRTY

Once again, if available, you get the full stacktrace which points to the store that got overwritten. In this example this would be the statement `ip = 5;`. Sometimes however, Valgrind reports multiple stores to pmemcheck (pmemcheck is a plug-in tool within Valgrind) on calls such as libc `memcpy()`. This isn't something you need to bother yourself with, you have no control over it. Therefore the need to inform pmemcheck to ignore such occurrences. Here the `--indiff` comes in handy. Valgrind has this notion of SuperBlocks - to put it simply, these are larger chunks of your code fed to pmemcheck for instrumentation. The `--indiff` option takes as its parameter, the number of blocks within which, stores made to the same place, with the same size and value will be ignored. This sounds complicated, but let's consider the previous example with a small modification.

{% highlight C linenos %}
#include <valgrind/pmemcheck.h>

int main(int argc, char** argv)
{
	int ip;
	VALGRIND_PMC_REGISTER_PMEM_MAPPING(&ip, sizeof (ip));
	ip = 5;
	ip = 5;
	VALGRIND_PMC_REMOVE_PMEM_MAPPING(&ip, sizeof (ip));
}
{% endhighlight %}

Now let's run this with `--indiff=1` and see what comes out.

	Number of stores not made persistent: 1
	Stores not made persistent properly:
	[0]    at 0x40079B: main (example.c:8)
	       Address: 0xfff000104	size: 4	state: DIRTY
	Total memory not made persistent: 4

This is the whole report. I omitted this part previously, because it was irrelevant to the example. As you can see, you don't get the *overwritten stores* message anymore. The value passed to the `--indiff` option is somewhat of a guess game. It's hard to determine how far apart different parts of your code will be in Valgrind's internal representation. Internally Valgrind changes your code to an intermediate representation (RISC-like) - you can read more [here][8f99b01c] if you're interested. Seeing as these statements are adjacent, I made an educated guess and decided to go with *1*.

#### Flushing errors

These are strictly performance related, no consistency issue can be observed. As there is nothing complicated here, we'll start of with an example.

{% highlight C linenos %}
#include <valgrind/pmemcheck.h>
#include <libpmem.h>

int main(int argc, char** argv)
{
	int ip;
	VALGRIND_PMC_REGISTER_PMEM_MAPPING(&ip, sizeof (ip));
	ip = 5;
	/* double flush */
	pmem_flush(&ip, sizeof (ip));
	pmem_flush(&ip, sizeof (ip));
	/* nothing to flush */
	pmem_flush((&ip + 1), sizeof (ip));
	VALGRIND_PMC_REMOVE_PMEM_MAPPING(&ip, sizeof (ip));
}
{% endhighlight %}

When you run this example with `--flush-check=yes` you get the following output:

	Number of redundantly flushed stores: 1
	Stores flushed multiple times:
	[0]    at 0x4007B5: main (example.c:8)
	       Address: 0xfff00005c	size: 4	state: FLUSHED

	Number of unnecessary flushes: 1
	[0]    at 0x4008F9: main (example.c:13)
	       Address: 0xfff000060	size: 4

The first message tells you that you flushed the first store twice (you get one occurrence for each redundant flush). Flushing something more than once doesn't make it more flushed. It does however waste CPU time. The second error informs you that you flushed something that wasn't *dirty* - there was no store made to that region of persistent memory. Once again this only wastes precious CPU cycles. As I mentioned before, the reported flushing errors only inform you of places you could correct to make your application run a little faster.

This concludes the introduction to basic and advanced features of pmemcheck. In the next blog post I will explain how the built-in transaction support in pmemcheck works.

[655a3db3]: https://github.com/pmem/valgrind "Valgrind-pmemcheck"
[d324cfe0]: http://htmlpreview.github.com/?https://github.com/pmem/valgrind/blob/pmem/docs/html/pmc-manual.html "Pmemcheck documentation"
[b4b537ec]: http://valgrind.org/ "Valgrind"
[41493750]: https://github.com/pmem/valgrind/issues "pmemcheck issues"
[e0997ea1]: http://giphy.com/gifs/rainbow-unicorn-highway-G0nTMRctvIp4Q "Magic"
[8f99b01c]: http://www.valgrind.org/docs/pubs.html "Valgrind Research Papers"
[5428585d]: http://www.valgrind.org/docs/manual/manual-core-adv.html#manual-core-adv.clientreq "Client Request Mechanism"
[c5f40b9e]: http://pmem.io/nvml/ "NVM Library"

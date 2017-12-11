---
title: Linux Examples
author: andyrudoff
layout: post
---

###### [Edit on 2017-12-11: The Linux examples are outdated now, look at [PMDK](/pmdk/) instead.]

The [basic architecture]({% post_url 2014-08-27-crawl-walk-run %})
for exposing persistent memory gives applications a very **raw**
type of access.  Applications can load/store directly to the
persistence, but then what.  What are the interesting problems
facing an application developer and what would some solutions
look like?

To help describe the issues and potential solutions, we've
published a set of [Linux examples](https://github.com/pmem/linux-examples)
around persistent memory.  These examples start with a simple
"hello, world!" style program for storing a string in persistent
memory, and end with a full binary tree sort example which maintains
a consistent tree data structure even in the face of power failure or
other types of system interruption.  The examples include a mini
fault injection framework to illustrate the type of tools that
are necessary for demonstrating programs correctly survive system
interruption.

While not really useful in a production environment, these examples
are educational, and provide a background on how persistent memory
is exposed to applications and what the application writer needs to
consider before using it.  So please
[check it out](https://github.com/pmem/linux-examples) and feel
free to ask questions, comment on it, or contribute to it.
(Scroll down on the GitHub repository page to see the README which
contains all sorts of details on the examples.)
A good
place to send questions or comments is our
[Google Group](http://groups.google.com/group/pmem).

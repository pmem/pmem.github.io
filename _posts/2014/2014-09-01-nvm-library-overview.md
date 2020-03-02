---
title: NVM Library Overview
author: andyrudoff
layout: post
---


###### [Edit on 2017-12-11: In the years since this entry was written, this work has evolved into [PMDK](/pmdk/).]

Why are we building an NVM Library?  Where does it live?  How
does it work?  This blog entry provides some answers,
which refer to this picture
showing the overall library architecture:

![Library Architecture](/assets/libarch.jpg)

#### Why?

The operating system exposes persistent memory to applications as
a memory-mapped file, using a _persistent memory aware file system_
as shown in the picture.  But that's a very **raw** form of access.
Stopping there would be like telling applications they have all the
dynamic memory they need because the OS provides the
[sbrk(2)](https://linux.die.net/man/2/sbrk) system call.  Just as
libc and most other language run-time environments provide memory
management APIs like [malloc(3) and free(3)](https://linux.die.net/man/3/malloc),
which build on the raw interfaces like sbrk(), we need to do something
similar for persistent memory.  Memory-mapping a pmem file gets you
direct access, but then you will want to carve it up into data structures
and update it in a way that remains consistent across system interruptions.

So why isn't the answer just to provide a version of malloc() and free()
that allocates from the system's pool of persistent memory?  Because those
interfaces don't comprehend the idea of persistence.  If a program allocates
a blob of memory using malloc(), but dies before linking anything to it,
that memory is a _persistent memory leak_ and the pool is then inconsistent
from that point on.  With volatile memory, that's not an issue since it
starts from nothing each time the program runs.  But for persistent memory,
we need a _pmem-aware_ malloc() library to make it useful.

#### Where?

As shown in the picture above, the library lives in user space, used by
the application as necessary.  The NVM Library is a convenience, not
a requirement; an application that wants to access raw persistent memory
directly is welcome to do so by mapping it and accessing it.  But an
application that wants to do things like malloc(), free(), and some sort
of transactions may find the NVM Library useful.

Another aspect of the _where_ question is what types of persistence does
the library support?  The intention is that the NVM Library will work
on top of any non-volatile memory, not just persistent memory.  By
writing a library that is optimized for persistent memory, but that
behaves reasonably on other types of NVM (like SSDs), there's a good
chance that applications can use the library for both configurations
and be simpler as a result.  (This is why the library is named the
_NVM Library_ and not the _PMEM Library_, by the way.)

#### How?

Researching persistent memory aware algorithms and making the best
architectural trade-offs is a big part of this project and that work
is underway.  So we don't have all the answers yet on how the library
works.  Some parts of the library are functional, other parts are
still being designed.  Check out the [NVM Library](/nvml/) page
for the latest man pages which describe the APIs available so far.

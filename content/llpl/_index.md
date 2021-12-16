---
title: "llpl"
draft: false
url: "java/llpl"
slider_enable: true
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "llpl"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Low-Level Persistence Library

The Low-Level Persistence Library (LLPL) is a Java library that provides access to off-heap persistent memory. LLPL includes several kinds of components that can be allocated and used alone or together in building applications:

* **heaps:** a pool of memory and an allocator for it
* **memory blocks:** unstructured bytes that can be laid out for any purpose and linked to build data structures
* **pre-built data structures:** arrays, linked list, and radix trees
* **memory pools:** a process-shareable pool of memory

Data stored in the components above can persist beyond the life of a JVM instance, i.e. across application or system restarts. LLPL provides APIs that help developers ensure consistency of stored data.

Memory allocated using LLPL is not garbage-collected and must be explicitly deallocated using LLPL APIs.

LLPL uses the Persistent Memory Development Kit (PMDK). For more information on PMDK, please visit http://pmem.io and https://github.com/pmem/pmdk.

### How To Build & Run

#### Prerequisites To Build

The following are the prerequisites for building this Java library:

1. Linux operating system
2. Persistent Memory Development Kit (PMDK) v1.5 or newer
3. Java 8 or newer
4. Build tools - `g++` compiler, `CMake` and `Maven`

#### Prerequisites To Run

This library assumes the availability of hardware persistent memory or emulated persistent memory. Instructions for creating emulated persistent memory are shown below.

#### Emulating Persistent Memory

The preferred way is to create an in-memory DAX file system. This requires Linux kernel 4.2 or greater. Please follow the steps at:

http://pmem.io/2016/02/22/pm-emulation.html

Alternatively, for use with older kernels, create a tmpfs partition as follows (as root):

``` sh
$ mount -t tmpfs -o size=4G tmpfs /mnt/mem  # creates a 4GB tmpfs partition
$ chmod -R a+rw /mnt/mem                    # enables read/write permissions to all users
```

#### Steps To Build And Run Tests

Once all the prerequisites have been satisfied:

``` sh
$ git clone https://github.com/pmem/llpl.git
$ cd llpl
$ mvn test -Dtest.heap.path=<path to persistent memory mount point>
```

Available Maven commands include:

* `compile` - builds sources
* `test` - builds and runs tests
* `javadoc:javadoc` - builds javadocs into `target/site/apidocs`
* `package` - builds jar file into `target` directory

#### Using This Library In Existing Java Application

**With Maven**

LLPL is available from the Maven central repository. Add the following dependency to your pom.xml:

``` xml
<dependency>
    <groupId>com.intel.pmem</groupId>
    <artifactId>llpl</artifactId>
    <version>1.2.0-release</version>
    <type>jar</type>
</dependency>
```

**With Classpath**

To use this library in your Java application, build the LLPL jar and include its location in your Java classpath. For example:

``` sh
$ mvn package
$ javac -cp .:<path>/llpl/target/llpl-<version>.jar <source>
$ java -cp .:<path>/llpl/target/llpl-<version>.jar <class>
```

Alternatively, include LLPL's `target/classes` directory in your Java classpath and the `target/cppbuild` directory in your `java.library.path`. For example:

``` sh
$ mvn compile
$ javac -cp .:<path>/llpl/target/classes <source>
$ java -cp .:<path>/llpl/target/classes -Djava.library.path=<path>/llpl/target/cppbuild <class>
```

### Contributing 

Thanks for your interest! Please see the CONTRIBUTING.md document for information on how to contribute.

We would love to hear your comments and suggestions via https://github.com/pmem/llpl/issues.
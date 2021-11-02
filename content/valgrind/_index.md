---
title: "Valgrind"
draft: false
slider_enable: true
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "Valgrind"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Valgrind: an enhanced version for pmem

This version has support for the new CLFLUSHOPT and CLWB instructions. It also introduces a new tool called pmemcheck which validates the correctness of stores made to persistent memory. Be aware that this is still a prototype tool.

### Documentation

* Entire Valgrind documentation is located in ['generated' sub-directory](http://pmem.io/valgrind/generated).

* Manual for [pmemcheck tool](http://pmem.io/valgrind/generated/pmc-manual.html) is located within Valgrind User Manual sub-section.

### Building 

All packages necessary to build this modified version of Valgrind are the same as for the original version.

Once the build system is setup, Valgrind is built using these command at the top level:

``` sh
	$ ./autogen.sh
	$ ./configure [--prefix=/where/to/install]
	$ make
```

To build tests:

``` sh
	$ make check
```

### Running Tests

To run all regression tests:

``` sh
	$ make regtest
```

To run pmemcheck tests only:

``` sh
	$ perl tests/vg_regtest pmemcheck
```

### Installation

To install Valgrind run (possibly as root if destination permissions require that):

``` sh
	$ make install
```

### Resources

For more information on Valgrind please refer to the original README files and the documentation which is available at:

``` sh
	$PREFIX/share/doc/valgrind/manual.html
```

Where $PREFIX is the path specified with --prefix to configure.

For information on how to run the new tool refer to the appropriate part of the documentation or type:

``` sh
	$ valgrind --tool=pmemcheck --help
```
---
title: Language bindings for pmemkv
author: lukaszstolarczuk
layout: post
identifier: pmemkv-bindings
---

[pmemkv][pmemkv_pmem_io] is a local/embedded key-value datastore optimized for persistent memory.
It is written in C and C++, but to satisfy a wider audience it comes with several bindings
for high-level languages. Currently: Java (with JNI), Node.js, Python and Ruby.

The picture below illustrates architecture and software stack of pmemkv and its bindings.
![pmemkv_bindings_image](/assets/pmemkv_bindings.png)

The most up-to-date information about pmemkv and its bindings is located in
[pmemkv's README file][pmemkv_bindings_readme].

### Common for bindings

There are few common characteristics of all bindings:

#### Compatibility with libpmemkv 1.0

All of our currently released bindings work properly with libpmemkv 1.0. Unfortunately their API
may not be functionally equivalent to the native C/C++ API. **Bindings in version 1.0**
deliver API compatible with **pmemkv 1.0**. Pmemkv's binding released in version X.Y
will be always compatible with pmemkv in the same X.Y version.
Additionally, bindings with major version 0 should be considered experimental.

The pmemkv project is currently undergoing intensive development, and it is likely that
the core library feature set will outpace features of the various bindings. Moreover,
individual bindings are developed independently. To see if a feature is present,
it is recommended to check documentation or a changelog for specific bindings.

#### Idiomatic language support

Each binding is developed to be as natural to the language as possible and hence
some parts of the API may slightly differ from the one known from pmemkv.

For example, statuses returned by pmemkv are translated into numeric value or wrapped
into exceptions. What's more, bindings using exceptions do not use status "OK", they just
return nothing or a numeric value (of `PMEMKV_STATUS_OK`) if no error occurred.

Some functions' return values may also differ - see details of each API
in binding's documentation and compare to pmemkv's man pages.

Each language has also different system of packaging and installation.
Native (or one of the most common) system was picked for each language.

#### Direct object access

Pmemkv library supports direct pointers to the data stored on the persistent memory
out of the box. Bindings need to support it and at the moment only some of them implement that.
See notes in individual descriptions below or in readme files on their repositories
to check the support. Bindings without direct access need to use buffer to copy the data to.

#### Examples, tests and CI

Each repository includes:
* examples, showing basic library usage,
* tests, verifying current implementation,
* Continuous Integration system, to check all incoming changes.

#### Contributions are welcome

All pmemkv related repositories are open source and BSD-licensed, so contributions are welcome.

### pmemkv-nodejs

Node.js binding is the first one that reached the 1.0 version. This guarantees
backward compatibility and stable API, compatible with **libpmemkv 1.0**. This binding supports
direct access to the data stored in the persistent memory.

It also delivers API description in form of JSDoc documentation (accessible e.g. as html in
[here][nodejs_pmem_io]) and NodeJS-native support for configuring engines by JSON object.
This binding uses exceptions for error handling. It uses npm for distribution and can be installed
with command `npm install` executed within the source directory. It then installs the dependencies
listed in package.json in the local node_modules folder.

For more information, such as required dependencies, usage and full installation guide,
see [pmemkv-nodejs github repository][nodejs_gh].

### pmemkv-python

This is the second binding that reached the 1.0 version. This guarantees backward compatibility
and stable API, compatible with **libpmemkv 1.0**. It supports direct access to the data
on the persistent memory.

It also delivers API description in form of PyDoc documentation (accessible e.g. as html in
[here][py_pmem_io]) and Python-native data type (`dict`) to store engine's configuration items.
It uses exceptions for error handling. To install this project in your system type:
`python3 setup.py install`. If you rather want it to be installed in your home directory
(`~/.local/lib/`), add `--user` to the installation command.

For more information, such as required dependencies, usage and full installation guide,
see [pmemkv-python github repository][py_gh].

### pmemkv-java

Latest released version is 1.0 - it guarantees backward compatibility and stable API,
compatible with **libpmemkv 1.0**. It supports direct data access and it uses Builder pattern
for configuring a database. This binding uses JNI to interoperate with C and that
pmemkv C JNI implementation is delivered as a part of the same repository.

It also delivers API description in form of JavaDoc documentation (accessible e.g. as html in
[here][java_pmem_io]). It uses exceptions for error handling. To install pmemkv-java in the system,
make sure **libpmemkv** is installed and just execute within the source directory: `mvn install`.

For more information, such as required dependencies, usage and full installation guide,
see [pmemkv-java github repository][java_gh].

As an alternative for java programmers, there are two other pmem options:
- a low-level library for pmem, called LLPL ([see introduction blog post][llpl_blog])
- and a library for Java objects on pmem - [Persistent Collections for Java][pcj_gh].

### pmemkv-ruby

It's currently released as version 0.9 and is compatible with libpmemkv 1.0, but comes
with no guarantees of stable API. It uses exceptions for error handling.
Installation of this project and its dependencies is done using bundler
(`gem install bundler -v '< 2.0'`) and it then comes down to executing command: `bundle install`.

For more information, such as required dependencies, usage and full installation guide,
see [pmemkv-ruby github repository][ruby_gh].

### Summary

| Language  | Version | Direct access | URL |
| --------- | ------- | ------------- | --- |
| **Node.js** | **1.0** | **yes** | **[GitHub][nodejs_gh]**, **[docs][nodejs_pmem_io]** |
| **Python** | **1.0** | **yes** | **[GitHub][py_gh]**, **[docs][py_pmem_io]** |
| **Java** | **1.0** | **yes** | **[GitHub][java_gh]**, **[docs][java_pmem_io]** |
| Ruby | 0.9 | no | [GitHub][ruby_gh] |

### Looking forward

We may deliver more bindings in the future, but we also encourage community to create their own bindings
and share them with us.

We are currently aware of two community developed bindings:
* [Rust binding][rust_binding_iss],
* [Go binding][go_binding_iss].

If you feel like we're missing some language, that may be useful to have bindings written in,
or you just want to let us know we don't have information about some (possibly yours) pmemkv's binding,
or you believe we're missing some functionalities, please [file an issue in pmemkv's repository][new_pmemkv_iss]
to let us know. We are always eager to help our community grow.

###### [This entry was edited on 2020-07-29 to reflect pmemkv-java recent release.]
###### [This entry was edited on 2020-10-08 to fix java binding implementation detail.]

[pmemkv_pmem_io]: https://pmem.io/pmemkv/
[pmemkv_bindings_readme]: https://github.com/pmem/pmemkv/#language-bindings
[nodejs_gh]: https://github.com/pmem/pmemkv-nodejs
[nodejs_pmem_io]: https://pmem.io/pmemkv-nodejs
[py_gh]: https://github.com/pmem/pmemkv-python
[py_pmem_io]: https://pmem.io/pmemkv-python
[java_gh]: https://github.com/pmem/pmemkv-java
[java_pmem_io]: https://pmem.io/pmemkv-java
[llpl_blog]: https://pmem.io/2020/05/27/llpl-intro1.html
[pcj_gh]: https://github.com/pmem/pcj
[ruby_gh]: https://github.com/pmem/pmemkv-ruby
[rust_binding_iss]: https://github.com/pmem/pmemkv/issues/192
[go_binding_iss]: https://github.com/pmem/pmemkv/issues/190
[new_pmemkv_iss]: https://github.com/pmem/pmemkv/issues

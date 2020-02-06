---
title: Language bindings for pmemkv
author: lukaszstolarczuk
layout: post
identifier: pmemkv-bindings
---

Along with [pmemkv][pmemkv_pmem_io] written in C/C++ there are a bunch of
bindings for high-level languages. Currently: Java (with JNI), Node.js, Python and Ruby.

Picture below properly summarize architecture and software stack of pmemkv and its bindings.
![pmemkv_bindings_image](/assets/pmemkv_bindings.png)

Always, the most current updates about bindings (and pmemkv in general) can be found on
[pmemkv's README file][pmemkv_bindings_readme].

### Common for bindings

There are few common characteristics regarding all bindings:

#### Work with libpmemkv 1.0

All of our currently released bindings work properly with libpmemkv 1.0. Unfortunately their API
may not be fully functionally equal to the native C/C++ API. These **bindings, which are
in version 1.0** deliver API in accordance with **pmemkv 1.0**. In general, pmemkv's binding
released in version X.Y is (always) functionally greater or equal to pmemkv in the same X.Y version.
Additionally, if a binding's version is prior to 1.0 (any 0.x version), it should be considered
an experimental implementation.

Taking into consideration that libpmemkv is developing in the fast pace, there are always
some gaps in the current versions of bindings' implementations. Even more, each binding
is developed in its own pace, so the best way to check compatibility with pmemkv
is to read changelog of the latest release of a specific binding.

#### Handling pmemkv statuses

Every language supports handling statuses returned from pmemkv's API. Each has its own way,
either translating each status into numeric value or wrapping them into exceptions. Bindings
using exceptions do not use status "OK", just returns nothing or a numeric value
(of `PMEMKV_STATUS_OK`) if no error occurred. Return value differs also for various functions -
to see details read binding's documentation or pmemkv's man pages.

#### Not always direct access to data

**Except in Python** (and soon [in Node.js][nodejs_pr]), our bindings do not (yet)
provide direct access to the data stored in the Persistent Memory.
They need to use buffer to copy the data to.

#### Examples, tests and CI

Each repository is equipped with basic, needed features like:
* examples, showing basic library usage,
* tests, verifying current implementation,
* Continuous Integration system, to check all incoming changes.

#### Packaging specific for language

Each language is different, so we try our best to use packaging and installation systems
native to (or one of the most common for) each binding's language.

#### Contributions are welcome

As all of pmem's organization repositories, the ones of pmemkv-bindings are also
open source and in each of them any support is more than welcome.

### pmemkv-nodejs

Node.js binding is the first one to be released in stable version (1.0). It means
it guarantees backward compatibility and it provides API functionally equal to
the native pmemkv C/C++ API **in version 1.0**.

It also delivers API description in form of JSDoc documentation (accessible e.g. as html in
[here](nodejs_pmem_io)) and NodeJS-native support for configuring engines by JSON object.
It handles returned statuses as exceptions and does not return any value when no errors occur.
Installation of this project is supported by simply typing `npm install` within the source directory.
It then installs the dependencies listed in package.json in the local node_modules folder.

For more information, like required dependencies, usage and full installation guide,
see [pmemkv-nodejs github repository][nodejs_gh].

### pmemkv-python

This is the second binding to be released in version 1.0. It has all functionalities
from pmemkv 1.0.

It also delivers API description in form of PyDoc documentation (accessible e.g. as html in
[here](py_pmem_io)) and Python-native data type (`dict`) to store engine's configuration items.
It handles returned statuses as exceptions and does not return any value when no errors occur.
To install this project in your system it's required to type in: `python3 setup.py install [--user]`,
and then it is installed in a system path or (when optional parameter `--user` is given) within
user's files (`~/.local/lib/`).

For more information, like required dependencies, usage and full installation guide,
see [pmemkv-python github repository][py_gh].

### pmemkv-java

Latest released version is 0.9 and it's working just fine with libpmemkv 1.0, but
its API may still change. In this binding's code, the JNI framework is used,
with implementation of pmemkv's C API delivered in separate repository.
It handles returned statuses as exceptions and does not return any value when no errors occur.
Installation process, when pmemkv and pmemkv-jni are installed in the system, comes down to
typing, within source directory, command: `mvn install`.

For more information, like required dependencies, usage and full installation guide,
see [pmemkv-java github repository][java_gh]. To see the repository of JNI content,
see [pmemkv-jni github repository][jni_gh].

### pmemkv-ruby

It's currently released as 0.9 version, properly working with pmemkv 1.0,
but its API is still a work in progress and it may change.
It handles returned statuses as exceptions and returns numeric value of `OK` status,
when no errors occur.
Installation of this project and its dependencies is done using bundler
(`gem install bundler -v '< 2.0'`) and it comes down to typing: `bundle install`.

For more information, like required dependencies, usage and full installation guide,
see [pmemkv-ruby github repository][ruby_gh].

### Summary

| Language  | Version | URL |
| --------- | ------- | --- |
| **Node.js** | **1.0** | **[GitHub][nodejs_gh]**, **[docs][nodejs_pmem_io]** |
| **Python** | **1.0** | **[GitHub][py_gh]**, **[docs][py_pmem_io]** |
| Java | 0.9 | [GitHub][java_gh] |
| Ruby | 0.9 | [GitHub][ruby_gh] |

### Looking forward

In future more bindings may come to life. There are currently two (of which we are aware)
still in development and outside of our organization, you can read about them here:
* [Rust binding][rust_binding_iss]
* [Go binding][go_binding_iss]

If you feel like we're missing some language, that may be useful to have in our portfolio,
or you just want to let us know we don't have information about some (possibly yours)
pmemkv's binding, or your feel like we're missing some functionalities, don't hesitate to
[file an issue in pmemkv's repository][new_pmemkv_iss] or on any of binding's issues page.


[pmemkv_pmem_io]: https://pmem.io/pmemkv/
[pmemkv_bindings_readme]: https://github.com/pmem/pmemkv/#language-bindings
[nodejs_pr]: https://github.com/pmem/pmemkv-nodejs/pull/49
[nodejs_gh]: https://github.com/pmem/pmemkv-nodejs
[nodejs_pmem_io]: https://pmem.io/pmemkv-nodejs
[py_gh]: https://github.com/pmem/pmemkv-python
[py_pmem_io]: https://pmem.io/pmemkv-python
[java_gh]: https://github.com/pmem/pmemkv-java
[jni_gh]: https://github.com/pmem/pmemkv-jni
[ruby_gh]: https://github.com/pmem/pmemkv-ruby
[rust_binding_iss]: https://github.com/pmem/pmemkv/issues/192
[go_binding_iss]: https://github.com/pmem/pmemkv/issues/190
[new_pmemkv_iss]: https://github.com/pmem/pmemkv/issues

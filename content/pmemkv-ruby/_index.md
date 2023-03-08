---
title: "pmemkv-ruby | PMemKV"
draft: false
slider_enable: true
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "pmemkv-ruby"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Discontinuation of the project
The **pmemkv-ruby** library will no longer be maintained by Intel.
- Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases,
or updates, to this project.
- Intel no longer accepts patches to this project.
- If you have an ongoing need to use this project, are interested in independently developing it, or would like to
maintain patches for the open source software community, please create your own fork of this project.
- You will find more information [here](/blog/2022/11/update-on-pmdk-and-our-long-term-support-strategy/).

### pmemkv-ruby

Ruby bindings for **pmemkv**.

The current API is simplified and not functionally equal to its native C/C++ counterpart.
In the future existing API may be extended in idiomatic way without preserving backward compatibility.

Current code can be accessed on <a href="https://github.com/pmem/pmemkv-ruby">github page</a>.

All known issues and limitations are logged as GitHub issues.

### Dependencies

* Ruby 2.2 or higher
* [PMDK](https://github.com/pmem/pmdk) - native persistent memory libraries
* [pmemkv](https://github.com/pmem/pmemkv) - native key/value library
* [ffi](https://github.com/ffi/ffi) - for native library integration
* Used only for testing:
  * [rspec](https://github.com/rspec/rspec) - test framework

### Installation

Start by installing [pmemkv](https://github.com/pmem/pmemkv/blob/master/INSTALLING.md) on your system.

Install Bundler:

```bash
gem install bundler -v '< 2.0'
```

Clone the pmemkv-ruby tree:

```bash
git clone https://github.com/pmem/pmemkv-ruby.git
cd pmemkv-ruby
```

Download and install gems: 

```bash
bundle install
```
### Testing

This library includes a set of automated tests that exercise all functionality.

```bash
LD_LIBRARY_PATH=path_to_your_libs bundle exec rspec
```

### Example

We are using `/dev/shm` to [emulate persistent memory](/2016/02/22/pm-emulation.html) in example.

Example can be found within *pmemkv-ruby* repository in [examples directory](https://github.com/pmem/pmemkv-ruby/tree/master/examples).
To execute the example:

```bash
PMEM_IS_PMEM_FORCE=1 ruby basic_example.rb
```

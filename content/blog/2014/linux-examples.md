---
# Blog post title
title: 'Linux Examples'

# Blog post creation date
date: 2014-08-29T19:55:17-07:00

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
author: 'andyrudoff'

# Categories to which this blog post belongs
blogs: ['linux']
tags: []

# Redirects from old URL
aliases: ['/2014/08/29/linux-examples.html']

# Blog post type
type: 'post'
---

###### [Edit on 2017-12-11: The Linux examples are outdated now, look at [PMDK](/pmdk/) instead.]

The [basic architecture](/blog/2014/08/crawl-walk-run.../).
for exposing persistent memory gives applications a very **raw**
type of access. Applications can load/store directly to the
persistence, but then what. What are the interesting problems
facing an application developer and what would some solutions
look like?

To help describe the issues and potential solutions, we've
published a set of [Linux examples](https://github.com/pmem/linux-examples)
around persistent memory. These examples start with a simple
"hello, world!" style program for storing a string in persistent
memory, and end with a full binary tree sort example which maintains
a consistent tree data structure even in the face of power failure or
other types of system interruption. The examples include a mini
fault injection framework to illustrate the type of tools that
are necessary for demonstrating programs correctly survive system
interruption.

While not really useful in a production environment, these examples
are educational, and provide a background on how persistent memory
is exposed to applications and what the application writer needs to
consider before using it. So please
[check it out](https://github.com/pmem/linux-examples) and feel
free to ask questions, comment on it, or contribute to it.
(Scroll down on the GitHub repository page to see the README which
contains all sorts of details on the examples.)
A good
place to send questions or comments is our
[Google Group](https://groups.google.com/group/pmem).

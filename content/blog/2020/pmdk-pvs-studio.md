---
# Blog post title
title: 'Static code analysis of the PMDK'

# Blog post creation date
date: 2020-08-20T19:55:17-07:00

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
author: 'michalbiesek'

# Categories to which this blog post belongs
blogs: ['pmdk']

tags: []

# Redirects from old URL
aliases: ['/2020/08/20/pmdk-pvs-studio.html']

# Blog post type
type: 'post'
---

# Introduction

In the PMDK team, we focus on the quality of our codebase. One of the
standard practices in the software development is a static code analysis, which
improves the overall project quality and fixes bugs in the early stage of
development. Since there is no silver bullet for avoiding bugs, we already use
two different static analysis tools and many runtime checkers e.g. [valgrind].
Improving static analysis effectiveness is a separate academic problem. What is
worth mentioning is the fact that different tools take a various approach. With
combined advantages of Open Source projects such as transparency and
reliability, as well as a new static analysis tool, we get a new bag of tricks
and a fresh look to an old problem :).

# PVS-Studio

[PVS-Studio][pvs-studio] is a static-analysis tool for detecting bugs and
security weaknesses in the source code of programs. What distinguishes
PVS-Studio is their comprehensive support for open-source projects.
After a quick e-mail exchange, the PVS-Studio team agreed to perform an analysis
of the PMDK project. The analysis is available in this [post][viva64-post].

# Summary 

PVS-Studio managed to find a couple of issues, which were not previously
detected; we analyzed detected bugs and addressed changes in the following
[Pull Request][pr-fix]. From my experience, I highly recommend cooperating with
the PVS-Studio team - excellent support and quick response time allows us to
improve the PMDK project. See [PVS-Studio][pvs-studio] yourself :)

[valgrind]: https://www.youtube.com/watch?v=R2m-wH7W-5U
[pvs-studio]: https://viva64.com/en/pvs-studio/
[viva64-post]: https://viva64.com/en/b/0756/
[pr-fix]: https://github.com/pmem/pmdk/pull/4942
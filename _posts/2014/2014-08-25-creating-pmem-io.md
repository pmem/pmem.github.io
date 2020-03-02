---
title: Creating pmem.io
author: andyrudoff
layout: post
---

The [pmem](https://github.com/pmem) project in GitHub has been
created as an open source project focused on persistent memory programming.
Everything on this web site and the associated GitHub repositories is
open source under the "three-clause" BSD license.

Some educational [Linux examples](https://github.com/pmem/linux-examples)
are included, which demonstrate the
[SNIA NVM programming model](https://snia.org/nvmp) and some of the
interesting features and challenges associated with persistent memory.

The team's initial focus is the Linux [NVM Library](/nvml/) which
will provide useful APIs for memory allocation, transactions, etc.
built on top of the NVM programming model.

This site includes a [pmem blog](/blog/) based on
[Jekyll](https://github.com/jekyll/jekyll) so over time we can
write up our experiences working with persistent memory programming.
There are [many ways](/about/) to get involved and we welcome your
contributions!

#### GitHub Pages and Jekyll

Since we will surely keep our focus on persistent memory and
immediately forget the steps taken to create this site, here's
a quick summary for reference.  First, any GitHub user or project
area can have [GitHub Pages](https://pages.github.com/) associated
with them with almost no effort.  We simply created a repository
named **pmem.github.io** to hold the pages.  GitHub pages use
Jekyll automatically, but it is convenient to install it locally
for previewing content before pushing changes to the repo.  On Debian Linux,
these steps did the trick:

{% highlight sh %}
$ sudo apt-get install ruby ruby-dev node python-pygments
$ sudo gem install jekyll
$ sudo gem install jekyll-mentions
# to start the jekyll server:
$ jekyll serve --watch
{% endhighlight %}


[Edit: Fedora steps added December 11, 2017]

On Fedora 26, here are steps that for running jekyll locally:

{% highlight sh %}
$ sudo dnf install ruby ruby-devel python-pygments nodejs
$ sudo dnf install redhat-rpm-config zlib-devel gcc-c++
$ gem install jekyll
$ gem install jekyll-mentions
$ gem install bundler
# change directory to jekyll tree, such as pmem.github.io repo clone
$ bundle install
# to start the jekyll server:
$ bundle exec jekyll serve --watch
{% endhighlight %}

Once the jekyll server is running, it watches for changes, re-generating
the web pages as necessary, and provides the content for preview
at the local URL `http://0.0.0.0:4000/`.

The rest of the details (like how to write a `_config.yml` or create
templates or includes) can all be found in the
[Jekyll documentation](https://jekyllrb.com/) or you can look
at the [source for this web site](https://github.com/pmem/pmem.github.io/)
for details.

###### [This entry was edited on 2017-12-11 to reflect the name change from [NVML to PMDK]({% post_url 2017-12-11-NVML-is-now-PMDK %}).]

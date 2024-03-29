---
# Blog post title
title: "Dockers usage in PMDK"

# Blog post creation date
date: 2022-12-20

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: "Dockers usage description in pmem organization and Memkind repository"

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ""

# Blog post thumbnail
# eg: image: "/images/posts/my_blog_thumbnail.png"
image: ""

# Blog post author
author: "lukaszstolarczuk"

# Categories to which this blog post belongs
blogs: ['PMDK']
# Blog tags
tags: ["Intro", "dockers", "pmdk", "memkind"]

# Blog post type
type: "post"
---

In this blog post, I'll describe why we believe dockers are easy to use, time-saving, and valuable for
day-to-day programming and debugging. If you have never heard of dockers (or containers in general),
please read, for example, [this overview](https://docs.docker.com/get-started/overview/). We use dockers
in almost all of the repositories in our organization. In this blog post, I will describe how we use
dockers based on the [PMDK](https://github.com/pmem/pmdk) repository. In some of our repositories,
like in [memkind](https://github.com/memkind/memkind), we use a bit different approach, but it
still relies on docker. The section ["Our various solutions"](#our-various-solutions) below
describes some differences between our repositories. Let's get to the details!

# Dockers are easy and maintainable

From our point of view, dockers significantly simplify part of our work.
We use dockers in our CI for the majority of tests' executions. We've started by preparing docker
images for commonly used Linux distributions. Each of our repositories contain `utils/docker`
directory, like [here](https://github.com/pmem/pmdk/tree/master/utils/docker). Images are stored usually
in a separate sub-directory - unsurprisingly called `images`. Among docker 'recipes' (Dockerfiles),
there are some scripts used within containers to install dependencies (e.g., `install-valgrind.sh`)
and a short README file with instructions on manually building and running dockers from our Dockerfile
images. Note that we only have docker images for publicly open Linux distributions; some of our
repositories/libraries support Windows' builds, and its testing is done purely on Virtual Machines
delivered by CI providers (like GitHub Actions).

# Cleaner workflow and reproducible environment

We figured that keeping "Linux images" as Docker files simplifies their maintenance and updates,
makes them publicly available in the repo (for any reviews and contributions) and allows for easy re-build
on demand by anyone. The last part proved to be especially useful for us - developers. Execution of tests
is just the beginning. If the CI fails, you have to dig through the CI's logs and try to guess what
happened. With a quickly reproducible environment, the job got a lot easier. We don't have to look
for a specific machine with some precise version of a Linux distribution. Just read our README ;-)
and simply build docker image on any developer's machine and debug the code within the exactly
same environment.

To be even lazier... I mean, productive... we sped up the whole process. GitHub comes with a great
feature - ["Packages"](https://github.com/features/packages). It allows us to store built docker
images for later re-use. We use it mainly on CI to save time on re-building (an unchanged image) and
get to tests' execution as soon as possible. If the image(s) or installation scripts are updated,
we have to re-build them, but only once. Such ready-to-use docker images can be easily stored as
a "package(s)" along the GitHub project - see, e.g.,
[PMDK's package](https://github.com/pmem/pmdk/pkgs/container/pmdk). Each image is tagged
by a release version (because PMDK's dependencies may have changed over time, in various release branches),
distribution's name, and CPU architecture (e.g., `1.12-ubuntu-22.04-x86_64`).

If you want to download and run one of our published images, it is as simple as entering any of our,
mentioned above, public "packages". Each of them provides a straightforward "how to" page generated
by GitHub about using such an image. You can either `pull` the image and just run it or use it
as a base image for your own Dockerfile 'recipe'. Building on top of our image gives you the advantage
of having all dependencies already prepared. Such an image would have to be expended with potential
custom packages and files, e.g., for further development or debugging.

# Usage in the Continuous Integration

As I mentioned above, we keep all "docker" related files, in most of our repositories,
in a `utils/docker` part of the tree. For example, in PMDK it is
[here](https://github.com/pmem/pmdk/tree/master/utils/docker).
The previously mentioned `images` directory contains:
* README file - to explain basics on how to use dockers
* Dockerfiles, which define steps to create our complete OS environments - docker 'recipes'
* installation scripts (for various dependencies and, e.g., testing tools), used to build images
* two helper scripts for building and pushing images to the "registry" (a.k.a. GitHub's Packages)

Dockerfile 'recipes' are used by the helper scripts, and they, in turn, are used by the
"upper" layer of scripts that reside directly in the `utils/docker` directory. `pull-or-rebuild-image.sh`
script makes use of these helpers. It decides whether to re-build the image or just download it
from the registry. Images' re-building is based on changes introduced by a user (e.g., in
a Pull Request).

In the `docker` directory, there are the most interesting scripts. There are some `build*` scripts
(usually just one - `build.sh`) and several `run-*` scripts. In the PMDK repository, there are two build scripts.
First one (`build-CI.sh`) is used by our CI as an entry point to prepare the environment (dockers)
and execute a selection of our tests. The second one (`build-local.sh`) is a simplified version
to run tests on your local machine manually (but still using dockers).
The second group of scripts (`run-*`) is prepared for executing specific sets of checks
(e.g., `run-build-package.sh` verifies if preparing PMDK packages is working properly).

All GitHub Actions workflows and jobs (using the listed above scripts) are defined in
`.github/workflows` sub-tree of our repositories. For PMDK it is
[here](https://github.com/pmem/pmdk/tree/master/.github/workflows).

# Our various solutions

As I wrote in the introduction, not all repositories are handled exactly the same. Each repository
related to PMem was developed by various people with heads full of ideas. Some requirements may
have forced teams to update testing workflows accordingly to their needs. Some changes to CI and
dockers were introduced in a rush (e.g., because of some deadlines) and were not ported to other
repositories. Having said that, we tried to keep the differences to the minimum.

The main differences are, for sure, in `run-*.sh` scripts, which are delivered specifically to
execute tests and checks adequate to the given library. Most repositories, like
[rpma](https://github.com/pmem/rpma/tree/main/utils/docker) introduce only one entry point script
(`build.sh`). In the PMDK repository, building the whole environment and tests' execution requires
a significant amount of env variables to be set on the host machine. To ease the process for local
re-building, `build-local.sh` was introduced.

The PMDK repository is tested in more environments, compared to other repositories, (including various
architectures) so some additional files are located in `utils/docker` to handle different CI's.
Example of such extra file is `arm64.blacklist`, which lists tests not applicable for `arm64` architecture.

As I mentioned in the beginning, Memkind approach is a little simplified and unique. There's still
[utils/docker directory](https://github.com/memkind/memkind/tree/master/utils/docker), but
it does not contain a separate `images` sub-directory, and it does not push images to GitHub's "registry".
It comes with great, pre-defined `docker_run_*.sh` scripts (used, e.g., in GitHub's workflow) and
an extra `run_local.sh` script (similar to PMDK). There's also a well-written README file
describing all files and the building process.

Finally, not all repositories share the same number of docker images. Each repository has its own
set of OSes, depending on the requirement for a specific library. We started some efforts
to unify it, but this isn't a piece of cake, and it would require some time to finish this up.
Currently, some "common" dockers are located in a separate repository called
[dev-utils-kit](https://github.com/pmem/dev-utils-kit).

# Summary

To summarize dockers' usage in PMDK, I'd have to say: it's very nice to have them
working in our CI! As I described above, there are multiple benefits of introducing them
into our development process, with "reproducibility" and "portability" as one of the greatest
(in my opinion). Overall, they add a little complexity to our workflows, but after
you get used to these virtual environments - they are great!

As for our various repositories - the testing environments and Continuous Integrations
come in a few flavors, but they are generally quite similar. The differences result from
various needs and different developers, but when you familiarize yourself with any of the
repositories, the other ones should be just as readable.

---
title: Escape Analysis in PMDK
author: lplewa
layout: post
identifier: escape_analiysis_H1_2021
---

### Introduction

We believe that PMDK has a really good quality assurance process but there is always a place to improve.
To try to find an area for improvement we decided to try a new process called Escape Analysis.
Escape is a confirmed bug that was reported by someone who is not involved in PMDK development.
As a PMDK team, we want to periodically take a time to look at those bugs,
and think about why we didn't find them ourselves, and how we can improve our process to find such issues sooner.
Of course, an important part of our development process is that every change in the code
should come with tests that prove that the change is correct and works.
For bug fixes, this means that we have to write a test that reproduces a problem, then make a bug fix, so that test will pass.
But anyway escape analysis allows us to double-check if we don't miss any occasion to improve the quality of the PMDK.

### How do we an Escape Analysis
First of all, we have to find all bugs which were reported at this time and select those which meet the requirements of being categorized as an Escape.
For a reminder, there are two things with must be true, to say that a bug is an Escape:
* It must be a confirmed bug.
* It must be found by someone who is not involved in PMDK development.

When we have such a list, we are going thru this list, and try to find what failed in our process that we didn't found this bug by ourselves.
To help us in this process we created "QA escape" labels. We use them to group escapes, which have a common issue in our process.
The last step is to find improvements in our process and add them to our backlog to implement.

### Escape Analysis H1 2021

So after this short introduction, it's time to share with you the results of our experiment.

Since 01-01-2021 6 escapes were found. Five were found in PMDK repository, and one in libpmemobj-cpp.

Two first bugs on our list are:
[Make Check failed on Arm64][5255] and [pmempool_sync/TEST6 fails on ppc64le using device dax][5112]
Both issues we labeled as "QA Escape: Experimental Feature". Arm and ppc ports are marked as experimental,
and we do not certify their quality, so it's not a surprise, that they have bugs.
Issues like this show, that if we decide to fully support such features we will have to put a lot of effort into their validation.

The next one is embarrassing. [Multiple inconsistency bugs in libpmemobj array example program][5217] we marked as "QA Escape: known issue".
We knew that this example is broken and for some reason, we forgot to remove it. Lesson learned: if something is broken in documentation, an example, or a comment,
fix it, or delete it immediately. It's better to don't have documentation than to have misleading one.

The fourth one is tricky [LTO breaks the build][5197]. We weren't sure if we should categorize it as a bug, or as a feature. We still don't support LTO,
which is enabled by default in a growing number of distributions, so this becomes a pressing problem.
We disabled LTO in PMDK for now, and we are working to enable it later. Anyway, we were aware of this issue so we marked it as a known issue too.

The last escape in the PMDK repository was a [Missing recovery for hashmap atomic in mapcli][5220]. We created really limited tests for our examples
they are mostly limited to compile and run it in the most basic scenario, just to ensure if its still "works". This issue and the array [one][5217] show
that we should consider adding additional tests for our examples. It is why we labeled it as "QA Escape: Missing test".

The only escape found in the libpmemobj-cpp repository is related to missing documentation for pmem::obj::transaction::manual class.
This class is a wrapper around C transactions API from pmemobj.
It's functionality is very similar to the C counterpart but it misses all the description (e.g. about transaction stages) which can be found in pmemobj manpages.
This results in misuses of C++ API.

### Summary

Escape Analysis allowed us to find minor improvements in our QA process, so it fulfilled our expectations.
I would like to take a moment to say thank you to the entire PMDK team for their outstanding job in ensuring PMDK quality.
Also, we want to say thank you to all of you who invested time in testing, analyze code, or even filing a bug report.
This helps us to constantly improve PMDK, so others users will have a better experience with it.

Did you find this blog post interesting, maybe you want to read more about how we develop and test PMDK.
Let us know in the comments.




[5255]https://github.com/pmem/pmdk/issues/5255
[5112]https://github.com/pmem/pmdk/issues/5112
[5217]https://github.com/pmem/pmdk/issues/5217
[5197]https://github.com/pmem/pmdk/issues/5197
[5220]https://github.com/pmem/pmdk/issues/5520
[1061]https://github.com/pmem/libpmemobj-cpp/issues/1061



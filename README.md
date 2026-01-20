# PROJECT NOT UNDER ACTIVE MANAGEMENT #  
This project will no longer be maintained by Intel.  
Intel has ceased development and contributions including, but not limited to, maintenance, bug fixes, new releases, or updates, to this project.  
Intel no longer accepts patches to this project.  

If you have an ongoing need to use this project, are interested in independently developing it, or would like to maintain patches for the open source software community, please create your own fork of this project.  

pmem.github.io
==============

### pmem.io web site

This repo contains the top-level content for the pmem.io website.

Files on the “main” branch of this repo contain the source which is run through
Hugo automatically when changed via the GitHub Actions configuration in:
```
.github/workflows/gh-pages.yml
```

The result is deployed to the “gh-pages” branch (so you should not edit those files
Directly or they will be overwritten by the next deployment).

Most of the basic content is under data/en, for example, the content for the URL
```
https://pmem.io/about/
```
is in the file:
```
data/en/about.yml
```

The contents of this site are BSD-licensed open source.

Send questions or comments to [piotr.balcer@intel.com](mailto:piotr.balcer@intel.com).

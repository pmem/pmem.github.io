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

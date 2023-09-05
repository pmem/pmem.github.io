---
# Blog post title
title: "Update on PMDK and our long term support strategy"

# Blog post creation date
date: 2022-11-16T00:00:00+02:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ""

# Blog post thumbnail
# eg: image: "/images/posts/my_blog_thumbnail.png"
image: ""

# Blog post author
author: "Piotr Balcer"

# Categories to which this blog post belongs
blogs: ['PMDK']

# Blog tags
tags: ["CXL", "PMEM", "PMDK"]

# Blog post type
type: "post"
---

Following Intel’s Q2 earnings call announcing the wind-down of Intel’s Optane business, Intel will also be winding down its investment in new feature development on Persistent Memory Development Kit (PMDK) libraries and adjusting long-term support and maintenance plans accordingly.

The PMDK collection of open-source libraries and tools hosted on GitHub will continue to be available to the software community.  Documentation and resources via the pmem.io website will also remain available. 

The PMDK suite builds on the SNIA NVM Programming Model for persistent memory, and, in several cases, the standard volatile memory programming model as well.  While the design of PMDK is vendor-neutral and memory device neutral, Intel’s staffing for the development and validation of PMDK has been motivated by the Intel(R) Optane(TM) product line.  Intel will continue to support PMDK during the product lifetime of the Intel(R) Optane(TM) persistent memory 100 series and 200 series.
Intel announced the end of future development of Optane products. Documentation and resources via the pmem.io website will be updated with specific support plans for each library.  Intel continues to encourage community contributions to PMDK.

We are engaging with customers to determine their specific needs for ongoing PMDK support. Intel plans on providing critical security and bug fixes to the PMDK libraries until the associated Optane products reach the end of life.

Intel encourages community members to become maintainers for libraries they are interested in. We are in discussions with customers and co-collaborators on PMDK to take on maintainer roles to continue the long-term development of PMDK and pmem.io. We will support the transition to support these new maintainers as they take on the leadership of the project.

The PMDK libraries, as well as the applications using them, are expected to work with the emerging CXL memory products without code changes, as the programming models for both persistent and volatile memory remains the same.  Of course, the power and performance characteristics of the solution will depend on the power and performance specifics of the CXL attached devices, which may be different from Optane devices.  

Intel has invested in bringing innovative new usage models to memory tiering and persistent memory via PMDK. These usage models will continue with the introduction of a new class of third-party CXL attached memory devices. We hope that the work we’ve done with PMDK and pmem.io will benefit the larger open-source community for years to come and look forward to welcoming new maintainers to carry it forward.

Thank you for your continued partnership.

Regards,

David Tuhy- Vice President, Intel Data Center & AI Group, GM Intel Optane Group

Andy Rudoff – Persistent Memory Software Architect, Intel Labs

## Frequently Asked Questions

> **Q:** Which libraries will have support for bug fixes?
>
> **A:** Please see below for a summary table of the specific support. Intel will only address issues and bug fixes related to Intel Optane PMem products.

{{<table "table table-striped table-bordered">}}
| Library | URL | Support Summary |
|---|---|---|
| libpmem<br /> libpmem2<br /> libpmemobj<br /> libpmempool<br /> pmempool<br /> pmreorder | https://github.com/pmem/pmdk | Issue Reporting and Bug Fixes via GitHub and pmdk_support@intel.com<br /> Seeking new co-maintainer(s) to continue development towards production release |
| valgrind<br /> pmemcheck | https://github.com/pmem/valgrind | Issue Reporting and Bug Fixes via GitHub and pmdk_support@intel.com<br /> Seeking new co-maintainer(s) to continue development towards production release |
| libmemkind<br /> TieredMemDB | https://github.com/memkind/memkind<br /> https://github.com/TieredMemDB | Issue Reporting and Bug Fixes via GitHub and pmdk_support@intel.com |
| PMUL<br /> LLPL<br /> PCJ | https://github.com/pmem/pmul<br /> https://github.com/pmem/llpl<br /> https://github.com/pmem/pcj | Issue Reporting and Bug Fixes via GitHub and pmdk_support@intel.com<br /> Intel JAVA Team Maintaining |
| ndctl | https://github.com/pmem/ndctl | Issue Reporting and Bug Fixes via GitHub and pmdk_support@intel.com<br /> Intel Linux Kernel Team Maintaining and continuing development for CXL-based memory devices & NVDIMMs |
| librpma | https://github.com/pmem/rpma | No further support or maintenance planned |
| pmdk-convert | https://github.com/pmem/pmdk-convert | No further support or maintenance planned |
| Libpmemstream | https://github.com/pmem/pmemstream | No further support or maintenance planned |
| Libpmemblk<br /> Libpmemlog | https://github.com/pmem/pmdk | No further support or maintenance planned |
| Libvmem | https://github.com/pmem/vmem | No further support or maintenance planned |
| Libpmemobj-cpp | https://github.com/pmem/libpmemobj-cpp | No further support or maintenance planned |
| Libpmemkv<br/> and its language bindings | https://github.com/pmem/pmemkv | No further support or maintenance planned |
| pmemkv-java | https://github.com/pmem/pmemkv-java | No further support or maintenance planned |
| pmemkv-nodejs | https://github.com/pmem/pmemkv-nodejs | No further support or maintenance planned |
| pmemkv-python | https://github.com/pmem/pmemkv-python | No further support or maintenance planned |
| pmemkv-ruby | https://github.com/pmem/pmemkv-ruby | No further support or maintenance planned |
| pmemkv-bench | https://github.com/pmem/pmemkv-bench | No further support or maintenance planned |
| Libminiasync | https://github.com/pmem/miniasync | No further support or maintenance planned |
| vmemcache | https://github.com/pmem/vmemcache | No further support or maintenance planned |
| pmemfile | https://github.com/pmem/pmemfile | No further support or maintenance planned |
| kb.pmem.io | https://github.com/pmem/knowledge-base | No further support or maintenance planned |
| vltrace | https://github.com/pmem/vltrace | No further support or maintenance planned |
| pynvm | https://github.com/pmem/pynvm | No further support or maintenance planned |
| syscall-intercept | https://github.com/pmem/syscall_intercept | External maintainer took over the maintenance and development of this repository<br /> Issues reporting and bug fixes via GitHub only |
{{</table>}}

> **Q:** What documentation is available for PMDK?
>
> **A:** The documentation for PMDK is available today and will continue to be available via both docs.pmem.io as well as https://github.com/pmem/docs.

> **Q:** How do I get help with PMDK?
>
> **A:** The official means to report issues or get Intel support will be via issue submission on the GitHub repositories and sending a request with details to pmdk_support@intel.com. 

> **Q:** What will happen to the open forum and slack channel connected on PMEM.io? 
>
> **A:** We expect that Intel developers and other community members will continue to communicate and support via the open forum and slack channel through the end of Q1’23. The ongoing availability of the open forum and slack channel on pmem.io depends on the results of ongoing discussions with community co-maintainers.

> **Q:** Will Intel be testing any of the PMDK libraries on other products in addition to Optane?
>
> **A:** No, Intel will focus testing on Optane products. There are no constraints to community members using PMDK libraries with DRAM, NVDIMMs, or CXL-attached memory devices.

> **Q:** Will the PMDK libraries function on CXL-attached devices?
>
> **A:** If abstractions in the application and OS user space have been implemented according to CXL specifications, we expect that the PMDK libraries can be functionally used with CXL-attached memory devices. 

> **Q:** When will support for the libraries end?
>
> **A:** For the libraries Intel continues to support, the critical security and bug fixes will follow the Intel Optane persistent memory lifecycle. Once an Optane persistent memory product reaches end of life (EOL), the associated bug fix support for PMDK on that product will also end. Some libraries will not have bug fix support, those are outlined in the table above.

###### [This entry was edited on 2023-03-28 to reflect the latest [announcement](/announcements/2023/customer-letter-march-2023/)]

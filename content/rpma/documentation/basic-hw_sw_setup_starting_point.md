---
title: "Basic - HW & SW setup starting point | RPMA"
draft: false
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "Basic - HW & SW setup starting point"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### HW & SW setup starting point description

In order to make the optimal use of your Remote Persistent Memory:

1. Make sure you have all following required HW components <a href="https://software.intel.com/content/www/us/en/develop/articles/qsg-intro-to-provisioning-pmem.html">[1]</a> and check: how many and what kind of those items do you have? Which socket/slot/channel are they located in?:

    * main system board supporting 2nd generation Intel® Xeon® Scalable processors,
    * 2nd generation Intel® Xeon® Scalable CPUs,
    * Intel® Optane™ persistent memory modules,
    * DRAM DIMMs and
    * RDMA-capable NICs

2.  Verify that you have the required Linux kernel version: the Linux NVDIMM/PMem drivers are enabled by default, starting with Linux mainline kernel 4.2. We recommend mainline kernel version 4.19 or later to deliver Reliability, Availability and Serviceability (RAS) features required by the Persistent Memory Development Kit (PMDK) <a href="https://software.intel.com/content/www/us/en/develop/articles/qsg-part2-linux-provisioning-with-optane-pmem.html">[2]</a>

3. Populate your DIMMs according to one of the recommended topologies as described in detail in the 1.2.4.1 chapter “Recommended Topologies” of “Intel® Optane™ DC Persistent Memory Quick Start Guide” <a href="https://www.intel.com/content/dam/support/us/en/documents/memory-and-storage/data-center-persistent-mem/Intel-Optane-DC-Persistent-Memory-Quick-Start-Guide.pdf">[3]</a>.

4. Configure your memory modules according to “Provision Intel® Optane™ DC Persistent Memory in Linux” <a href="https://software.intel.com/content/www/us/en/develop/videos/provisioning-intel-optane-dc-persistent-memory-modules-in-linux.html">[4]</a> and “Optimizing Memory Performance” <a href="https://pmem.io/rpma/documentation/basic-reqs-optimizing-memory-performance.html">[5]</a> in order to configure your memory properly and optimize its performance.

### References

* [1] [Quick Start Guide Part 1: Persistent Memory Provisioning Introduction - Hardware Requirements](https://software.intel.com/content/www/us/en/develop/articles/qsg-intro-to-provisioning-pmem.html)
* [2] [Quick Start Guide Part 2: Linux Provisioning for Intel® Optane™ Persistent Memory](https://software.intel.com/content/www/us/en/develop/articles/qsg-part2-linux-provisioning-with-optane-pmem.html)
* [3] [Intel® Optane™ DC Persistent Memory Quick Start Guide](https://www.intel.com/content/dam/support/us/en/documents/memory-and-storage/data-center-persistent-mem/Intel-Optane-DC-Persistent-Memory-Quick-Start-Guide.pdf)
* [4] [Provision Intel® Optane™ DC Persistent Memory in Linux](https://software.intel.com/content/www/us/en/develop/videos/provisioning-intel-optane-dc-persistent-memory-modules-in-linux.html)
* [5] [Optimizing Memory Performance](/rpma/documentation/basic-reqs-optimizing-memory-performance.html)

### Disclaimer

Performance varies by use, configuration and other factors.

No product or component can be absolutely secure.

Your costs and results may vary.

Intel technologies may require enabled hardware, software or service activation.

Intel disclaims all express and implied warranties, including without limitation, the implied warranties of merchantability, fitness for a particular purpose, and non-infringement, as well as any warranty arising from course of performance, course of dealing, or usage in trade.
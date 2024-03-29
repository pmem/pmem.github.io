---
title: "NDCTL"
draft: false
slider_enable: true
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "NDCTL"
# Description
description: 'Manage and monitor non-volatile memory devices in Linux. Create and manage dimms, regions, and namespaces, update firmware, perform secure erase operations, and much more!'
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
type: '/ndctl'
---

### ndctl

Utility library for managing the libnvdimm (non-volatile memory device) sub-system in the Linux kernel

### Build

``` sh
./autogen.sh
./configure CFLAGS='-g -O2' --prefix=/usr --sysconfdir=/etc --libdir=/usr/lib64
make
make check
sudo make install
```

There are a number of packages required for the build steps that may not be installed by default. For information about the required packages, see the "BuildRequires:" lines in ndctl.spec.in.

https://github.com/pmem/ndctl/blob/master/ndctl.spec.in

### Documentation

* [NVDIMM kernel sub-system](https://www.kernel.org/doc/html/latest/driver-api/nvdimm/index.html)
* [Getting started guide](https://nvdimm.wiki.kernel.org/start)
* [User Guide](https://docs.pmem.io/ndctl-user-guide/)

### Unit Tests

The unit tests run by `make check` require the nfit_test.ko module to be loaded. To build and install nfit_test.ko:

1. Obtain the kernel source. For example,

    ``` sh
    git clone -b libnvdimm-for-next git://git.kernel.org/pub/scm/linux/kernel/git/nvdimm/nvdimm.git
    ```

2. Skip to step 3 if the kernel version is >= v4.8. Otherwise, for kernel versions < v4.8, configure the kernel to make some memory available to CMA (contiguous memory allocator). This will be used to emulate DAX.

    ``` sh
    CONFIG_DMA_CMA=y
    CONFIG_CMA_SIZE_MBYTES=200
    ```
    or<br />
    `cma=200M` on the kernel command line.

3. Compile the libnvdimm sub-system as a module, make sure "zone device" memory is enabled, and enable the btt, pfn, and dax features of the sub-system:

    ``` sh
    CONFIG_X86_PMEM_LEGACY=m
    CONFIG_ZONE_DEVICE=y
    CONFIG_LIBNVDIMM=m
    CONFIG_BLK_DEV_PMEM=m
    CONFIG_ND_BLK=m
    CONFIG_BTT=y
    CONFIG_NVDIMM_PFN=y
    CONFIG_NVDIMM_DAX=y
    CONFIG_DEV_DAX_PMEM=m
    CONFIG_ENCRYPTED_KEYS=y
    ```

4. Build and install the unit test enabled libnvdimm modules in the following order. The unit test modules need to be in place prior to the `depmod` that runs during the final `modules_install`

    ``` sh
    make M=tools/testing/nvdimm
    sudo make M=tools/testing/nvdimm modules_install
    sudo make modules_install
    ```

5. Now run `make check` in the ndctl source directory, or `ndctl test`, if ndctl was built with `--enable-test`.

### Troubleshooting

The unit tests will validate that the environment is set up correctly before they try to run. If the platform is misconfigured, i.e. the unit test modules are not available, or the test versions of the modules are superseded by the "in-tree/production" version of the modules `make check` will skip tests and report a message like the following in test/test-suite.log:

``` sh
SKIP: libndctl
==============
test/init: nfit_test_init: nfit.ko: appears to be production version: /lib/modules/4.8.8-200.fc24.x86_64/kernel/drivers/acpi/nfit/nfit.ko.xz
__ndctl_test_skip: explicit skip test_libndctl:2684
nfit_test unavailable skipping tests
```

If the unit test modules are indeed available in the modules 'extra' directory the default depmod policy can be overridden by adding a file to /etc/depmod.d with the following contents:

``` sh
override nfit * extra
override device_dax * extra
override dax_pmem * extra
override dax_pmem_core * extra
override dax_pmem_compat * extra
override libnvdimm * extra
override nd_blk * extra
override nd_btt * extra
override nd_e820 * extra
override nd_pmem * extra
```

The nfit_test module emulates pmem with memory allocated via vmalloc(). One of the side effects is that this breaks 'physically contiguous' assumptions in the driver. Use the '--align=4K option to 'ndctl create-namespace' to avoid these corner case scenarios.
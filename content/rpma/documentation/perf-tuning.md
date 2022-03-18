---
title: "Performance - Tuning | RPMA"
draft: false
layout: "library"
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "Performance - Tuning"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---

### Performance - Tuning

A collection of the best methods, practices, and configuration values known for delivering the best possible performance in a consistent and predictable way for the workloads built on the librpma library.

### General notes

The *Performance - Tuning* aims to collect all tested and proven procedures with known beneficial effects on the performance of the workloads built on the librpma library. Nonetheless, each change of configuration should be set up and tested in a testing environment before being applied to a production system. Backing up all data and configuration settings prior to tuning is also recommended.

### BIOS settings

### Cascade Lake

* Platform and CPU related <a href="https://software.intel.com/content/www/us/en/develop/articles/optimizing-computer-applications-for-latency-part-1-configuring-the-hardware.html">[1.1]</a>
    * Disable lower CPU power states: C-states<a href="https://software.intel.com/content/www/us/en/develop/articles/power-management-states-p-states-c-states-and-package-c-states.html">[1.2]</a>, C1E, and memory and PCI-e power saving states. Settings vary from vendor to vendor so some of them may not be available for you e.g.:
        * Power and Performance - CPU C State Control - Package C-State - **C0/C1 state**
        * Power and Performance - CPU C State Control - C1E - **Disabled**
    <br /><br />
    * Check for other settings that might influence performance. This varies greatly by OEM, but should include anything power related, such as fan speed settings (more is better) e.g.:
        * Power and Performance - CPU Power and Performance Policy - **Performance**
        * System Acoustic and Performance Configuration - Set Fan Profile - **Performance**
    <br /><br />

### Ice Lake

* Platform and CPU related [[1.1]][opt-part-1]
    * Disable lower CPU power states: C-states[[1.2]][power-states] and memory and PCI-e power saving states. Settings vary from vendor to vendor so some of them may not be available for you e.g.:
        * Advanced Power Management Configuration - Package C State Control - Package C-State - **C0/C1 state**
    <br /><br />
    * Check for other settings that might influence performance. This varies greatly by OEM, but should include anything power related, such as fan speed settings (more is better) e.g.:
        * System Acoustic and Performance Configuration - Set Fan Profile - **Performance**

### Cascade Lake

* PMem-related
    * Configure maximum available operating power for your PMem devices **[XXX source and details are missing]**. **Note:** Different sizes of PMem devices have different performance capabilities. If it is important for you, pick the right one for your application e.g.: <a href="https://www.intel.com/content/www/us/en/products/docs/memory-storage/optane-persistent-memory/optane-persistent-memory-200-series-brief.html">[1.3]</a>
        * Memory Configuration - Average Power Budget - **18 mW**
        * Memory Configuration - NVM Performance Setting - **Latency Optimized**

### Ice Lake

* PMem-related
    * configure maximum available operating power for your PMem devices **[XXX source and details are missing]**. **Note**: Different sizes of PMem devices have different performance capabilites. If it is important for you, pick the right one for your application e.g.: [[1.3]][pmem-200-brief]
        * Memory Configuration - PMem Configuration - 200 Series PMem Average Power Limit (in mW) - **15 mW**
        * Memory Configuration - PMem Configuration - PMem Performance Setting - **BW Optimized**

#### Not yet confirmed <a href="https://software.intel.com/content/www/us/en/develop/articles/optimizing-computer-applications-for-latency-part-1-configuring-the-hardware.html">[1.1]</a>

### Cascade Lake

* Ensure that Intel® Turbo Boost<a href="https://www.intel.com/content/www/us/en/architecture-and-technology/turbo-boost/turbo-boost-technology.html">[1.4]</a> is on.
    * Power and Performance - CPU P State Control - Intel Turbo Boost Technology - **Enabled**
    * Power and Performance - CPU P State Control - Energy Efficient Turbo - **Disabled**
    <br /><br />
* Disable hyper-threading to reduce variations in latency (jitter).
    * Processor Configuration - Intel(R) Hyper-Threading Tech - **Disabled**
    <br /><br />
* Disable any virtualization options.
    * Processor Configuration - Intel(R) Virtualization Technology - **Disabled**
    <br /><br />
* Disable any monitoring options.
    * Memory Configuration - Thermal Monitor - **Disabled**
    <br /><br />
* Disable Hardware Power Management, introduced in the Intel® Xeon® processor E5-2600 v4 product family. It provides more control over power management, but it can cause jitter and so is not recommended for latency-sensitive applications.

### Ice Lake

* Disable hyper-threading to reduce variations in latency (jitter).
    * Processor Configuration - Intel(R) Hyper-Threading Tech - **Disabled**
    <br /><br />
* Disable any monitoring options.
    * Advanced Power Management Configuration - CPU Thermal Managment - Thermal Monitor - **Disable**
    <br /><br />
* Disable Hardware Power Management, introduced in the Intel® Xeon® processor E5-2600 v4 product family. It provides more control over power management, but it can cause jitter and so is not recommended for latency-sensitive applications.
    * Advanced Power Management Configuration - - CPU P State Control - Energy Efficient Turbo - **Disable**
    * Advanced Power Management Configuration - - CPU P State Control - - Turbo Mode - **Disable**

#### References

* [1.1] [Optimizing Computer Applications for Latency: Part 1: Configuring the Hardware](https://software.intel.com/content/www/us/en/develop/articles/optimizing-computer-applications-for-latency-part-1-configuring-the-hardware.html)
* [1.2] [Intel: Power Management States: P-States, C-States, and Package C-States](https://software.intel.com/content/www/us/en/develop/articles/power-management-states-p-states-c-states-and-package-c-states.html)
* [1.3] [Intel® Optane™ Persistent Memory 200 Series Brief](https://www.intel.com/content/www/us/en/products/docs/memory-storage/optane-persistent-memory/optane-persistent-memory-200-series-brief.html)
* [1.4] [Intel® Turbo Boost Technology 2.0 - Higher Performance When You Need It Most](https://www.intel.com/content/www/us/en/architecture-and-technology/turbo-boost/turbo-boost-technology.html)

### CPU

Disabling `intel_pstate` is recommended for latency sensitive workloads. <a href="https://access.redhat.com/sites/default/files/attachments/201501-perf-brief-low-latency-tuning-rhel7-v2.1.pdf">[2.1]</a>

#### CPU driver

To disable `intel_pstate` CPU driver edit your `/etc/default/grub` and add the following to the kernel command line:

``` sh
GRUB_CMDLINE_LINUX_DEFAULT="intel_pstate=disable"
```

Rebuild the grub.cfg file as follows (make sure you use the path adjusted to your OS):

* On BIOS-based machines, issue the following command:

``` sh
sudo grub2-mkconfig -o /boot/grub2/grub.cfg
```

* On UEFI-based machines, issue the following command:

``` sh
sudo grub2-mkconfig -o /boot/efi/EFI/redhat/grub.cfg
```

This change requires a reboot to take effect.

#### CPU scaling governor

Set the most performant scaling governor:

``` sh
sudo cpupower frequency-set --governor performance
```

#### CPU frequency

List available frequencies and pick the biggest one.

``` sh
cpupower frequency-info | grep steps
  available frequency steps:  2.30 GHz, 2.30 GHz, 2.20 GHz, 2.10 GHz, 2.00 GHz, 1.90 GHz, 1.80 GHz, 1.70 GHz, 1.60 GHz, 1.50 GHz, 1.40 GHz, 1.30 GHz, 1.20 GHz, 1.10 GHz, 1000 MHz
```

Set the frequency range to the single biggest available value:

``` sh
FREQ=2.3Ghz
sudo cpupower frequency-set --min $FREQ
sudo cpupower frequency-set --max $FREQ
```

#### References

* [2.1] <a href="https://access.redhat.com/sites/default/files/attachments/201501-perf-brief-low-latency-tuning-rhel7-v2.1.pdf">Low Latency Performance Tuning for Red Hat Enterprise Linux 7</a>

### RDMA-capable network interface (RNIC)

#### PCIe attributes <a href="https://community.mellanox.com/s/article/understanding-pcie-configuration-for-maximum-performance">[3.1]</a>

* Width - provide for the RNIC the maximum number of PCIe lanes it is capable of using
* Speed - attach the RNIC to PCIe interface of maximum supported speed
* Max Payload Size - learn what the Max Payload Size for your system is and adjust expectations accordingly
* Max Read Request - configure the maximum available read request value

Knowing all of these attributes allows calculating the RNIC bandwidth limitation. For details please see “Understanding PCIe Configuration for Maximum Performance” Chapter <a href="https://community.mellanox.com/s/article/understanding-pcie-configuration-for-maximum-performance#Calculating-PCIe-Limitations">"Calculating PCIe Limitations"</a>.

#### Link mode <a href="https://community.mellanox.com/s/article/howto-change-the-ethernet-port-speed-of-mellanox-adapters--linux-x">[3.2]</a>

List supported link modes:

``` sh
$ ethtool ens785f0
Settings for ens785f0:
        Supported ports: [ Backplane ]
        Supported link modes:   1000baseKX/Full
# ...
                                100000baseKR4/Full
                                100000baseSR4/Full
                                100000baseCR4/Full
                                100000baseLR4_ER4/Full
# ...
```

Set the maximum available value:

``` sh
$ sudo ethtool -s ens785f0 speed 100000 autoneg off
```

#### Maximum Transmission Unit (MTU) <a href="https://community.mellanox.com/s/article/mtu-considerations-for-roce-based-applications">[3.3]</a>

Set MTU to the maximum recommended value. In most cases, it is 4200.

``` sh
$ ifconfig ens785f0 mtu 4200
```

#### References

* [3.1] [Mellanox: Understanding PCIe Configuration for Maximum Performance](https://community.mellanox.com/s/article/understanding-pcie-configuration-for-maximum-performance)
* [3.2] [Mellanox: HowTo Change the Ethernet Port Speed of Mellanox Adapters (Linux)](https://community.mellanox.com/s/article/howto-change-the-ethernet-port-speed-of-mellanox-adapters--linux-x)
* [3.3] [Mellanox: MTU Considerations for RoCE based Applications](https://community.mellanox.com/s/article/mtu-considerations-for-roce-based-applications)

### Running workloads
In order to learn how to run the individual workloads, please visit: https://github.com/pmem/rpma/master/tools/perf/BENCHMARKING.md

### Disclaimer

Performance varies by use, configuration and other factors.

No product or component can be absolutely secure.

Your costs and results may vary.

Intel technologies may require enabled hardware, software or service activation.

Intel disclaims all express and implied warranties, including without limitation, the implied warranties of merchantability, fitness for a particular purpose, and non-infringement, as well as any warranty arising from course of performance, course of dealing, or usage in trade.

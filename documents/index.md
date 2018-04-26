---
title: Documents
---

#### Introductory Materials

This [USENIX ;login: article](https://www.usenix.org/system/files/login/articles/login_summer17_07_rudoff.pdf),
published in the Summer of 2017,
provides an overview of the persistent memory programming model
and the related software work that has been taking place.
It includes a very high-level description of programming with
[PMDK][pmdk], although it refers to it by the old name,
[NVM Libraries]({% post_url 2017-12-11-NVML-is-now-PMDK %}).
This article is actually a follow-on to an
[earlier article](https://www.usenix.org/system/files/login/articles/08_rudoff_040-045_final.pdf),
published in the Summer 2013 issue of USENIX ;login:.

The [Persistent Memory Summit 2018](https://www.snia.org/pm-summit), held
January 24th, 2018, provided a day full of informative talks and panels
on persistent memory (slides and videos of the sesssions are available at
above link).

The projects on this site,
like [PMDK][pmdk], are product-neutral, meant to work on any
product that provides the persistent memory programming model.
One such product is Intel's
[3D XPoint](https://www.youtube.com/watch?v=Wgk4U4qVpNY).
Intel has assembled a collection of persistent memory programming
documentation, webinars, videos, and related materials on the
[Intel Developer Zone](https://software.intel.com/en-us/persistent-memory).

Dozens of companies have collaborated on a common
persistent memory programming model in the SNIA NVM Programming
Technical Workgroup (TWG).  SNIA provides a
[persistent memory page](http://www.snia.org/PM) on their web site
with links to recent presentations, persistent memory standards and
white papers, etc.  The SNIA activity continues to be very active,
working on areas like remote persistent memory and security.  The
original programming model specification put forward the basic
idea that applications access persistent memory as _memory-mapped files_,
a concept which has appeared as the **DAX** feature (short for
_Direct Access_) on both [Linux](https://nvdimm.wiki.kernel.org)
and [Windows](https://channel9.msdn.com/Events/Build/2016/P470).


#### Standards

The [SNIA NVM Programming Model](https://www.snia.org/sites/default/files/technical_work/final/NVMProgrammingModel_v1.2.pdf)
standard describes the basic programming model used for persistent memory
programming.

The [ACPI Specification](http://www.uefi.org/specifications), starting
with version 6.0, defines the _NVDIMM Firmware Interface Table_ (NFIT)
which is how the existence of persistent memory is communicated to
operating systems.  The specification also describes how NVDIMMs are
partitioned into _namespaces_, methods for communicating with NVDIMMs, etc.

The [UEFI Specification](http://www.uefi.org/specifications), covers
other NVDIMM-related topics such as the _Block Translation Table_ (BTT)
which allows an NVDIMM to provide block device semantics.

Before the above two standards were developed, the
[NVDIMM Namespace Specification](NVDIMM_Namespace_Spec.pdf) [pdf] described
the namespace and BTT mechanisms.  The link to the outdated document
is maintained here for reference.

#### Related Specifications

The [NVDIMM Driver Writers Guide](NVDIMM_DriverWritersGuide-July-2016.pdf) [pdf]
is targeted to driver writers for NVDIMMs that adhere to the NFIT tables in the
Advanced Configuration and Power Interface (ACPI) V6.0 specification,
the Device Specific Method (DSM) specification and the NVDIMM Namespace Specification.
This document specifically discusses the block window HW interface and persistent memory
interface that Intel is proposing for NVDIMMs. A version of the document with
[change bars](NVDIMM_DriverWritersGuide-July-2016_wChanges.pdf) [pdf] from the previous
version is also available.

The [NVDIMM DSM Interface](NVDIMM_DSM_Interface-V1.7.pdf?1) [pdf], Version 1.7,
is targeted to writers of BIOS and OS drivers for NVDIMMs whose design adheres to the
NFIT Tables in the ACPI specification.  The document specifically discusses the
NVDIMM Device Specific Method (_DSM) example.

[pmdk]: http://pmem.io/pmdk/ "Persistent Memory Development Kit"

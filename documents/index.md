---
title: Documents
---

The following documents are available:

* The [NVDIMM Namespace Specification](NVDIMM_Namespace_Spec.pdf) [pdf] describes
  a method for sub-dividing persistent memory into _namespaces_, which are
  analogous to NVM Express Namespaces.  The document also describes the
  _Block Translation Table_  (BTT) layout which provides single sector write atomicity
  for block devices built on pmem (see [this blog post]({% post_url 2014-09-23-btt %}) for
  more information on the BTT).

  **NOTE:** This document and blog post describe the original V1.1 Namespace labels and
  V1.0 BTT layout.  To improve interoperability and facilitate wider adoption of these
  specifications, the contents of these documents are now standardized through the UEFI
  and ACPI specifications.  Beginning with UEFI V2.7 the NVDIMM Label Protocol chapter
  replaces the namespace and label portions of the original Namespace specification
  and describe V1.2 labels.  The BTT Layout chapter of the UEFI specification
  standardizes V2.0 of the BTT.  The ACPI 6.2 specification adds common Namespace label
  Interfaces for accessing the Label Storage Area on an NVDIMM device.
  See \_LSI, \_LSR, & \_LSW interface methods.
  It is recommended that new implementations move to the standardized versions of the
  Namespace labels and BTT as described in the
  [UEFI specification](http://www.uefi.org/sites/default/files/resources/UEFI_Spec_2_7.pdf) [pdf]
  and [ACPI specification](http://www.uefi.org/sites/default/files/resources/ACPI_6_2.pdf) [pdf].
  The versions of the Namespaces and BTT found here on _pmem.io_ and accompanying
  blog posts are considered deprecated and maintained here as a reference for original
  implementations.

* The [NVDIMM Driver Writers Guide](NVDIMM_DriverWritersGuide-July-2016.pdf) [pdf]
  is targeted to driver writers for NVDIMMs that adhere to the NFIT tables in the
  Advanced Configuration and Power Interface (ACPI) V6.0 specification,
  the Device Specific Method (DSM) specification and the NVDIMM Namespace Specification.
  This document specifically discusses the block window HW interface and persistent memory
  interface that Intel is proposing for NVDIMMs. A version of the document with
  [change bars](NVDIMM_DriverWritersGuide-July-2016_wChanges.pdf) [pdf] from the previous
  version is also available.

* The [NVDIMM DSM Interface](NVDIMM_DSM_Interface-V1.6.pdf) [pdf], Version 1.6,
  is targeted to writers of BIOS and OS drivers for NVDIMMs whose design adheres to the
  NFIT Tables in the ACPI specification.  The document specifically discusses the
  NVDIMM Device Specific Method (_DSM) example.

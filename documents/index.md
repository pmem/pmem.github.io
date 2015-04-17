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

* The [NVDIMM Driver Writers Guide](NVDIMM_Driver_Writers_Guide.pdf) [pdf]
  is targeted to driver writers for NVDIMMs that adhere to the NFIT tables in the
  Advanced Configuration and Power Interface (ACPI) V6.0 specification,
  the Device Specific Method (DSM) specification and the NVDIMM Namespace Specification.
  This document specifically discusses the block window HW interface and persistent memory
  interface that Intel is proposing for NVDIMMs.

* The [NVDIMM DSM Interface Example](NVDIMM_DSM_Interface_Example.pdf) [pdf]
  is targeted to writers of BIOS and OS drivers for NVDIMMs whose design adheres to the
  NFIT Tables in the ACPI V6.0 specification.  The document specifically discusses th
  NVDIMM Device Specific Method (_DSM) example.

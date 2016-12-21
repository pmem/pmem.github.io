---
title: NVML for Windows
author: peluse
layout: post
---

Throughout 2016 a team of engineers from Microsoft, Intel, HPE and HPI have
been working to port the NVML project to Windows and we are happy to
announce that Technical Preview release is now available!

Our main goal in porting the library was to make it as easy as
possible for developers to use the library in both Linux and Windows
environments. To meet this goal, we focused on these elements as we
undertook the effort:

* Maintain common API
* Reuse existing library code
* Reuse as much test code and semantics as possible

Our efforts have paid off! We are labeling this a Technical Preview
because we have yet to add UNICODE support for paths which is
generally considered a required item for Windows components. However,
the library is fully functional today provided wide characters are
not used. Here are some of the more interesting points about the
Windows port:

#### Common Code

You will only find a few areas where there are Widnows specific files
and/or directories in the repository and there were added only in
cases where there was really no other choice; OS specific implementations
of locks and threads for example. Use of OS compilation switches,
ifdef WIN32, are minimized and only used where breaking out the
implementation would cause either performance or readability issues.
Beyond items like those, everything else is common.

#### Test Code

The unit test framework for the original library implementation is a
combination of BASH, Perl and C. Although using native BASH in Windows
may happen sometime in the near future, the group chose to go with
Powershell for the Windows port.  The majority of the C code is common
minus a few minor tweaks here and there so basically anywhere you find
a BASH script controlling a test under Linux you can find the equivalent
Powershell script under Windows, this includes the unit test framework
as well as RUNTESTS.SH (RUNTESTS.PS1). The syntax and output of the
scripts are nearly identical.

#### Tools

To build the Windows versions, simply use Visual Studio to open up the
NVML.SLN file the source directory and build the debug and release
versions and you are ready to go. Details are in the README files.

The Windows unit tests are executed automatically when a pull request
is made, ReviewBoard is still used for reviews however AppVeyor controls
the build and test execution instead of Travis.

As part of the port, the team used Trello to track our backlog and will
continue to do so until the UNICODE work is complete at which time any
Windows issues will be visible right alongside Linux issues in Github.
The Trello webpage for the porting effort is publicly available at:
[NVML for Windows](https://trello.com/b/IMPSJ4Iu/nvml-for-windows}

#### Windows Non-Volatile Memory Support

Obviously NVML isn't much use under Windows without native NVM support.
The following two videos provide a great overview of Windows NVM
capabilities:

* [Using Non-volatile Memory (NVDIMM-N) as Block Storage in Windows Server 2016](https://channel9.msdn.com/Events/Build/2016/P466)
* [Using Non-volatile Memory (NVDIMM-N) as Byte-Addressable Storage in Windows Server 2016](https://channel9.msdn.com/Events/Build/2016/P470)


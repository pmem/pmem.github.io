---
# Blog post title
title: 'Running FIO with pmem engines'

# Blog post creation date
date: 2018-06-25T19:55:17-07:00

# Change to 'false' when publishing the blog post
draft: false

# Blog post description
description: ''

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ''

# Blog post thumbnail
# eg: image: "/images/my_blog_thumbnail.png"
image: ''

# Blog post author
author: 'lukaszstolarczuk'

# Categories to which this blog post belongs
blogs: ['FIO']

tags: []

# Redirects from old URL
aliases: ['/2018/06/25/fio-tutorial.html']

# Blog post type
type: 'post'
---

When we, the PMDK team, want to check performance of our library, either to see
if there was any regression or if our tweaks did a good job, we run benchmarks.
One of them is FIO. It helps us simulate synthetic traffic of reads and writes
to a pmem device. In this blog post I will introduce this tool and explain how
we commonly use it.

### Preparing the environment

For starters, links to needed software:

- [FIO][1fiolink]
- [NDCTL][ndctllnk]

And, of course, to execute any FIO tests using pmem engines you need PMDK
installed in your system.

Tests can be run on [emulated pmem][13rde117] or real hardware (devices
presented to system by NVDIMM Firmware Interface Table (NFIT), according to ACPI
Specification v6.0+). Either way you have to prepare device(s) before test
execution. First step is to configure a namespace (emulated pmem is delivered
already as a namespace). For this step you can use command `ndctl create-namespace`. There are two main options to configure namespace in our
case, either you choose mode `fsdax` or `devdax`. Namespace configured with
**fsdax** is a block device with support of dax operations. It can host a
dax-capable file system (e.g. ext4 or xfs) and should be configured like:
{{< highlight bash >}}
ndctl create-namespace --type=pmem --mode=fsdax --region=X [--align=4k]
mkfs.ext4 /dev/pmemX
mkdir /mnt/pmem
mount /dev/pmemX /mnt/pmem -o dax
{{< /highlight >}}
In all cases regions should be created and maintained using administrative tool
specific for your platform. Optional parameter **align** can be used to change
alignment, while default for both mentioned modes is 2M.

Namespace with **devdax** mode is a character device and is a raw access
analogue of filesystem-dax. Configuration is simpler, because there's no need
for file system preparation:
{{< highlight bash >}}
ndctl create-namespace --type=pmem --mode=devdax --region=X [--align=4k]
{{< /highlight >}}

To sum up, when you execute the following commands:
{{< highlight bash >}}
ls /mnt/pmem
ls /dev/dax\*
{{< /highlight >}}
You should see mounted file system or dev-dax device available to use.

### FIO

Installation of FIO is pretty straightforward and is described on [its
GitHub page][1fiolink]. Execution is also not complicated, but it
requires preparation of the actual benchmark workload (workload is a set
of options describing how FIO will generate the traffic for
measurements). We'll see how workloads look for specific jobs below, but
first a few general rules:

- jobs should run on a time-based manner with ramp-up time to generate traffic
  before the measurements (if `time_based` parameter is not set FIO will run until
  requests transfer data of given size; duration of a job would be unpredictable),
- numjobs parameter describes how many threads/processes FIO will spawn to do
  the job. This is the place to scale your workload - you can use more or less
  jobs to simulate wanted traffic and/or achieve wanted consumption level of
  available CPU resources,
- blocksize parameter tells how big chunks of data will be transferred; this
  will influence bandwidth (bigger bs usually means higher BW, because there's
  less requests and each request has its own overhead) and IOPS (lower bs usually
  means higher IOPS, because smaller requests are completed faster and therefore
  in a given unit of time more requests can be issued).
  For details on available parameters and options go to [HOWTO page][191te3w1].

FIO is generating I/O traffic using engines specific for the job. To specify
which engine is used in a job there's workload's option `ioengine=my_engine`.
All of them are described in [their c file][44ku0112] and all have corresponding
[examples][55ku0123]. Few of them are related to persistent memory:

**libpmem**

This engine reads/writes data using libpmem library. Works on a namespace
created in `fsdax` mode. Full example workload for generating traffic of
sequential reads using this engine can be found [here][fiolibpm]. There are
additional comments within the jobfile to explain specific parameters.

**dev-dax**

It also uses libpmem library, but as the name suggest it is specified to work
with device-dax devices. Our full example workload [DaxSeqR.fio][fiodevdx]
shows how to properly use FIO with /dev/dax. Since we don't work on a "regular"
file we use a little trick to achieve more realistic measurements - we set a
separate space for each thread doing reads/writes using option
`offset_increment=(int)GB`. This way threads' requests do not overlap each
other and results are not cached in the processor. In case FIO reads the same
part over and over (using different threads on the same space) it ends up not
reading from the device.

**pmemblk**

This engine is using libpmemblk library. Results delivered by this engine will
not show you the best performance of your hardware, only what this specific
library is capable of. While using this engine, `blocksize` and
`size` of a file are given as part of `filename` option, like here:
{{< highlight bash >}}
filename=/mnt/pmem6/testjob,512,1024000
#size=1024000M
#bs=512
{{< /highlight >}}

This is a bit different approach, comparing with other engines which use
parameter "bs" and "size" (see commented part above). Full example workload
doing traffic of sequential reads for pmemblk can be found [here][fiopmblk].

**mmap**

It's the most "basic" of mentioned engines, because its purpose is just to read
from/write to a memory mapped region. It can be used with pmem, but is not
tailor-made. It generates traffic doing memcpy to/from memory region. Difference
between this engine and `libpmem` is that it doesn't use PMDK library and hence
doesn't take advantages of functions specific for writing to persistent memory.
Again, full example working workload is available: [MmapSeqR.fio][fiommap1]

### Execution and results

Command to run FIO is: `fio [options] [jobfile] ...`.

Since we use workloads defined in a file (as opposed to specifying parameters
in command line), we execute FIO with command like:

`numactl -N 0 fio --output=my_workload.json --output-format=json my_workload.fio`

[numactl command][nctl1234] guarantees that processes are pinned to selected
numa node. The same can also be achieved by assigning CPU mask for FIO using
option `cpus_allowed`. We chose JSON format to save our results in
it, for more convenient automatic parsing. Last parameter is our input file.

In the resulting file (here called my_workload.json) we look for a list
of ["jobs"] and then a section with the name of our job ("jobname").
There is all the benchmark results that our simulated traffic has
delivered, including i.a. bw (bandwidth averaged per second, in kiB),
iops (IOPS count) and lat_ns (total latency in nanoseconds) divided into
read/write sub-sections and additional data like CPU usage (in %). For
details, please see "Interpreting the output" section in FIO's [HOWTO
page][191te3w2] (you can look at "normal" output, since it has similar
attributes as in "json" formatted output).

###### [This entry was edited on 2018-09-03 to update one of the ndctl's legacy parameter and change the way workloads are presented.]

[1fiolink]: https://github.com/axboe/fio
[ndctllnk]: https://github.com/pmem/ndctl
[13rde117]: /blog/2016/02/how-to-emulate-persistent-memory
[191te3w1]: https://github.com/axboe/fio/blob/master/HOWTO
[191te3w2]: https://github.com/axboe/fio/blob/master/HOWTO#L3292
[44ku0112]: https://github.com/axboe/fio/tree/master/engines
[55ku0123]: https://github.com/axboe/fio/tree/master/examples
[nctl1234]: https://linux.die.net/man/8/numactl
[fiolibpm]: https://gist.github.com/lukaszstolarczuk/b358293ad818447f0f0388161bbaa332
[fiodevdx]: https://gist.github.com/lukaszstolarczuk/d78d069eaedbe8e35024ef23fcaa5bed
[fiopmblk]: https://gist.github.com/lukaszstolarczuk/b97f2650a29233e7a8aa0dee26892339
[fiommap1]: https://gist.github.com/lukaszstolarczuk/939a1241485d51ec7947ad9caf26d00b

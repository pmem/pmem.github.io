---
title: Running FIO with pmem engines
author: lukaszstolarczuk
layout: post
identifier: fio_tutorial
---

When we want to check performance of our library, either see if there was any regression or if our tweaks did the good job, we execute benchmarks. One of them is FIO. It helps us simulate synthethic traffic of reads and writes to pmem device. In this blog post we'll try to introduce this tool and explain how we use it.

### Preparing environment
For starters, links to needed software:
* [FIO][1fioLink]
* [NDCTL][ndctlLnk]
And of course, to execute any FIO tests using pmem engines you need PMDK installed in your system.

Tests can be run on [emulated pmem][13rde117] or real hardware (devices introduced as NVDIMM Firmware Interface Table (NFIT), according to ACPI Specification v6.0+). Either way you have to prepare device(s) before test execution.
First step is to configure namespace (emulated pmem is delivered already as a namespace). For this step you can use command `ndctl create-namespace`. There are two main options to configure namespace in our case - either you choose mode `fsdax` or `devdax`.
Namespace configured with **fsdax** is a block device with support of dax operations. It requires dax-capable filesystem (e.g. ext4 or xfs) and should be configured like:
{% highlight bash %}
ndctl create-namespace --type=pmem --mode=fsdax --region=X [--align=4k]
mkfs.ext4 /dev/pmemX
mkdir /mnt/pmem
mount /dev/pmemX /mnt/pmem -o dax
{% endhighlight %}
In both cases (this and one below) region(s) should be created/maintained using administrative tool specific for your hardware. Optional parameter **align** can be used to change alignment, while default for both modes is 2M.

Namespace using **devdax** mode is a character device and is raw access analogue of filesystem-dax. Configuration is simpler, because there's no need for filesystem preparation:
{% highlight bash %}
ndctl create-namespace --type=pmem --mode=dax --region=X [--align=4k]
{% endhighlight %}

At the end of this section when you execute commands:
{% highlight bash %}
ls /mnt/pmem
ls /dev/dax*
{% endhighlight %}
You should see mounted filesystem or dev-dax device available to use.

### FIO
Installation of FIO is pretty staraightforward and is described on their GitHub readme page. Execution is also not complicated, but it requires preparing the acutal workload to benchmark. We'll see how workloads look for specific jobs below, but at first few general rules:
* job at best should run on a time-based manner with ramp-up time to generate traffic before the right measurements,
* numjobs parameter is telling how many threads/processes FIO will spawn to do the job; this is the best place to scale your workload,
* blocksize parameter tells how big chunks of data will be transfered; this will influence bandwidth (bigger bs usually means higher BW/s) and IOPS (lower bs usually means higher IOPS).
For details on available parameters and options go to [HOWTO page][191te3w1].

FIO is generating I/O traffic using engines specific for the job. To specify which engine is used in a job there's workload's option `ioengine=my_engine`. All of them are described in [their c file][44ku0112] and all have corresponding [examples][55ku0123]. Few of them are related to persistent memory:
** libpmem **
This engine reads/writes data using libpmem library. Works on a namespace created in `fsdax` mode. Full exemplary workload for generating traffic of sequential reads using this engine can be found [here][fiolibPM]. There are additional comments within the jobfile to explain specific usage.

** dev-dax **
It also uses libpmem library, but as the name suggest it is specified to work with device-dax devices. Our full exemplary workload [DaxSeqR.fio][fiodevDX] shows how to properly use FIO with /dev/dax. Since we don't work on a "regular" file we use a little trick to achieve more realistic measurments - we set a seperate space for each thread doing reads/writes using option `offset_increment=(int)GB`. This way threads' requests do not overlap each other and results are not cached.

** pmemblk **
This engine is using libpmemblk library. Results delivered by this engine will not show you the best performance of your hardware, only what this specific library is capable of. Interestingly while using this engine, `blocksize` and `size` of a file are given as part of `filename` option, like here:
{% highlight %}
filename=/mnt/pmem6/testjob,512,1024000
#size=1024000M
#bs=512
{% endhighlight %}
Full exemplary workload doing traffic of sequential reads for pmemblk can be found [here][fioPMblk]

** mmap **
It's the most "basic" of mentioned engines, because its purpose is just to read from/write to a memory mapped region. It can be used with pmem, but is not tailor-made. It generates traffic doing memcpy to/from memory region. Again, full exemplary working workload is available: [MmapSeqRead.fio][fioMMap1]

### Execution and results
Command to run FIO is basically: `fio [options] [jobfile] ...`.
Since we use workloads written to a file (in contrary to specifying parameters in command line), we execute FIO with command like `numactl -N 0 fio --output=my_workload.log --output-format=json my_workload.fio`. [numactl command][nctl1234] gurantees to map process to selected numa node. We can achieve this also by giving CPU mask to FIO option `cpus_allowed`. We also choose JSON format to save our results in it, because is much simpler to parse them afterwards.
In results we look for section with our jobname in the list of ["jobs"]. Within, there are all data that our simulated traffic has produced, including i.a. bw_bytes (BW/s in bytes), iops (IOPS count) and lat_ns (total latency in nano seconds) divided into read/write sub-sections and e.g. cpu usage (in %). For more details you should look for "Interpreting the output" in FIO's [HOWTO page][191te3w2] ("json" output is similar to "normal", except the latter is less convinent for automatic parsing).


[1fioLink]: https://github.com/axboe/fio
[ndctlLnk]: https://github.com/pmem/ndctl
[13rde117]: http://pmem.io/2016/02/22/pm-emulation.html
[191te3w1]: https://github.com/axboe/fio/blob/master/HOWTO
[191te3w2]: https://github.com/axboe/fio/blob/master/HOWTO#L3292
[44ku0112]: https://github.com/axboe/fio/tree/master/engines
[55ku0123]: https://github.com/axboe/fio/tree/master/examples
[nctl1234]: https://linux.die.net/man/8/numactl
[fiolibPM]: http://pmem.io/assets/LibpmemSeqR.fio
[fiodevDX]: http://pmem.io/assets/DaxSeqR.fio
[fioPMblk]: http://pmem.io/assets/PmemblkSeqR.fio
[fioMMap1]: http://pmem.io/assets/MmapSeqR.fio
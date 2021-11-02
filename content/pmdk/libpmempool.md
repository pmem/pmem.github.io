---
title: "libpmempool | PMDK"
draft: false
slider_enable: true
# Page title background image
bg_image: '/images/backgrounds/faq_header.jpg'
# Header
header: "libpmempool"
# Description
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
---
### The libpmempool library

**libpmempool** provides a set of utilities for management, diagnostics and
repair of persistent memory pools.
By pool in this context we mean pmemobj pool, pmemblk pool, pmemlog pool or
BTT layout, independent of the underlying storage.
The **libpmempool** is for applications that need high reliability or built-in
troubleshooting. It may be useful for testing and debugging purposes also.

Man pages that contains a list of the **Linux** interfaces provided:

* Man page for <a href="../manpages/linux/master/libpmempool/libpmempool.7.html">libpmempool current master</a>


Man pages that contains a list of the **Windows** interfaces provided:

* Man page for <a href="../manpages/windows/master/libpmempool/libpmempool.7.html">libpmempool current master</a>

### libpmempool Examples

#### More Detail Coming Soon

{{< highlight c "linenos=true,hl_lines=5,linenostart=37">}}
#include <stddef.h>
#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <libpmempool.h>

#define PATH "./pmem-fs/myfile"
#define CHECK_FLAGS (PMEMPOOL_CHECK_FORMAT_STR|PMEMPOOL_CHECK_REPAIR|\
		PMEMPOOL_CHECK_VERBOSE)

int
main(int argc, char *argv[])
{
	PMEMpoolcheck *ppc;
	struct pmempool_check_status *status;
	enum pmempool_check_result ret;

	/* arguments for check */
	struct pmempool_check_args args = {
		.path		= PATH,
		.backup_path	= NULL,
		.pool_type	= PMEMPOOL_POOL_TYPE_DETECT,
		.flags		= CHECK_FLAGS
	};

	/* initialize check context */
	if ((ppc = pmempool_check_init(&args, sizeof(args))) == NULL) {
		perror("pmempool_check_init");
		exit(EXIT_FAILURE);
	}

	/* perform check and repair, answer 'yes' for each question */
	while ((status = pmempool_check(ppc)) != NULL) {
		switch (status->type) {
		case PMEMPOOL_CHECK_MSG_TYPE_ERROR:
			printf("%s\n", status->str.msg);
			break;
		case PMEMPOOL_CHECK_MSG_TYPE_INFO:
			printf("%s\n", status->str.msg);
			break;
		case PMEMPOOL_CHECK_MSG_TYPE_QUESTION:
			printf("%s\n", status->str.msg);
			status->str.answer = "yes";
			break;
		default:
			pmempool_check_end(ppc);
			exit(EXIT_FAILURE);
		}
	}

	/* finalize the check and get the result */
	ret = pmempool_check_end(ppc);
	switch (ret) {
		case PMEMPOOL_CHECK_RESULT_CONSISTENT:
		case PMEMPOOL_CHECK_RESULT_REPAIRED:
			return 0;
		default:
			return 1;
	}
}
{{< /highlight >}}
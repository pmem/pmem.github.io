---
draft: false
layout: "library"
slider_enable: true
description: ""
disclaimer: "The contents of this web site and the associated <a href=\"https://github.com/pmem\">GitHub repositories</a> are BSD-licensed open source."
aliases: ["libpmemkv_config.3.html"]
title: "libpmemkv | PMDK"
---
{{< manpages >}}

[comment]: <> (SPDX-License-Identifier: BSD-3-Clause)
[comment]: <> (Copyright 2019-2020, Intel Corporation)

[comment]: <> (libpmemkv_config.3 -- man page for libpmemkv configuration API)

[NAME](#name)<br />
[SYNOPSIS](#synopsis)<br />
[DESCRIPTION](#description)<br />
[EXAMPLE](#example)<br />
[ERRORS](#errors)<br />
[SEE ALSO](#see-also)<br />


# NAME #

**pmemkv_config** - Configuration API for libpmemkv

# SYNOPSIS #

```c
#include <libpmemkv.h>

pmemkv_config *pmemkv_config_new(void);
void pmemkv_config_delete(pmemkv_config *config);
int pmemkv_config_put_data(pmemkv_config *config, const char *key, const void *value,
			size_t value_size);
int pmemkv_config_put_object(pmemkv_config *config, const char *key, void *value,
			void (*deleter)(void *));
int pmemkv_config_put_object_cb(pmemkv_config *config, const char *key, void *value,
				void *(*getter)(void *), void (*deleter)(void *));
int pmemkv_config_put_uint64(pmemkv_config *config, const char *key, uint64_t value);
int pmemkv_config_put_int64(pmemkv_config *config, const char *key, int64_t value);
int pmemkv_config_put_string(pmemkv_config *config, const char *key, const char *value);
int pmemkv_config_get_data(pmemkv_config *config, const char *key, const void **value,
			size_t *value_size);
int pmemkv_config_get_object(pmemkv_config *config, const char *key, void **value);
int pmemkv_config_get_uint64(pmemkv_config *config, const char *key, uint64_t *value);
int pmemkv_config_get_int64(pmemkv_config *config, const char *key, int64_t *value);
int pmemkv_config_get_string(pmemkv_config *config, const char *key, const char **value);

pmemkv_comparator *pmemkv_comparator_new(pmemkv_compare_function *fn, const char *name,
					 void *arg);
void pmemkv_comparator_delete(pmemkv_comparator *comparator);
```

For general description of pmemkv and available engines see **libpmemkv**(7).
For description of pmemkv core API see **libpmemkv**(3).

# DESCRIPTION #

pmemkv database is configured using pmemkv_config structure. It stores mappings
of keys (null-terminated strings) to values. A value can be:

+ **uint64_t**
+ **int64_t**
+ **c-style string**
+ **binary data**
+ **pointer to an object** (with accompanying deleter function)

It also delivers methods to store and read configuration items provided by
a user. Once the configuration object is set (with all required parameters),
it can be passed to *pmemkv_open()* method.

List of options which are required by pmemkv database is specific to an engine.
Every engine has documented all supported config parameters (please see **libpmemkv**(7) for details).

`pmemkv_config *pmemkv_config_new(void);`
:	Creates an instance of configuration for pmemkv database.

	On failure, NULL is returned.

`void pmemkv_config_delete(pmemkv_config *config);`
:	Deletes pmemkv_config. Should be called ONLY for configs which were not
	passed to pmemkv_open (as this function moves ownership of the config to
	the database).

`int pmemkv_config_put_uint64(pmemkv_config *config, const char *key, uint64_t value);`

:	Puts uint64_t value `value` to pmemkv_config at key `key`.

`int pmemkv_config_put_int64(pmemkv_config *config, const char *key, int64_t value);`

:	Puts int64_t value `value` to pmemkv_config at key `key`.

`int pmemkv_config_put_string(pmemkv_config *config, const char *key, const char *value);`

:	Puts null-terminated string to pmemkv_config. The string is copied to the config.

`int pmemkv_config_put_data(pmemkv_config *config, const char *key, const void *value, size_t value_size);`

:	Puts copy of binary data pointed by `value` to pmemkv_config. `value_size`
	specifies size of the data.

`int pmemkv_config_put_object(pmemkv_config *config, const char *key, void *value, void (*deleter)(void *));`

:	Puts `value` to pmemkv_config. `value` can point to arbitrary object.
	`deleter` parameter specifies function which will be called for `value`
	when the config is destroyed (using pmemkv_config_delete).

`int pmemkv_config_put_object_cb(pmemkv_config *config, const char *key, void *value, void *(*getter)(void *), void (*deleter)(void *));`

:	Extended version of pmemkv_config_put_object. It accepts one additional argument -
	a `getter` callback. This callback interprets the custom object (`value`) and returns
	a pointer which is expected by pmemkv.

	Calling pmemkv_config_put_object_cb with `getter` implemented as:
	```
	void *getter(void *arg) { return arg; }
	```
	is equivalent to calling pmemkv_config_put_object.

`int pmemkv_config_get_uint64(pmemkv_config *config, const char *key, uint64_t *value);`

:	Gets value of a config item with key `key`. Value is copied to variable pointed by
	`value`.

`int pmemkv_config_get_int64(pmemkv_config *config, const char *key, int64_t *value);`

:	Gets value of a config item with key `key`. Value is copied to variable pointed by
	`value`.

`int pmemkv_config_get_string(pmemkv_config *config, const char *key, const char **value);`

:	Gets pointer to a null-terminated string. The string is not copied. After successful call
	`value` points to string stored in pmemkv_config.

`int pmemkv_config_get_data(pmemkv_config *config, const char *key, const void **value, size_t *value_size);`

:	Gets pointer to binary data. Data is not copied. After successful call
	`*value` points to data stored in pmemkv_config and `value_size` holds size of the data.

`int pmemkv_config_get_object(pmemkv_config *config, const char *key, const void **value);`

:	Gets pointer to an object. After successful call, `*value` points to the object.

Config items stored in pmemkv_config, which were put using a specific function can be obtained
only using corresponding pmemkv_config_get_ function (for example, config items put using pmemkv_config_put_object
can only be obtained using pmemkv_config_get_object). Exception from this rule
are functions for uint64 and int64. If value put by pmemkv_config_put_int64 is in uint64_t range
it can be obtained using pmemkv_config_get_uint64 and vice versa.

`pmemkv_comparator *pmemkv_comparator_new(pmemkv_compare_function *fn, const char *name, void *arg);`

:	Creates instance of a comparator object. Accepts comparison function `fn`,
	`name` and `arg. In case of persistent engines, `name` is stored within the engine.
	Attempt to open a database which was createad with different comparator of different name will fail
	with PMEMKV_STATUS_COMPARATOR_MISMATCH. `arg` is saved in the comparator and passed to a
	comparison function on each invocation.

	Neither `fn` nor `name` can be NULL.

	`fn` should perform a three-way comparison. Return values:
	* negative value if the first key is less than the second one
	* zero if both keys are the same
	* positive value if the first key is greater than the second one

	The comparison function should be thread safe - it can be called from multiple threads.

	On failure, NULL is returned.

`void pmemkv_comparator_delete(pmemkv_comparator *comparator);`

:	Removes the comparator object. Should be called ONLY for comparators which were not
	put to config (as config takes ownership of the comparator).

To set a comparator for the database use `pmemkv_config_put_object`:

```c
pmemkv_comparator *cmp = pmemkv_comparator_new(&compare_fn, "my_comparator", NULL);

pmemkv_config_put_object(cfg, "comparator", cmp, (void (*)(void *)) & pmemkv_comparator_delete);
```

## ERRORS ##

Each function, except for *pmemkv_config_new()* and *pmemkv_config_delete()*, returns status.
Possible return values are:

+ **PMEMKV_STATUS_OK** -- no error
+ **PMEMKV_STATUS_UNKNOWN_ERROR** -- unknown error
+ **PMEMKV_STATUS_NOT_FOUND** -- record (or config item) not found
+ **PMEMKV_STATUS_CONFIG_PARSING_ERROR** -- parsing data to config failed
+ **PMEMKV_STATUS_CONFIG_TYPE_ERROR** -- config item has different type than expected

# EXAMPLE #

The following example is taken from `examples/pmemkv_config_c/pmemkv_config.c`.

```c
// SPDX-License-Identifier: BSD-3-Clause
#include <assert.h>
#include <libpmemkv.h>
#include <libpmemkv_json_config.h>
#include <stdlib.h>
#include <string.h>

/* deleter for int pointer */
void free_int_ptr(void *ptr)
{
	free(ptr);
}

int main()
{
	pmemkv_config *config = pmemkv_config_new();
	assert(config != NULL);

	/* Put int64_t value */
	int status = pmemkv_config_put_int64(config, "size", 1073741824);
	assert(status == PMEMKV_STATUS_OK);

	char buffer[] = "ABC";

	/* Put binary data stored in buffer */
	status = pmemkv_config_put_data(config, "binary", buffer, 3);
	assert(status == PMEMKV_STATUS_OK);

	const void *data;
	size_t data_size;

	/* Get pointer to binary data stored in config */
	status = pmemkv_config_get_data(config, "binary", &data, &data_size);
	assert(status == PMEMKV_STATUS_OK);
	assert(data_size == 3);
	assert(((const char *)data)[0] == 'A');

	int *int_ptr = malloc(sizeof(int));
	assert(int_ptr != NULL);
	*int_ptr = 10;

	/* Put pointer to dynamically allocated object, free_int_ptr is called on
	 * pmemkv_config_delete */
	status = pmemkv_config_put_object(config, "int_ptr", int_ptr, &free_int_ptr);
	assert(status == PMEMKV_STATUS_OK);

	int *get_int_ptr;

	/* Get pointer to object stored in config */
	status = pmemkv_config_get_object(config, "int_ptr", (void **)&get_int_ptr);
	assert(status == PMEMKV_STATUS_OK);
	assert(*get_int_ptr == 10);

	pmemkv_config_delete(config);

	pmemkv_config *config_from_json = pmemkv_config_new();
	assert(config_from_json != NULL);

	/* Parse JSON and put all items found into config_from_json */
	status = pmemkv_config_from_json(config_from_json, "{\"path\":\"/dev/shm\",\
		 \"size\":1073741824,\
		 \"subconfig\":{\
			\"size\":1073741824\
			}\
		}");
	assert(status == PMEMKV_STATUS_OK);

	const char *path;
	status = pmemkv_config_get_string(config_from_json, "path", &path);
	assert(status == PMEMKV_STATUS_OK);
	assert(strcmp(path, "/dev/shm") == 0);

	pmemkv_config *subconfig;

	/* Get pointer to nested configuration "subconfig" */
	status = pmemkv_config_get_object(config_from_json, "subconfig",
					  (void **)&subconfig);
	assert(status == PMEMKV_STATUS_OK);

	size_t sub_size;
	status = pmemkv_config_get_uint64(subconfig, "size", &sub_size);
	assert(status == PMEMKV_STATUS_OK);
	assert(sub_size == 1073741824);

	pmemkv_config_delete(config_from_json);

	return 0;
}

```

# SEE ALSO #

**libpmemkv**(7), **libpmemkv**(3) , **libpmemkv_json_config**(3) and **<https://pmem.io>**

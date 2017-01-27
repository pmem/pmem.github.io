---
title: Modeling strings with libpmemobj C++ bindings
author: RobDickinson
layout: post
---

C++ developers using libpmemobj have more than one option for modeling strings,
depending on the size of the strings and whether they are fixed or varying
in length. In this post we'll review the representations that work,
known variations to avoid, and finally present a persistent string
class that implements these best practices.

#### Avoid wrapping fixed-size arrays

You might expect (like I did at first!) that `p<char[size]>` is a proper way
to simply model a fixed-size string, but actually this is not correct.
The `p<>` template does not implement the subscript `operator[]` and so
`p<char[size]>` won't even compile.

#### Avoid wrapping std::string

Never use `p<std::string>` or `persistent_ptr<std::string>`. This code will
compile, but these implementations will not be power-fail safe (at best)
and might be unstable (at worst). This advice applies to using `p<>`
or `persistent_ptr<>` wrappers around any complex types that perform
their own memory management.

#### Avoid arrays of persistent pointers to single chars

Don't model a variable-length string with representations like
`persistent_ptr<char> X[]` or `persistent_ptr<p<char>[]>`. These are
horribly inefficient and should always be avoided. Let's consider what
happening behind the scenes here. Each char in the variable-length string
will be stored as one char in persistent memory and so each char requires
another 16 bytes for its individual persistent pointer. So writing a
single char results in 16x more bytes written than necessary. Reading a
single char results in 16x more bytes read than necessary, since every
persistent char read requires dereferencing a 16-byte persistent pointer.
These are not the strings you're looking for.

#### Use fixed-length strings inside persistent structs

The easiest proper way to model a fixed-length string is within a
persistent struct or class. The char array will be stored inside the
struct/class, which is wrapped in a `persistent_ptr`, as in the example below.

```
class MyAssociation {
public:
  void set_key(std::string* key);
  void set_value(std::string* value);
private:
  char key[SIZE];
  char value[SIZE];
};

struct MyAssociations {
  persistent_ptr<MyAssociation> preferred_association;
  persistent_ptr<MyAssociation> alternate_associations[SIZE];
};
```

A small complication here is that the `set_key` and `set_value` methods
must always call `pmemobj_tx_add_range_direct` prior to modifying their
respective internal fields. (This call would typically be done automatically
by a `p<>` wrapper, if we had one.)

Technically, calling `pmemobj_tx_add_range_direct` is redundant when
modifying a newly allocated object, as the entire memory range of a new
object is already in the current transaction. However, the performance
improvement from skipping `pmemobj_tx_add_range_direct` is very low,
especially considering the risk of corruption from missing one of these calls.

#### Use variable-length strings inside persistent structs

For a long or variable-length string, it's best to use `persistent_ptr<char[]>`
within a persistent struct or class. A version of `MyAssociation` for this
case is shown below.

```
class MyAssociation {
public:
  void set_key(std::string* key);
  void set_value(std::string* value);
private:
  persistent_ptr<char[]> key;
  persistent_ptr<char[]> value;
};

struct MyAssociations {
  persistent_ptr<MyAssociation> preferred_association;
  persistent_ptr<MyAssociation> alternate_associations[SIZE];
};
```

Here the `set_key` and `set_value` methods will each call `make_persistent` to
allocate a char array (including the null termination char). This is a
significant increase in number of persistent allocations over the previous
version for small strings. These methods will also have to use
`delete_persistent` properly to avoid leaking persistent memory.

#### Use a persistent string class

All these guidelines so far are a lot to remember, so a simple persistent
string class like the example below can provide some relief.

```
#define SSO_CHARS 15
#define SSO_SIZE (SSO_CHARS + 1)

class PersistentString {
public:
  char* data() const { return str ? str.get() : const_cast<char*>(sso); }
  void reset();
  void set(std::string* value);
private:
  char sso[SSO_SIZE];
  persistent_ptr<char[]> str;
};

void PersistentString::reset() { 
  sso[0] = 0;
  if (str) delete_persistent<char[]>(str, strlen(str.get()) + 1);
}

void PersistentString::set(std::string* value) {
  unsigned long length = value->length();
  if (length <= SSO_CHARS) {
    if (str) {
      delete_persistent<char[]>(str, strlen(str.get()) + 1);
      str = nullptr;
    }
    pmemobj_tx_add_range_direct(sso, SSO_SIZE);
    strcpy(sso, value->c_str());
  } else {
    if (str) delete_persistent<char[]>(str, strlen(str.get()) + 1);
    str = make_persistent<char[]>(length + 1);
    strcpy(str.get(), value->c_str());
  }
}
```

Note how `PersistentString` uses a small internal array, or allocates a
second persistent array, automatically based on the length of the string.
The `set` method follows the proper rules for calling
`pmemobj_tx_add_range_direct`, based on the length of the incoming string.

Although `PersistentString` could additionally store the length of the
persistent string, we chose not to do so above for two reasons -- first
because this reduces space for storing short strings (making the long string
case more probable), and second because maintaining the length as a separate
persistent field requires more journaling overhead. This might be a good case
for using a volatile field within a persistent type, should NVML support
that concept someday.

*Many thanks to @tomaszkapela and @pbalcer for contributing to this post!*

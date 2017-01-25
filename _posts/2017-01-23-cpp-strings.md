---
title: Modeling strings with libpmemobj C++ bindings
author: RobDickinson
layout: post
---

C++ developers using libpmemobj have multiple options for modeling strings,
depending on the size of the strings and whether they are fixed or varying
in length.

For small fixed-length strings, use of `p<char[size]>` is efficient and easy.
For large or variable-length strings, or for structs or arrays of such
strings, then things get a little more complicated.

#### Avoiding use of std::string

For starters, never use `p<std::string>` or `persistent_ptr<std::string>`.
These forms might seem a logical place to start, but these implementations
will not be power-fail safe (at best) and might be unstable (at worst).
This advice applies to using `p<>` or `persistent_ptr<>` wrappers around
any complex types that perform their own memory management.
Don't make this obvious mistake!

#### Avoiding arrays of persistent pointers to single chars

Don't model a variable-length string with `p<char> X[]`. This is power-fail
safe, but wastes a lot of memory. Let's consider what's happening behind the
scenes here. With this representation, each char in the variable-length
string will be stored as one char in persistent memory, plus another 16 bytes
for the persistent pointer to that char. So writing a single char results in
16x more bytes written than necessary. Reading a single char results in 16x
more bytes read than necessary, since reading every persistent char requires
dereferencing a 16-byte persistent pointer. Obviously this is quite wasteful.

#### Using fixed-length strings inside persistent structs

While `p<char[size]>` is the obvious starting point for modeling fixed-size
strings, we'll frequently be modeling strings within nested persistent
structs and classes as well. So we'll use a `p<>` wrapper around a complex
type that contains one or more strings, as with `MyAssocations` below.

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
  p<MyAssociation> preferred_association;
  p<MyAssociation> alternate_associations[SIZE];
};
```

The downside of this approach is that the `set_key` and `set_value` methods
must call `pmemobj_tx_add_range_direct` prior to modifying their respective
internal fields.

However, calling `pmemobj_tx_add_range_direct` is not required
when modifying a newly allocated object, as the entire memory range of a new
object is already in the current transaction.

So for best performance, we could add `init_key` and `init_value` setter
methods, which don't call `pmemobj_tx_add_range_direct` by convention, and so
would only be safe to use in the context of a newly allocated object. These
would be slightly faster than `set_key` and `set_value`.

#### Using variable-length strings inside persistent structs

The most efficient way to model a long variable-length string within a
persistent struct or class is by using `persistent_ptr<char[]>`.
A version of `MyAssociation` for this case is shown below.

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
  p<MyAssociation> preferred_association;
  p<MyAssociation> alternate_associations[SIZE];
};
```

This approach requires the actual string data (the `char[]` instances) to be
allocated separately from the persistent struct itself. In the example above,
`set_key` and `set_value` methods will each call `make_persistent` to
allocate a char array (including the null termination char). This is a
significant increase in number of persistent allocations over the previous
version for small strings.

#### Using a persistent string class

All these guidelines so far are a lot to remember, so a simple persistent
string class like the example below can provide some relief.

```
#define SSO_CHARS 15
#define SSO_SIZE (SSO_CHARS + 1)

class PersistentString {
public:
  char* data() const { return str ? str.get() : const_cast<char*>(sso); }
  void set(std::string* value);
private:
  char sso[SSO_SIZE];
  persistent_ptr<char[]> str;
};

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

Note how `PersistentString` uses a small internal buffer, or allocates a
second persistent buffer, automatically based on the length of the string.
The `set` method follows the proper rules for calling
`pmemobj_tx_add_range_direct`, based on the length of the incoming string.

Although `PersistentString` could additionally store the length of the
persistent string, we chose not to do so above for two reasons -- first
because this reduces space for storing short strings (making the long string
case more probable), and second because maintaining the length as a separate
persistent field requires more journaling overhead. This might be a good case
for using a volatile field within a persistent type, should NVML support
that concept someday.

*Please note that `PersistentString` above is not complete -- a finished
implementation would at a minimum also have a method to recover
any persistent memory that's been allocated!*

---
# Blog post title
title: 'Type safety macros in libpmemobj'

# Blog post creation date
date: 2015-06-11T19:55:17-07:00

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
author: 'plebioda'

# Categories to which this blog post belongs
blogs: ['macros']

tags: []

# Redirects from old URL
aliases: ['/2015/06/11/type-safety-macros.html']

# Blog post type
type: 'post'
---

The _PMEMoid_ plays the role of a persistent pointer in a _pmemobj_ pool.
It consist of a shortened UUID of the pool which the object comes from and an
offset relative to the beginning of the pool:

```c++
typedef struct pmemoid {
  uint64_t pool_uuid_lo;
  uint64_t off;
} PMEMoid;
```

Operating on such _persistent pointers_ is equivalent to operating on raw
pointers to volatile objects represented by void *. This approach is error
prone and such errors are very hard to find.

There is a real need to provide some mechanism which would associate
a persistent pointer with a type. The _libpmemobj_ provides a set
of macros which allows to avoid such situations and generates compile-time
errors when trying to assign a _PMEMoids_ of different types.

As an example it is totally acceptable to perform the following operation:

```c++
PMEMoid car = pmemobj_tx_alloc(pop, sizeof (struct car), TYPE_CAR);
PMEMoid pen = pmemobj_tx_alloc(pop, sizeof (struct pen), TYPE_PEN);
...
car = pen;
```

This code compiles fine, however the programmer probably didn't intend for
that that to happen and it will probably lead to hard to debug unexpected
behavior.

Another problem with untyped pointers is accessing the fields of a structure or
a union. To do this it is required to convert a _PMEMoid_ to a pointer of the
desired type and only after that the fields may be accessed:

```c++
PMEMoid car;
...
struct car *carp = pmemobj_direct(car);
carp->velocity = 0;
```

This leads to a situation where each object must have two representations in
the code: the _PMEMoid_ and a typed pointer.

#### Anonymous unions

One of the possible solutions is to use anonymous unions which contain the
_PMEMoid_ and information about the type of the object - a pointer to the
desired type. This pointer may be used for type checking in assignments and for
conversion from _PMEMoid_ to a pointer of the desired type.

The macro which declares the anonymous union looks like this:

```c++
#define OID_TYPE(type)\
union {\
  type *_type;\
  PMEMoid oid;\
}
```

When using the _OID_TYPE()_ macro the following code would generate a
compile-time error:

```c++
OID_TYPE(struct car) car;
OID_TYPE(struct pen) pen;
...
OID_ASSIGN_TYPED(car, pen);
```

The conversion from _PMEMoid_ to the typed pointer may be achieved using the
_DIRECT_RW()_ and _DIRECT_RO()_ macros for read-write and read-only access
respectively:

```c++
OID_TYPE(struct car) car1;
OID_TYPE(struct car) car2;
...
DIRECT_RW(car1)->velocity = DIRECT_RO(car2)->velocity * 2;
```

The definition of _DIRECT_RW()_ and _DIRECT_RO()_ macros look like this:

```c++
#define DIRECT_RW(o) ((typeof(*(o)._type)*)pmemobj_direct((o).oid)))
#define DIRECT_RO(o) ((const typeof (*(o)._type)*)pmemobj_direct((o).oid))
```

###### No declaration

Contrary to the later mentioned named unions, the anonymous unions don't need a
declaration. The _OID_TYPE()_ macro may be used for every type at any time.
This makes using the anonymous unions simple and clear.

###### Assignment

The assignment of typed persistent pointers must be performed using special
macro. The two anonymous unions which consist of fields with exactly the
same types are not compatible and generates a compilation error:

```
    error: incompatible types when assigning to type ‘union <anonymous>’
    from type ‘union <anonymous>’
```

The `OID_ASSIGN_TYPED()` looks like the following:

```c++
#define OID_ASSIGN_TYPED(lhs, rhs)\
  __builtin_choose_expr(\
  __builtin_types_compatible_p(\
    typeof((lhs)._type),\
    typeof((rhs)._type)),\
  (void) ((lhs).oid = (rhs).oid),\
  (lhs._type = rhs._type))
```

It utilizes the gcc builtin operator *__builtin_types_compatible_p* which checks
the compatibility of types represented by *typed persistent pointers*. If the
types are compatible the actual assignment is performed. Otherwise the fake
assignment of *_type* fields is performed in order to get clear message about
the error:

```c++
OID_TYPE(struct car) car;
OID_TYPE(struct pen) pen;

OID_ASSIGN_TYPED(car, pen);
```

```
    error: assignment from incompatible pointer type [-Werror]
      (lhs._type = rhs._type))
    	       ^
    note: in expansion of macro ‘OID_ASSIGN_TYPED’
     OID_ASSIGN_TYPED(car, pen);
```

###### Passing typed persistent pointer as a function parameter

Passing a typed persistent pointer as a function parameter generates a
compile-time error:

```c++
void stop(OID_TYPE(struct car) car)
{
  D_RW(car)->velocity = 0;
}
...
OID_TYPE(struct car) car;
...
stop(car);
```

```
    error: incompatible type for argument 1 of ‘stop’
      stop(car);
      ^
    note: expected ‘union <anonymous>’ but argument is of type
    ‘union <anonymous>’
     stop(OID_TYPE(struct car) car)
```

###### Type numbers

The _libpmemobj_ requires a type number for each allocation. Associating an
unique type number for each type requires to use type numbers as separate
defines or enums when using anonymous unions. It could be possible to embed the
type number in the anonymous union but it would require to pass the type number
every time the _OID_TYPE()_ macro is used.

#### Named unions

The second possible solution for type safety mechanism are named unions.
The idea behind named unions is the same as for anonymous unions but each type
allocated from persistent memory should have a corresponding named union which
holds the _PMEMoid_ and type information.

The macro which declares the named union may look like this:

```c++
#define TOID(type)\
union _toid_##type##_toid

#define TOID_DECLARE(type)\
TOID(type)\
{\
 PMEMoid oid;\
 type *_type;\
}
```

The _TOID_DECLARE()_ macro is used to declare a named union which is used as a
_typed persistent pointer_. The _TOID()_ macro is used to declare a variable
of this type:

```c++
TOID_DECLARE(struct car);
...

TOID(struct car) car1;
TOID(struct car) car2;
...
D_RW(car1)->velocity = 2 * D_RO(car2)->velocity;
```

The name of such a declared union is obtained by concatenating the desired type
name with a *_toid_* prefix and a *_toid_* postfix. The prefix is required to
handle the two token type names like _struct name_, _union name_ and
_enum name_. In such case the macro expands to two tokens in which the first
one is declared as an empty macro thus avoiding the compilation errors which
would appear, if only postfix or prefix was used.
For example in case of the _struct car_ the _TOID()_ macro will expand to the
following:

```c++
_toid_struct car_toid
```

The `_toid_struct` token and analogous for `enum car` and `union car` may be
removed by declaring the following empty macros:

```c++
#define _toid_struct
#define _toid_union
#define _toid_enum
```

In result the _typed persistent pointer_ for _struct car_ will be named
_car_toid_. In case of one-token types the name of union will consist of both
prefix and postfix. For example in case of _size_t_ type, the _TOID()_ macro
will expand to the following:

```c++
_toid_size_t_toid
```

Using such mechanism it is possible to declare named unions for two-token types.

The definition of _D_RW()_ and _D_RO()_ macros are the same as in case of
anonymous unions.

###### Assignment

In case of named unions there is no issue with assignments encountered in
anonymous unions. The assignment may be performed without using any additional
macro:

```c++
TOID_DECLARE(struct car);
TOID_DECLARE(struct pen);
...

TOID(struct car) car1;
TOID(struct car) car2;
...
car1 = car2;
```

The above example compiles without any errors but the following code would
generate an error:

```c++
TOID_DECLARE(struct car);
TOID_DECLARE(struct pen);
...

TOID(struct car) car;
TOID(struct pen) pen;
...
car = pen;
```

```
    error: incompatible types when assigning to type ‘union car_toid’ from
    type ‘union pen_toid’
      car = pen;
    	^
```

Which clearly points where the problem is.

###### Passing typed persistent pointer as a function parameter

It is also possible to pass the named union as a function parameter:

```c++
TOID_DECLARE(struct car);

void stop(TOID(struct car) car)
{
  D_RW(car)->velocity = 0;
}
...

TOID(struct car) car;

stop(car);
```

Passing _typed persistent pointer_ of a different type generates a clear error
message:

```c++
TOID_DECLARE(struct car);
TOID_DECLARE(struct pen);

void stop(TOID(struct car) car)
{
  D_RW(car)->velocity = 0;
}
..

TOID(struct pen) pen;

stop(pen);
```

```
    error: incompatible type for argument 1 of ‘stop’
      stop(pen);
```

###### Type numbers

Since the named union must be declared before using it, the type number may be
assigned to the type in the declaration. The type number shall be assigned at
compilation time and it can be embedded in the _typed persistent pointer_ by
modifying the _TOID_DECLARE()_ macro:

```c++
#define TOID_DECLARE(type, type_num)\
typedef uint8_t _toid_##type##_toid_id[(type_num)];\
TOID(type)\
{\
 PMEMoid oid;\
 type *_type;\
 _toid_##type##_toid_id *_id;\
}
```

The type id may be obtained using the _sizeof ()_ operator both from type and an
object:

```c++
#define TOID_TYPE_ID(type) (sizeof (_toid_##type##_toid_id))
#define TOID_TYPE_ID_OF(obj) (sizeof (*(obj)._id))
```

The declaration of such _typed persistent pointer_ may look like this:

```c++
TOID_DECLARE(struct car, 1);
TOID_DECLARE(struct pen, 2);
```

It is also possible to use macros or enums to declare a type id:

```c++
enum {
  TYPE_CAR,
  TYPE_PEN
};

TOID_DECLARE(struct car, TYPE_CAR);
TOID_DECLARE(struct pen, TYPE_PEN);
```

This solution requires to assign the type id explicitly at declaration time.
Since the set of types allocated from the _pmemobj_ pool is well known at
compilation time it is possible to declare all types by declaring a pool's
layout without explicitly assigning the type id. The layout declaration looks
like this:

```c++
/* Declaration of layout */
POBJ_LAYOUT_BEGIN(my_layout)
POBJ_LAYOUT_TOID(my_layout, struct car)
POBJ_LAYOUT_TOID(my_layout, struct pen)
POBJ_LAYOUT_END(my_layout)
```

Using such declaration of layout all types declared inside the
_POBJ_LAYOUT_BEGIN()_ and _POBJ_LAYOUT_END()_ macros will be assigned with
consecutive type ids.

#### Summary

The following table contains a summary of both described solutions:

| Feature            | Anonymous unions | Named unions |
| ------------------ | ---------------- | ------------ |
| Declaration        | +                | -            |
| Assignment         | -                | +            |
| Function parameter | -                | +            |
| Type numbers       | -                | +            |

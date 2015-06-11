---
title: Type safety macros in libpmemobj
author: plebioda
layout: post
---
The *PMEMoid* plays the role of a persistent pointer in a *pmemobj* pool.
It consist of a shortened UUID of the pool which the object comes from and an
offset relative to the beginning of the pool:

	typedef struct pmemoid {
		uint64_t pool_uuid_lo;
		uint64_t off;
	} PMEMoid;

Operating on such *persistent pointers* is equivalent to operating on raw
pointers to volatile objects represented by void \*. This approach is error
prone and such errors are very hard to find.

There is a real need to provide some mechanism which would associate
a persistent pointer with a type. The *libpmemobj* provides a set
of macros which allows to avoid such situations and generates compile-time
errors when trying to assign a *PMEMoids* of different types.

As an example it is totally acceptable to perform the following operation:

	PMEMoid car = pmemobj_tx_alloc(pop, sizeof (struct car), TYPE_CAR);
	PMEMoid pen = pmemobj_tx_alloc(pop, sizeof (struct pen), TYPE_PEN);
	...
	car = pen;

This code compiles fine, however the programmer probably didn't intend for
that that to happen and it will probably lead to hard to debug unexpected
behavior.

Another problem with untyped pointers is accessing the fields of a structure or
a union. To do this it is required to convert a *PMEMoid* to a pointer of the
desired type and only after that the fields may be accessed:

	PMEMoid car;
	...
	struct car *carp = pmemobj_direct(car);
	carp->velocity = 0;

This leads to a situation where each object must have two representations in
the code: the *PMEMoid* and a typed pointer.

#### Anonymous unions

One of the possible solutions is to use anonymous unions which contain the
*PMEMoid* and information about the type of the object - a pointer to the
desired type. This pointer may be used for type checking in assignments and for
conversion from *PMEMoid* to a pointer of the desired type.

The macro which declares the anonymous union looks like this:

	#define	OID_TYPE(type)\
	union {\
		type *_type;\
		PMEMoid oid;\
	}

When using the *OID\_TYPE()* macro the following code would generate a
compile-time error:

	OID_TYPE(struct car) car;
	OID_TYPE(struct pen) pen;
	...
	OID_ASSIGN_TYPED(car, pen);

The conversion from *PMEMoid* to the typed pointer may be achieved using the
*DIRECT\_RW()* and *DIRECT\_RO()* macros for read-write and read-only access
respectively:

	OID_TYPE(struct car) car1;
	OID_TYPE(struct car) car2;
	...
	DIRECT_RW(car1)->velocity = DIRECT_RO(car2)->velocity * 2;

The definition of *DIRECT\_RW()* and *DIRECT\_RO()* macros look like this:

	#define	DIRECT_RW(o) ((typeof(*(o)._type)*)pmemobj_direct((o).oid)))
	#define	DIRECT_RO(o) ((const typeof (*(o)._type)*)pmemobj_direct((o).oid))

###### No declaration

Contrary to the later mentioned named unions, the anonymous unions don't need a
declaration. The *OID\_TYPE()* macro may be used for every type at any time.
This makes using the anonymous unions simple and clear.

###### Assignment

The assignment of typed persistent pointers must be performed using special
macro. The two anonymous unions which consist of fields with exactly the
same types are not compatible and generates a compilation error:

	error: incompatible types when assigning to type ‘union <anonymous>’
	from type ‘union <anonymous>’

The *OID\_ASSIGN\_TYPED()* looks like the following:

	#define	OID_ASSIGN_TYPED(lhs, rhs)\
	__builtin_choose_expr(\
		__builtin_types_compatible_p(\
			typeof((lhs)._type),\
			typeof((rhs)._type)),\
		(void) ((lhs).oid = (rhs).oid),\
		(lhs._type = rhs._type))

It utilizes the gcc builtin operator *__builtin_types_compatible_p* which checks
the compatibility of types represented by *typed persistent pointers*. If the
types are compatible the actual assignment is performed. Otherwise the fake
assignment of *_type* fields is performed in order to get clear message about
the error:

	OID_TYPE(struct car) car;
	OID_TYPE(struct pen) pen;

	OID_ASSIGN_TYPED(car, pen);

	error: assignment from incompatible pointer type [-Werror]
	  (lhs._type = rhs._type))
		       ^
	note: in expansion of macro ‘OID_ASSIGN_TYPED’
	 OID_ASSIGN_TYPED(car, pen);

###### Passing typed persistent pointer as a function parameter

Passing a typed persistent pointer as a function parameter generates a
compile-time error:

	void stop(OID_TYPE(struct car) car)
	{
		D_RW(car)->velocity = 0;
	}
	...
	OID_TYPE(struct car) car;
	...
	stop(car);

	error: incompatible type for argument 1 of ‘stop’
	  stop(car);
	  ^
	note: expected ‘union <anonymous>’ but argument is of type
	‘union <anonymous>’
	 stop(OID_TYPE(struct car) car)

###### Type numbers

The *libpmemobj* requires a type number for each allocation. Associating an
unique type number for each type requires to use type numbers as separate
defines or enums when using anonymous unions. It could be possible to embed the
type number in the anonymous union but it would require to pass the type number
every time the *OID\_TYPE()* macro is used.

#### Named unions

The second possible solution for type safety mechanism are named unions.
The idea behind named unions is the same as for anonymous unions but each type
allocated from persistent memory should have a corresponding named union which
holds the *PMEMoid* and type information.

The macro which declares the named union may look like this:

	#define	TOID(type)\
	union _toid_##type##_toid

	#define	TOID_DECLARE(type)\
	TOID(type)\
	{\
		PMEMoid oid;\
		type *_type;\
	}

The *TOID_DECLARE()* macro is used to declare a named union which is used as a
*typed persistent pointer*. The *TOID()* macro is used to declare a variable
of this type:

	TOID_DECLARE(struct car);
	...

	TOID(struct car) car1;
	TOID(struct car) car2;
	...
	D_RW(car1)->velocity = 2 * D_RO(car2)->velocity;

The name of such a declared union is obtained by concatenating the desired type
name with a *_toid_* prefix and a *_toid* postfix. The prefix is required to
handle the two token type names like *struct name*, *union name* and
*enum name*. In such case the macro expands to two tokens in which the first
one is declared as an empty macro thus avoiding the compilation errors which
would appear, if only postfix or prefix was used.
For example in case of the *struct car* the *TOID()* macro will expand to the
following:

	_toid_struct car_toid

The *_toid_struct* token and analogous for *enum car* and *union car* may be
removed by declaring the following empty macros:
	
	#define _toid_struct
	#define _toid_union
	#define _toid_enum

In result the *typed persistent pointer* for *struct car* will be named
*car_toid*. In case of one-token types the name of union will consist of both
prefix and postfix. For example in case of *size_t* type, the *TOID()* macro
will expand to the following:

	_toid_size_t_toid

Using such mechanism it is possible to declare named unions for two-token types.

The definition of *D_RW()* and *D_RO()* macros are the same as in case of
anonymous unions.

###### Assignment

In case of named unions there is no issue with assignments encountered in
anonymous unions. The assignment may be performed without using any additional
macro:
	
	TOID_DECLARE(struct car);
	TOID_DECLARE(struct pen);
	...

	TOID(struct car) car1;
	TOID(struct car) car2;
	...
	car1 = car2;

The above example compiles without any errors but the following code would
generate an error:


	TOID_DECLARE(struct car);
	TOID_DECLARE(struct pen);
	...

	TOID(struct car) car;
	TOID(struct pen) pen;
	...
	car = pen;

	error: incompatible types when assigning to type ‘union car_toid’ from
	type ‘union pen_toid’
	  car = pen;
		^

Which clearly points where the problem is.

###### Passing typed persistent pointer as a function parameter

It is also possible to pass the named union as a function parameter:

	TOID_DECLARE(struct car);

	void stop(TOID(struct car) car)
	{
		D_RW(car)->velocity = 0;
	}
	..

	TOID(struct car) car;

	stop(car);

Passing *typed persistent pointer* of a different type generates a clear error
message:


	TOID_DECLARE(struct car);
	TOID_DECLARE(struct pen);

	void stop(TOID(struct car) car)
	{
		D_RW(car)->velocity = 0;
	}
	..

	TOID(struct pen) pen;

	stop(pen);

	error: incompatible type for argument 1 of ‘stop’
	  stop(pen);

###### Type numbers

Since the named union must be declared before using it, the type number may be
assigned to the type in the declaration. The type number shall be assigned at
compilation time and it can be embedded in the *typed persistent pointer* by
modifying the *TOID_DECLARE()* macro:

	#define	TOID_DECLARE(type, type_num)\
	typedef uint8_t _toid_##type##_toid_id[(type_num)];\
	TOID(type)\
	{\
		PMEMoid oid;\
		type *_type;\
		_toid_##type##_toid_id *_id;\
	}

The type id may be obtained using the *sizeof ()* operator both from type and an
object:

	#define	TOID_TYPE_ID(type) (sizeof (_toid_##type##_toid_id))
	#define	TOID_TYPE_ID_OF(obj) (sizeof (*(obj)._id))

The declaration of such *typed persistent pointer* may look like this:

	TOID_DECLARE(struct car, 1);
	TOID_DECLARE(struct pen, 2);

It is also possible to use macros or enums to declare a type id:


	enum {
		TYPE_CAR,
		TYPE_PEN
	};

	TOID_DECLARE(struct car, TYPE_CAR);
	TOID_DECLARE(struct pen, TYPE_PEN);

This solution requires to assign the type id explicitly at declaration time.
Since the set of types allocated from the *pmemobj* pool is well known at
compilation time it is possible to declare all types by declaring a pool's
layout without explicitly assigning the type id. The layout declaration looks
like this:

	/*
	 * Declaration of layout
	 */
	POBJ_LAYOUT_BEGIN(my_layout)
	POBJ_LAYOUT_TOID(my_layout, struct car)
	POBJ_LAYOUT_TOID(my_layout, struct pen)
	POBJ_LAYOUT_END(my_layout)

Using such declaration of layout all types declared inside the
*POBJ\_LAYOUT\_BEGIN()* and *POBJ\_LAYOUT\_END()* macros will be assigned with
consecutive type ids.

#### Summary

The following table contains a summary of both described solutions:

Feature | Anonymous unions | Named unions
--------|------------------|-------------
Declaration | + | -
Assignment | - | +
Function parameter | - | +
Type numbers | - | +

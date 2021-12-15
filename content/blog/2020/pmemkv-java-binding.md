---
# Blog post title
title: 'API overview of pmemkv-java binding'

# Blog post creation date
date: 2020-10-30T19:55:17-07:00

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
author: 'karczex'

# Categories to which this blog post belongs
blogs: ['pmemkv']

tags: []

# Redirects from old URL
aliases: ['/2020/10/30/pmemkv-java-binding.html']

# Blog post type
type: 'post'
---

Pmemkv is a key-value data store written in C and C++, however, it also opens up
a way to leverage persistent memory by developers who  prefer high-level languages - such as Java.
For more information about other bindings please read [Language bindings for pmemkv][pmemkv-bindings]
article and [pmemkv README][pmemkv-bindings-readme]

We built an API for pmemkv-java binding on top of libpmemkv 1.0 API, but java binding
is also compatible with newer versions of libpmemkv. You don’t have to worry
about the dependencies, because packages from the OS vendor’s repositories suffice.

### Examples

[Examples directory][examples] in pmemkv-java repository is a good resource, where you may learn how to
create pmemkv database and use it with different types of data; starting from simple [ByteBuffer][ByteBufferExample],
up to little bit more [real-life-application][PicturesExample], which shows you how to store and display pictures.

#### Implementing Converter class

Since pmemkv-java binding is the java wrapper for C++ library, it internally relies on java native interface,
so it can only store ByteBuffer objects. Because it wouldn't be very convenient to operate on ByteBuffers inside
a high level application, we introduced an additional layer of types converters. It makes it  possible to store
instance of any class in pmemkv, as long as an appropriate converter is available.

You have to implement Converter generic interface, with two methods:
 *  toByteBuffer() - converts (if possible) or serializes objects to ByteBuffer.
 *  fromByteBuffer() - converts objects back.

```java
class ImageConverter implements Converter<BufferedImage> {
	public ByteBuffer toByteBuffer(BufferedImage entry) {
		ByteArrayOutputStream out = new ByteArrayOutputStream();
		try {
			ImageIO.write(entry, "png", out);
		} catch (IOException e) {
			return null;
		}
		return ByteBuffer.wrap(out.toByteArray());
	}

	public BufferedImage fromByteBuffer(ByteBuffer entry) {
		BufferedImage out = null;
		try {
			out = ImageIO.read(new ByteBufferBackedInputStream(entry));
		} catch (IOException e) {
			return null;
		}
		return out;
	}
}
```

#### Database creation and configuration

Database is the generic class, which needs to be specialized by key and value types.
If you want to create and configure your database, you have to call `setValueConverter()`
and `setKeyConverter()` methods, and make sure their types correspond with Database key and value types.

```java
db = new Database.Builder<String, BufferedImage>(engine)
		.setSize(size)
		.setPath(Path)
		.setKeyConverter(new StringConverter())
		.setValueConverter(new ImageConverter())
		.setForceCreate(true)
		.build();
```

##### Opening already existing database

For persistent engines, database may be created by your application during a previous run  or e.g. by [pmempool][pmempool] tool.
In such case, you have to specify only a path and converter objects.

```java
db = new Database.Builder<String, BufferedImage>(engine)
		.setPath(Path)
		.setKeyConverter(new StringConverter())
		.setValueConverter(new ImageConverter())
		.build();
```

#### Putting data into database

To store an object of the appropriate type, you have to call a `put()` method.

```java
BufferedImage image_buffer = null;
try {
	image_buffer = ImageIO.read(image);
} catch (IOException e) {
	System.exit(1);
}
db.put(image.getName(), image_buffer);
```

#### Operating on stored data

You can use group of  `get*()`  methods, which gets lambda expressions,
and can directly read data stored in the database.

```java
public void paint(Graphics g) {
	System.out.println("Draw images from pmemkv database");
	AtomicInteger yPosition = new AtomicInteger(0);
	db.getAll((k, v) -> {
		System.out.println("\tDraw" + k);
		Graphics2D g2 = (Graphics2D) g;
		g.drawImage(v, 0, yPosition.getAndAdd(v.getHeight()), null);
	});
}
```

### What's next?

Currently pmemkv-java 1.0 API is compatibile with pmemkv 1.0 API. In upcoming releases we are
going to implement features available in new pmemkv versions.


[pmemkv-bindings]: /blog/2020/03/language-bindings-for-pmemkv/
[pmemkv-bindings-readme]: https://github.com/pmem/pmemkv/#language-bindings
[examples]: https://github.com/pmem/pmemkv-java/tree/master/examples
[ByteBufferExample]: https://github.com/pmem/pmemkv-java/blob/master/examples/ByteBufferExample.java
[PicturesExample]: https://github.com/pmem/pmemkv-java/blob/master/examples/PicturesExample.java
[pmempool]: /pmdk/pmempool/

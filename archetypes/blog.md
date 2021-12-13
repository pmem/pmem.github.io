---
# Blog post title
title: "{{ replace .Name "-" " " | title }}"

# Blog post creation date
date: {{ .Date }}

# Change to 'false' when publishing the blog post
draft: true

# Blog post description
description: ""

# Blog post hero image. Used to override the default hero background image.
# eg: image: "/images/my_blog_heroimg.png"
hero_image: ""

# Blog post thumbnail
# eg: image: "/images/posts/my_blog_thumbnail.png"
image: ""

# Blog post author
author: "Blog Author"

# Categories to which this blog post belongs
blogs: ['category1']
# Blog tags
tags: ["tag1", "tag2"]

# Blog post type
type: "post"
---

<!--- Delete this comment and write your blog content here.  -->

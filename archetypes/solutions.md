---
# Solution title
title: "{{ replace .Name "-" " " | title }}"

# Creation date
date: {{ .Date }}

# Publish immediately. 
draft: false

# ISV or Vendor name
vendor_name: ""

# Product Name
product_name: ""

# Product or Project home page URL
solution_url: "https://pmemproduct.com"

# Vendor or Product image
image: "https://pmemproduct.com/productimg.jpg"

# Brief description
description: ""

# Taxonomy
# Category examples: Databases, Healthcare, Security, Financial Services, Cloud Service Provider, Developer Libraries, Developer Tools, Operating Systems, etc...
# Tag examples: SQL, NoSQL, In-Memory Database/IMDB, Kubernetes, OpenStack, OpenShift, etc.
# Price examples: Free Trial, Free, Paid, BYOL
solutions: ["category"]
tags: ["tag1", "tag2"]
price: ["price"]

# Post type. Do not modify.
type: 'solution'

# Featured. Specify true or false to show on Solution page
featured: 
---

<!--- Do not write any content here. The front matter is the only required information. --->

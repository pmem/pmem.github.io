---
# Webinar Title
title: "{{ replace .Name "-" " " | title }}"

# Webinar dates and times (UTC)
event_start_date: '2021-01-01T00:00:00Z'
event_end_date: '2021-01-01T00:00:00Z'

# Webinar image
# eg: image: 'images/events/3.jpg' // Use a local image
# eg: image: 'https://myevent.com/heroimg.jpg' // Use an image from the event website
image: '/images/Learn_pmdk.png'

# Webinar URL. Specify the HTTPS URL for the event home page.
webinar_url: 'https://myevent.com'

# Registration URL (if different than the event home page)
webinar_registration: 'https://myevent.com/register'

# Publish immediately
draft: false

# Brief webinar description
description: ''

# Video URL
# eg: url: "https://www.youtube.com/watch?v=E2KYqdyZcQY"
video_url: ''

# Webinars category
webinars: ['memkind']

# Post type
type: 'webinar'
---

<!--- Do not write any content here. The front matter is the only required information. -->

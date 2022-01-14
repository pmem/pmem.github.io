# Contributing to this repository 

## Getting started

Before you begin:
- The [pmem.io](https://pmem.io) website is powered by the [Hugo](https://gohugo.io/) static site generator and hosted on [GitHub Pages](https://pages.github.com/).
- Have you read the [code of conduct](CODE_OF_CONDUCT.md)?
- Review the [existing issues](https://github.com/pmem/pmem.github.io/issues) and see if we [accept contributions](#types-of-contributions) for your type of issue.

## Types of contributions
You can contribute to the pmem.io content and website in several ways. This GitHub repository is a place to discuss and collaborate on pmem.io! A small, but mighty team is maintaining this repo. To preserve our bandwidth, off topic conversations will be closed.

### :mega: Discussions
Discussions are where we have conversations. If you have a great new idea or suggestion for the site, or want to share something amazing you've learned in our docs, start a new discussion on the [PMem Forum](https://groups.google.com/forum/#!forum/pmem). 

### :bug: Issues
[Issues](https://github.com/pmem/pmem.github.io/issues) are used to track tasks that contributors can help with. Search the open issues to see if there's something you want to help with. 

If you've found an issue in the content or the website functionality that should be updated or fixed, search open issues to see if someone else has reported the same thing. If it's something new, [open a new issue](https://github.com/pmem/pmem.github.io/issues). We'll use the issue to have a conversation about the problem you want to fix. We highly recommend using the following title format:

`[Page|Section] <Object> and <Defect>`

A good title is:

`[Developer Hub] 'Developer Tools' Cards whitespace should be reduced`

Where "Developer Tools Cards" is the Object/Thing, and "whitespace should be reduced" is the defect, or thing to fix.

A bad title is:

`I don't like the spacing between the Dev Hub tools`


### :hammer_and_wrench: Pull requests
A [pull request](https://docs.github.com/en/github/collaborating-with-issues-and-pull-requests/about-pull-requests) is a way to suggest changes in our repository.

When we merge those changes, they should be deployed to the live site within 24 hours. To learn more about opening a pull request in this repo, see [Opening a pull request](#opening-a-pull-request) below. The site will automatically get rebuilt using GitHub Actions, but it can take time to update the cached versions across the globe.

### :question: Support
We are a small team working hard to keep up with the demands of an active community. Unfortunately, we just can't help with support questions in this repository. If you are experiencing a problem with persistent memory, please [reach out to the Community](/community). Any issues, discussions, or pull requests opened here requesting support will be reviewed and addressed.

### :earth_asia: Translations

The source content in this repository is written in English. We are working hard to internationalize the site to make it available in multiple languages. We are currently not accepting translation contributions, but may do so in the future. 



## Start with an Issue
If you spot an issue with the site, would like to contribute new content, or would like to fix or refresh the styling of the website, please [open a new issue](https://github.com/pmem/pmem.github.io/issues). We'll use the issue to have a conversation about the contribution.

You can browse [existing issues](https://github.com/pmem/pmem.github.io/issues) to find something that needs help!

### Ready to make a change? Fork the repo!

You **<u>must</u>** make changes in a fork of this repository. Pull requests directly to this repository will not be accepted. You can fork this project using several methods:

Fork using Github.com

- Fork your own copy of [pmem/pmem.github.io](https://github.com/pmem/pmem.github.io) to your GitHub account by clicking the Fork button in the top right.

Fork using GitHub Desktop:

- [Getting started with GitHub Desktop](https://docs.github.com/en/desktop/installing-and-configuring-github-desktop/getting-started-with-github-desktop) will guide you through setting up Desktop.
- Once Desktop is set up, you can use it to [fork the repo](https://docs.github.com/en/desktop/contributing-and-collaborating-using-github-desktop/cloning-and-forking-repositories-from-github-desktop)!

Fork using the command line:

- [Fork the repo](https://docs.github.com/en/github/getting-started-with-github/fork-a-repo#fork-an-example-repository) so that you can make your changes without affecting the original project until you're ready to merge them.

Fork with [GitHub Codespaces](https://github.com/features/codespaces):

- [Fork, edit, and preview](https://docs.github.com/en/free-pro-team@latest/github/developing-online-with-codespaces/creating-a-codespace) using [GitHub Codespaces](https://github.com/features/codespaces) without having to install and run the project locally.

### Create a new Branch

Before making any changes, [create a local branch](https://docs.github.com/en/desktop/contributing-and-collaborating-using-github-desktop/making-changes-in-a-branch/managing-branches).

### Make your update

Make your changes to the file(s) you'd like to update. Please review [using this codebase](#working-in-the-githubdocs-repository) to learn the structure of this repository and what file(s) should be modified and where to create new content.

#### Creating new content and articles

Hugo uses the [Goldmark](https://github.com/yuin/goldmark/) Markdown processor which is fully [CommonMark](https://commonmark.org/)-compliant.

If you are creating new content, you should use the `hugo new` command to generate the markdown file. For example, to create a new Blog article with the title "My Amazing Blog Article", run:

- `$ hugo new blog/my-amazing-blog-article.md`

Other examples:

```
// Create a new Event
$ hugo new event/my-super-event.md

// Create a new News article or Announcement
$ hugo new announcements/my-big-news-article.md

// Add a new solution
$ hugo new solutions/vendor-product.md

// Add a new video
$ hugo new videos/video-title.md
```

Note: If you forget the `.md` file extension, Hugo will give you a generic front matter, which isn't what you want. 

You can now edit the newly created file. Update the front matter, then add your content. Some content types, such as videos and solutions, require you to update the front matter only with no content. Blog and News articles require both front matter and content.

##### Image Resources

Many content types require images - blogs, news, solutions, etc. You should use images that you have ownership of or permission to use. If you use an image from the Internet, please attribute the original owner. There are many that offer open license use of their images and illustrations for any project, commercial or personal without attribution or costs. Here's a list of some of them:

**Photos**

- [UnSplash](https://unsplash.com/)
- [Flickr](https://www.flickr.com/)
- [Canva](https://www.canva.com/)
- [Pixabay](https://pixabay.com/)
- [Pexels](https://www.pexels.com/)
- [FreeStocks](https://freestocks.org/)
- [StockSnap](https://stocksnap.io/)
- [Burst](https://burst.shopify.com/)
- [FreeImages](https://www.freeimages.com/)

**Illustrations & Vector**

- [Illu-station](https://themeisle.com/illustrations/)
- [Undraw.co](https://undraw.co/)
- [IRA Design](https://iradesign.io/)
- [DrawKit](https://www.drawkit.io/#browse-now-button)
- [ManyPixels](https://gallery.manypixels.co/)
- [Freebie Supply](https://freebiesupply.com/)
- [Ouch!](https://icons8.com/ouch/)

#### Modifying the website HTML and CSS

If you wish to make changes to pages, styling, or the theme, you'll need to create and modify files in the `/data` and `/themes/pmem-hugo` directories. The data and content is intentionally decoupled from the theme to allow future styling changes without having to rewrite the content. 

### Review your change(s)

You must review your changes using a local version of Hugo to confirm the site builds without errors and the changes you made render as you expect and do not break the website. You can run Hugo locally or on a remote host on macOS, Windows, Linux, OpenBSD, and FreeBSD. See the [Install Hugo](https://gohugo.io/getting-started/installing/) documentation for step-by-step instructions.

  - You'll need **Hugo 0.88.0** or newer to run the site locally. 
  - Are you contributing to markdown? Hugo uses the [Goldmark](https://github.com/yuin/goldmark/) Markdown processor which is fully [CommonMark](https://commonmark.org/)-compliant.

### Open a pull request

When you're done making changes and you'd like to propose them for review, use the [pull request template](#pull-request-template) to open your PR (pull request).

### Submit your pull request and get it reviewed

- Once you submit your pull request (PR), others from the community will review it with you. The first thing you're going to want to do is a [self review](#self-review).
- After that, we may have questions, check back on your PR to keep up with the conversation.
- Did you have an issue, like a merge conflict? Check out our [git tutorial](https://lab.github.com/githubtraining/managing-merge-conflicts) on how to resolve merge conflicts and other issues.

### Your PR is merged!

Congratulations! The whole PMem community thanks you. 

## Opening a pull request
You can use the GitHub user interface :pencil2: for small changes, like fixing a typo or updating a readme. You can also fork the repo and then clone it locally, to view changes and run your tests on your machine.

## Working in the pmem.io website repository
Here's some information that might be helpful while working on the pmem.io website repository. The following describes the directory structure of this repository.

- **Archetypes** - [Archetypes](https://gohugo.io/content-management/archetypes/) are content template files in the archetypes directory of the project that contain preconfigured front matter. These will be used when you run `hugo new`. If you need to modify an archetype file, please consider that content created using these templates will also have to be modified and the layout files may need to be updated to support the change(s). If you create a new archetype file, you will need to also create a layout file to render the post types.
- **Content** - This is where the majority of the content for the website resides. Directories within the `/content` directory are called *[Sections](https://gohugo.io/content-management/sections/)* and represent the site layout. For example: blog, community, documents,. events, faq, learn, news, solutions, videos, etc. Hugo uses the [Goldmark](https://github.com/yuin/goldmark/) Markdown processor which is fully [CommonMark](https://commonmark.org/)-compliant. 
- **Data** - Most of the pages use YAML files from this folder to render the content. If you want to make changes to a page, you'll likely only need to modify the corresponding yml file here and not the layout file. 
- **Static** - Files in the `/static` folder are included in the website without modification. Static files include css, images, pdf, etc. [Read more about static files](https://gohugo.io/content-management/static-files/) in the Hugo documentation.
- **Themes** - The website uses a custom theme based on [Canvas | The Multi-Purpose HTML5 Template](https://themeforest.net/item/canvas-the-multipurpose-html5-template/9228123). 

## Reviewing Pull Requests
Every PR must be reviewed by at least one, preferably two or more, people before merging. The purpose of reviews is to create the best content we can for people who use the pmem.io website.

- Reviews are always respectful, acknowledging that everyone did the best possible job with the knowledge they had at the time.
- Reviews discuss content, not the person who created it. 
- Reviews are constructive and start conversation around feedback.  

### Self review
You should always review your own PR first.

For content changes, make sure that you:
- [ ] Confirm that the changes meet the user experience and goals.
- [ ] Test your changes using a Desktop, Tablet, and Mobile device using different browsers to verify the site renders correctly. There are many online services to assist you with these tests.
- [ ] Compare your pull request's source changes to staging to confirm that the output matches the source and that everything is rendering as expected. This helps spot issues like typos, content that doesn't follow the style guide, or content that isn't rendering due to versioning problems. Remember that lists and tables can be tricky.
- [ ] Review the content for technical accuracy.
- [ ] Copy-edit the changes for grammar and spelling.
- [ ] Check new or updated Go statements.
- [ ] If there are any failing checks in your PR, troubleshoot and resolve them until they're all pass.
- [ ] Pull request template



When you open a pull request, you must fill out the "Ready for review" template before we can review your PR. This template helps reviewers understand your changes and the purpose of your pull request.

### Suggested changes
We may ask for changes to be made before a PR can be merged, either using [suggested changes](https://docs.github.com/en/github/collaborating-with-issues-and-pull-requests/incorporating-feedback-in-your-pull-request) or pull request comments. You can apply suggested changes directly through the UI. You can make any other changes in your fork, then commit them to your branch.

As you update your PR and apply changes, mark each conversation as [resolved](https://docs.github.com/en/github/collaborating-with-issues-and-pull-requests/commenting-on-a-pull-request#resolving-conversations).

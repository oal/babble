![Babble](https://rawgit.com/oal/babble/master/public/static/logo.svg)

# Babble CMS
Babble is a simple content management system. Its main goal is ease of use, 
both for developers and non-technical end users.

Babble CMS was heavily inspired by [Lektor](https://www.getlektor.com/)
and [Kirby](https://getkirby.com/) in how stores content structure (models) and
website content in text files on disk, rather than in an SQL database. This means
that the whole website can be kept in a version control system like Git. It uses
the Twig templating engine (similar to Lektor's Jinja2), and comes with a user
friendly admin interface, including a file manager and image cropping functionality.

**This project is still in development, and is probably not ready for production use.**

## Website structure
#### /assets
Contains the admin Vue.js project. Doesn't need to be present on production sites.

#### /babble
Backend code. Do not make changes here unless you're working on Babble itself.

#### /cache
Static HTML files and dependency tracking generated upon a page's first page load.

#### /content
YAML files for your site's content. This directory contains sub directories for each model,
in which content is stored as one record per file, organized by record ID
(example: `/models/Page/about.yaml`).

#### /models
Defines data types for the site. All data types (models) must be capitalized (Page, not page)
.yaml files. See this directory for examples.

##### /models/blocks
Similar to models, but may be used in "list" fields. Example: You want a gallery. Create an
"Image" block with the fields "image" and "description", and set this as the only available
block for your list field. This allows you to specify any number of "Image" blocks for this
content type.

#### /public
Contains any publicly available content, including index.php which initiates Babble and
serves all requests. As long as index.php is left unchanged, you are free to make changes
in this directory.

#### /templates
This directory contains Twig templates which will be matched against request path and
models.

Templates starting with a lowercase character are rendered directly (like `blog.twig`) 
when a request to `/blog` is received. Capitalized templates are matched against models, 
so if there's a `Page.twig` file, and a request for `/about` doesn't match `about.twig`, 
it will be assumed to be a `Page`, and rendered accordingly.

Templates prefixed with underscores (_) are hidden, and can be used with template
inheritance or for special purposes (`_404.twig` is used when no content was found). 
Often you want a `_base.twig` file for your site's layout, and extend from this for any
other page.

## Installation
```
mkdir public/uploads
sudo chmod www-data public/uploads

sudo chown -R www-data content
sudo chown -R www-data cache

sudo apt install php-imagick

composer install
composer dump-autoload -o
```
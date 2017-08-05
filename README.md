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
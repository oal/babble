# Installation
```
mkdir public/uploads
sudo chmod www-data public/uploads

sudo chown -R www-data content
sudo chown -R www-data cache

sudo apt install php-imagick

composer install
composer dump-autoload -o
```
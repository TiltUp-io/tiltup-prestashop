#!/bin/sh

php -d memory_limit=256M
echo "Installing Tiltup Crypto Payments Module"
php bin/console prestashop:module install tiltupcryptopaymentsmodule
echo "Configuring Tiltup Crypto Payments Module"
php bin/console prestashop:module configure tiltupcryptopaymentsmodule
rm -rf /var/www/html/var/cache/dev
rm -rf /var/www/html/var/logs
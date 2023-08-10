# tiltup-prestashop

TiltUp Prestashop plugin

## Local env setup

### Windows

1. Download & install [Xampp](https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/7.4.33/) as local PHP
   interpreter.
2. Download & install [Composer](https://getcomposer.org/download/)
3. Clone this repo.
4. Download & unzip [Prestashop release](https://github.com/PrestaShop/PrestaShop/releases/tag/1.7.8.9) to a dedicated
      folder. Unzip the `prestashop.zip` file inside as well.
5. Add Prestashop "binaries" as External Library in your IDE.
6. Clone and add [this repo](https://github.com/julienbourdeau/PhpStorm-PrestaShop-Autocomplete) as External Library in
   your IDE for code autocompletion.

#### How to add something as an external library?  
Just copy/paste this into your `vendor` folder  

![intellij-external-library-setup.png](assets/readme/intellij-external-library-setup.png)

## Bootstrapping the application

1. `composer install`
2. `composer up` will spin up a Dockerized MariaDB & Prestashop instance.
2. The repo folder is mounted onto the Prestashop container (aka hot reload), so all your changes will be immediately
   reflected in the shop.
3. Navigate to `localhost:8080` to access your shop.
4. Navigate to `localhost:8080/admin-dev` to access admin panel.

## Debugging

### IntelliJ IDEA

1. Install the official PHP Plugin.
2. The local container already comes with Xdebug enabled, so no extra config needed.
3. Setup server and path mappings as follows:![intellij-debug-setup.png](assets/readme/intellij-debug-setup.png)
4. Click `Start listening to PHP Debug Connections` to start debugging.

## Prestashop

### Module installation

The module is installed by a post-install script. However, it can also be installed/uninstalled from command line:

```shell
php bin/console prestashop:module install tiltupcryptopaymentsmodule
```

### Module configuration

1. Navigate to Admin -> Modules -> Module Manager.
2. Search for `tiltup` and click `Configure`.

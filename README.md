# dpd-module

Installation
------------

```bash
composer require bigfish/dpd-module
```

Upgrade
-------

```bash
composer update bigfish/dpd-module
```

After upgrade or install
------------------------

```bash
php bin/magento module:enable BigFish_Shipping
```
```bash
php bin/magento setup:upgrade
```
```bash
php bin/magento setup:di:compile
```
```bash
php bin/magento setup:static-content:deploy
```


forked module: https://github.com/DPDBeNeLux/magento2-shipping
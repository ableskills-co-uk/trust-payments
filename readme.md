Trust Payments module for [ableskills.co.uk](https://www.ableskills.co.uk) (Magento 2).  

## How to install
```posh             
sudo service cron stop           
bin/magento maintenance:enable  
composer require ableskills/trust-payments:* 
rm -rf var/di var/generation generated/*
bin/magento setup:upgrade
bin/magento cache:enable
bin/magento setup:di:compile
bin/magento cache:clean
rm -rf pub/static/* var/cache var/page_cache var/view_preprocessed
bin/magento setup:static-content:deploy \
	--area adminhtml \
	--theme Ableskills/default \
	-f en_US en_GB
bin/magento setup:static-content:deploy \
	--area frontend \
	--theme Ableskills/default \
	-f en_US en_GB
bin/magento cache:clean
bin/magento maintenance:disable
sudo service cron start
```      

## How to upgrade
```posh              
sudo service cron stop           
bin/magento maintenance:enable  
composer remove ableskills/trust-payments
composer clear-cache
composer require ableskills/trust-payments:*    
rm -rf var/di var/generation generated/*
bin/magento setup:upgrade
bin/magento cache:enable
bin/magento setup:di:compile
bin/magento cache:clean
rm -rf pub/static/* var/cache var/page_cache var/view_preprocessed
bin/magento setup:static-content:deploy \
	--area adminhtml \
	--theme Ableskills/default \
	-f en_US en_GB
bin/magento setup:static-content:deploy \
	--area frontend \
	--theme Ableskills/default \
	-f en_US en_GB
bin/magento cache:clean
bin/magento maintenance:disable
sudo service cron start
```

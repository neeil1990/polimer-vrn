<?php

$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_ANALYTIC_TITLE'] = 'Загрузка заказов и аналитики';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_ANALYTIC_DESCRIPTION'] = '*/5 * * * * bitrix /usr/bin/php -f #PATH#/bitrix/modules/darneo.ozon/cron/importanalytic.php&nbsp;#CLIENT_KEY_ID#';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_ANALYTIC_HELPER'] = 'Загружает заказы и аналитические данные с OZON (каждые 5 минут)';

$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CATALOG_TITLE'] = 'Загрузка каталога';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CATALOG_DESCRIPTION'] = '*/30 * * * * bitrix /usr/bin/php -f #PATH#/bitrix/modules/darneo.ozon/cron/importcatalog.php&nbsp;#CLIENT_KEY_ID#';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CATALOG_HELPER'] = 'Загружает список товаров с OZON (каждые 30 минут).';

$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CORE_TITLE'] = 'Загрузка категорий и характеристик';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CORE_DESCRIPTION'] = '*/5 * * * * bitrix /usr/bin/php -f #PATH#/bitrix/modules/darneo.ozon/cron/importcore.php&nbsp;#CLIENT_KEY_ID#';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CORE_HELPER'] = 'Загружает категории и характеристики с OZON. Включить, если необходимо обновить данные, чтобы не обновлять вручную. Автоматически отключает активность после обновления.';

$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_CATALOG_TITLE'] = 'Выгрузка каталога';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_CATALOG_DESCRIPTION'] = '00 00 * * * bitrix /usr/bin/php -f #PATH#/bitrix/modules/darneo.ozon/cron/exportcatalog.php&nbsp;#CLIENT_KEY_ID#';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_CATALOG_HELPER'] = 'Выгружает список товаров на OZON, обновляет существующие и создает новые (каждый день в 00:00). Не используется для выгрузки цен и остатков';

$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_PRICE_TITLE'] = 'Выгрузка цен';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_PRICE_DESCRIPTION'] = '*/15 * * * * bitrix /usr/bin/php -f #PATH#/bitrix/modules/darneo.ozon/cron/exportprice.php&nbsp;#CLIENT_KEY_ID#';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_PRICE_HELPER'] = 'Выгружает цены товаров на OZON (каждые 15 минут)';

$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_STOCK_TITLE'] = 'Выгрузка остатков';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_STOCK_DESCRIPTION'] = '*/15 * * * * bitrix /usr/bin/php -f #PATH#/bitrix/modules/darneo.ozon/cron/exportstock.php&nbsp;#CLIENT_KEY_ID#';
$MESS['DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_STOCK_HELPER'] = 'Выгружает остатки товаров на OZON (каждые 15 минут)';


<?php

$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_FIELD_SETUP_ID'] = 'Откуда загружать';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_FIELD_SETUP_ID_HELP'] = 'Выберите настройку обработки заказов, из которой будут скопированы правила и загружен список кампаний бизнеса';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_FIELD_CAMPAIGN_ID'] = 'Какие кампании';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_FIELD_CAMPAIGN_ID_HELP'] = 'Отметьте кампании, которые необходимо создать в 1С-Битрикс. Настройки обработки заказов будут скопированы автоматически.';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_FIELD_GROUP_PRODUCT_STORE'] = 'Откуда брать данные об остатках';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_SUCCESS_MESSAGE'] = 'Кампании успешно импортированы';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_SUCCESS_DETAILS'] = '
<p>Осталось заполнить Настройки API для каждой кампании:</p>
<ol>
<li>Откройте страницу Настройки&nbsp;&rarr; Настройки API в <a href="https://partner.market.yandex.ru/" target="_blank">личном кабинете</a>;</li>
<li>Выберите кампанию из выпадающего списка справа от заголовка страницы;</li>
<li>Скопируйте &laquo;Адрес для запросов&raquo; из 1С-Битрикс в поле &laquo;URL для запросов API&raquo;;</li>
<li>Значение поля &laquo;Авторизационный токен&raquo; скопируйте в настройки кампании 1С-Битрикс (поле &laquo;Токен для запросов&raquo;);</li>
<li>Повторите действия для каждой кампании.</li>
</ol>
';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_ERROR_SETUP_EMPTY'] = '<a href="#SETUP_URL#">Настройте</a> хотя бы одну кампанию перед запуском импорта';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_ERROR_CAMPAIGN_LOAD'] = 'Не удалось загрузить доступные кампании: #MESSAGE#';
$MESS['YANDEX_MARKET_COMPONENT_TRADINGIMPORT_EDITFORM_ERROR_UNIQUE_CODE'] = 'Не удалось создать уникальный код для кампании #CAMPAIGN_ID#';

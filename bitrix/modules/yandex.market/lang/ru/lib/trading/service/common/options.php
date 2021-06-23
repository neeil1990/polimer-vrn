<?php

$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_TITLE'] = 'Обработка заказов из маркетплейса Яндекс.Маркета';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_TAB_COMMON'] = 'Общие настройки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_GROUP_COMPANY_INFO'] = 'Информация о магазине';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_GROUP_SERVICE_REQUEST'] = 'Обмен данными через API';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_CAMPAIGN_ID'] = 'Номер кампании';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_CAMPAIGN_ID_PLACEHOLDER'] = '21579827';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_CAMPAIGN_ID_DESCRIPTION'] = 'Зайдите в <a href="http://partner.market.yandex.ru/" target="_blank">личный кабинет</a> и кликните на стрелку рядом с названием магазина — откроется выпадающая строка. Справа от названия будет номер рекламной кампании в формате № XX-12345678 — скопируйте сюда восемь цифр после дефиса.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_TAX_SYSTEM'] = 'Система налогообложения магазина';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_YANDEX_TOKEN'] = 'Токен для запросов';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_YANDEX_TOKEN_PLACEHOLDER'] = '3D000001B86C3C97';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_YANDEX_TOKEN_DESCRIPTION'] = 'Зайдите в раздел <a href="http://partner.market.yandex.ru/" target="_blank">личного кабинета</a> &laquo;Настройки&raquo; и скопируйте сюда авторизационный токен на странице &laquo;Настройки API&raquo;.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_YANDEX_INCOMING_URL'] = 'Адрес для запросов';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_YANDEX_INCOMING_URL_DESCRIPTION'] = 'Укажите значение отсюда в URL API на той же странице.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_YANDEX_INCOMING_URL_NOTE_HTTPS'] = 'Запросы выполняются только по&nbsp;протоколу https.<br /> Проверьте настройки <nobr>SSL-сертификата</nobr>';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_ID_INTRO'] = '
Теперь зарегистрируйте приложение на сервере <a href="https://oauth.yandex.ru/" target="_blank">oauth.yandex.ru</a> — укажите там любое удобное название приложения, а также следующие значения:
<ul>
	<li>Платформы &mdash; Веб-сервисы;</li>
	<li>Callback URI &mdash; <a href="#CALLBACK_URI#" target="_blank">#CALLBACK_URI#</a>;</li>
	<li>Доступы &mdash; Яндекс.Маркет &rarr; API Яндекс.Маркета для партнеров.</li>
</ul>
Затем скопируйте сюда полученную информацию:
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_OAUTH_TOKEN'] = 'Токен для запросов магазина';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_ID'] = 'Идентификатор приложения';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_ID_PLACEHOLDER'] = '48b1b3b233ae46eb8dc3272f95cdbc4e';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_PASSWORD'] = 'Пароль приложения';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_PASSWORD_PLACEHOLDER'] = '4h9d81d12ef04e1c9125342abc04ea36';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL'] = 'Содержание журнала событий';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_DESCRIPTION'] = 'Выберите вариант &laquo;Информация&raquo; — тогда в журнале событий будут отображаться ошибки и любые действия с заказами.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_GROUP_ORDER'] = 'Оплата и доставка';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PERSON_TYPE'] = 'Тип плательщика';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PAY_SYSTEM'] = 'Платежная система (#TYPE#)';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PROFILE_ID'] = 'Профиль покупателя';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_DELIVERY_ID'] = 'Служба доставки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_ACCEPT_OLD_PRICE'] = 'Если цена товара изменилась с момента запроса цены<br /> до момента оформления заказа';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_ACCEPT_OLD_PRICE_DECLINE'] = 'Не оформлять заказ';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_ACCEPT_OLD_PRICE_MODIFY'] = 'Оформлять заказ со старой ценой';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_GROUP_PROPERTY'] = 'Свойства заказа';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_LEGAL_NAME'] = 'Название организации';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_LEGAL_NAME_PLACEHOLDER'] = 'ИП Витгенштейн Людвиг Карлович';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_LEGAL_NAME_HELP'] = 'Оно должно совпадать с названием, которое указано в учредительных документах и на странице &laquo;Юридические данные&raquo; в личном кабинете.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_LOGO'] = 'Логотип магазина';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_LOGO_HELP'] = 'Вы можете загрузить файл с логотипом магазина — тогда ваши покупатели увидят его на ярлыках, которые вы будете наклеивать на коробки с заказами. Допустимые форматы — JPG и PNG. Размеры — 321&#215;37 px для ярлыка 9,5&#215;13,4 см и 491&#215;56 px для ярлыка 4,8&#215;21 см. Чужие логотипы использовать нельзя.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_NAME'] = 'Название магазина';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_NAME_PLACEHOLDER'] = 'Romashka';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_COMPANY_NAME_HELP'] = 'Оно должно совпадать с названием, которое вы указали в личном кабинете. Название будет отображаться на ярлыках, которые вы будете распечатывать и наклеивать на каждую коробку с заказом.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_TAB_STORE'] = 'Данные о&nbsp;ценах';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_SKU_FIELD'] = 'Откуда брать ваши SKU';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_SKU_FIELD_DESCRIPTION'] = 'Для каждого из&nbsp;каталогов выберите поля, в&nbsp;которых хранятся ваши SKU. Они будут использоваться для обмена информацией о&nbsp;заказах и&nbsp;остатках.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_STORE_TRACE'] = 'Ограничивать по фактическому наличию';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_STORE'] = 'Откуда брать данные об остатках';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_STORE_DESCRIPTION'] = '
Теперь выберите поля, из&nbsp;которых маркетплейс будет получать данные об&nbsp;остатках ваших товаров:
<ol>
<li>Общее количество товаров из&nbsp;каталога указано в&nbsp;разделе &laquo;Контент&raquo; &rarr; Торговые предложения &rarr; каждый товар из&nbsp;вашего <nobr>прайс-листа</nobr> на&nbsp;маркетплейсе &rarr; вкладка &laquo;Торговый каталог&raquo; &rarr; вкладка &laquo;Параметры&raquo; &rarr; поле &laquo;Доступное количество&raquo;<br /><br /></li>
<li>Если вы&nbsp;добавили в&nbsp;<nobr>&laquo;1С-Битрикс&raquo;</nobr> ваши склады, вы&nbsp;можете выбрать, остатки на&nbsp;каких складах сделать доступными для заказа на&nbsp;маркетплейсе. Но&nbsp;отгружать заказы из&nbsp;маркетплейса с&nbsp;того склада, который вы&nbsp;указали в&nbsp;личном кабинете. Доступное количество товаров на&nbsp;ваших складах указано в&nbsp;разделе &laquo;Контент&raquo; &rarr; Торговые предложения &rarr; каждый товар из&nbsp;вашего <nobr>прайс-листа</nobr> на&nbsp;маркетплейсе &rarr; вкладка &laquo;Торговый каталог&raquo; &rarr; вкладка &laquo;Склады&raquo; &rarr; поле &laquo;Количество товара&raquo; напротив каждого склада.</li>
</ol>
Ваши данные об&nbsp;остатках должны быть актуальными, чтобы на&nbsp;витрине маркетплейса отображались только товары в&nbsp;наличии.
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_SOURCE'] = 'Выбор цены';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_SOURCE_NO_VALUE'] = 'По умолчанию';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_TYPE'] = 'Типы цен';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_DISCOUNT'] = 'Рассчитывать скидки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_GROUP_STATUS_OUT'] = 'Вы можете передавать Турбо-страницам статусы:';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_TAB_STATUS'] = 'Статусы заказов';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_GROUP_STATUS_IN'] = 'Турбо-страницы могут передавать вам статусы:';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_ERROR'] = 'Ошибки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_WARNING'] = 'Предупреждения';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_INFO'] = 'Информация';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_DEBUG'] = 'Отладка';
$MESS['YANDEX_MARKET_TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_NO_VALUE'] = 'Отключить';
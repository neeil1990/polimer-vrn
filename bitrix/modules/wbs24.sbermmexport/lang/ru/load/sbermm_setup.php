<?
$MESS["BX_CATALOG_EXPORT_IBLOCK"] = "Выберите инфоблок для выгрузки:";
$MESS["BX_CATALOG_EXPORT_YANDEX_SITE"] = "Выберите сайт для выгрузки:";
$MESS["BX_CATALOG_EXPORT_YANDEX_COMPANY_NAME"] = "Название компании:";
$MESS["BX_CATALOG_EXPORT_YANDEX_ERR_EMPTY_SITE"] = "Не указан ID сайта для выгрузки";
$MESS["BX_CATALOG_EXPORT_YANDEX_ERR_BAD_SITE"] = "Сайт с указанным ID не найден либо деактивирован";
$MESS["BX_CATALOG_EXPORT_YANDEX_OPTION_CONVERT_TO_UTF"] = "Создать выгрузку в кодировке utf-8:";

$MESS["BX_CATALOG_EXPORT_SET_ID"] = "Артикул в СберМегаМаркет (для товара):";
$MESS["BX_CATALOG_EXPORT_SET_ID_NOTE"] = "Выберите свойство инфоблока товаров. Также можно выбрать XML_ID, в этом случае будет использован внешний код. По умолчанию в аритикул подставляется ID товара";
$MESS["BX_CATALOG_EXPORT_SET_OFFER_ID"] = "Артикул в СберМегаМаркет (для торгового предложения):";
$MESS["BX_CATALOG_EXPORT_SET_OFFER_ID_NOTE"] = "Выберите свойство инфоблока торговых предложений. Также можно выбрать XML_ID, в этом случае будет использован внешний код. По умолчанию в аритикул подставляется ID торгового предложения";
$MESS["BX_CATALOG_EXPORT_MIN_STOCK"] = "Минимальное разрешенное кол-во на остатке";
$MESS["BX_CATALOG_EXPORT_ORDER_BEFORE"] = "Время окончания операционного дня, час.";
$MESS["BX_CATALOG_EXPORT_ORDER_BEFORE_EMPTY_VALUE"] = "Не указано";
$MESS["BX_CATALOG_EXPORT_ORDER_BEFORE_NOTE"] = "Если значение не указано, то по умолчанию используется 12";
$MESS["BX_CATALOG_EXPORT_DELIVERY_DAYS"] = "Количество рабочих дней для отгрузки заказа";
$MESS["BX_CATALOG_EXPORT_DELIVERY_DAYS_NOTE"] = "Если значение не указано, то по умолчанию используется 3";
$MESS["BX_CATALOG_EXPORT_STORE_ID"] = "ID склада, указанный в ЛК СберМегаМаркет";
$MESS["BX_CATALOG_EXPORT_IGNORE_SALE"] = "Игнорировать скидки (выгружать полную цену):";
$MESS["BX_CATALOG_EXPORT_IGNORE_SALE_NOTE"] = "Если стоит галочка, то при использовании Ценообразования используется цена без скидки. То есть игнорируются скидки из раздела \"Маркетинг\"";
$MESS["BX_CATALOG_EXPORT_RULES_NOTE"] =
    "<p style='text-align:center;'><strong>Загрузка нескольких фидов в Личный кабинет СберМегаМаркет</strong></p>"
    ."<p>Для того чтобы загрузить несколько фидов в личный кабинет, они должны удовлетворять следующим требованиям:</p>"
    ."<ul><li>Фиды должны содержать одинаковый справочник категорий. В каждом файле справочники категорий должны быть полностью идентичными, даже если в конкретном файле нет офферов по этим категориям.</li>"
    ."<li>Фиды должны содержать неповторяющиеся офферы, то есть в двух файлах не может быть одинаковых офферов (offerID должен быть уникальным, как и сам товар).</li></ul>"
    ."<p>При возникновении ошибок, связанных с обработкой фидов, весь ассортимент продавца будет снят с продажи на макетплейсе sbermegamarket.ru.</p>"
    .'<p><a href="https://min-lb-vip.goods.ru/mms/documents/assortment/%D0%9F%D1%80%D0%B0%D0%B2%D0%B8%D0%BB%D0%B0%20%D0%B7%D0%B0%D0%BF%D0%BE%D0%BB%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F%20XML%20%D1%84%D0%B8%D0%B4%D0%B0%20.pdf" target="_blank">Правила заполнения XML фида</a>.</p>'
;

$MESS["WBS24.SBERMMEXPORT.MANUAL_CALL_NOTE"] = "<b>Внимание!</b> В ДЕМО режиме модуля установлено ограничение на выгрузку до 1000 товаров. Для демонстрации корректной работы модуля, при запуске формирования фида ВРУЧНУЮ выгружается не более 1000 товаров. Количество зависит от установленных вами фильтров. Для выгрузки всех товаров настраивайте формирование ФИДа на АГЕНТах или CRON.";
$MESS["WBS24.SBERMMEXPORT.OPEN_LINK"] = "Открыть ссылку";
$MESS["WBS24.SBERMMEXPORT.COPY_LINK"] = "Копировать ссылку";
$MESS["WBS24.SBERMMEXPORT.COPY_LINK_SUCCESS"] = "Скопировано";

$MESS["BX_CATALOG_EXPORT_FEED_PROTECT"] = "Фид защищен логином и паролем:";
$MESS["BX_CATALOG_EXPORT_FEED_LOGIN"] = "Логин:";
$MESS["BX_CATALOG_EXPORT_FEED_PASS"] = "Пароль:";

// вкладка "Ценообразование"
$MESS["CAT_ADM_MISC_EXP_TAB_EXTPRICE"] = "Ценообразование";
$MESS["CAT_ADM_MISC_EXP_TAB_EXTPRICE_TITLE"] = "Расширенное управление ценами";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_ON"] = "Использовать ценообразование:";

$MESS["WBS24_SBERMMEXPORT_FORMULA_SUBTITLE"] = "Ценообразование по формуле <sup style='color: red;'>NEW</sup>";
$MESS["WBS24_SBERMMEXPORT_FORMULA_ON"] = "Использовать ценообразование по формуле:";
$MESS["WBS24_SBERMMEXPORT_FORMULA_PRICE"] = "<b>Цена со скидкой (price):</b>";
$MESS["WBS24_SBERMMEXPORT_FORMULA_OLDPRICE"] = "<b>Цена без скидки (oldprice)</b>";
$MESS["WBS24_SBERMMEXPORT_FORMULA_MARK_PRICE"] = "Цена";
$MESS["WBS24_SBERMMEXPORT_FORMULA_MARK_PRICE_DISCOUNT"] = "Цена со скидкой";
$MESS["WBS24_SBERMMEXPORT_FORMULA_NOTE"] =
    "<p style='text-align: left;'>
    В формулах можно использовать:<br>
    1) метки цен, которые доступны для выбора под каждой формулой;<br>
    2) арифметические действия * / + - и скобки для указания приоритета ( );<br>
    3) числа, включая дробные (разделителем десятичной дроби является точка).
    </p>"
;

$MESS["BX_CATALOG_EXPORT_EXTPRICE_SUBTITLE"] = "Ценообразование (правила изменения цены)";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_PRICE"] = "Цена со скидкой (price)";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_PRICE_NOTE"] = "Формула: Цена со скидкой (price) = Цена * ( 1 + K% ) + S";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_PLUS_PERCENT"] = "K =";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_PLUS_PERCENT_NOTE"] = "K - наценка (%). Например, если значение 30, это значит плюс 30% к цене";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_PLUS_ADDITIONAL_SUM"] = "S =";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_PLUS_ADDITIONAL_SUM_NOTE"] = "S - надбавка. Обычно используется для включения в цену стоимости доставки";

$MESS["BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE"] = "Цена без скидки (oldprice)";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE_NOTE"] = "Если указано 20, то \"Цена со скидкой\" будет меньше \"Цены без скидки\" на 20%";
$MESS["BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE_PLUS_PERCENT"] = "Размер скидки:";

// вкладка "Склады"
$MESS["CAT_ADM_MISC_EXP_TAB_WAREHOUSE"] = "Склады / остатки";
$MESS["CAT_ADM_MISC_EXP_TAB_WAREHOUSE_TITLE"] = "Настройка остатков";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_NOTE"] = "По умолчанию остаток берется из \"Доступного количества\" товара в параметрах \"Торгового каталога\"";

$MESS["BX_CATALOG_EXPORT_WAREHOUSE_SUBTITLE"] = "Остатки с складов";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_EXTEND_NOTE"] = "В фиде выводится сумма остатков со всех выбранных складов";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_EXTEND_ON"] = "Использовать остатки на складах:";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_FILTER_ON"] = "Выборочная выгрузка складов:";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_FILTER_ON_NOTE"] = "Поставьте галочку рядом со складом, с которого необходимо выгружать данные в SberMegaMarket";

$MESS["BX_CATALOG_EXPORT_WAREHOUSE_PROPBASED_SUBTITLE"] = "Остатки из свойств (Простой товар)";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_PROPBASED_NOTE"] = "В фиде выводится сумма остатков из всех выбранных свойств";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_PROPBASED_ON"] = "Получать остатки из свойств:";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_PROPBASED_PROP"] = "Свойство:";
$MESS["BX_CATALOG_EXPORT_WAREHOUSE_PROPBASED_WARNING"] = "<span style='color: red;'>Данная настройка работает только для простых товаров. Для товаров с торговыми предложениями получение остатков из свойств на данный момент не доступно</span>";

// вкладка "Ограничения"
$MESS["CAT_ADM_MISC_EXP_TAB_LIMITATIONS"] = "Ограничения по цене";
$MESS["CAT_ADM_MISC_EXP_TAB_LIMITATIONS_TITLE"] = "Настройка ограничений";
$MESS["BX_CATALOG_EXPORT_PRICE_LIMIT_ON"] = "Использовать ограничение по цене";
$MESS["BX_CATALOG_EXPORT_PRICE_LIMIT"] = "Цена товара:";
$MESS["BX_CATALOG_EXPORT_PRICE_LIMIT_MIN"] = "от ";
$MESS["BX_CATALOG_EXPORT_PRICE_LIMIT_MAX"] = " до ";
$MESS["BX_CATALOG_EXPORT_PRICE_LIMIT_NOTE"] = "В выгрузку попадут только товары с указанным диапазоном цены";
$MESS["BX_CATALOG_EXPORT_PRICE_LIMIT_BEFORE_EXTPRICE"] = "Использовать цену до Ценообразования";
$MESS["BX_CATALOG_EXPORT_PRICE_LIMIT_BEFORE_EXTPRICE_NOTE"] = "Если стоит галочка, то используется цена из каталога до применения Ценообразования";

// вкладка "Фильтры"
$MESS["CAT_ADM_MISC_EXP_TAB_FILTER"] = "Фильтры";
$MESS["CAT_ADM_MISC_EXP_TAB_FILTER_TITLE"] = "Настройка фильтров";
$MESS["BX_CATALOG_EXPORT_FILTER_ON_NOTE"] = "<font style='color:red'>Все ограничения, испульзуемые в предыдущей вкладке, имеют наивысший приоритет</font>";
$MESS["BX_CATALOG_EXPORT_FILTER_ON"] = "Использовать фильтры";
?>

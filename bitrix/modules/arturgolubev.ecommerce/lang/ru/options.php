<?
$MESS["ARTURGOLUBEV_EC_DEMO_IS_EXPIRED"] = "Демо период-модуля закончился. Полную версию решения вы можете приобрести в <a href=\"http://marketplace.1c-bitrix.ru/solutions/arturgolubev.ecommerce/\" target=\"_blank\">Marketplace 1c-Bitrix</a>";
$MESS["ARTURGOLUBEV_EC_CLEAR_CACHE"] = 'Настройки модуля изменены. Очистите <a target="_blank" href="/bitrix/admin/cache.php?lang=ru">весь кеш сайта</a> и закройте это уведомление';


$MESS["ARTURGOLUBEV_EC_OPTIONS_MAINTAB"] = "Основные настройки";
$MESS["ARTURGOLUBEV_EC_OFF_MODE"] = "Выключить работу модуля для всех сайтов:";
$MESS["ARTURGOLUBEV_EC_GET_ORDER_ID_FROM"] = "Номер заказа передавать из поля";
$MESS["ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ID"] = "Идентификатор заказа (ID)";
$MESS["ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ACCOUNT_NUMBER"] = "Номер заказа (ACCOUNT_NUMBER)";

$MESS["ARTURGOLUBEV_EC_BRAND_NO_SELECT"] = "Не выбрано";
$MESS["ARTURGOLUBEV_EC_OPTIONS_BRAND"] = "Настройка данных";
$MESS["ARTURGOLUBEV_EC_DATA_SECTION"] = "Настройка передаваемых данных данных";
$MESS["ARTURGOLUBEV_EC_OPTIONS_NO_SELECT"] = "Не выбрано";
$MESS["ARTURGOLUBEV_EC_OPTIONS_BRAND_PROP"] = "Свойство бренд в инфоблоке ";

$MESS["ARTURGOLUBEV_EC_SYSTEM"] = "Системные настройки";
$MESS["ARTURGOLUBEV_EC_DEBUG_MODE"] = "<span data-hint=\"Опция для разработчиков. Режим отладки позволяет отображать отдаваемые на отправку данные в Javascript-консоли\"></span> Режим отладки:";
$MESS["ARTURGOLUBEV_EC_OFF_AJAX"] = "<span data-hint=\"Позволяет уменьшить нагрузку на хостинг, но переводит обработку событий добавления-удаления из корзины на следующий хит пользователя\"></span> Отключить фоновый ajax-запрос:";
$MESS["ARTURGOLUBEV_EC_OFF_AJAX_ORDER"] = "<span data-hint=\"В связи со спецификой некоторых интернет магазинов, ajax запрос на странице оформления заказа может вызывать неоднозначные ситуации. Активировать эту опцию рекомендуется по совету технической поддержки модуля\"></span> Отключить фоновый ajax-запрос на странице оформления заказа:";

$MESS["ARTURGOLUBEV_EC_SITE_SETTING"] = 'Настройки для сайта';
$MESS["ARTURGOLUBEV_EC_DISABLED_SITE"] = "Отключить для сайта";

$MESS["ARTURGOLUBEV_EC_OPTIONS_ALL_SETTING"] = "Общие настройки";
$MESS["ARTURGOLUBEV_EC_ORDER_PAGE"] = '<span data-hint="Используется для Google Analitycs и Facebook"></span> Страница оформления заказа';
$MESS["ARTURGOLUBEV_EC_ORDER_PAGE_NOTE"] = "Адрес страницы необходимо указывать в формате <b>/personal/order/make/</b> без <b>?ORDER_ID</b>, get параметров и домена";

$MESS["ARTURGOLUBEV_EC_OPTIONS_YANDEX_SETTING_TAB"] = "Яндекс.Метрика";
$MESS["ARTURGOLUBEV_EC_OPTIONS_YANDEX_SETTING"] = "Яндекс.Метрика";
$MESS["ARTURGOLUBEV_EC_YA_OFF"] = "Отключить передачу данных в Яндекс.Метрику";
$MESS["ARTURGOLUBEV_EC_CONTAINER_NAME"] = "<span data-hint=\"Имя контейнера данных должно совпадать с указанным в настройках метрики. Если нет необходимости в другом названии переменной, рекоммендуется использовать стандартное - dataLayer\"></span> Имя контейнера данных яндекс";
$MESS["ARTURGOLUBEV_EC_YA_TARGET_ORDER"] = "<span data-hint=\"Для данной настройки используйте цель типа Javascript-событие. В настройку нужно вписать цифровой ID цели.\"></span> Номер цели срабатывающей при оформлении заказа";

$MESS["ARTURGOLUBEV_EC_OPTIONS_GOOGLE_SETTING_TAB"] = "Google Analitycs";
$MESS["ARTURGOLUBEV_EC_GA_OFF"] = "Отключить передачу данных в Google Analitycs";
$MESS["ARTURGOLUBEV_EC_OPTIONS_GOOGLE_SETTING"] = "Google Analitycs";

$MESS["ARTURGOLUBEV_EC_OPTIONS_FACEBOOK_SETTING"] = "Facebook";
$MESS["ARTURGOLUBEV_EC_FB_OFF"] = "Отключить передачу данных в Facebook";

$MESS["ARTURGOLUBEV_EC_OPTIONS_CONVERT_CURRENCY"] = "Конвертация валют";
$MESS["ARTURGOLUBEV_EC_CONVERT_CURRENCY"] = "Конвертация валюты:";
$MESS["ARTURGOLUBEV_EC_CONVERT_CURRENCY_EMPTY"] = "Не конвертировать";
$MESS["ARTURGOLUBEV_EC_OPTIONS_CONVERT_CURRENCY_NOTE"] = "Опция позволяет передавать цены в валюте отличной от валюты магазина. Данная возможность необходима например для Беларуси, т.к. Google Analitycs не поддерживает валюту BYN. Конвертация происходит по курсу валют <a href='/bitrix/admin/currencies_rates.php?lang=ru' target='_blank'>из настроек</a>";



$MESS["ARTURGOLUBEV_EC_OLD_VERSION"] = "Настройки совместимости с предыдущими версиями модуля";
$MESS["ARTURGOLUBEV_EC_OLD_ANALITYCS_VERSION"] = "Использовать старую версию аналитики (analitycs.js)";
$MESS["ARTURGOLUBEV_EC_OLD_VERSION_NOTE"] = "В режиме совместимости модуль не может работать с библиотекой gtag и gtm. Проверьте соотвествует ли настройка <a target=\"_blank\" href=\"http://arturgolubev.ru/instructions/metrics/\">инструкции</a> и отключите опцию \"Использовать старую версию аналитики (analitycs.js)\".";


$MESS["ARTURGOLUBEV_EC_OFF_AJAX_DESCRIPTION"] = "
<b>Справка по некоторым настройкам:</b><br/>
<p>\"Режим отладки\" опция для разработчиков. Режим отладки позволяет отображать отдаваемые на отправку данные в Javascript-консоли</p>
<p>\"Отключить фоновый ajax-запрос\" позволяет уменьшить нагрузку на хостинг, но переводит обработку событий добавления-удаления из корзины на следующий хит пользователя</p>
<p>\"Отключить фоновый ajax-запрос на странице оформления заказа\" - в связи со спецификой некоторых интернет магазинов, ajax запрос на странице оформления заказа может вызывать неоднозначные ситуации. Активировать эту опцию рекомендуется по совету технической поддержки модуля</p>
";


/* help tab */
$MESS["ARTURGOLUBEV_ECOMMERCE_HELP_TAB_NAME"] = "Информация";
$MESS["ARTURGOLUBEV_ECOMMERCE_HELP_TAB_TITLE"] = "Полезная информация";

$MESS["ARTURGOLUBEV_ECOMMERCE_CARD_TEXT"] = "Карточка решения на Marketplace -";
$MESS["ARTURGOLUBEV_ECOMMERCE_CARD_TEXT_VALUE"] = "<a href='https://marketplace.1c-bitrix.ru/solutions/arturgolubev.ecommerce/#tab-about-link' target='_blank'>открыть</a>";

$MESS["ARTURGOLUBEV_ECOMMERCE_INSTALL_TEXT"] = "Информация по установке -";
$MESS["ARTURGOLUBEV_ECOMMERCE_INSTALL_TEXT_VALUE"] = "<a href='https://marketplace.1c-bitrix.ru/solutions/arturgolubev.ecommerce/#tab-install-link' target='_blank'>открыть</a>";

$MESS["ARTURGOLUBEV_ECOMMERCE_INSTALL_PAGE_TEXT"] = "Текстовая инструкция (более информативная) -";
$MESS["ARTURGOLUBEV_ECOMMERCE_INSTALL_PAGE_TEXT_VALUE"] = "<a href='http://arturgolubev.ru/instructions/metrics/' target='_blank'>открыть</a>";

$MESS["ARTURGOLUBEV_ECOMMERCE_INSTALL_VIDEO_TEXT"] = "Видео-инструкция -";
$MESS["ARTURGOLUBEV_ECOMMERCE_INSTALL_VIDEO_TEXT_VALUE"] = "<a href='http://arturgolubev.ru/learning/course/index.php?COURSE_ID=3&INDEX=Y' target='_blank'>открыть</a>";

$MESS["ARTURGOLUBEV_ECOMMERCE_FAQ_TEXT"] = "Часто задаваемые вопросы по данному модулю -";
$MESS["ARTURGOLUBEV_ECOMMERCE_FAQ_TEXT_VALUE"] = "<a href='http://arturgolubev.ru/learning/course/index.php?COURSE_ID=3&INDEX=Y' target='_blank'>открыть</a>";

$MESS["ARTURGOLUBEV_ECOMMERCE_FAQ_MAIN_TEXT"] = "Вопросы по покупке, оплате, активации модуля и т.п. -";
$MESS["ARTURGOLUBEV_ECOMMERCE_FAQ_MAIN_TEXT_VALUE"] = "<a href='http://arturgolubev.ru/learning/course/?COURSE_ID=1&INDEX=Y' target='_blank'>открыть</a>";
?>
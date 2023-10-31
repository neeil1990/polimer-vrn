<?
// $MESS["ARTURGOLUBEV_OZON_API_TOKEN"] = "<span data-hint='todo'></span>API токен:";

$MESS["ARTURGOLUBEV_OZON_DEMO_IS_EXPIRED"] = "Демонстрационный период работы решения закончился. Для дальнейшего использования необходимо приобрести полую версию решения в <a href=\"http://marketplace.1c-bitrix.ru/solutions/arturgolubev.ozon/\" target=\"_blank\">marketplace.1c-bitrix.ru</a>";
$MESS["ARTURGOLUBEV_WILDBERRIES_RIGHTS_ERROR"] = 'Недостаточно прав для просмотра данного раздела';


$MESS["ARTURGOLUBEV_OZON_ERROS_SETTING_TITLE"] = "Для эффективной работы решения:<br>";
$MESS["ARTURGOLUBEV_OZON_AGENTS_NOT_CRON"] = "Агенты выполняются на хитах (константа BX_CRONTAB_SUPPORT не определена). Для стабильной работы автоматических выгрузок агенты рекомендуется перевести на cron (Процесс перевода агентов на cron не сложен, но требует создания cron задания на сервере, поэтому для выполнения рекомендую обратиться к разработчикам)";
$MESS["ARTURGOLUBEV_OZON_CLEAR_CACHE"] = 'Настройки модуля изменены. Очистите <a target="_blank" href="/bitrix/admin/cache.php?lang=ru">Все страницы HTML кеша</a> и закройте это уведомление';
$MESS["ARTURGOLUBEV_OZON_CURL_NOT_FOUND"] = "Серверная библиотека CURL необходимая для работы решения не найдена! Обратитесь в техническую поддержку сервера";
$MESS["ARTURGOLUBEV_OZON_MAIN_CLEAR_LOG_NOSET"] = 'Параметр "Сколько дней хранить события" <a target="_blank" href="/bitrix/admin/settings.php?lang='.LANG.'&mid=main&tabControl_active_tab=edit8">главного модуля</a> не заполнен. Рекомендуется ограничить хранение записей журнала 14, 30 или 60 днями';
$MESS["ARTURGOLUBEV_OZON_SALE_NOT_FOUND"] = "Не найдены модули sale или catalog. Проверьте редакцию вашей лицензии 1с-Битрикс (Для работы решения требуется редакция \"Малый Бизнес\" или \"Бизнес\")";
$MESS["ARTURGOLUBEV_OZON_SITE_ID_INCORRECT"] = "Сайты не найдены.";


$MESS["ARTURGOLUBEV_OZON_SAVE_TO_CONTINUE"] = "Сохраните настройки после изменения";
$MESS["ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT_REQUIRED"] = "Выберите значение";
$MESS["ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT"] = "Не выбрано";
$MESS["ARTURGOLUBEV_OZON_SELECTBOX_NO_SELECT_PROP"] = "Выберите параметр";
$MESS["ARTURGOLUBEV_OZON_LOADING"] = "Загрузка";

/* settings menu */
$MESS["ARTURGOLUBEV_OZON_SMENU_SYSTEMS"] = "Системные";
$MESS["ARTURGOLUBEV_OZON_SMENU_GET_WAREHOUSE"] = "Получить идентификаторы складов";
$MESS["ARTURGOLUBEV_OZON_SMENU_GET_LIMIT"] = "Получить лимит создания карточек";

/* main tab */
$MESS["ARTURGOLUBEV_OZON_SITE_SETTING_TITLE"] = "Настройки интеграции сайта #sid#";
$MESS["ARTURGOLUBEV_OZON_MAIN_TAB"] = "Базовые настройки";
$MESS["ARTURGOLUBEV_OZON_ADMIN_NAME"] = "<span data-hint='Для отображения в админ панели'></span>Название интеграции:";
$MESS["ARTURGOLUBEV_OZON_SELLER_BLOCK"] = "Параметры продавца";
$MESS["ARTURGOLUBEV_OZON_API_TOKEN"] = "API токен:";
$MESS["ARTURGOLUBEV_OZON_CLIENT_ID"] = "Client ID:";
$MESS["ARTURGOLUBEV_OZON_STOCKS_COUNT"] = "Количество складов на Ozon:";
$MESS["ARTURGOLUBEV_OZON_SELLER_STOCK_ID"] = "<span data-hint='Получить ID склада можно после заполнения API-ключа и ClientID прямо в настройках решения. Заполните API-ключ и ClientID, сохраните. Вверху нажмите Системные -> Получить идентификаторы складов'></span>ID Склада #num#:";
$MESS["ARTURGOLUBEV_OZON_SYSTEM_SETTINGS"] = "Системные";
$MESS["ARTURGOLUBEV_OZON_SYSTEM_WRITE_SEND_DATA"] = "<span data-hint='Стандартное логирование в файл функцей AddMessage2Log. При включенной опции файл лога будет быстро расти, не забывайте очищать'></span>Логировать запросы к Ozon в файл:";

/* catalog tab */
$MESS["ARTURGOLUBEV_OZON_CATALOG_TAB"] = "Каталоги";
$MESS["ARTURGOLUBEV_OZON_CATALOG_TITLE_TAB"] = "Выбор и настройки каталогов участвующих в интеграции";
$MESS["ARTURGOLUBEV_OZON_CATALOG_ID"] = "<span data-hint='Если используются торговые предложения, выбрать нужно и инфоблок Каталога и инфоблок Торговых предложений'></span>Каталоги товаров участвующие в выгрузке:";
$MESS["ARTURGOLUBEV_OZON_PRODUCT_CATALOG_INDIVIDUAL"] = "Параметры каталога";
$MESS["ARTURGOLUBEV_OZON_SELECTBOX_PROPERTY_TITLE"] = "== Свойства инфоблока ==";
$MESS["ARTURGOLUBEV_OZON_CATALOG_FILTER"] = "<span data-hint='Решение будет выгружать остатки и цены для товаров попадающих под отбор'></span>Свойство для отбора товаров:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_FILTER_VALUE"] = "<span data-hint='Решение будет выгружать остатки и цены для товаров попадающих под отбор'></span>Значение свойства для отбора товаров:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_FILTER_MAIN_IDS"] = "Товары с заполненным Артикулом Ozon";
$MESS["ARTURGOLUBEV_OZON_CATALOG_FILTER_COUNT"] = " товаров каталога попадает под текущий отбор";

$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE"] = "Источник цены с учетом скидки:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_OLD"] = "Источник цены до скидки:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_MIN"] = "Источник минимальной цены:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_AUTOSALE"] = "Автоприменение скидок:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_AUTOSALE_ON"] = "Включено для всех";
$MESS["ARTURGOLUBEV_OZON_CATALOG_AUTOSALE_OFF"] = "Выключено для всех";
$MESS["ARTURGOLUBEV_OZON_CATALOG_AUTOSALE_PROP"] = "Из свойства \"Автоприменение скидок\"";
$MESS["ARTURGOLUBEV_OZON_PRICE_OPTIMAL"] = "Оптимальная";
$MESS["ARTURGOLUBEV_OZON_PRICE_W_SALES"] = " с учетом скидок битрикса";
$MESS["ARTURGOLUBEV_OZON_PRICE_WO_SALES"] = " без учета скидок битрикса";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICES_CONVERT"] = "<span data-hint='Конвертация применяемся ко всем типам выгружаемых цен, если: цена берется из типа цены и валюта цены отличается от конвертируемой. Конвертация происходит по курсу валют на сайте'></span>Конвертировать цены в валюту:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICES_CONVERT_NO_CONVERT"] = "Не конвертировать";
$MESS["ARTURGOLUBEV_OZON_CATALOG_STOCKS"] = "Источник остатков для склада #num#:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_STOCKS_STORE_SUM"] = "Склады суммирования остатков склада #num#:";
$MESS["ARTURGOLUBEV_OZON_STOCKS_CATALOG_QUANTITY"] = "Доступное количество";
$MESS["ARTURGOLUBEV_OZON_STOCKS_CATALOG_STORE_SUM"] = "Сумма остатков нескольких складов";
$MESS["ARTURGOLUBEV_OZON_STOCKS_CATALOG_STORE_AMOUNT"] = "Количество на складе \"#NAME#\"";
$MESS["ARTURGOLUBEV_OZON_CATALOG_OZON_ID"] = "<span data-hint='Поле в котором хранится Артикул Озон. Значение данного поля будет использоваться как основной идентификатор для всех операций'></span>Источник Артикула Ozon:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_OZON_ID_FORM_ID"] = "[ID] ID элемента";
$MESS["ARTURGOLUBEV_OZON_CATALOG_OZON_ID_FORM_XML_ID"] = "[XML_ID] XML_ID элемента";
$MESS["ARTURGOLUBEV_OZON_CATALOG_OUR_SETTINGS"] = "Общие настройки каталогов";
$MESS["ARTURGOLUBEV_OZON_CATALOG_STOCKS_DEVIATION"] = "<span data-hint='Опция позволяет скорректировать передаваемое количество остатков. Если у вас на складе 10 товаров, а в поле уменьшения указано 2, то на WB будет отправлен остаток равный 8'></span>Уменьшать отправляемые остатки на указанное количество:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION"] = "<span data-hint='Опция позволяет скорректировать передаваемую цену'></span>Коррекция передаваемой цены:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_VALUE"] = "Значение коррекции цены:";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_NO"] = "Не корректировать";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_UP_PERCENT"] = "Увеличить на процент";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_DOWN_PERCENT"] = "Уменьшить на процент";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_UP_CONSTANT"] = "Увеличить на число";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_DOWN_CONSTANT"] = "Уменьшить на число";
$MESS["ARTURGOLUBEV_OZON_CATALOG_PRICE_CORRECTION_FORMULA"] = "По формуле";

/* orders tab */
$MESS["ARTURGOLUBEV_OZON_ORDERS_TAB"] = "Заказы";
$MESS["ARTURGOLUBEV_OZON_ORDERS_TITLE_TAB"] = "Настройки работы со заказами";
$MESS["ARTURGOLUBEV_OZON_ORDERS_CREATE_VARIANTS"] = "Параметры загрузки заказов";
$MESS["ARTURGOLUBEV_OZON_ORDERS_CREATE_SETTINGS"] = "Параметры создания заказов полученных от Ozon";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FIRST_LOAD"] = "<span data-hint='Используется только для первой загрузки'></span>Загружать заказы начиная с даты:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NEXT_LOAD"] = "<span data-hint='Позволяет сменить дату следующей загрузки после остановки'></span>Продолжить загрузку заказов начиная с даты:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOLOAD_CANCELED"] = "<span data-hint='Заказы имеющие статус cancelled на момент загрузки будут пропущены'></span>Не загружать отменённые заказы:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS"] = "<span data-hint='Поиск товара происходит по chrtId и nmId приходящим в сборочном задании'></span>Заказы с неопознанными товарами:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS_ERROR"] = "Не загружать, при следующей загрузке пытаться опознать снова";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS_SKIP"] = "Пропускать";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOFIND_PRODUCTS_NONAME"] = "Добавить в заказ безымянный товар (без количественного учета)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOSTOCK_PRODUCTS"] = "<span data-hint='Иногда бывают ситуации, когда сборочное задание загружается, а товар в битриксе имеет нулевой остаток. '></span>Заказы с товарами без доступного количества:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOSTOCK_PRODUCTS_ERROR"] = "Не загружать, при следующей загрузке пытаться снова";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NOSTOCK_PRODUCTS_NONAME"] = "Добавить в заказ безымянный товар (без количественного учета)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_USER_ID"] = "<span data-hint='Необязательное. Если не заполнено, заказы будут оформлены на анонимного пользователя'></span>ID Пользователя от которого будут созданы заказы:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_SITE_ID"] = "Сайт для новых заказов:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_PERSON_TYPE"] = "Тип лица для новых заказов:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_DELIVERY"] = "Служба доставки для новых заказов:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_DELIVERY_M"] = "Служба доставки для новых заказов (склад #store_id#):";
$MESS["ARTURGOLUBEV_OZON_ORDERS_PAYSYSTEM"] = "Система оплаты для новых заказов:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_PROP_OZON_ID"] = "<span data-hint='Необязательное. Информация сохраняется в служебные поля и дублируется в выбранное'></span>Дублировать Номер отправления в поле:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_PROP_SEND_DATA"] = "<span data-hint='Необязательное. Информация сохраняется в служебные поля и дублируется в выбранное'></span>Дублировать Дату отгрузки в поле:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_NEW_GET_TIME"] = "Установлена дата \"от\" для загрузки заказов: #date# (#time#)";

$MESS["ARTURGOLUBEV_OZON_ORDERS_UPDATE_SETTINGS"] = "Параметры обновления заказов";
// $MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_USERSTATUS"] = "<span data-hint='При достижении указанного статуса заказы перестанут запрашивать обновление у WB. Обязательно заполните корректно!'></span>Финальные пользовательские статусы заказов:";
// $MESS["ARTURGOLUBEV_ORDERS_USER_STATUS_1"] = "Отмена клиента [1]";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME"] = "Обновлять заказ в течении:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_1"] = "1 месяца после создания";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_2"] = "2 месяцев после создания";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_3"] = "3 месяцев после создания";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_4"] = "4 месяцев после создания";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_5"] = "5 месяцев после создания";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FINAL_TIME_6"] = "6 месяцев после создания";

$MESS["ARTURGOLUBEV_OZON_ORDERS_CRM_SETTINGS"] = "Дополнительные настройки для CRM";
$MESS["ARTURGOLUBEV_OZON_ORDERS_CRM_RESPONSIBLE"] = "<span data-hint='Опция только для редакций ИМ + CRM. Указанный пользователь будет назначаться ответственным для заказов'></span>ID пользователя назначаемого Ответственным:";

$MESS["ARTURGOLUBEV_OZON_ORDERS_RFBS_SETTINGS"] = "Дополнительные настройки realFBS";
$MESS["ARTURGOLUBEV_OZON_ORDERS_PROP_RFBS_FIO"] = "Сохранять ФИО в поле:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_PROP_RFBS_PHONE"] = "Сохранять Телефон в поле:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_PROP_RFBS_ADDRES"] = "Сохранять Адрес доставки в поле:";

/* workers tab */
$MESS["ARTURGOLUBEV_OZON_EXCHANGES_TAB"] = "Автоматические выгрузки";
$MESS["ARTURGOLUBEV_OZON_API_PRICES"] = "Работа с ценами";
$MESS["ARTURGOLUBEV_OZON_API_STOCKS"] = "Работа с остатками";
$MESS["ARTURGOLUBEV_OZON_API_ORDERS"] = "Работа с заказами";
$MESS["ARTURGOLUBEV_OZON_WORKER_STOCKS"] = "<span data-hint='Система выберет все подходящие под отбор товары и отправит актуальные остатки на Ozon'></span>Периодическая полная выгрузка остатков:";
$MESS["ARTURGOLUBEV_OZON_WORKER_STOCKS_CHANGES"] = "<span data-hint='Система выберет все подходящие под отбор и имеющие изменение остатков товары и отправит актуальные остатки на Ozon'></span>Периодическая выгрузка изменений остатков:";
$MESS["ARTURGOLUBEV_OZON_WORKER_STOCKS_LOCK"] = "Недоступно, пока включено обнуление остатков";
$MESS["ARTURGOLUBEV_OZON_WORKER_STOCKS_EMPTY"] = "<span data-hint='Единоразовая выгрузка. Система выберет все подходящие под отбор товары и отправит нулевые остатки на Ozon'></span>Полная выгрузка с обнулением остатков:";
$MESS["ARTURGOLUBEV_OZON_WORKER_STOCKS_EMPTY_LOCK"] = "Недоступно, пока включено обновление остатков";
$MESS["ARTURGOLUBEV_OZON_WORKER_PRICES"] = "<span data-hint='Система выберет все подходящие под отбор товары и отправит актуальные цены на Ozon'></span>Периодическая полная выгрузка цен:";
$MESS["ARTURGOLUBEV_OZON_WORKER_PRICES_CHANGES"] = "<span data-hint='Система выберет все подходящие под отбор и имеющие изменение цен товары и отправит актуальные цены на Ozon'></span>Периодическая выгрузка изменений цен:";
$MESS["ARTURGOLUBEV_OZON_WORKER_FBS_ORDERS"] = "<span data-hint='Система будет переодически запрашивать новые Заказы у Ozon и сохранять их на сайт'></span>Загружать заказы:";
$MESS["ARTURGOLUBEV_OZON_WORKER_FBS_ORDERS_UPDATE"] = "<span data-hint='Система будет переодически запрашивать статусы заказов у Ozon и обновлять на сайте пользовательский статус'></span>Обновлять статусы загруженных заказов:";
$MESS["ARTURGOLUBEV_OZON_ORDERS_UPDATE_RETURNS"] = "<span data-hint='При обновлении заказа решение будет получать более детальный статус возврата'></span>Получать расширенные статусы возвратов:";

/* automatization tab */
$MESS["ARTURGOLUBEV_OZON_AUTO_STATUS_TAB"] = "Автоматизация";
$MESS["ARTURGOLUBEV_OZON_AUTO_STATUS_TAB_TITLE"] = "Автоматизация работы с заказами";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_MAP"] = "Соотвествие статусов Озона и статусов Битрикса";
$MESS["ARTURGOLUBEV_OZON_ORDERS_FLAG_MAP"] = "Соотвествие статусов Озона и флагов Битрикса";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_AWAITING_PACKAGING"] = "<span data-hint='Стартовый статус, новый заказ приходит в этом статусе'></span>Ожидает упаковки (awaiting_packaging)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_AWAITING_DELIVER"] = "<span data-hint='Устанавливает продавец, отправляем на озон'></span>Ожидает отгрузки (awaiting_deliver) [Сайт>Озон]";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_DELIVERING"] = "<span data-hint='Устанавливает озон'></span>Доставляется (delivering)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_DELIVERED"] = "<span data-hint='Устанавливает озон'></span>Доставлено (delivered)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_CANCELLED"] = "<span data-hint='Устанавливает продавец'></span>Отменено (cancelled) [Сайт>Озон]";

$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_RETURNED_TO_SELLER"] = "<span data-hint='Статус возврата. Устанавливает озон'></span>Возврат: Возвращён продавцу (returned_to_seller)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_WAITING_FOR_SELLER"] = "<span data-hint='Статус возврата. Устанавливает озон'></span>Возврат: В ожидании продавца (waiting_for_seller)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_ACCEPTED_FROM_CUSTOMER"] = "<span data-hint='Статус возврата. Устанавливает озон'></span>Возврат: Принят от покупателя (accepted_from_customer)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_CANCELLED_WITH_COMPENSATION"] = "<span data-hint='Статус возврата. Устанавливает озон'></span>Возврат: Отменено с компенсацией (cancelled_with_compensation)";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOFBS_READY_FOR_SHIPMENT"] = "<span data-hint='Статус возврата. Устанавливает озон'></span>Возврат: Готов к отправке (ready_for_shipment)";

$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_NULL"] = "Не автоматизировать";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_CANCELED"] = "Заказ отменён";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_DEDUCTED"] = "Заказ отгружен";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_AUTOMATION_PAYED"] = "Заказ оплачен";

/* rights tab */
$MESS["ARTURGOLUBEV_OZON_RIGHTS_TAB"] = "Права доступа";
$MESS["ARTURGOLUBEV_OZON_RIGHTS_SETTINGS"] = "Настройка модуля:";
$MESS["ARTURGOLUBEV_OZON_RIGHTS_SETTINGS_TEXT"] = "Администраторам доступен весь функционал решения включая настройку";
$MESS["ARTURGOLUBEV_OZON_RIGHTS_CARD"] = "Работа с карточками товаров:";
$MESS["ARTURGOLUBEV_OZON_RIGHTS_ORDER"] = "Работа с заказами, актами и ттн:";


/* agent page */
$MESS["ARTURGOLUBEV_OZON_AGENT_TAB"] = "Состояние агентов";
$MESS["ARTURGOLUBEV_OZON_AGENTS_NOTIFICATION"] = "
Агенты - независимые друг от друга процессы, выполняющие рассчёты, выгрузки и прочие действия по расписанию.<br/>В данном разделе вы можете увидеть список всех автоматических процессов решения, посмотреть их статус, время последнего и следующего выполнения. Посмотреть результаты выполнения тех или иных процессов можно в разделе <a href=\"/bitrix/admin/arturgolubev_ozon_logs.php?lang=".LANG."&sid=".$_GET["sid"]."\">Логи работы решения</a>
<br/><br/><br/>";
$MESS["ARTURGOLUBEV_OZON_AGENT_LIST_TITLE"] = "Список работающих агентов:";
$MESS["ARTURGOLUBEV_OZON_AGENT_TABLE_NAME"] = "Агент";
$MESS["ARTURGOLUBEV_OZON_AGENT_TABLE_STATUS"] = "Статус";
$MESS["ARTURGOLUBEV_OZON_AGENT_TABLE_LAST_WORK"] = "Последнее выполнение";
$MESS["ARTURGOLUBEV_OZON_AGENT_TABLE_NEXT_WORK"] = "Следующее выполнение";
$MESS["ARTURGOLUBEV_OZON_AGENT_TABLE_EDIT"] = "Редактировать";
$MESS["ARTURGOLUBEV_OZON_AGENT_STOCK_FULL_NAME"] = "Агент периодической полной выгрузки остатков";
$MESS["ARTURGOLUBEV_OZON_AGENT_STOCK_CHANGES_NAME"] = "Агент периодической выгрузки изменений остатков";
$MESS["ARTURGOLUBEV_OZON_AGENT_STOCK_EMPTY_NAME"] = "Агент обнуления остатков";
$MESS["ARTURGOLUBEV_OZON_AGENT_PRICES_FULL_NAME"] = "Агент периодической полной выгрузки цен";
$MESS["ARTURGOLUBEV_OZON_AGENT_PRICES_CHANGES_NAME"] = "Агент периодической выгрузки изменений цен";
$MESS["ARTURGOLUBEV_OZON_AGENT_SALES_CHANGES_NAME"] = "Агент периодической выгрузки изменений скидок";
$MESS["ARTURGOLUBEV_OZON_AGENT_STOCK_UPDATE_NAME"] = "Агент выгрузки изменений остатков и цен";
$MESS["ARTURGOLUBEV_OZON_AGENT_FBS_ORDERS_NAME"] = "Агент загрузки заказов";
$MESS["ARTURGOLUBEV_OZON_AGENT_FBS_ORDERS_UPDATE_NAME"] = "Агент обновления заказов";
$MESS["ARTURGOLUBEV_OZON_AGENT_STATUS_NOT_FOUND"] = "Не активен";
$MESS["ARTURGOLUBEV_OZON_AGENT_STATUS_WORKED"] = "Работает";
$MESS["ARTURGOLUBEV_OZON_AGENT_STATUS_NO_ACTIVE"] = "Отключен!";
$MESS["ARTURGOLUBEV_OZON_AGENT_STATUS_WAIT"] = "Ожидает запуска";
$MESS["ARTURGOLUBEV_OZON_AGENT_EDIT_NOTE"] = "<b>В агентах можно редактировать поля \"Интервал\" и \"Дата и время следующего запуска\". Редактирование других полей приведёт к некорректной работе решения</b>";

/* log page */
$MESS["ARTURGOLUBEV_OZON_LOG_TAB"] = "Логи работы решения";
$MESS["ARTURGOLUBEV_OZON_LOG_HREF_TITLE_STOCKS"] = "Смотреть все события";
$MESS["ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_CHANGES"] = "Отслеживание параметров товаров:";
$MESS["ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_STOCKS"] = "Выгрузка остатков:";
$MESS["ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_PRICES"] = "Выгрузка цен:";
$MESS["ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_ORDERS"] = "Загрузка заказов:";
$MESS["ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_ACTUALIZE"] = "Работа с заказами:";
$MESS["ARTURGOLUBEV_OZON_LOG_TABLE_TITLE_CARD"] = "Создание карточек товаров:";
$MESS["ARTURGOLUBEV_OZON_LOG_NOTIFICATION"] = '
Логи работы решения - основной мониор действий производимых решением.<br/>С его помощью можно найти проблемы, посмотреть какие выгрузки, как и когда были выполнены. Итерации когда выгружать нечего (например нет изменений остатков или нет новых заказов) в логе не фиксируются.<br/><br/>

 У решения есть справочник <a target="_blank" href="https://arturgolubev.ru/knowledge/course33/lesson190/">Типовые ошибки и методы их решения</a> - при появлении ошибок ознакомьтесь с описанием вашей ошибки в справочнике и примите рекомендуемые действия. 
<br/><br/><br/>';
$MESS["ARTURGOLUBEV_OZON_LOG_EXT_TITLE"] = "Расширенное логирование запросов в файл:";
$MESS["ARTURGOLUBEV_OZON_LOG_EXT_NOSETTING_INFO"] = "отключено в настройках";
$MESS["ARTURGOLUBEV_OZON_LOG_EXT_NODEFINE_INFO"] = "отключено т.к. константа LOG_FILENAME не назначена";
$MESS["ARTURGOLUBEV_OZON_LOG_EXT_WORK_INFO"] = "включено, логирование происходит в файл #filename#";

/* acts page */
$MESS["ARTURGOLUBEV_OZON_ACTS_PAGE_TITLE"] = "Акты и Товарно-транспортные накладные";
$MESS["ARTURGOLUBEV_OZON_ACTS_NOTIFICATION"] = "<div class=\"agoz_spoiler_head agoz_js_spoiler\">Краткое описание работы с функционалом <i class=\"fa fa-angle-down\" aria-hidden=\"true\"></i></div>
<div class=\"agoz_spoiler_body\">
	<p>С помощью данного раздела вы можете запросить Акты и Товарно-транспортные накладные для текущих собранных и ожидающих отправки в Ozon заказов.</p>
	<ol>
		<li>Акты и Накладные запрашиваются на конкретный склад. Если у вас несколько складов появится выбор склада для которого запрашиваются документы</li>
		<li>Акты и Накладные запрашиваются одним запросов. Разбить их на несколько отдельных файлов не получится (Ozon отдаёт всё вместе одним файлом)</li>
		<li>Акты и Накладные запрашиваются сразу для всех заказов в статусе Ожидает отгрузки</li>
		<li>При повторном запросе документа Ozon отдаёт тот же файл, а не формирует его заново</li>
	</ol>
</div>
<br/>";

$MESS["ARTURGOLUBEV_OZON_ACTS_INFORMATION"] = "<b>Справочная информация:</b><br/><br/>
Дата последней успешной загрузки заказов с Ozon: #agent_date#<br/><br/>
Заказов в статусе \"Ожидает сборки\": #oc1#<br/>
";
$MESS["ARTURGOLUBEV_OZON_ACTS_SHIPMENT_LINE_NAME"] = "Сбор до #date# - количество заказов с этой датой #count#";
$MESS["ARTURGOLUBEV_OZON_ACTS_BUTTON_ACT_CREATE"] = "Запросить акт и накладную";
$MESS["ARTURGOLUBEV_OZON_ACTS_SELECT_STORE"] = "Выбор склада: ";
?>
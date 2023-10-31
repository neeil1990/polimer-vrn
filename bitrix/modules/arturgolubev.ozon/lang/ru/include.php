<?
$MESS["ARTURGOLUBEV_OZON_ORDER_BUTTON_MAIN"] = "Ozon";
$MESS["ARTURGOLUBEV_OZON_MAIN_NAME"] = "Интеграция с Ozon: ";
$MESS["ARTURGOLUBEV_OZON_MAIN_SUCCESS"] = "Успешно";
$MESS["ARTURGOLUBEV_OZON_MAIN_ERROR_FROM_OZON"] = "Ошибочный результат при запросе к Ozon - ";
$MESS["ARTURGOLUBEV_OZON_MAIN_ERROR_IN_DEV"] = "Функционал в разработке. Обратитесь в техническую поддержку решения";

/* main warnings */
$MESS["ARTURGOLUBEV_OZON_LOG_SETTINGS_ERROR_NO_IB_FILTER"] = "Ошибка. Настройки отбора товара некорректны - проверьте параметры отбора <a href=\"/bitrix/admin/settings.php?lang=".LANG."&mid=arturgolubev.ozon&sid=#sid#\">Перейти в настройки</a>";
$MESS["ARTURGOLUBEV_OZON_LOG_SETTINGS_ERROR_NO_OZONID_PROP"] = "Ошибка. Не указан Источник OZON ID <a href=\"/bitrix/admin/settings.php?lang=".LANG."&mid=arturgolubev.ozon&sid=#sid#\">Перейти в настройки</a>";
$MESS["ARTURGOLUBEV_OZON_LOG_SETTINGS_ERROR_NO_STOCKS_PROP"] = "Ошибка. Не указан \"Источник остатков\" для выгрузки <a href=\"/bitrix/admin/settings.php?lang=".LANG."&mid=arturgolubev.ozon&sid=#sid#\">Перейти в настройки</a>";
$MESS["ARTURGOLUBEV_OZON_LOG_SETTINGS_ERROR_NO_PRICE_PROP"] = "Ошибка. Не указан \"Источник базовой цены\" для выгрузки <a href=\"/bitrix/admin/settings.php?lang=".LANG."&mid=arturgolubev.ozon&sid=#sid#\">Перейти в настройки</a>";

/* universal errors */
$MESS["ARTURGOLUBEV_OZON_LOG_UNIVERSAL_ERROR"] = "Запрос к Ozon > <b>Ошибка (#STATUS#)</b> Ответ: #RESULT#";
$MESS["ARTURGOLUBEV_OZON_LOG_UNIVERSAL_SENDED"] = "Выгружено: #SUCC#";
$MESS["ARTURGOLUBEV_OZON_LOG_UNIVERSAL_SENDED_PLUS"] = "Выгружено: #SUCC#; Отклонено: #ERR#";
$MESS["ARTURGOLUBEV_OZON_CHANGE_QUEUE_ERROR"] = "Ошибка добавления товара <b>#ID#</b> в список синхронизации изменений";

/* stocks export */
$MESS["ARTURGOLUBEV_OZON_GET_WAREHOUSE_ERROR"] = "Ошибка при получении складов: ";
$MESS["ARTURGOLUBEV_OZON_LOG_STOCK_LOADING_START_FULL"] = "Полная выгрузка остатков: ";
$MESS["ARTURGOLUBEV_OZON_LOG_STOCK_LOADING_START_EDIT"] = "Выгрузка изменений остатков: ";
$MESS["ARTURGOLUBEV_OZON_LOG_STOCK_LOADING_START_EMPT"] = "Обнуление остатков: ";
$MESS["ARTURGOLUBEV_OZON_STOCK_SEND_ERROR"] = "Ошибка обновления остатков #offer_id# - ";

/* price export */
$MESS["ARTURGOLUBEV_OZON_LOG_PRICES_SEND_FULL"] = "Полная выгрузка цен: ";
$MESS["ARTURGOLUBEV_OZON_LOG_PRICES_SEND_CHANGES"] = "Выгрузка изменений цен: ";
$MESS["ARTURGOLUBEV_OZON_PRICE_SEND_ERROR"] = "Ошибка обновления цен #offer_id# - ";

$MESS["ARTURGOLUBEV_OZON_GET_PRICES_ERROR"] = "Ошибка получения данных о цене: ";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_COL_NAME"] = "Поле";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_COL_VALUE"] = "Значение";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_offer_id"] = "Артикул";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_price_index"] = "Ценовой индекс";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_volume_weight"] = "Объёмный вес товара";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_prices"] = "Цены";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_commissions"] = "Комиссии";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_auto_action_enabled"] = "Автоприменение акций";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_marketing_price"] = "Цена на товар с учетом всех акций";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_marketing_seller_price"] = "Цена на товар с учетом акций продавца";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_min_ozon_price"] = "Минимальная цена на аналогичный товар на Ozon";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_min_price"] = "Минимальная цена товара после применения всех скидок";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_old_price"] = "Цена до учёта скидок";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_premium_price"] = "Цена для клиентов с подпиской";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_price"] = "Цена товара с учётом скидок";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_recommended_price"] = "Цена на товар, рекомендованная системой";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_retail_price"] = "Цена поставщика";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_vat"] = "Ставка НДС для товара";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_currency_code"] = "Код валюты";

$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbo_deliv_to_customer_amount"] = "Последняя миля (FBO)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbo_direct_flow_trans_max_amount"] = "Магистраль до (FBO)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbo_direct_flow_trans_min_amount"] = "Магистраль от (FBO)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbo_fulfillment_amount"] = "Комиссия за сборку заказа (FBO)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbo_return_flow_amount"] = "Комиссия за возврат и отмену (FBO)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbo_return_flow_trans_min_amount"] = "Комиссия за обратную логистику от (FBO)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbo_return_flow_trans_max_amount"] = "Комиссия за обратную логистику до (FBO)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_deliv_to_customer_amount"] = "Последняя миля (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_direct_flow_trans_max_amount"] = "Магистраль до (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_direct_flow_trans_min_amount"] = "Магистраль от (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_first_mile_min_amount"] = "Комиссия за обработку отправления от (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_first_mile_max_amount"] = "Комиссия за обработку отправления до (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_return_flow_amount"] = "Комиссия за возврат и отмену, обработка отправления (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_return_flow_trans_max_amount"] = "Комиссия за возврат и отмену, магистраль до (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_fbs_return_flow_trans_min_amount"] = "Комиссия за возврат и отмену, магистраль от (FBS)";
$MESS["ARTURGOLUBEV_OZON_GET_PRICES_TABLE_sales_percent"] = "Процент комиссии за продажу (FBO и FBS)";

/* product check params */
$MESS["ARTURGOLUBEV_OZON_ERROR_PRODUCT_NO_OZONID"] = "#NAME# [bxid: #ID#] - не заполнен Артикул Ozon";
$MESS["ARTURGOLUBEV_OZON_ERROR_PRODUCT_NO_PRICE"] = "#NAME# [bxid: #ID#] - не определена Базовая цена товара";

/* orders */
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_OPTIONS_ERROR"] = "Загрузка заказов прервана, настройка решения не завершена. Проверьте настройки создания заказов: \"Сайт \", \"Тип лица\", \"Служба доставки\", \"Система оплаты\" - они должны быть заполнены";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_START"] = "Загрузка заказов: ";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_NOSTOCK_BASKET_INFO"] = " (Без количественного учета остатков)";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_ERROR_PRODUCT_CANCEL"] = "Заказ #posting_number# не загружен, т.к. товар с артикулом #offer_id# не найден";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_ERROR_PRODUCT_SKIP"] = "Заказ #posting_number# пропущен, т.к. товар с артикулом #offer_id# не найден";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_BASKET_ADD_ERROR"] = "Ошибка добавления товара #offer_id# в корзину при загрузке заказа #posting_number#. Ошибка #errors#";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_SAVE_SUCCESS"] = "Заказ #posting_number# загружен [bxid: #bitrixId#]";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_SAVE_SAVE_ERROR"] = "Заказ #posting_number# не загружен. Ошибка #errors#";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_SAVE_SUCCESS_NOSTOCK"] = "Заказ #posting_number# загружен. Некоторые товары были загружены без количественного учета! [bxid: #bitrixId#]";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_END_ERROR_NOTIFY"] = "Загрузка заказов завершена с ошибками <a href=\"/bitrix/admin/arturgolubev_ozon_logs.php?lang=ru&sid=#sid#\">Перейти в лог работы</a>";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_END_ERROR"] = "Создание заказов завершено с ошибками";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDERS_LOAD_SAVE_CANCELED"] = "Заказ #orderId# не будет загружен, т.к. отменён пользователем (status: #status#)";

/* order tab */
$MESS["ARTURGOLUBEV_OZON_ORDERINFO_SHIP_DATA_TITLE"] = "Деление на отправления при отправке статуса \"Ожидает отгрузки\":";
$MESS["ARTURGOLUBEV_OZON_ORDERINFO_SHIP_BOX"] = "Отправление #";
$MESS["ARTURGOLUBEV_OZON_ORDERINFO_DATA_TITLE"] = "Все данные о заказе (beta):";
$MESS["ARTURGOLUBEV_OZON_ORDERINFO_SHIP_PROD_NAME"] = "Название товара";
$MESS["ARTURGOLUBEV_OZON_ORDERINFO_SHIP_PROD_QUANTITY"] = "Количество";

/* orders stickers */
$MESS["ARTURGOLUBEV_OZON_LOG_ORDER_LOADSTICKER_ERROR"] = "Ошибка получения этикетки заказа: ";
$MESS["ARTURGOLUBEV_OZON_LOG_ORDER_LOADINFO_ERROR"] = "Ошибка получения информации о заказе: ";
$MESS["ARTURGOLUBEV_OZON_GET_STICKER"] = "Печать этикетки";
$MESS["ARTURGOLUBEV_OZON_ORDER_PRINT_STICKER_MASS"] = "Ozon: Распечатать этикетки";
$MESS["ARTURGOLUBEV_OZON_ORDER_STICKER_ERROR_NO_OZON_ORDERS"] = "В выборке найдены заказы не принадлежащие Озону";
$MESS["ARTURGOLUBEV_OZON_ORDER_STICKER_ERROR_MULTIPLE_SITES"] = "Выбранные заказы принадлежат разным сайтам";
$MESS["ARTURGOLUBEV_OZON_SET_ORDER_STATUS_AWAITING_DELIVER"] = "Установить статус: Ожидает отгрузки";
$MESS["ARTURGOLUBEV_OZON_MASS_STATUS_AWAITING_DELIVER"] = "Ozon: Установить статус \"Ожидает отгрузки\"";
$MESS["ARTURGOLUBEV_OZON_SET_ORDER_STATUS_CANCELLED"] = "Установить статус: Отменено";
$MESS["ARTURGOLUBEV_OZON_MASS_STATUS_CANCELLED"] = "Ozon: Установить статус \"Отменено\"";

/* order status */
$MESS["ARTURGOLUBEV_OZON_ORDER_SETSTATUS_NO_OZON_ORDER"] = "Заказ не принадлежит Озону";
$MESS["ARTURGOLUBEV_OZON_ORDER_SETSTATUS_CHANGED_SUCCESS"] = "Сайт>Ozon: Заказ на Ozon #orderId# успешно сменил статус на #statusId# [bxid: #bitrixId#] ";
$MESS["ARTURGOLUBEV_OZON_ORDER_SETSTATUS_RESULT_WINDOW_INFO"] = "Смена статуса заказа#orderid#: ";

/* order returns */
$MESS["ARTURGOLUBEV_OZON_ORDER_GET_RETURNS_ERROR"] = "Запрос информации о возврате прошел с ошибками: ";

/* order update */
$MESS["ARTURGOLUBEV_OZON_ORDERS_UPDATE_ERROR"] = "Во время сохранения изменений заказа произошла ошибка! [bxid: #bitrixId#]";
$MESS["ARTURGOLUBEV_OZON_ORDERS_STATUS_UPDATE_SUCCESS"] = "Ozon>Сайт: Заказ ozon изменил статус с #status_old# на #status_new# [ozonid: #orderId#, bxid: #bitrixId#]";
$MESS["ARTURGOLUBEV_OZON_ORDERS_TRIGGER_OZUPDATE_SUCCESS"] = "Сайт>Ozon: Заказ ozon изменил статус на #status_new# по триггеру автоматизации [ozonid: #orderId#, bxid: #bitrixId#]";

$MESS["ARTURGOLUBEV_OZON_ORDER_TRIGGER_STATUS_SET"] = "Установка статуса #status_new#. ";
$MESS["ARTURGOLUBEV_OZON_ORDER_TRIGGER_STATUS_ERROR"] = "Ошибки обновления заказа [bxid: #bitrixId#] по триггеру автоматизации: ";
$MESS["ARTURGOLUBEV_OZON_ORDER_TRIGGER_STATUS_SUCCESS"] = "Успешное обновление заказа [bxid: #bitrixId#] по триггеру статуса: ";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_STATUS_ERROR"] = "Заказ не смог сменить статус на #status_new#";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_STATUS_SUCCESS"] = "Заказ сменил статус на #status_new#";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_CANCEL_ERROR"] = "Ошибка установки Отмены заказа";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_CANCEL_SUCCESS"] = "Успешная установка Отмены заказа";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_DEDUCTED_ERROR"] = "Ошибка установки Отгрузки заказа";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_DEDUCTED_SUCCESS"] = "Успешная установка Отгрузки заказа";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_PAYED_ERROR"] = "Ошибка установки Оплаты заказа";
$MESS["ARTURGOLUBEV_OZON_ORDER_UPDATE_PAYED_SUCCESS"] = "Успешная установка Оплаты заказа";

/* acts */
$MESS["ARTURGOLUBEV_OZON_ACT_HAS_CREATED"] = "Акт создан, можно печатать <a href=\"/bitrix/admin/arturgolubev_ozon_acts.php?lang=".LANG."&sid=#sid#\">Перейти на страницу актов</a>";
$MESS["ARTURGOLUBEV_OZON_ACT_CREATE_ERROR"] = "Запрос актов прошел с ошибками: ";

/* event system */
$MESS["ARTURGOLUBEV_OZON_EVENT_ACTIVE_LIST_TITLE"] = "Активные события кастомизации расчётов:";
$MESS["ARTURGOLUBEV_OZON_EVENT_CALCULATE_STOCKS_NAME"] = "Событие рассчета остатков";
$MESS["ARTURGOLUBEV_OZON_EVENT_CALCULATE_PRICES_NAME"] = "Событие рассчета цен";
$MESS["ARTURGOLUBEV_OZON_EVENT_CALCULATE_GET_PRICES_NAME"] = "Событие получения цен";

?>
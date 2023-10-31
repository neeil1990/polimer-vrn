<?php
$MESS["TWINPX_MODULE_NAME"] = "Модуль Яндекс Доставка";
$MESS["TWINPX_MODULE_DESCRIPTION"] = "Модуль для интеграции с сервисом Яндекс Доставка для Bitrix CMS";
$MESS["TWINPX_PARTNER_NAME"] = "Twin px";
$MESS["TWINPX_PARTNER_URI"] = "https://twinpx.ru";

$MESS["TWINPX_INSTALL_TITLE"] = "Удаление модуля Яндекс Доставка";

$MESS["TWINPX_EVENT_TYPE_NAME"] = "Уведомление модуля доставки Яндекс";
$MESS["TWINPX_EVENT_TYPE_DESCRIPTION"] = "#ID# - ID оффера\n#OFFER_ID# - Номер заказа\n#ORDER_DATE# - Дата создание заказа\n#STATUS_DESCRIPTION# - Текст ошибки\n#SALE_EMAIL# - E-Mail отдела продаж";

$MESS["TWINPX_EVENT_TEMPLATE_SUBJECT"] = "Ошибка при оформление Яндекс доставки";
$MESS["TWINPX_EVENT_TEMPLATE_MESSAGE"] = "<div>Ошибка при оформление доставки #OFFER_ID#.</div><div>Ошибка: #STATUS_DESCRIPTION#.</div><div>Вам необходимо зайти в административную часть модуля Яндекс.Доставки. Выбрать меню проблемной заявки и нажать \"Оформить доставку\". Учитывайте, что стоимость оформления доставки может отличаться от той, которую уже оплатил клиент.</div>";

$MESS["TWINPX_EVENT2_TYPE_NAME"] = "Создан заказ с пост-оплаты";
$MESS["TWINPX_EVENT2_TYPE_DESCRIPTION"] = "#ID# - ID заявки\n#ORDER_ID# - Номер заказа\n#OFFER_ID# - Номер оффера\n#ORDER_DATE# - Дата создание заказа\n#STATUS_DESCRIPTION# - Статус заказа\n#SALE_EMAIL# - E-Mail отдела продаж";

$MESS["TWINPX_EVENT2_TEMPLATE_SUBJECT"] = "Оформлена заявка Яндекс Доставка № #ID# с оплатой при получении";
$MESS["TWINPX_EVENT2_TEMPLATE_MESSAGE"] = "<p>Оформлена заявка Яндекс Доставка № #ID# для заказа № #ORDER_ID# с оплатой при получении. Заявка будет отменена автоматически (в соответствии с настройками модуля), если для Заказа № #ORDER_ID# вы не выставите флаг \"Доставка разрешена\".</p>";

$MESS["TWINPX_EVENT3_TYPE_NAME"] = "Отмена заказа";
$MESS["TWINPX_EVENT3_TYPE_DESCRIPTION"] = "#ID# - ID заявки\n#ORDER_ID# - Номер заказа\n#ORDER_DATE# - Дата создание заказа\n#STATUS_DESCRIPTION# - Описание статусе заказа\n#SALE_EMAIL# - E-Mail отдела продаж";

$MESS["TWINPX_EVENT3_TEMPLATE_SUBJECT"] = "Срок подтверждения бронирования заявки Яндекс Доставка № #ID# истёк";
$MESS["TWINPX_EVENT3_TEMPLATE_MESSAGE"] = "<p>Срок подтверждения бронирования заявки Яндекс Доставка № #ID# истёк, для заказ № #ORDER_ID# не был установлен флаг \"Доставка разрешена\". Заявка отменена автоматически.</p>";

$MESS["TWINPX_EVENT4_TYPE_NAME"] = "Создана заявка с оплатой на сайте";
$MESS["TWINPX_EVENT4_TYPE_DESCRIPTION"] = "#ID# - ID заявки\n#ORDER_ID# - Номер заказа\n#OFFER_ID# - Номер оффера\n#ORDER_DATE# - Дата создание заказа\n#STATUS_DESCRIPTION# - Статус заказа\n#SALE_EMAIL# - E-Mail отдела продаж";

$MESS["TWINPX_EVENT4_TEMPLATE_SUBJECT"] = "Оформлена заявка Яндекс Доставка № #ID# с оплатой на сайте";
$MESS["TWINPX_EVENT4_TEMPLATE_MESSAGE"] = "<p>Оформлена заявка Яндекс Доставка № #ID# для заказа № #ORDER_ID# с оплатой на сайте. Заявка будет отменена автоматически (в соответствии с настройками модуля), если для Заказа № #ORDER_ID# не будет установлен флаг \"Оплачен\".</p>";

$MESS["TWINPX_EVENT5_TYPE_NAME"] = "Отмена заказа";
$MESS["TWINPX_EVENT5_TYPE_DESCRIPTION"] = "#ID# - ID заявки\n#ORDER_ID# - Номер заказа\n#ORDER_DATE# - Дата создание заказа\n#STATUS_DESCRIPTION# - Описание статусе заказа\n#SALE_EMAIL# - E-Mail отдела продаж";

$MESS["TWINPX_EVENT5_TEMPLATE_SUBJECT"] = "Срок подтверждения бронирования заявки Яндекс Доставка № #ID# истёк";
$MESS["TWINPX_EVENT5_TEMPLATE_MESSAGE"] = "<p>Срок подтверждения бронирования заявки Яндекс Доставка № #ID# истёк, заказ № #ORDER_ID# не был оплачен. Заявка отменена автоматически.</p>";

$MESS["TWINPX_EVENT6_TYPE_NAME"] = "Бронирование упрощенной доставки";
$MESS["TWINPX_EVENT6_TYPE_DESCRIPTION"] = "#ORDER_ID# - Номер заказа\n#ORDER_DATE# - Дата заказа\n#DELIVERY_NAME# - Название доставки\n#TIME_INTERVAL# - Интервал доставки\n#EMAIL# - E-Mail заказчика\n#SALE_EMAIL# - E-Mail отдела продаж";

$MESS["TWINPX_EVENT6_TEMPLATE_SUBJECT"] = "Оформлена доставка для вашего заказа";
$MESS["TWINPX_EVENT6_TEMPLATE_MESSAGE"] = "<p>Оформлена доставка для вашего заказа #ORDER_ID#.</p><p>Название доставки: #DELIVERY_NAME#<br>Дата и время доставки: #TIME_INTERVAL#</p>";

$MESS["TWPX_PERM_D"] = "Доступ закрыт";
$MESS["TWPX_PERM_N"] = "Создать";
$MESS["TWPX_PERM_R"] = "Просмотр данные";
$MESS["TWPX_PERM_W"] = "Полный доступ";

$MESS["ORDER_PROPS_PVZ"] = "Адрес ПВЗ (Яндекс Доставка)";

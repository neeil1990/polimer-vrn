<?php

$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_ID'] = 'Служба доставки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_NAME'] = 'Название';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_TYPE'] = 'Способ доставки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_TYPE_HELP'] = '
<p><strong>Самовывоз</strong>&nbsp;&mdash; покупатель сможет забрать заказ из&nbsp;Точки продаж, которая добавлена в&nbsp;личном кабинете маркетплейса;</p>
<p><strong>Почта</strong>&nbsp;&mdash; доставка в&nbsp;отделение почтовой службы;</p>
<p><strong>Курьерская доставка</strong>&nbsp;&mdash; заказ доставит курьер магазина или службы доставки.</p>
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_FIXED_PERIOD'] = 'Фиксированный срок доставки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_FIXED_PERIOD_HELP'] = '
<p>По умолчанию срок доставки рассчитывается на&nbsp;основе настроек выбранной <a href="#DELIVERY_ADMIN_URL#" target="_blank">службы доставки</a></p>
<p>Если срок доставки заказов Маркета отличается, отметьте &laquo;Фиксированный срок доставки&raquo;, и заполните требуемое количество дней ниже.</p>
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_PERIOD'] = 'Срок доставки для&nbsp;Маркета';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_DAYS'] = 'Срок доставки по&nbsp;умолчанию';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_DAYS_HELP'] = 'Срок доставки является обязательным. Заполните значения по&nbsp;умолчанию, если служба доставки не&nbsp;поддерживает или может не&nbsp;передавать интервал доставки.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_DAYS_UNIT_1'] = 'день';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_DAYS_UNIT_2'] = 'дня';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_DAYS_UNIT_5'] = 'дней';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SUMMARY'] = '#TYPE# &laquo;#ID#&raquo;, за #DAYS# (#HOLIDAY.CALENDAR#)';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_OUTLET_TYPE'] = 'Набор пунктов выдачи';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_OUTLET_TYPE_MANUAL'] = 'Собственный';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_OUTLET_TYPE_HELP'] = '
<p>Загрузите <a href="https://yandex.ru/support/marketplace/orders/dbs/settings/stores.html#concept_wlx_vrr_vsb" target="_blank">файл</a> или добавьте пункты выдачи в <a href="https://yandex.ru/support/marketplace/orders/dbs/settings/stores.html#create-point" target="_blank">личном кабинете</a>.</p>
<p>Если используете модуль службы доставки, выберите вариант с названием модуля, иначе используйте вариант с фразой &laquo;файл&raquo;.</p>
<p>Для пунктов магазина выберите вариант &laquo;Собственный&raquo; и заполните требуемые точки.</p> 
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_OUTLET'] = 'Пункты выдачи';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_INVERTIBLE'] = 'Ускоренная настройка';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_INVERTIBLE_HELP'] = '
<p>Отметьте данную опцию и включите <a href="https://yandex.ru/support/marketplace/orders/dbs/settings/cdek.html" target="_blank">доставку СДЭК</a> в разделе Настройки &rarr;&nbsp;Доставка магазина, чтобы Маркет начал рассчитывать срок доставки СДЭК без загрузки пунктов выдачи.</p>
<p>Выбранная служба доставки будет использована для приема заказов в 1С-Битрикс.</p>
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_BEHAVIOR'] = 'Плановая дата отгрузки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_BEHAVIOR_HELP'] = 'Укажите, за сколько дней до получения заказа покупателем вы отдаёте его в службу доставки.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_BEHAVIOR_OPTION_DELIVERY_DAY'] = 'В день доставки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_BEHAVIOR_OPTION_ORDER_DAY'] = 'В день сборки заказа';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_BEHAVIOR_OPTION_DELIVERY_OFFSET'] = 'Несколько дней до доставки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_BEHAVIOR_OPTION_ORDER_OFFSET'] = 'Несколько дней после сборки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_OFFSET'] = 'Количество дней для&nbsp;отгрузки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SHIPMENT_DATE_OFFSET_HELP'] = 'Необходимое количество рабочих дней для отгрузки заказа. В&nbsp;качестве режима работы используется График отгрузки.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SCHEDULE_GROUP'] = 'График доставки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_AUTOMATION'] = 'Автоматизация';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_AUTO_FINISH'] = 'Завершать заказ после отправки';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_AUTO_FINISH_HELP'] = 'При уведомлении от&nbsp;маркетплейса устанавливать статусы заказа <nobr>1С-Битрикс</nobr>, указанные на&nbsp;вкладке &laquo;Статусы заказов&raquo; для статусов маркетплейса DELIVERY и&nbsp;DELIVERED.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_DIGITAL_ADAPTER'] = 'Доступные ключи';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_DIGITAL_ADAPTER_HELP'] = 'Выберите модуль, в котором храните цифровые ключи для автоматической отправки покупателю.';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SCHEDULE'] = 'Расписание';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_SCHEDULE_HELP'] = '
<p>На&nbsp;основе расписания будет сформирован список возможных дат доставки и&nbsp;временных интервалов, доступных покупателю для выбора.</p>
<p>На&nbsp;каждый день можно создать до&nbsp;5&nbsp;интервалов.</p>
<p>Формат времени: <nobr>24-часовой</nobr>, ЧЧ: ММ. В&nbsp;качестве минут всегда должно быть указано 00 (исключение&nbsp;&mdash; 23:59). Максимальное значение: 23:59.</p>
<p>Если интервалы не&nbsp;соответствуют требованиям, будет выполнено автоматическое округление. Например: интервал от&nbsp;9:15 до&nbsp;11:30 будет преобразован к&nbsp;9:00 до&nbsp;12:00.</p>
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_ASSEMBLY_DELAY'] = 'Сборка заказа';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_ASSEMBLY_DELAY_HELP'] = '
<p>Время необходимое магазину для передачи заказа в&nbsp;службу доставки.</p>
<p>Например, чтобы увеличить срок доставки, рассчитанный службой доставки:</p>
<ul>
<li>Выберите вариант 2 дня, чтобы добавить два дополнительных дня на сборку заказа.</li>
<li>Укажите при заказе до 21:00, если после выбранного времени потребуется ещё один дополнительный день.</li>
</ul>
<p>Если выбран вариант По умолчанию, будет использовано время на сборку заказа из Графика отгрузки.</p>
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_PERIOD_WEEKEND_RULE'] = 'Учет выходных';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_PERIOD_WEEKEND_RULE_HELP'] = '
<p>Как будет изменена первая дата доставки, если срок доставки от&nbsp;2 до&nbsp;4 дней:</p>
<ul>
<li><strong>Срок доставки указан в&nbsp;рабочих днях</strong>&nbsp;&mdash; второй рабочий день ^1;</li>
<li><strong>Выдача в&nbsp;ближайший рабочий день</strong>&nbsp;&mdash; первый рабочий день после двух дней ^1;</li>
<li><strong>Служба доставки учитывает выходные</strong>&nbsp;&mdash; второй день.</li>
</ul>
<p>^1 если заказ выполняется в&nbsp;нерабочее время с&nbsp;учетом задержки отгрузки, отсчет начнется со&nbsp;следующего рабочего дня.</p>
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_PERIOD_WEEKEND_RULE_FULL'] = 'Срок доставки указан в рабочих днях';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_PERIOD_WEEKEND_RULE_EDGE'] = 'Выдача в ближайший рабочий день';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_PERIOD_WEEKEND_RULE_NONE'] = 'Служба доставки учитывает выходные';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_INTERVAL_FORMAT'] = 'Формат интервала';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_INTERVAL_FORMAT_TIME'] = 'Выбор даты и времени';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_INTERVAL_FORMAT_PERIOD'] = 'Выбор даты';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_INTERVAL_FORMAT_HELP'] = '
<p>Какой выбор даты доставки предлагать покупателю при оформлении заказа.</p>
<ul>
<li>Выбор даты и&nbsp;времени&nbsp;&mdash; пользователю будет предложено выбрать день и временной интервал доставки;</li>
<li>Выбор даты&nbsp;&mdash; пользователь сможет выбрать только день доставки.</li>
</ul>
';
$MESS['YANDEX_MARKET_TRADING_SERVICE_MARKETPLACEDBS_OPTIONS_DELIVERYOPTION_HOLIDAY_GROUP'] = 'Праздничные дни';

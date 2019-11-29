<?
$module_id = "sotbit.seometa";
$MESS[$module_id."_edit1"] = "Настройки";
$MESS[$module_id."_GROUP_SETTINGS"] = "Настройки";

$MESS[$module_id."_FILTER_TYPE"] = "Тип фильтра в каталоге";
$MESS[$module_id."_FILTER_TYPE_bitrix_chpu"] = "Стандартный фильтр с ЧПУ";
$MESS[$module_id."_FILTER_TYPE_bitrix_not_chpu"] = "Стандартный фильтр без ЧПУ";
$MESS[$module_id."_FILTER_TYPE_misshop_chpu"] = "Фильтр MissShop с ЧПУ";
$MESS[$module_id."_FILTER_TYPE_combox_chpu"] = "Kombox фильтр с ЧПУ";
$MESS[$module_id."_FILTER_TYPE_combox_not_chpu"] = "Kombox фильтр без ЧПУ";
$MESS[$module_id."_FILTER_TYPE_NOTE"] = "<ul style='margin:0;padding-left:20px;'>
<li><b>Стандартный фильтр с ЧПУ</b> — ссылки вида /catalog/pants/filter/brand-is-company1/apply/</li>
<li><b>Стандартный фильтр без ЧПУ</b> — ссылки вида /catalog/pants/?set_filter=y&arrFilter_5_4244200709=Y</li>
<li><b>Фильтр MissShop с ЧПУ</b> — ссылки вида /catalog/pants/filter/brand-company1/apply/<br>используется в решениях от Сотбит: MissShop, MisterShop, B2BShop</li>
<li><b>Kombox фильтр с ЧПУ</b> — ссылки вида /catalog/pants/filter/brand-company1/</li>
<li><b>Kombox фильтр без ЧПУ</b> — ссылки вида /catalog/pants/?brand=company1</li>
</ul>";

$MESS[$module_id."_NO_INDEX"] = "Отключить индексацию всех страниц";
$MESS[$module_id."_NO_INDEX_NOTE"] = "Если в настройке условия будет отключена опция \"Закрыть от индексации\", то страница с условием будет попадать в индекс.";

$MESS[$module_id."_SOURCE"] = "Список источников";
$MESS[$module_id."_SOURCE_NOTE"] = "Введите список сайтов, с которых нужно фиксировать переходы.<br>Каждый источник необходимо указывать в отдельной строке.";

$MESS[$module_id."_TITLE"] = "Настройки";
$MESS[$module_id."_PAGENAV"] = "Пагинация";
$MESS[$module_id."_PAGENAV_NOTE"] = "Укажите часть url который будет отображаться при пагинации, где \"%N%\" - номер страницы.<br>Например \"/page_%N%/\"";

$MESS[$module_id."_PAGINATION_TEXT"] = "Текст для метаинформации при пагинации";
$MESS[$module_id."_PAGINATION_TEXT_NOTE"] = "Введите текст, который будет отображаться после метаинформации на страницах пагинации, где \"<b>%N%</b>\" - номер страницы.<br>
Например: <b>(страница %N%)</b>";

$MESS[$module_id."_MANAGED_CACHE_ON"] = "Включить тегированное кеширование";
$MESS[$module_id."_USE_CANONICAL"] = "Добавлять канонический url (canonical)";
$MESS[$module_id."_USE_GET"] = "Добавлять GET-параметры в ЧПУ";
$MESS[$module_id."_USE_GET_NOTE"] = "Если в каталоге не используется ЧПУ-режим, то нужно отключить данную настройку.<br> Нужно включить, если помимо самого фильтра используются какие-либо дополнительные параметры.";
$MESS[$module_id."_RETURN_AJAX"] = "Перехват ajax-запросов";
$MESS[$module_id."_RETURN_AJAX_NOTE"] = "Отметьте, если при включенном ajax режиме в каталоге происходит редирект на некорректную страницу.";

$MESS[$module_id."_GROUP_SETTINGS_FOR_PROG"] = "Для разработчиков";
$MESS[$module_id.'_FILTER_EXCEPTION_SETTINGS'] = "Исключить из фильтрации";
$MESS[$module_id.'_FILTER_EXCEPTION_SETTINGS_NOTE'] = "Указанные поля ( ключи массива \${\$FilterName} ) не будут учитываться при работе в условиях.<br>В качестве разделителя использовать <b>;</b><br><br><b>Пример:</b><br> PROPERTY_REGIONS<b>;</b>PROPERTY_CUSTOM_FILTER";

$MESS["SEO_META_DEMO"] = 'Модуль работает в демо-режиме. Приобрести полнофункциональную версию вы можете по адресу: <a href="http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa/" target="_blank">http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa</a>';
$MESS["SEO_META_DEMO_END"] = 'Демо-режим закончен. Приобрести полнофункциональную версию вы можете по адресу: <a href="http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa/" target="_blank">http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa</a>';
?>
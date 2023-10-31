<?
$MESS ['askaron_pro1c_tab_debug'] = "Отладка обмена";

$MESS ['askaron_pro1c_header_files'] = "Адреса страниц обмена с 1С, для которых применяется модуль";
$MESS ['askaron_pro1c_header_clear_cache'] = "Ускорение сайта. Отложенный сброс управляемого кеша инфоблоков";
$MESS ['askaron_pro1c_header_failure_resistance'] = "Отказоустойчивость обмена";

$MESS ['askaron_pro1c_header_fast'] = "Ускорение обмена";

$MESS ['askaron_pro1c_header_debug'] = "Отладка обмена";
//$MESS ['askaron_pro1c_header_products'] = "Описание товаров";


$MESS ['askaron_pro1c_more'] = "Еще";

$MESS['askaron_pro1c_settings_help'] = "Укажите адреса, для которых применяется модуль. Например:
	<br /><br /><strong>/bitrix/admin/1c_exchange.php</strong> - стандартная страница обмена;
	<br /><br /><strong>/bitrix/admin/askaron_pro1c_exchange.php</strong> - копия стандартной страницы обмена. 1С с ней работает точно так же, как с <strong>/bitrix/admin/1c_exchange.php</strong>
	<br><br><strong>/crm/1c_exchange.php</strong> - страница обмена счетами для Битрикс24 в коробке.

	";

$MESS ['askaron_pro1c_field_active']		= "Активность";
$MESS ['askaron_pro1c_field_name']			= "Aдрес страницы";
$MESS ['askaron_pro1c_field_skip_product']	= "Загружать только цены и остатки<br />(файл с описанием товаров будет пропущен)";

$MESS ['askaron_pro1c_managed_cache_off'] = 'Управляемый кеш <a href="cache.php?lang='.LANGUAGE_ID.'&amp;tabControl_active_tab=fedit4">выключен</a>';


$MESS ['askaron_pro1c_header_clear_cache_help'] = 'Высокопосещаемый сайт на стандартных компонентах может работать очень
	медленно во время обмена с 1С из-за того,
	что при выгрузке товаров постоянно сбрасывается управляемый кеш инфоблока.
	Управляемый кеш инфоблока сбрасывается сразу для всех товаров инфоблока.
	<br><br>Отключение мгновенного сброса кеша при записи позволяет существенно ускорить сайт.
	 <br><br>
	 Модуль позволяет запретить
	 сброс кеша непосредственно во время обмена и выполнить сброс на несколько минут позже с помощью агента.
	<br><br>
	<a href="http://askaron.ru/api_help/course1/lesson160/" target="_blank">Подробное описание опций в документации</a>
	 ';

$MESS ['askaron_pro1c_disable_clear_tag_cache_for_script'] = "Запрещать сброс управляемого кеша инфоблоков<br>(при работе указанных страниц обмена выше)";
$MESS ['askaron_pro1c_disable_clear_tag_cache_for_script_help'] = '
Опция запрещает мгновенный сброс кеша в инфоблоке при выгрузке товаров.<br><br>
Разработчикам.
Данная опция работает для стандартных случаев, когда кеш сбрасывается функцией CIBlock::clearIblockTagCache().
<br><br>Если какой-нибудь ваш самописный обработчик в ходе обмена делает <nobr>$CACHE_MANAGER->ClearByTag("ibclock_id_4"),</nobr> то его следует исправить на CIBlock::clearIblockTagCache(4).
';

$MESS ['askaron_pro1c_clear_tag_cache_agent_enabled'] = "Периодически сбрасывать управляемый кеш инфоблоков с помощью агента";
$MESS ['askaron_pro1c_clear_tag_cache_agent_enabled_help'] = 'Агент периодически очищает кеш инфоблоков, но не всё подряд. Агент проверяет, в каких инфоблоках изменилась дата инфоблока, дата раздела или элемента.

<br><br>Время последней проверки: <strong>#LAST_DATE#</strong>';

$MESS ['askaron_pro1c_clear_tag_cache_agent_main_page'] = "Пересоздать кеш на главной странице сайта после сброса кеша агентом";
$MESS ['askaron_pro1c_clear_tag_cache_agent_main_page_help'] = '
После сброса кеша инфоблоков агент зайдет на главную страницу сайта и создаст кеш для неавторизованного пользователя.
<br><br>
Это поможет избежать ситуации, когда после сброса кеша у какого-нибудь пользователя заново генерируется кеш меню и других компонентов шаблона.
';



$MESS ['askaron_pro1c_clear_tag_cache_agent_interval'] = "Интервал запуска агента";
$MESS ['askaron_pro1c_clear_tag_cache_agent_interval_2'] = "минут";

$MESS ['askaron_pro1c_clear_tag_cache_agent_interval_help'] = 'Не имеет смысла ставить слишком часто (потеря производительности) или слишком редко (могут отображаться неактульные данные на сайте). Оптимально в диапазоне 5-30 минут.';

$MESS ['askaron_pro1c_import_pause'] = "Интервал между шагами при импорте товаров";
$MESS ['askaron_pro1c_import_pause_2'] = "секунд";
$MESS ['askaron_pro1c_import_pause_help'] = 'Добавление небольшого интервала между шагами существенно снижает нагрузку на сервер и позволяет выгрузить неограниченное количество товаров даже на самый слабый хостинг. Чем хуже сервер, тем больше ставьте: 2, 5, 10, 20 секунд.<br /><br />Длина одного шага настраивается <a href="1c_admin.php?lang=#LANG#">в настройках обмена с 1С</a>. Обычно 30 секунд, но на слабых хостингах рекомендуется уменьшать.';

$MESS ['askaron_pro1c_time_limit'] = "Максимальное время выполнения одного шага";
$MESS ['askaron_pro1c_time_limit_2'] = 'секунд';
$MESS ['askaron_pro1c_time_limit_help'] = 'На этой странице max_execution_time=#TIME_LIMIT#.<br /><br />Установите скрипту достаточное время для выполнения одного шага обмена. Например, 180 или 300. 0 — неограниченное время шага. Если опция не задана, max_execution_time устанавливаться не будет.';

$MESS ['askaron_pro1c_memory_limit'] = "Максимaльный объем памяти доступный шагу скрипта";
$MESS ['askaron_pro1c_memory_limit_2'] = 'мегабайт';
$MESS ['askaron_pro1c_memory_limit_help'] = 'На этой странице memory_limit=#MEMORY_LIMIT#.<br /><br />Установите скрипту достаточное количество памяти для выполнения одного шага обмена. Например, 512 или 1024 мегабайт. -1 — неограниченный объем оперативной памяти. Если опция не задана, memory_limit устанавливаться не будет.';


$MESS ['askaron_pro1c_forbidden'] = "Запретить выполнение скрипта";
$MESS ['askaron_pro1c_forbidden_help'] = 'Установите флаг и нажмите «Сохранить», если вам надо прервать или запретить обмен с 1С. Удобно при переносе сайта на другой сервер, чтобы не допустить рассинхронизации.';

$MESS ['askaron_pro1c_additional_settings'] = "Дополнительные настройки";

$MESS ['askaron_pro1c_header_quantity'] = "Доступное количество";


$MESS ['askaron_pro1c_quantity_set_to_zero'] = "Если количествo не пришло из 1С, то устанавливать 0";
$MESS ['askaron_pro1c_quantity_set_to_zero_help'] = 'Опцию необходимо включать лишь на некоторых сайтах, где со стороны 1С используется старая версия модуля обмена. К новой версии Битрикса приходит пустое количество, и нулевые остатки на сайте не записываются
	<br /><br />
	<a href="http://askaron.ru/api_help/course1/lesson99/" target="_blank">Подробное описание опции в документации</a>.';


$MESS ['askaron_pro1c_log'] = "Записывать все шаги в обычный лог-файл";

$MESS ['askaron_pro1c_log_trace'] = "Записывать в лог-файл порядок вызова функций";
$MESS ['askaron_pro1c_log_trace_help'] = "Количество строк трейса, которые дополнительно попадут в лог-файл";

$MESS ['askaron_pro1c_log_max_size'] = "Максимальный размер лог-файла";
$MESS ['askaron_pro1c_log_max_size_2'] = "мегабайт";

$MESS ['askaron_pro1c_log_max_size_help'] = "При достижении максимального размера лог-файл будет очищен.
Это нужно, чтобы место на сайте не закочилось.";


//$MESS ['askaron_pro1c_log_help_warning'] = '<strong>Внимание! </strong> Не оставляйте эту опцию постоянно включенной. Лог-файл со временем может стать очень большим.';

$MESS ['askaron_pro1c_log_help'] = 'Запись в лог делается стандартной функцией
<a href="http://dev.1c-bitrix.ru/api_help/main/functions/debug/addmessage2log.php">AddMessage2Log</a> в файл #LOG_FILENAME#.
<br><br>
<a href="askaron_pro1c_log_view.php?lang='.LANGUAGE_ID.'" target="_blank">Cтраница просмотра лог-файла</a> (в новой вкладке)
';



//$MESS ['askaron_pro1c_log_help_not_defined'] = 'Вы можете указать другой путь, если установите константу LOG_FILENAME в /bitrix/php_interface/dbconn.php.';
//
//$MESS ['askaron_pro1c_log_help_not_exist'] = 'Лог-файл не существует';
//$MESS ['askaron_pro1c_log_help_file_info'] = '<a href="#URL#" target="_blank">Открыть лог-файл</a>
//	<br />изменен <strong>#DATE#</strong>
//	<br />размер <strong>#BYTE#</strong> байт</a>';

//$MESS ['askaron_pro1c_log_clear'] = "Очистить лог-файл";


//$MESS ['askaron_pro1c_import_date'] = 'Дата последнего успешного импорта описаний товаров';
//$MESS ['askaron_pro1c_offers_date'] = 'Дата последнего успешного импорта цен и остатков';

$MESS ['askaron_pro1c_live_log'] = 'Живой лог';
//$MESS ['askaron_pro1c_live_log_title'] = 'Живой (интерактивный) лог';
$MESS ['askaron_pro1c_live_log_help'] = 'Живой лог позволяет в реальном времени наблюдать за обменом и видеть ответы сайта для 1С. Используется модуль Битрикса <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=41&amp;LESSON_ID=2033">Push and Pull</a>.';

$MESS ['askaron_pro1c_live_log_version'] = '<strong>Внимание!</strong> Для работы Живого лога должен быть установлен модуль Битрикса <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=41&amp;LESSON_ID=2033">Push and Pull</a> версии не ниже 14.0.0. Сейчас #CURRENT_VERSION#. <a href="update_system.php?lang=#LANG#">Обновите</a> Push and Pull.';

$MESS ['askaron_pro1c_live_log_open'] = '<a href="askaron_pro1c_live_log.php?lang=#LANG#" target="_blank">Открыть живой лог</a> (в новой вкладке)';

$MESS ['askaron_pro1c_pull_not_installed'] = '<strong>Модуль Push and Pull не установлен!</strong>';

$MESS ['askaron_pro1c_pull_install'] = '<a href="module_admin.php?lang=#LANG#">Установить модуль</a> Push and Pull';


$MESS ['askaron_pro1c_pull_notice'] = '<strong>Внимание!</strong>
	<br /><br />Чтобы Живой лог работал без задержек рекомендуется в <a href="settings.php?lang=#LANG#&amp;mid=pull&amp;mid_menu=1">настойках модуля Push and Pull</a> включить опцию <em>«На сервере установлен "Сервер очередей" (nginx-push-stream-module)»</em>.	
	<br /><br />Важно: настройка nginx-push-stream-module на вашем сервере в обязаннности разработчика модуля не входит. nginx-push-stream-module уже настроен на Виртуальной машине Битрикса 4.2 и выше.';


$MESS ['askaron_pro1c_copy_exchange_files'] = "Копировать XML-файлы обмена в папку модуля";
$MESS ['askaron_pro1c_copy_exchange_files_help'] = 'Опция полезна лишь на некоторых 1С, которые удаляют файлы обмена на сайте. Например, УТ 11.1.10.199 (без дополнения) при загрузке файла цен удаляет файл описаний товаров.
<br><br>
Не включайте опцию постоянно. Она нужна лишь программисту при настройке и отладе обмена. Модуль не следит за размером папки.
<br><br>
Папка файлов <a target="_blank" href="/bitrix/admin/fileman_admin.php?lang='.LANGUAGE_ID.'&amp;path='.urlencode("/upload/1c_catalog_copy_askaron_pro1c").'">/upload/1c_catalog_copy_askaron_pro1c</a>
';


$MESS ['askaron_pro1c_fast_write'] = "Быстрая запись свойств товаров";
$MESS ['askaron_pro1c_fast_write_help'] = 'В инфоблоках, где количество свойств порядка тысячи, товары могут записываться по секунде и дольше. В лог-файле можно увидеть время записи элемента.
	<br /><br />В таких случаях обмен можно ускорить в несколько раз за счет записи только используемых свойств. Опцию следует включать только на тех сайтах, где в инфоблоке много свойств.
	<br /><br />Совет: сравните время записи элемента в лог-файле до и после включения опции.
	<br /><br /><a href="http://askaron.ru/api_help/course1/lesson78/" target="_blank">Подробное описание опции в документации</a>.';

$MESS ['askaron_pro1c_error_save'] = "Список настроек не был сохранен";
$MESS ['askaron_pro1c_error_save_header'] = "Ошибка при сохранении данных";


?>

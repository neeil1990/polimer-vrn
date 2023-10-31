<?
$moduleId = "sotbit.seometa";
$MESS[$moduleId."_edit1"] = "Настройки";
$MESS[$moduleId."_GROUP_SETTINGS"] = "Настройки";

$MESS[$moduleId."_FILTER_TYPE"] = "Тип фильтра в каталоге";
$MESS[$moduleId."_FILTER_TYPE_bitrix_chpu"] = "Стандартный фильтр с ЧПУ";
$MESS[$moduleId."_FILTER_TYPE_bitrix_not_chpu"] = "Стандартный фильтр без ЧПУ";
$MESS[$moduleId."_FILTER_TYPE_misshop_chpu"] = "Фильтр MissShop с ЧПУ";
$MESS[$moduleId."_FILTER_TYPE_combox_chpu"] = "Kombox фильтр с ЧПУ";
//$MESS[$module_id."_FILTER_TYPE_combox_not_chpu"] = "Kombox фильтр без ЧПУ";
$MESS[$moduleId."_FILTER_TYPE_NOTE"] = "<ul style='margin:0;padding-left:20px;'>
<li><b>Стандартный фильтр с ЧПУ</b> — ссылки вида /catalog/pants/filter/brand-is-company1/apply/</li>
<li><b>Стандартный фильтр без ЧПУ</b> — ссылки вида /catalog/pants/?set_filter=y&arrFilter_5_4244200709=Y</li>
<li><b>Фильтр MissShop с ЧПУ</b> — ссылки вида /catalog/pants/filter/brand-company1/apply/<br>используется в решениях от Сотбит: MissShop, MisterShop, B2BShop</li>
<li><b>Kombox фильтр с ЧПУ</b> — ссылки вида /catalog/pants/filter/brand-company1/</li>
</ul>";

$MESS[$moduleId."_FILTER_SEF"] = "ЧПУ фильтра в каталоге";
$MESS[$moduleId."_FILTER_SEF_NOTE"] = "В поле можно указать маску ссылки битрикс, отличной от стандартной, формата /filter/#FILTER_PARAMS#/apply<br><br><b>Например:</b><br>/f/#FILTER_PARAMS#/a<br>или<br>/filter/#FILTER_PARAMS#";

$MESS[$moduleId."_NO_INDEX"] = "Отключить индексацию всех страниц";
$MESS[$moduleId."_NO_INDEX_NOTE"] = "Если в настройке условия будет отключена опция \"Закрыть от индексации\", то страница с условием будет попадать в индекс.";

$MESS[$moduleId."_PRODUCT_AVAILABLE_FOR_COND"] = "Учет доступности товара";
$MESS[$moduleId."_PRODUCT_AVAILABLE_FOR_COND_NOTE"] = "Если будет включена опция \"Учет доступности товара\", то при генерации ЧПУ будет учитываться свойство \"Доступность\" товара";

$MESS[$moduleId."_TITLE"] = "Настройки";
$MESS[$moduleId."_PAGENAV"] = "Пагинация";
$MESS[$moduleId."_PAGENAV_NOTE"] = "Укажите часть url который будет отображаться при пагинации, где \"%N%\" - номер страницы.<br>Например \"page_%N%/\"";

$MESS[$moduleId."_PAGINATION_TEXT"] = "Текст для метаинформации при пагинации";
$MESS[$moduleId."_PAGINATION_TEXT_NOTE"] = "Введите текст, который будет отображаться после метаинформации на страницах пагинации, где \"<b>%N%</b>\" - номер страницы.<br>
Например: <b>(страница %N%)</b>";

$MESS[$moduleId."_MANAGED_CACHE_ON"] = "Включить тегированное кеширование";
$MESS[$moduleId."_USE_CANONICAL"] = "Добавлять канонический url (canonical)";
$MESS[$moduleId."_USE_GET"] = "Добавлять GET-параметры в ЧПУ";
$MESS[$moduleId."_USE_GET_NOTE"] = "Если в каталоге не используется ЧПУ-режим, то нужно отключить данную настройку.<br> Нужно включить, если помимо самого фильтра используются какие-либо дополнительные параметры.";
$MESS[$moduleId."_RETURN_AJAX"] = "Перехват ajax-запросов";
$MESS[$moduleId."_RETURN_AJAX_NOTE"] = "Отметьте, если при включенном ajax режиме в каталоге происходит редирект на некорректную страницу.";

$MESS[$moduleId."_GROUP_SETTINGS_FOR_PROG"] = "Для разработчиков";
$MESS[$moduleId.'_FILTER_EXCEPTION_SETTINGS'] = "Исключить из фильтрации";
$MESS[$moduleId.'_FILTER_EXCEPTION_SETTINGS_NOTE'] = "Указанные поля ( ключи массива \${\$FilterName} ) не будут учитываться при работе в условиях.<br>В качестве разделителя использовать <b>;</b><br><br><b>Пример:</b><br> PROPERTY_REGIONS<b>;</b>PROPERTY_CUSTOM_FILTER";
$MESS[$moduleId.'_IS_SET_ACTIVE'] = "Делать активными ЧПУ после генерации";
$MESS[$moduleId.'_PARAMS_EXCEPTION_SETTINGS'] = "Исключить страницы с параметрами из обработки";
$MESS[$moduleId.'_PARAMS_EXCEPTION_SETTINGS_NOTE'] = "Исключает страницы из обработки с указанными параметрами.<br><br><b>Пример:</b><br>У нас есть ajax запрос который \"ломается\" на странице сгенерированной нашим модулем и в запросе есть параметр:<br><b>is_ajax=Y</b><br>Чтобы исключить этот запрос из обработки требуется просто указать этот параметр по шаблону:<br><b>ключ=значение</b><br>( <code style='background-color: '>ajax=Y;</code> В качестве разделителя нужно использовать <code><b>;</b></code> )";

$MESS[$moduleId.'_GROUP_SETTINGS_FOR_STAT'] = 'Статистика';
$MESS[$moduleId.'_INC_STATISTIC'] = 'Включить учет статистики';
$MESS[$moduleId.'_INC_STATISTIC_NOTE'] = 'Статистика работает только с настроенными ЧПУ.<br>Учитываются переходы поисковых роботов Yandex и Google';
$MESS[$moduleId.'_PERIOD_STATISTIC'] = 'Периодичность проверки (сек.)';
$MESS[$moduleId.'_PERIOD_STATISTIC_NOTE'] = 'Укажите с какой периодичностью будет проверяться актуальность страниц (значение указывается в секундах, по умолчанию 86400 сек.).<br>Для корректной работы, минимальное значение должно быть больше 0 (чем меньше значение, тем выше нагрузка на сервер).';
$MESS[$moduleId.'_AGENT_PERIOD_STATISTIC'] = 'Агент для актуализации статистики<br>(рекомендуется запускать агента из под cron.)';
$MESS[$moduleId.'_AGENT_LIMIT_STATISTIC'] = 'Лимит для запроса в агенте';
$MESS[$moduleId.'_AGENT_LIMIT_STATISTIC_NOTES'] = 'Настройка "Лимит для запроса в агенте" ограничивает максимальное значение обрабатываемых записей из таблицы "Статистика в списке" при работе агента.
                                                   <br> Если количество записей в таблице больше чем указанный лимит, то агент автоматически перезапустится, пока не обработает все записи.
                                                   <br>Для корректной работы, минимальное значение должно задаваться целым числом больше 0.
                                                   <br><br> <b>Значение рекомендуется выставлять в зависимости от мощности сервера.</b>';
$MESS[$moduleId.'_AGENT_EXEC'] = 'Дата и время следующего запуска';
$MESS[$moduleId.'_AGENT_INTERVAL'] = 'Интервал (сек.)';
$MESS[$moduleId.'_AGENT_INTERVAL_NOTE'] = 'Агент начнет свою работу в указанный период времени и будет повторяться исходя из этой даты и интервала. Дата следующего запуска обновляется автоматически.';

$MESS[$moduleId.'_CURRENCY_TYPE'] = "Валюта";
$MESS[$moduleId.'_CURRENCY_TYPE_NOTE'] = "В какой валюте отображаются товары в каталоге.";
$MESS[$moduleId.'_SITEMAP_FILE_SIZE'] = "Максимальный размер файла (seometa_sitemap)";
$MESS[$moduleId.'_SITEMAP_FILE_SIZE_NOTE'] = "Указывается для оганичения максимального размера генерируемого файла карты сайта модулем Сотбит: Умный фильтр.<br>Размер требуется писать в мегабайтах, только число. (пример: 30 -- что равно 30Mb)<br> Для правильной работы максимальный размер равен 50 Mb. (по умолчанию установлено занчение равное 50 Mb)";

$MESS[$moduleId.'_SITEMAP_COUNT_LINKS'] = "Максимальное количество ссылкой в одном файле (seometa_sitemap)";
$MESS[$moduleId.'_SITEMAP_COUNT_LINKS_NOTE'] = "Указывается для оганичения максимального количества ссылок записываемых в файл карты сайта модулем Сотбит: Умный фильтр.<br>Для правильной работы максимальный размер равен 50000. (по умолчанию установлено занчение равное 50000)";

$MESS[$moduleId.'_GENERATE_ALL_CONDITIONS'] = "Перегенерировать все ЧПУ ссылки при построении карты сайта";
$MESS[$moduleId.'_GENERATE_ALL_CONDITIONS_NOTE'] = "При установленном чекбоксе, при генерации карты сайта, все ЧПУ ссылки будут перегенерированны в каждом из условий.<br>При установленном чекбоксе время построения карты сайта может значительно увеличиться.<br><br><b>Рекомендация:</b><br>При генерации карты сайта рекомендуется снимать данный чекбокс, для ускорения построения карты сайта.";

$MESS[$moduleId.'_SITEMAP_COUNT_LINKS_FOR_OPERATION'] = "Количество ссылок обрабатываемых за один шаг, при генерации карты сайта";
$MESS[$moduleId.'_SITEMAP_COUNT_LINKS_FOR_OPERATION_NOTE'] = "<b>Работает при отключенном чекбоксе \"Перегенерировать все ЧПУ ссылки при построении карты сайта\"<br> в настройках карты сайта</b><br><br>При установленном чекбоксе, будет браться установленное количество ссылок для обработки.<br>Если во время генерации карты сайта возникает ошибка завершения скрипта из-за истечения времени ожидания (TIME OUT),<br>рекомендуется уменьшить указанное значение.<br><br><b>Рекомендация:</b><br>Для увеличения скорости построения карты сайта можно увеличить это значение,<br>но скрипт может завершить свое выполнение по истечению времени (TIME OUT),<br> по этому данное значение подбирается опытным путем.<br><br>(Значение по умолчанию: 10000)";

$MESS["SEO_META_DEMO"] = 'Модуль работает в демо-режиме. Приобрести полнофункциональную версию вы можете по адресу: <a href="http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa/" target="_blank">http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa</a>';
$MESS["SEO_META_DEMO_END"] = 'Демо-режим закончен. Приобрести полнофункциональную версию вы можете по адресу: <a href="http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa/" target="_blank">http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa</a>';
?>

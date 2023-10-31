<?
$MESS["SEO_META_DEMO"] = 'Модуль работает в демо-режиме. Приобрести полнофункциональную версию вы можете по адресу: <a href="http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa/" target="_blank">http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa</a>';
$MESS["SEO_META_DEMO_END"] = 'Демо-режим закончен. Приобрести полнофункциональную версию вы можете по адресу: <a href="http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa/" target="_blank">http://marketplace.1c-bitrix.ru/solutions/sotbit.seometa</a>';

$MESS["SEO_META_NOT_CONFIGURED_TAB_BEHAVIOR"] = "Поведение";
$MESS["SEO_META_NOT_CONFIGURED_TAB_BEHAVIOR_TITLE"] = "Настрока поведения фильтрованных страниц";
$MESS["SEO_META_NOT_CONFIGURED_TAB_META"] = "Метаинформация";
$MESS["SEO_META_EDIT_NOT_CONFIGURED_TAB_META_TITLE"] = "Настройка метаинформации для отфильтрованных страниц";

$MESS["SEO_META_NOT_CONFIGURED_TITLE"] = "Параметры: SEO ненастроенных страниц";
$MESS["SEO_META_NOT_CONFIGURED_SAVED"] = "Параметры сохранены";
$MESS["SEO_META_NOT_CONFIGURED_SAVE_ERROR"] = "Ошибка при сохранении";

$MESS["SEO_META_NOT_CONFIGURED_ACT"] = "Активность:";
$MESS["SEO_META_NOT_CONFIGURED_INDEX"] = "Закрыть от индексации";
$MESS["SEO_META_NOT_CONFIGURED_ADD_CANONICAL"] = "Установка canonical (каноническая страница при этом будет равна странице раздела)";
$MESS["SEO_META_NOT_CONFIGURED_BEHAVIOR_FILTERED_PAGES"] = "Поведение фильтрованных страниц";
$MESS["SEO_META_NOT_CONFIGURED_BEHAVIOR_PAGINATION_PAGES"] = "Поведение страниц при пагинации";
$MESS["SEO_META_NOT_CONFIGURED_BEHAVIOR"] = "Настройки метаинформации";
$MESS["SEO_META_NOT_CONFIGURED_SITES"] = "Сайты:";
$MESS["SEO_META_NOT_CONFIGURED_TYPE_OF_INFOBLOCK"] = "Тип инфоблока:";
$MESS["SEO_META_NOT_CONFIGURED_INFOBLOCK"] = "Инфоблок:";
$MESS["SEO_META_NOT_CONFIGURED_FILTER_TYPE"] ="Тип фильтра в каталоге";

$MESS["SEO_META_NOT_CONFIGURED_META_GROUP"] ="Метаинформация";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_TITLE"] = "Шаблон META TITLE";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_KEYWORDS"] = "Шаблон META KEYWORDS";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_DESCRIPTION"] = "Шаблон META DESCRIPTION";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_PAGE_TITLE"] = "Заголовок раздела";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_BREADCRUMB_TITLE"] = "Название страницы в хлебных крошках";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_TOP_DESC"] = "Верхнее описание";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_BOTTOM_DESC"] = "Нижнее описание";
$MESS["SEO_META_NOT_CONFIGURED_META_ELEMENT_ADD_DESC"] = "Дополнительное описание";
$MESS["SEO_META_NOT_CONFIGURED_META_NOTE"] = "<b>Фильтры обработчики:</b>
<ul>
<li><b>{=lower arg1 ... argN}</b> - приведение к нижнему регистру</li>
<li><b>{=upper arg1 ... argN}</b> - приведение к верхнему регистру</li>
<li><b>{=concat arg1 ... argN \", \"}</b> - сцепление строк через разделитель</li>
<li><b>{=limit arg1 ... argN \"&lt;delimiter&gt;\" NN}</b> - ограничение NN элементов по разделителю &lt;delimiter&gt;</li>
<li><b>{=translit arg1 ... argN}</b> - транслитерация выбранных аргументов</li>
<li><b>{=min arg1 ... argN}</b> - выборка минимального числового значения</li>
<li><b>{=max arg1 ... argN}</b> - выборка максимального числового значения</li>
<li><b>{=distinct arg1 ... argN}</b> - уникальные (без дублей) значения</li>
<li><b>{=first_upper arg1 ... argN}</b> - приведение к верхнему регистру первой буквы строки</li>
<li><b>{=nonfirst arg1 ... argN}</b> - не выводить строку на первой странице (строка будет выводиться только при пагинации)</li>
<li><b>{=iffilled this.name 'Называние %s'}</b> - проверка значения на пустоту (вывод значения производится функцией: sprintf, параметры формата этой функции поддерживаются)</li>
<li id=\"prop_list_container\"><b>{=prop_list \"NAME\", \",\", \"VALUE\"}</b> - вывод всех выбранных свойств и их значений через разделитель<span data-hint-html data-hint=\"
Данная функция может принимать 3 параметра. <br>
Пояснения по параметрам: 
<ul>
    <li> Первый параметр: параметр свойства </li> 
    <li> Второй параметр: разделитель </li> 
    <li> Третий параметр: параметр значения свойства </li> 
</ul>
Примеры:
В умном фильтре мы выбрали свойства: <br>
Цвет:
<ul>
    <li> красный </li>
    <li> черный  </li>
</ul>
Производитель:
<ul>
    <li> Nike </li>
    <li> Geox </li>
    <li> Adidas </li>
</ul>
<b>{=prop_list 'NAME', ',', 'VALUE'}</b><br>
В результате будет взято значение параметра свойства NAME , потом список свойств разделенных запятой (,) <br>
Цвет: красный, черный Производитель: Nike, Geox, Adidas <br><br>
<b>{=prop_list '', ',', 'VALUE'}</b><br>
В результате будет сформирован список свойств через разделитель (,) <br>
красный, черный, Nike, Geox, Adidas <br><br>
<b>{=prop_list 'NAME', ',', ''}</b><br>
В результате будет сформирован список из параметров (NAME) свойств через разделитель (,) <br>
Цвет, Производитель <br>
В свойствах и в значениях свойст доступны и другие ключи:
<ul>
    <li> NAME </li>
    <li> CODE </li>
    <li> HTML_VALUE </li>
    <li> UPPER </li>
    <li> URL_ID </li>
</ul>    
\"></span></li>
<li id=\"morphy_container\"><b>{=morphy arg1 ... argN \", \"}</b> - преобразование значений аргументов в нужную языковую форму<span data-hint-html data-hint=\"
Для преобразования значений аргументов в нужную форму необходимо поместить контейнер с аргументом в контейнер вида: <br> {=morphy arg1 ... argN, 'МР, ЕД, РД'}<br>
где МР, ЕД, РД модификаторы - род, число, падеж - соответсвенно.<br>
Для модификаторов используются двойные кавычки<br>
Допустимые значения модификаторов:<br>
Род:
<ul>
    <li>МР - мужской</li>
    <li>ЖР - женский</li>
    <li>СР - средний</li>
</ul>
Число:
<ul>
    <li>МН - множественное</li>
    <li>ЕД - единственное</li>
</ul>
Падеж:
<ul>
    <li>ИМ - именительный</li>
    <li>РД - родительный</li>
    <li>ДТ - дательный</li>
    <li>ВН - винительный</li>
    <li>ТВ - творительный</li>
    <li>ПР - предложный</li>
</ul>
<p>
Пример использования:<br>
Телефоны {=morphy {=concat {=OfferProperty 'TSVET' } ', '}, 'МР, ЕД, РД'} цвета<br>
В приведенном примере названия цветов будут преобразованы в форму родильного падежа, единственного числа, мужского рода.<br>
На выходе получится строка вида:<br>
Телефоны синего, золотого, красного цвета</p>
<p>Количество модификаторов от одного до трех. Если указано множественное число, модификатор рода указывать не следует.</p>
\"></span></li>
</ul>";

$MESS["SEO_META_EDIT_CHANGEFREQ"]="Частота изменения страницы";
$MESS["SEO_META_EDIT_CHANGEFREQ_ALWAYS"]="[always] Всегда";
$MESS["SEO_META_EDIT_CHANGEFREQ_HOURLY"]="[hourly] Раз в час";
$MESS["SEO_META_EDIT_CHANGEFREQ_DAILY"]="[daily] Раз в день";
$MESS["SEO_META_EDIT_CHANGEFREQ_WEEKLY"]="[weekly] Раз в неделю";
$MESS["SEO_META_EDIT_CHANGEFREQ_MONTHLY"]="[monthly] Раз в месяц";
$MESS["SEO_META_EDIT_CHANGEFREQ_YEARLY"]="[yearly] Раз в год";
$MESS["SEO_META_EDIT_CHANGEFREQ_NEVER"]="[never] Никогда";
$MESS["SEO_META_EDIT_CHANGEFREQ_NOTE"]="Используется для карты сайта. Вероятная частота изменения страницы. Это значение предоставляет общую информацию для поисковых систем и может не соответствовать точно частоте сканирования этой страницы.";

$MESS["SEO_META_FILTERS_default"] ="Из общих настроек";
$MESS["SEO_META_FILTERS_bitrix_chpu"] ="Стандартный фильтр с ЧПУ";
$MESS["SEO_META_FILTERS_bitrix_not_chpu"] ="Стандартный фильтр без ЧПУ";
$MESS["SEO_META_FILTERS_misshop_chpu"] ="Фильтр MissShop с ЧПУ";
$MESS["SEO_META_FILTERS_combox_chpu"] ="Kombox фильтр с ЧПУ";
//$MESS["SEO_META_FILTERS_combox_not_chpu"] ="Kombox фильтр без ЧПУ";
?>
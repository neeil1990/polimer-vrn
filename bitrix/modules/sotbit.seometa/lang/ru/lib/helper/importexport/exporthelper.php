<?
$MESS['SEO_META_CHPU_RUN_INIT'] = 'Выполнено...';
$MESS['SEO_META_CHPU_RUN_TITLE'] = 'Выгрузка "ЧПУ"';
$MESS['SEO_META_COND_RUN_TITLE'] = 'Выгрузка "Условий"';
$MESS['SEO_META_RUN_FINISH'] =  'Выгрузка выполнена';

$MESS['SEOMETA_ELEMENT_TITLE'] = 'Шаблон META TITLE';
$MESS['SEOMETA_ELEMENT_KEYWORDS'] = 'Шаблон META KEYWORDS';
$MESS['SEOMETA_ELEMENT_DESCRIPTION'] = 'Шаблон META DESCRIPTION';
$MESS['SEOMETA_ELEMENT_PAGE_TITLE'] = 'Заголовок раздела';
$MESS['SEOMETA_ELEMENT_BREADCRUMB_TITLE'] = 'Название страницы в хлебных крошках';
$MESS['SEOMETA_ELEMENT_TOP_DESC'] = 'Верхнее описание';
$MESS['SEOMETA_ELEMENT_BOTTOM_DESC'] = 'Нижнее описание';
$MESS['SEOMETA_ELEMENT_ADD_DESC'] = 'Дополнительное описание';
$MESS['SEOMETA_TEMPLATE_NEW_URL'] = 'Шаблон для новой ссылки';
$MESS['SEOMETA_SPACE_REPLACEMENT'] = 'Замена для пробела';
$MESS['SEOMETA_ELEMENT_FILE'] = 'Изображение';
$MESS['SEOMETA_CONNECTED_LINKS'] = 'Связанные ЧПУ';
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ACTIVE'] = "Поле отвечает за активность условия.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ACTIVE'] = "Возможные значения:\n
Y - условие будет загружено активным;
N - условие будет загружено неактивным";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_SEARCH'] = "Поле отвечает за вывод страницы условий в поиске по сайту.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_SEARCH'] = "Возможные значения:\n
Y - страница условия будет выводиться в поиске по сайту;
N - страница условия не будет выводиться в поиске по сайту";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_SORT'] = "Поле отвечает за сортировку условий и влияет на порядок применения условия.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_SORT'] = "Возможные значения: 100, 200 и т.д";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_NO_INDEX'] = "Поле отвечает за возможность закрытия страниц условий от индексации поисковых роботов.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_NO_INDEX'] = "Возможные значения:\n
Y - страница условия будет закрыта от индексации;
N - страница условия не будет закрыта от индексации";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_STRONG'] = "Поле отвечает за то, при каких выбранных значениях фильтра, будет отрабатывать условие.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_STRONG'] = "Возможные значения:\n
Y - условие будет выполняться только при соответствующих ему выбранных значениях фильтра;
N - условие будет выполняться при выбранных значениях фильтра, включающих это условие";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_NAME'] = "Поле отвечает за наименование условия.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_NAME'] = "Возможные значения: любое сочетание букв и символов, кроме пробельного символа";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_SITES'] = "Поле отвечает за то, на каких сайтах будет выполняться условие.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_SITES'] = "Возможные значения: перечисление LID сайтов через запятую (s1, s2, s3)";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_TYPE_OF_INFOBLOCK'] = "В поле указывается тип инфоблока, для которого применяется условие.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_TYPE_OF_INFOBLOCK'] = "Возможные значения: catalog, services и т.д.";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_INFOBLOCK'] = "Инфоблок, для которого применяется условие.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_INFOBLOCK'] = "Возможные значения: 1, 2 и т.д.";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_SECTIONS'] = "Поле отвечает за то, какие разделы относятся к условию.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_SECTIONS'] = "Возможные значения: перечисление ID разделов через запятую (1, 2, 3).";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_RULE'] = "[] - группа условий (открывающая и закрывающая скобка обязательна, можно комбинировать);\n
{AND} и {OR} - операторы объединяющие условия и(или) свойства конструкции;\n
CondIBProp:2:3:Equal:123:
*CondIBProp - указывает на использование свойства инфоблока,
*CondIBMinFilterProperty - указывает на использование минимального значения свойства инфоблока,
*CondIBMaxFilterProperty - указывает на использование максимального значения свойства инфоблока,
*2 - id инфоблока (можно использовать символьный код инфоблока),
*3 - id свойства инфоблока (можно испольозовать символьный код свойства),
*Equal - обязательное условие,
*123 - значение свойства (можно не заполнять, тогда обрабатываться будут все значения свойства).
\n
CondIBMinFilterPriceBASE - минимальное значение для цены.\n
CondIBMaxFilterPriceBASE - максимальное значение для цены.";

$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_RULE'] = "[[CondIBProp:2:3:Equal:123]{AND}[CondIBProp:2:2:Equal]] - пример с использованием двух групп условий, объединенных {AND}.\n
[CondIBMinFilterPriceBASE:Equal:749{AND}CondIBMinFilterPriceBASE:Equal:1100] - пример использования диапазона цен.\n
[CondIBProp:clothes:manufacturer:Equal:Blue{OR}CondIBProp:clothes:manufacturer:Equal:White] - пример использования символьных кодов и условия {OR}.\n
[[CondIBProp:2:7:Equal{AND}CondIBProp:2:33:Equal]{AND}[CondIBProp:2:5:Equal{AND}CondIBProp:2:33:Equal]] - пример с несколькими условиями.";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_FILTER_TYPE'] = "Поле отвечает за то, с каким типом фильтра работает условие";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_FILTER_TYPE'] = "Возможные значения:\n
default - Из общих настроек;
bitrix_chpu - Стандартный фильтр с ЧПУ;
bitrix_not_chpu - Стандартный фильтр без ЧПУ;
misshop_chpu - Фильтр MissShop с ЧПУ;
combox_chpu - Kombox фильтр с ЧПУ.";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_PRIORITY'] = "Поле используется для карты сайта. Устанавливает приоритетность URL относительно других URL на сайте.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_PRIORITY'] = "Возможные значения: от 0.1 до 1";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_CHANGEFREQ'] = "Поле используется для карты сайта. Вероятная частота изменения страницы.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_CHANGEFREQ'] = "Возможные значения:\n
always - Всегда;
hourly - Раз в час;
daily - Раз в день;
weekly - Раз в неделю;
monthly - Раз в месяц;
yearly - Раз в год;
never - Никогда;";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_TITLE'] = "Поле используется для изменения заголовка страницы из тега <title>.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_TITLE'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_KEYWORDS'] = "Поле используется для изменения мета тега <meta name='keywords'>.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_KEYWORDS'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_DESCRIPTION'] = "Поле используется для изменения мета тега <meta name='description'>.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_DESCRIPTION'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_PAGE_TITLE'] = "Поле используется для изменения заголовка страницы из тега <h1>.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_PAGE_TITLE'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_BREADCRUMB_TITLE'] = "Поле используется для изменения текста в хлебных крошках.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_BREADCRUMB_TITLE'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_TOP_DESC'] = "Поле используется для добавления верхнего описания и вывода его при помощи отложенных функций.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_TOP_DESC'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_BOTTOM_DESC'] = "Поле отвечает за добавление нижнего описания и вывода его при помощи отложенных функций.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_BOTTOM_DESC'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_ADD_DESC'] = "Поле отвечает за добавление дополнительного описания в произвольном месте.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_ADD_DESC'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_ELEMENT_FILE'] = "Поле отвечает за добавление изображения.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_ELEMENT_FILE'] = "Возможные значения: \n
Относительный путь, если изображение уже загружено на сайт, к примеру - /upload/seometa/35e/l035tu3ws7rdewnw9edq80pxmajdnu9n.jpg;
id изображения, если  если изображение уже загружено на сайт, к примеру - 4999;
url к изображению, к примеру - https://img.freepik.com/free-photo/a-cupcake-with-a-strawberry-on-top-and-a-strawberry-on-the-top_1340-35087.jpg.";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_TAG'] = "Поле отвечает за вывод тегов.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_TAG'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - {=this.Name}{=concat {=ProductProperty \"BRAND_REF\" } \", \"}";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_HIDE_IN_SECTION'] = "Поле отвечает за скрытие тегов принадлежащих тому разделу, в которым находится пользователь.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_HIDE_IN_SECTION'] = "Возможные значения:\n
Y - теги будут скрыты в разделе;
N - теги не будут скрыты в разделе";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_TEMPLATE_NEW_URL'] = "Поле отвечает за шаблон ссылки.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_TEMPLATE_NEW_URL'] = "Возможные значения: любое сочетание букв и символов.
Так же могут использоваться те же шаблоны, что и при создании условия через аминистративную часть.
К примеру - /catalog/#SECTION_CODE#/{#PROPERTY_CODE#_#PROPERTY_VALUE#:/:-}/";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_SPACE_REPLACEMENT'] = "Поле отвечает за то, какой символ будет заменять пробелы в значении свойства в ссылке.";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_SPACE_REPLACEMENT'] = "Возможные значения: символы типа - \"_-\"";
$MESS['SEOMETA_EXPORTHELPER_COND_DESCRIPTION_GENERATE_AJAX'] = "Поле отвечает за показ прогресс бара, при генерации условий";
$MESS['SEOMETA_EXPORTHELPER_COND_EXAMPLE_GENERATE_AJAX'] = "Возможные значения:\n
Y - показывать прогресс бар;
N - не показывать прогресс бар";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ACTIVE'] = "Поле отвечает за активность ЧПУ.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ACTIVE'] = "Возможные значения:\n
Y - ЧПУ будет загружено активным;
N - ЧПУ будет загружено неактивным";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_NAME'] = "Поле отвечает за наименование ЧПУ.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_NAME'] = "Возможные значения: любое сочетание букв и символов, кроме пробельного символа";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_SORT'] = "Поле отвечает за сортировку ЧПУ.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_SORT'] = "Возможные значения: 100, 200 и т.д";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_REAL_URL'] = "В поле записывается оригинальная ссылка.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_REAL_URL'] = "Возможные значения: /catalog/dresses/filter/sizes_clothes-is-a11f96c3b88d222460d9796067d28b0c/apply/";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_NEW_URL'] = "В поле записывается ссылка, которую вы хотите видеть при отработке модуля.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_NEW_URL'] = "Возможные значения: /catalog/dresses/filter/sizes_clothes-is-xs/";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_SITE'] = "В качестве значения привязки используется значение id сайта.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_SITE'] = "Возможные значения: s1, s2 (значение может быть только одно).";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_TITLE'] = "Поле используется для изменения заголовка страницы из тега <title>.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_TITLE'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_KEYWORDS'] = "Поле используется для изменения мета тега <meta name='keywords'>.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_KEYWORDS'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_DESCRIPTION'] = "Поле используется для изменения мета тега <meta name='description'>.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_DESCRIPTION'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_PAGE_TITLE'] = "Поле используется для изменения заголовка страницы из тега <h1>.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_PAGE_TITLE'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_BREADCRUMB_TITLE'] = "Поле используется для изменения текста в хлебных крошках.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_BREADCRUMB_TITLE'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_TOP_DESC'] = "Поле используется для добавления верхнего описания и вывода его при помощи отложенных функций.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_TOP_DESC'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_BOTTOM_DESC'] = "Поле отвечает за добавление нижнего описания и вывода его при помощи отложенных функций.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_BOTTOM_DESC'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_ADD_DESC'] = "Поле отвечает за добавление дополнительного описания в произвольном месте.";
$MESS['SEOMETA_EXPORTHELPER_CHPUEXAMPLE_ELEMENT_ADD_DESC'] = "Возможные значения: любое сочетание букв и символов.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_DESCRIPTION_ELEMENT_FILE'] = "Поле отвечает за добавление изображения.";
$MESS['SEOMETA_EXPORTHELPER_CHPU_EXAMPLE_ELEMENT_FILE'] = "Возможные значения: \n
Относительный путь, если изображение уже загружено на сайт, к примеру - /upload/seometa/35e/l035tu3ws7rdewnw9edq80pxmajdnu9n.jpg;
id изображения, если  если изображение уже загружено на сайт, к примеру - 4999;
url к изображению, к примеру - https://img.freepik.com/free-photo/a-cupcake-with-a-strawberry-on-top-and-a-strawberry-on-the-top_1340-35087.jpg.";
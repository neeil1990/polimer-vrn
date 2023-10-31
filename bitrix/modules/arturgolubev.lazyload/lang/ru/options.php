<?
$MESS["ARTURGOLUBEV_LAZYLOAD_DEMO_IS_EXPIRED"] = "Демо период-модуля закончился. Полную версию решения вы можете приобрести в <a href=\"http://marketplace.1c-bitrix.ru/solutions/arturgolubev.lazyload/\" target=\"_blank\">Marketplace 1c-Bitrix</a>";

$MESS["ARTURGOLUBEV_LAZYLOAD_CLEAR_CACHE"] = 'Настройки модуля изменены. Очистите <a target="_blank" href="/bitrix/admin/cache.php?lang=ru">Все страницы HTML кеша</a> и закройте это уведомление';


$MESS["ARTURGOLUBEV_LAZYLOAD_DOMD_NOT_FOUND"] = "Отсуствует расширение <a href=\"https://www.php.net/ru/dom\" target=\"_blank\">PHP-dom</a>. Для работы модуля оно необходимо, пожалуйста установите его (необходимо обратиться в хостинг или к вашему системному администратору).";
$MESS["ARTURGOLUBEV_LAZYLOAD_ENABLE"] = "Модуль включён:";
$MESS["ARTURGOLUBEV_LAZYLOAD_ENABLE_IFRAME"] = "Включить отложенную загрузку для iframe (включая YouTube):";
$MESS["ARTURGOLUBEV_LAZYLOAD_DISABLED_SITE"] = "Отключить для сайта";
$MESS["ARTURGOLUBEV_LAZYLOAD_JQUERY"] = "Подключить встроенную библиотеку JQuery:";
$MESS["ARTURGOLUBEV_LAZYLOAD_SELECTORS"] = "К каким картинкам применять:<br/> (Укажите в формате css селекторов, через запятую)";
$MESS["ARTURGOLUBEV_LAZYLOAD_SELECTORS_NOTE"] = "В данное поле вам нужно ввести css селекторы картинок, к которым нужно применить ленивую загрузку. Например: .bx-content img<br>Или укажите у картинок в коде сайта class=\"agllimage\" - к картинкам с таким классом алгоритм применится автоматически без дополнительного указания селекторов";

$MESS["ARTURGOLUBEV_LAZYLOAD_PAGE_EXCEPTION"] = "Страницы исключения:<br>(без доменного имени, каждый url в новой строке)";
$MESS["ARTURGOLUBEV_LAZYLOAD_PAGE_EXCEPTION_NOTE"] = "Каждую страницу указывайте в новой строке. В данной опции Вы можете указывать путь страницы вида /example/ или использовать маску /personal/*";

$MESS["ARTURGOLUBEV_LAZYLOAD_SETTING_DOP_INFO"] = "
<b style='color: #000; font-size:16px;'>Справка по настройкам</b>
<div style='color:#333;'>
<p><b>Подключить встроенную библиотеку JQuery</b> - для работы модуля необходима библиотека JQuery, если у вас на сайте не подключена данная библиотека, то активируйте галочку</p>
<p><b>К каким картинкам применять</b> - в этой настройке Вам нужно ввести <a href='http://htmlbook.ru/css/selector' target='_blank'>css селекторы</a> картинок, к которым нужно применить отложенную загрузку. <i>Например</i>, если вы хотите применить отложенную загрузку ко всем элементам внутри блока с классом class=\"bx-content\" нужно указать такой селектор: <b>.bx-content img</b> 
либо указать у картинок в коде сайта <b>class=\"agllimage\"</b> - к ним применится отложенная загрузка без дополнительного указания селекторов</p>

<p><b>Страницы исключения</b> - Необязательная настройка. В этой настройке можно указать страницы, на которых Не нужно применять отложенную загрузку. Указывать страницы нужно без доменного имени и каждый url в новой строке, например - <b>/example/</b>
<br>Парамерт поддерживает указание масок со *, к примеру указание в опции <b>/personal/*</b> отключит работу модуля на всех страницах, url которых начинается с /personal/</p>
</div>
";


$MESS["ARTURGOLUBEV_LAZYLOAD_MAIN_TAB_SET"] = "Настройки";
$MESS["ARTURGOLUBEV_LAZYLOAD_MAIN_TAB_TITLE_SET"] = "Общие настройки";



$MESS["ARTURGOLUBEV_LAZYLOAD_VISUAL_TAB_SET"] = "Визуальные настройки";

$MESS["ARTURGOLUBEV_LAZYLOAD_IMAGE_VIEW_EFFECT"] = "Отображение картинки после загрузки";
$MESS["ARTURGOLUBEV_LAZYLOAD_EFFECT_TYPE"] = "Плавное появление картинки:";
$MESS["ARTURGOLUBEV_LAZYLOAD_FADE_IN_ENABLE"] = "Включено";
$MESS["ARTURGOLUBEV_LAZYLOAD_FADE_IN_DISABLED"] = "Отключено";
$MESS["ARTURGOLUBEV_LAZYLOAD_EFFECT_SPEED"] = "Скорость анимации появления:";


$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADER_SETTING"] = "Анимация загрузки (прелоадер)";
$MESS["ARTURGOLUBEV_LAZYLOAD_DISABLE_PRELOADER"] = "Не показывать анимацию загрузки:";
$MESS["ARTURGOLUBEV_LAZYLOAD_SHOW_PRELOADER_FOR_ALL"] = "Показывать анимацию загрузки у картинок без размера<br> (только для &lt;img /&gt;):";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADER_IMAGE"] = "Выбор анимации загрузки:";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADER_IMAGE_SIZE"] = "Размер анимации загрузки:";


$MESS["ARTURGOLUBEV_LAZYLOAD_ADDITIONAL_FEATURES"] = "Дополнительные возможности";
$MESS["ARTURGOLUBEV_LAZYLOAD_AUTO_LOADING"] = "Загрузить картинки сразу после загрузки страницы:";
$MESS["ARTURGOLUBEV_LAZYLOAD_SIMPLE_DISABLE"] = "Отключено";
$MESS["ARTURGOLUBEV_LAZYLOAD_SIMPLE_ENABLE"] = "Включено";

$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING"] = "Предзагрузка картинок на Х пикселей вперёд:";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING_1"] = "Отключено";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING_100"] = "100px";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING_200"] = "200px";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING_300"] = "300px";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING_400"] = "400px";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING_500"] = "500px";
$MESS["ARTURGOLUBEV_LAZYLOAD_PRELOADING_NOTE"] = "<b>Предзагрузка картинок</b> помогает сделать отложенную загрузку менее заметной для обычного пользователя. При включенной опции загрузка картинок будет происходить чуть раньше, чем они попадут в зону видимости пользователя. Рекомендуемое значение 300px";

$MESS["ARTURGOLUBEV_LAZYLOAD_SYSTEM_SETTINGS"] = "Системные настройки";
$MESS["ARTURGOLUBEV_LAZYLOAD_DEBUG"] = "Отладка";

$MESS["ARTURGOLUBEV_LAZYLOAD_SITE_SETTING"] = "Настройки сайта";



/* help tab */
$MESS["ARTURGOLUBEV_LAZYLOAD_HELP_TAB_NAME"] = "Информация";
$MESS["ARTURGOLUBEV_LAZYLOAD_HELP_TAB_TITLE"] = "Полезная информация";

$MESS["ARTURGOLUBEV_LAZYLOAD_CARD_TEXT"] = "Карточка решения на Marketplace -";
$MESS["ARTURGOLUBEV_LAZYLOAD_CARD_TEXT_VALUE"] = "<a href='https://marketplace.1c-bitrix.ru/solutions/arturgolubev.lazyload/#tab-about-link' target='_blank'>ссылка</a>";

$MESS["ARTURGOLUBEV_LAZYLOAD_INSTALL_TEXT"] = "Информация по установке -";
$MESS["ARTURGOLUBEV_LAZYLOAD_INSTALL_TEXT_VALUE"] = "<a href='https://marketplace.1c-bitrix.ru/solutions/arturgolubev.lazyload/#tab-install-link' target='_blank'>ссылка</a>";

$MESS["ARTURGOLUBEV_LAZYLOAD_INSTALL_VIDEO_TEXT"] = "Видео-инструкция -";
$MESS["ARTURGOLUBEV_LAZYLOAD_INSTALL_VIDEO_TEXT_VALUE"] = "<a href='http://arturgolubev.ru/learning/course/index.php?COURSE_ID=5&INDEX=Y' target='_blank'>ссылка</a>";

$MESS["ARTURGOLUBEV_LAZYLOAD_FAQ_TEXT"] = "Часто задаваемые вопросы по данному модулю -";
$MESS["ARTURGOLUBEV_LAZYLOAD_FAQ_TEXT_VALUE"] = "<a href='http://arturgolubev.ru/learning/course/index.php?COURSE_ID=5&INDEX=Y' target='_blank'>ссылка</a>";

$MESS["ARTURGOLUBEV_LAZYLOAD_FAQ_MAIN_TEXT"] = "Вопросы по оплате, активации модуля и т.п. -";
$MESS["ARTURGOLUBEV_LAZYLOAD_FAQ_MAIN_TEXT_VALUE"] = "<a href='http://arturgolubev.ru/learning/course/?COURSE_ID=1&INDEX=Y' target='_blank'>ссылка</a>";
?>
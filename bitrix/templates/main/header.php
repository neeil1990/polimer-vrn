<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Application,
    Bitrix\Main\Context,
    Bitrix\Main\Request,
    Bitrix\Main\Page\Asset,
    Bitrix\Main\Localization\Loc;


if(defined("ERROR_404") && ERROR_404){ $error_404 = true; }

$pages   = $APPLICATION -> GetCurDir();
$pages   = explode('/', $pages);
$is_main = (($APPLICATION -> GetCurDir() == '/') || ($APPLICATION -> GetCurDir() == SITE_DIR)) && !$error_404;
$noh1    = $pages[1] == 'personal' || $pages[1] == 'price' || ($pages[1] == 'catalog' && $pages[2]);
?><!DOCTYPE html>
<html lang="ru">
	<head>
        <script src='https://www.google.com/recaptcha/api.js' async defer></script>

		<title><?$APPLICATION->ShowTitle()?></title>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
		<!-- JavaScript -->
		<script src="//cdn.jsdelivr.net/alertifyjs/1.10.0/alertify.min.js"></script>
		<!-- CSS -->
		<link rel="stylesheet" href="//cdn.jsdelivr.net/alertifyjs/1.10.0/css/alertify.min.css"/>
		<!-- Default theme -->
		<link rel="stylesheet" href="//cdn.jsdelivr.net/alertifyjs/1.10.0/css/themes/default.min.css"/>

		<?
		$APPLICATION->ShowHead();


		Asset::getInstance()->addString('<link rel="icon" href="https://polimer-vrn.ru/favicon.ico" type="image/x-icon">');
		Asset::getInstance()->addString('<link rel="shortcut icon" href="https://polimer-vrn.ru/favicon.svg" type="image/svg+xml">');
		Asset::getInstance()->addString('<link rel="icon" href="https://polimer-vrn.ru/favicon1.svg" type="image/svg+xml">');
		Asset::getInstance()->addString('<meta name="msapplication-tooltip" content="«Полимер»">');
		Asset::getInstance()->addString('<meta name="msapplication-TileImage" content="/tileicon.png">');
		Asset::getInstance()->addString('<meta name="msapplication-TileColor" content="#014075">');
		Asset::getInstance()->addString('<meta http-equiv="X-UA-Compatible" content="IE=edge">');
		Asset::getInstance()->addString('<meta name="viewport" content="width=device-width, initial-scale=1">');
		Asset::getInstance()->addString('<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800&amp;subset=cyrillic,latin-ext" rel="stylesheet">');
		Asset::getInstance()->addString('<link href="https://fonts.googleapis.com/css?family=Fira+Sans:300,300i,400,400i,500,500i,700,700i&amp;subset=cyrillic-ext,latin-ext" rel="stylesheet">');

		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/vendor.min.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/slick.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/Font-Awesome/css/font-awesome.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/fonts.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/ion.rangeSlider.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/ion.rangeSlider.skinModern.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/jquery-ui.min.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/jquery.fancybox.min.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/social-likes_classic.css');
		Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/css/wickedpicker.min.css');

        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/js/lightslider/css/lightslider.css');
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/js/lightGallery/css/lightgallery.css');


		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/jquery.min.js');
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/lightslider/js/lightslider.js');
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/lightGallery/js/lightgallery.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/vendor.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/slick.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/jquery-ui.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/datepicker-ru.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/ion.rangeSlider.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/jquery.fancybox.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/jquery.kinetic.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/jquery.sticky.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/swipe.js');


		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/common.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/social-likes.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/wickedpicker.min.js');
		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/jquery.maskedinput.min.js');

		Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/js/jquery.bpopup.min.js');

		Asset::getInstance()->addJs('/js/readmore.js');
		Asset::getInstance()->addJs('/js/function.js');
		?>



<meta name="yandex-verification" content="2094627454c95762" />
<meta name="yandex-verification" content="f47bc301df09d1f5" />


<!-- Yandex.Metrika counter -->
<script type="text/javascript" >
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym(29722775, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true,
        ecommerce:"dataLayer"
   });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/29722775" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->

	</head>
   	<body>
   		<?$APPLICATION->ShowPanel()?>
		<!-- [if lt IE 10]>
			<p class="browsehappy"> Ваш браузер <strong>устарел</strong>.
			Пожалуйста <a href="http://browsehappy.com/">обновите</a> его.</p>
		<![endif]-->
      	<div class="container">
			<header>
				<div class="hmobile">
					<div class="wr cl">
						<a href="/" class="hmobile__logo">
							<img src="<?=SITE_TEMPLATE_PATH?>/img/logo_svg.svg" alt="Полимер" width="165" />
						</a>
						<div class="hmobile__phone">+7 (473) 250-22-33</div>
						<a href="/search/index.php" class="hmobile__search"></a>
						<a href="#" class="menu__trigger">
							<span class="i1"></span>
							<span class="i2"></span>
							<span class="i3"></span>
						</a>
						<?$APPLICATION->IncludeComponent("bitrix:sale.basket.basket.line", "basket.small.mobile", Array(
							"HIDE_ON_BASKET_PAGES" => "Y",	// Не показывать на страницах корзины и оформления заказа
							"PATH_TO_BASKET" => SITE_DIR."personal/cart/",	// Страница корзины
							"PATH_TO_ORDER" => SITE_DIR."personal/order/",	// Страница оформления заказа
							"PATH_TO_PERSONAL" => SITE_DIR."personal/",	// Страница персонального раздела
							"PATH_TO_PROFILE" => SITE_DIR."personal/",	// Страница профиля
							"PATH_TO_REGISTER" => SITE_DIR."login/",	// Страница регистрации
							"POSITION_FIXED" => "N",	// Отображать корзину поверх шаблона
							"SHOW_AUTHOR" => "N",	// Добавить возможность авторизации
							"SHOW_EMPTY_VALUES" => "Y",	// Выводить нулевые значения в пустой корзине
							"SHOW_NUM_PRODUCTS" => "Y",	// Показывать количество товаров
							"SHOW_PERSONAL_LINK" => "Y",	// Отображать персональный раздел
							"SHOW_PRODUCTS" => "N",	// Показывать список товаров
							"SHOW_TOTAL_PRICE" => "Y",	// Показывать общую сумму по товарам
						),
							false
						);?>

					</div>
				</div><!--end::hmobile-->
				<div class="mm__wrap">
					<div class="wr cl">
						<div class="cl">

							<?$APPLICATION->IncludeComponent(
								"bitrix:search.form",
								"search-form-mobile",
								array(
									"PAGE" => "#SITE_DIR#search/",
									"USE_SUGGEST" => "Y",
									"COMPONENT_TEMPLATE" => "search-form"
								),
								false
							);?>


							<a href="/personal/order/make/" class="mm__account">Личный кабинет</a>
						</div><!--end::cl-->

						<?$APPLICATION->IncludeComponent("bitrix:menu", "mobile-cat-menu-one", Array(
							"ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
							"CHILD_MENU_TYPE" => "left",	// Тип меню для остальных уровней
							"DELAY" => "N",	// Откладывать выполнение шаблона меню
							"MAX_LEVEL" => "1",	// Уровень вложенности меню
							"MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
								0 => "",
							),
							"MENU_CACHE_TIME" => "3600",	// Время кеширования (сек.)
							"MENU_CACHE_TYPE" => "N",	// Тип кеширования
							"MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
							"ROOT_MENU_TYPE" => "mobile-categories",	// Тип меню для первого уровня
							"USE_EXT" => "N",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
						),
							false
						);?>

						<div class="cl">
							<a href="/sale/" class="mm__action">Акции</a>
							<a href="/calc/" class="mm__calculation">Бесплатный расчет</a>
						</div>
						<div class="mm__phone">
                            <a href="tel:<?=tel(tplvar('phone_top_mobile'))?>" class="phone_engineer"><?= tplvar('phone_top_mobile', true);?></a>
                        </div>
						<div class="cl">
							<div class="mm__timework header__timework">
								<div class="line cl">
									<div class="days">ПН-ПТ</div>
									<div class="hours"><?= tplvar('week', true);?></div>
								</div>
								<div class="line cl">
									<div class="days">CБ</div>
									<div class="hours"><?= tplvar('saturday', true);?></div>
								</div>
								<div class="line cl">
									<div class="days">ВС</div>
									<div class="hours"><span class="weekend"><?= tplvar('sun', true);?></span></div>
								</div>
							</div><!--end::mm__timework-->
							<a href="#" class="header__letter show-popup" data-id="mailus">Написать письмо</a>
							<a href="/contacts/" class="header__adress">Адреса магазинов</a>
						</div><!--end::cl-->
						<?$APPLICATION->IncludeComponent(
							"bitrix:menu",
							"top-mobile",
							Array(
								"ALLOW_MULTI_SELECT" => "N",
								"CHILD_MENU_TYPE" => "top",
								"DELAY" => "N",
								"MAX_LEVEL" => "1",
								"MENU_CACHE_GET_VARS" => array(""),
								"MENU_CACHE_TIME" => "3600",
								"MENU_CACHE_TYPE" => "AUTO",
								"MENU_CACHE_USE_GROUPS" => "Y",
								"ROOT_MENU_TYPE" => "top",
								"USE_EXT" => "N"
							)
						); // mm__menu?>
					</div><!--end::wr-->
				</div><!--end::mm__wrap-->
				<div class="header__top">
					<div class="wr cl">
					<?$APPLICATION->IncludeComponent("bitrix:menu", "top-multilevel", Array(
						"ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
							"CHILD_MENU_TYPE" => "left",	// Тип меню для остальных уровней
							"DELAY" => "N",	// Откладывать выполнение шаблона меню
							"MAX_LEVEL" => "2",	// Уровень вложенности меню
							"MENU_CACHE_GET_VARS" => "",	// Значимые переменные запроса
							"MENU_CACHE_TIME" => "3600",	// Время кеширования (сек.)
							"MENU_CACHE_TYPE" => "A",	// Тип кеширования
							"MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
							"ROOT_MENU_TYPE" => "top",	// Тип меню для первого уровня
							"USE_EXT" => "N",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
							"COMPONENT_TEMPLATE" => "horizontal_multilevel",
							"MENU_THEME" => "site"
						),
						false
					); //menu__top ?>

                    <?
                    $cityContactParams = 0;
                    if($_COOKIE['city'] == 'Лиски')
                        $cityContactParams = 1;
                    elseif ($_COOKIE['city'] == 'Старый Оскол')
                        $cityContactParams = 2;
                    ?>
                    <a href="/contacts/?city=<?=$cityContactParams?>" class="header__adress_without_icon">Адреса магазинов</a>
                    <?
                    $APPLICATION->IncludeFile("/include/location.php", Array(), Array(
                        "MODE"      => "html",
                        "NAME"      => "Редактирование включаемой области раздела",
                        "TEMPLATE"  => ""
                    ));
                    ?>
                    <!--<a href="#" class="header__letter show-popup" data-id="mailus">Написать письмо</a>-->
					</div><!--end::wr-->
				</div><!--end::header__top-->
				<div class="header__main">
					<div class="wr cl">
						<a href="/" class="header__logo">
							<img src="<?=SITE_TEMPLATE_PATH?>/img/logo_svg.svg" alt="Полимер" width="206">
						</a>
						<div class="header__phone">
                            <?
                            switch ($_COOKIE['city']) {
                                case "Лиски":
                                    $APPLICATION->IncludeFile("/include/phones/top/phone_lsk.php", Array(), Array(
                                        "MODE"      => "html",
                                        "NAME"      => "Редактирование включаемой области раздела",
                                        "TEMPLATE"  => ""
                                    ));
                                    break;
                                case "Старый Оскол":
                                    $APPLICATION->IncludeFile("/include/phones/top/phone_osk.php", Array(), Array(
                                        "MODE"      => "html",
                                        "NAME"      => "Редактирование включаемой области раздела",
                                        "TEMPLATE"  => ""
                                    ));
                                    break;
                                default:
                                    $APPLICATION->IncludeFile("/include/phones/top/phone_vrn.php", Array(), Array(
                                        "MODE"      => "html",
                                        "NAME"      => "Редактирование включаемой области раздела",
                                        "TEMPLATE"  => ""
                                    ));
                            }
                            ?>
						</div>
                        <?
                        switch ($_COOKIE['city']) {
                            case "Лиски":
                                $APPLICATION->IncludeFile("/include/modes/mode_lsk.php", Array(), Array(
                                    "MODE"      => "html",
                                    "NAME"      => "Редактирование включаемой области раздела",
                                    "TEMPLATE"  => ""
                                ));
                                break;
                            case "Старый Оскол":
                                $APPLICATION->IncludeFile("/include/modes/mode_osk.php", Array(), Array(
                                    "MODE"      => "html",
                                    "NAME"      => "Редактирование включаемой области раздела",
                                    "TEMPLATE"  => ""
                                ));
                                break;
                            default:
                                $APPLICATION->IncludeFile("/include/modes/mode_vrn.php", Array(), Array(
                                    "MODE"      => "html",
                                    "NAME"      => "Редактирование включаемой области раздела",
                                    "TEMPLATE"  => ""
                                ));
                        }
                        ?>
						<a href="/calc/" class="header__calculation">Бесплатный<br>расчет</a>
						<a href="/price/" class="header__price">Прайс-листы</a>
					</div><!--end::wr-->
				</div><!--end::header__main-->

                <div class="header__bottom"> <!-- fixed -->
                    <div class="wr cl">

                        <a href="/" class="logo">
                            <img src="<?=SITE_TEMPLATE_PATH?>/img/logo_svg.svg" alt="Полимер">
                        </a>

                        <div class="header__catalog cl">
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:catalog.section.list",
                                "top-menu-catalog",
                                array(
                                    "ADD_SECTIONS_CHAIN" => "Y",
                                    "CACHE_GROUPS" => "Y",
                                    "CACHE_TIME" => "36000000",
                                    "CACHE_TYPE" => "A",
                                    "COUNT_ELEMENTS" => "Y",
                                    "IBLOCK_ID" => "21",
                                    "IBLOCK_TYPE" => "1c_catalog",
                                    "SECTION_CODE" => "",
                                    "SECTION_FIELDS" => array(
                                        0 => "",
                                        1 => "",
                                    ),
                                    "SECTION_ID" => $_REQUEST["SECTION_ID"],
                                    "SECTION_URL" => "",
                                    "SECTION_USER_FIELDS" => array(
                                        0 => "",
                                        1 => "",
                                    ),
                                    "SHOW_PARENT_NAME" => "Y",
                                    "TOP_DEPTH" => "3",
                                    "VIEW_MODE" => "LINE",
                                    "COMPONENT_TEMPLATE" => "top-menu-catalog"
                                ),
                                false
                            );?>
                        </div>

                        <div class="f_phone">
                            <?
                            switch ($_COOKIE['city']) {
                                case "Лиски":
                                    $APPLICATION->IncludeFile("/include/phones/stickly/phone_lsk.php", Array(), Array(
                                        "MODE"      => "html",
                                        "NAME"      => "Редактирование включаемой области раздела",
                                        "TEMPLATE"  => ""
                                    ));
                                    break;
                                case "Старый Оскол":
                                    $APPLICATION->IncludeFile("/include/phones/stickly/phone_osk.php", Array(), Array(
                                        "MODE"      => "html",
                                        "NAME"      => "Редактирование включаемой области раздела",
                                        "TEMPLATE"  => ""
                                    ));
                                    break;
                                default:
                                    $APPLICATION->IncludeFile("/include/phones/stickly/phone_vrn.php", Array(), Array(
                                        "MODE"      => "html",
                                        "NAME"      => "Редактирование включаемой области раздела",
                                        "TEMPLATE"  => ""
                                    ));
                            }
                            ?>
                        </div>

                        <div class="header__action">

                            <?$APPLICATION->IncludeComponent(
                                "bitrix:news.list",
                                "last-sale",
                                array(
                                    "ACTIVE_DATE_FORMAT" => "d.m.Y",
                                    "ADD_SECTIONS_CHAIN" => "N",
                                    "AJAX_MODE" => "N",
                                    "AJAX_OPTION_ADDITIONAL" => "",
                                    "AJAX_OPTION_HISTORY" => "N",
                                    "AJAX_OPTION_JUMP" => "N",
                                    "AJAX_OPTION_STYLE" => "Y",
                                    "CACHE_FILTER" => "N",
                                    "CACHE_GROUPS" => "Y",
                                    "CACHE_TIME" => "36000000",
                                    "CACHE_TYPE" => "A",
                                    "CHECK_DATES" => "Y",
                                    "DETAIL_URL" => "",
                                    "DISPLAY_BOTTOM_PAGER" => "Y",
                                    "DISPLAY_DATE" => "Y",
                                    "DISPLAY_NAME" => "Y",
                                    "DISPLAY_PICTURE" => "Y",
                                    "DISPLAY_PREVIEW_TEXT" => "Y",
                                    "DISPLAY_TOP_PAGER" => "N",
                                    "FIELD_CODE" => array(
                                        0 => "",
                                        1 => "",
                                    ),
                                    "FILTER_NAME" => "",
                                    "HIDE_LINK_WHEN_NO_DETAIL" => "N",
                                    "IBLOCK_ID" => "3",
                                    "IBLOCK_TYPE" => "news",
                                    "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                                    "INCLUDE_SUBSECTIONS" => "Y",
                                    "MESSAGE_404" => "",
                                    "NEWS_COUNT" => "5",
                                    "PAGER_BASE_LINK_ENABLE" => "N",
                                    "PAGER_DESC_NUMBERING" => "N",
                                    "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                                    "PAGER_SHOW_ALL" => "N",
                                    "PAGER_SHOW_ALWAYS" => "N",
                                    "PAGER_TEMPLATE" => ".default",
                                    "PAGER_TITLE" => "Акции",
                                    "PARENT_SECTION" => "",
                                    "PARENT_SECTION_CODE" => "",
                                    "PREVIEW_TRUNCATE_LEN" => "",
                                    "PROPERTY_CODE" => array(
                                        0 => "",
                                        1 => "",
                                    ),
                                    "SET_BROWSER_TITLE" => "N",
                                    "SET_LAST_MODIFIED" => "N",
                                    "SET_META_DESCRIPTION" => "N",
                                    "SET_META_KEYWORDS" => "N",
                                    "SET_STATUS_404" => "N",
                                    "SET_TITLE" => "N",
                                    "SHOW_404" => "N",
                                    "SORT_BY1" => "ACTIVE_FROM",
                                    "SORT_BY2" => "SORT",
                                    "SORT_ORDER1" => "DESC",
                                    "SORT_ORDER2" => "ASC",
                                    "STRICT_SECTION_CHECK" => "N",
                                    "COMPONENT_TEMPLATE" => "last-sale"
                                ),
                                false
                            );?>

                        </div>

                        <?$APPLICATION->IncludeComponent("prime:search.title", "search.title", Array(
                            "CATEGORY_0" => array(	// Ограничение области поиска
                                0 => "iblock_1c_catalog",
                            ),
                            "CATEGORY_0_TITLE" => "Каталог",	// Название категории
                            "CATEGORY_0_iblock_1c_catalog" => array(	// Искать в информационных блоках типа "iblock_1c_catalog"
                                0 => "21",
                            ),
                            "CHECK_DATES" => "N",	// Искать только в активных по дате документах
                            "CONTAINER_ID" => "title-search",	// ID контейнера, по ширине которого будут выводиться результаты
                            "INPUT_ID" => "title-search-input",	// ID строки ввода поискового запроса
                            "NUM_CATEGORIES" => "1",	// Количество категорий поиска
                            "ORDER" => "date",	// Сортировка результатов
                            "PAGE" => "#SITE_DIR#search/",	// Страница выдачи результатов поиска (доступен макрос #SITE_DIR#)
                            "SHOW_INPUT" => "Y",	// Показывать форму ввода поискового запроса
                            "SHOW_OTHERS" => "N",	// Показывать категорию "прочее"
                            "TOP_COUNT" => "15",	// Количество результатов в каждой категории
                            "USE_LANGUAGE_GUESS" => "N",	// Включить автоопределение раскладки клавиатуры
                        ),
                            false
                        );?>

                        <a href="/personal/orders-list.php" class="header__account">Личный кабинет</a>

                        <?$APPLICATION->IncludeComponent("bitrix:sale.basket.basket.line", "basket.small", Array(
                            "HIDE_ON_BASKET_PAGES" => "Y",	// Не показывать на страницах корзины и оформления заказа
                            "PATH_TO_BASKET" => SITE_DIR."personal/cart/",	// Страница корзины
                            "PATH_TO_ORDER" => SITE_DIR."personal/order/",	// Страница оформления заказа
                            "PATH_TO_PERSONAL" => SITE_DIR."personal/",	// Страница персонального раздела
                            "PATH_TO_PROFILE" => SITE_DIR."personal/",	// Страница профиля
                            "PATH_TO_REGISTER" => SITE_DIR."login/",	// Страница регистрации
                            "POSITION_FIXED" => "N",	// Отображать корзину поверх шаблона
                            "SHOW_AUTHOR" => "N",	// Добавить возможность авторизации
                            "SHOW_EMPTY_VALUES" => "Y",	// Выводить нулевые значения в пустой корзине
                            "SHOW_NUM_PRODUCTS" => "Y",	// Показывать количество товаров
                            "SHOW_PERSONAL_LINK" => "Y",	// Отображать персональный раздел
                            "SHOW_PRODUCTS" => "N",	// Показывать список товаров
                            "SHOW_TOTAL_PRICE" => "Y",	// Показывать общую сумму по товарам
                        ),
                            false
                        );?>

                    </div><!--end::wr-->
                </div><!--end::header__bottom-->

			</header>
			<?if(!$is_main){?>

			<?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "breadcrumb", Array(
				"PATH" => "",
				"SITE_ID" => SITE_ID,
				"START_FROM" => "0",
			),
				false
			);?>

	         <?}?>
			<?if($is_main){?>
	        <div class="mp__banners cl">

				<?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"slider-home",
	array(
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => array(
			0 => "CODE",
			1 => "NAME",
			2 => "PREVIEW_TEXT",
			3 => "PREVIEW_PICTURE",
			4 => "DETAIL_PICTURE",
			5 => "",
		),
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "5",
		"IBLOCK_TYPE" => "slider",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"INCLUDE_SUBSECTIONS" => "Y",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "SORT",
		"SORT_BY2" => "SORT",
		"SORT_ORDER1" => "ASC",
		"SORT_ORDER2" => "ASC",
		"COMPONENT_TEMPLATE" => "slider-home",
		"STRICT_SECTION_CHECK" => "N"
	),
	false
);?>

				<?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"baners-home",
	array(
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => array(
			0 => "CODE",
			1 => "NAME",
			2 => "PREVIEW_TEXT",
			3 => "PREVIEW_PICTURE",
			4 => "DETAIL_PICTURE",
			5 => "",
		),
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "6",
		"IBLOCK_TYPE" => "baners",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"INCLUDE_SUBSECTIONS" => "Y",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "2",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_BY2" => "SORT",
		"SORT_ORDER1" => "DESC",
		"SORT_ORDER2" => "ASC",
		"COMPONENT_TEMPLATE" => "baners-home"
	),
	false
);?>


	        </div><!--end::mp__banners-->
	        <?}?>
         	<div class="wr">
         		<?
                if(!$is_main && $pages[1] !== 'basket' && !($pages[1] == 'catalog' && $pages[3])){?>
	            <div class="page_content">
	            	<?if(!$noh1){?>

	               	<?$APPLICATION->IncludeComponent("bitrix:menu", "section", Array(
						"ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
							"CHILD_MENU_TYPE" => "top",	// Тип меню для остальных уровней
							"DELAY" => "N",	// Откладывать выполнение шаблона меню
							"MAX_LEVEL" => "1",	// Уровень вложенности меню
							"MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
								0 => "",
							),
							"MENU_CACHE_TIME" => "3600",	// Время кеширования (сек.)
							"MENU_CACHE_TYPE" => "AUTO",	// Тип кеширования
							"MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
							"ROOT_MENU_TYPE" => "left",	// Тип меню для первого уровня
							"USE_EXT" => "N",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
						),
						false
					); // mm__menu?>
	               	<?}?>
	            <?}?>

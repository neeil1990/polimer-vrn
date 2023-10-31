<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->IncludeComponent("bitrix:sale.basket.basket.line", "basket.small", Array(
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
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule( 'iblock' );
CModule::IncludeModule( 'catalog' );
CModule::IncludeModule( 'sale' );

switch ($_REQUEST['FORM_ID']) {
    case "mailus":
        $APPLICATION->IncludeComponent(
            "nbrains:main.feedback",
            "write-mail",
            array(
                "EMAIL_TO" => "sale@polimer-vrn.ru",
                "EVENT_MESSAGE_ID" => array(
                    0 => "92",
                ),
                "IBLOCK_ID" => "14",
                "IBLOCK_TYPE" => "feedback",
                "OK_TEXT" => "Спасибо, ваше сообщение принято.",
                "PROPERTY_CODE" => array(
                    0 => "FIO",
                    1 => "PHONE",
                    2 => "EMAIL",
                    3 => "DESC",
                    4 => "RULE",
                ),
                "USE_CAPTCHA" => "Y",
                "COMPONENT_TEMPLATE" => "write-mail",
                "AJAX_MODE" => "Y"
            ),
            false
        );
        break;
    case "oneclick":

        $arResult = getUrlProd($_REQUEST['URL']);
        if(CFile::GetPath($arResult['MORE_PHOTO']['VALUE'][0])){
            $img = CFile::GetPath($arResult['MORE_PHOTO']['VALUE'][0]);
        }else{
            $img = CFile::GetPath($arResult['DETAIL_PICTURE']);
        }
        $APPLICATION->IncludeComponent(
            "nbrains:main.feedback",
            "buy-one-click",
            array(
                "EMAIL_TO" => "sale@polimer-vrn.ru",
                "EVENT_MESSAGE_ID" => array(
                    0 => "90",
                ),
                "IBLOCK_ID" => "15",
                "IBLOCK_TYPE" => "feedback",
                "OK_TEXT" => "Спасибо, ваше сообщение принято.",
                "PROPERTY_CODE" => array(
                    0 => "FIO",
                    1 => "PRICE",
                    2 => "STORE",
                    3 => "IMG_PRODUCT",
                    4 => "PHONE",
                    5 => "EMAIL",
                    6 => "RULE",
                    7 => "PRODUCT",
                    8 => "LINK_PRODUCT",
                ),
                "USE_CAPTCHA" => "Y",
                "COMPONENT_TEMPLATE" => "buy-one-click",
                "PRODUCT" => array(
                    "NAME" => $arResult["NAME"],
                    "LINK" => $arResult["DETAIL_PAGE_URL"],
                    "IMG" => $img,
                    "PRICE" => priceDiscount($arResult["ID"]),
                ),
                "AJAX_MODE" => "Y"
            ),
            false
        );
        break;
    case "specialist":

        $APPLICATION->IncludeComponent(
            "nbrains:main.feedback",
            "free-consultant",
            array(
                "EMAIL_TO" => "sale@polimer-vrn.ru",
                "EVENT_MESSAGE_ID" => array(
                    0 => "91",
                ),
                "IBLOCK_ID" => "16",
                "IBLOCK_TYPE" => "feedback",
                "OK_TEXT" => "Спасибо, ваше сообщение принято.",
                "PROPERTY_CODE" => array(
                    0 => "FIO",
                    1 => "PHONE",
                    2 => "EMAIL",
                    3 => "RULE",
                    4 => "PRODUCT",
                    5 => "LINK_PRODUCT",
                ),
                "USE_CAPTCHA" => "Y",
                "COMPONENT_TEMPLATE" => "free-consultant",
                "AJAX_MODE" => "Y"
            ),
            false
        );
        break;
    case "order-product":

        $APPLICATION->IncludeComponent(
            "nbrains:main.feedback",
            "order-product",
            array(
                "EMAIL_TO" => "sale@polimer-vrn.ru, sv6@list.ru",
                "EVENT_MESSAGE_ID" => array(
                    0 => "103",
                ),
                "IBLOCK_ID" => "18",
                "IBLOCK_TYPE" => "feedback",
                "OK_TEXT" => "Спасибо, ваше сообщение принято.",
                "PROPERTY_CODE" => array(
                    0 => "EMAIL",
                    1 => "PHONE",
                    2 => "FIO",
                    3 => "PRODUCT",
                    4 => "LINK_PRODUCT",
                    5 => "RULE",
                ),
                "USE_CAPTCHA" => "Y",
                "COMPONENT_TEMPLATE" => "order-product",
                "COMPOSITE_FRAME_MODE" => "A",
                "COMPOSITE_FRAME_TYPE" => "AUTO",
                "URL" => $_REQUEST['URL'],
                "AJAX_MODE" => "Y"
            ),
            false
        );
        break;
    case "reviews":

        $APPLICATION->IncludeComponent(
            "nbrains:main.feedback",
            "reviews",
            array(
                "EMAIL_TO" => "sale@polimer-vrn.ru",
                "EVENT_MESSAGE_ID" => array(
                    0 => "113",
                ),
                "IBLOCK_ID" => "26",
                "IBLOCK_TYPE" => "feedback",
                "OK_TEXT" => "Спасибо, ваше сообщение принято.",
                "PROPERTY_CODE" => array(
                    0 => "FIO",
                    1 => "SITY",
                    2 => "MESSAGE",
                    3 => "RULE",
                ),
                "USE_CAPTCHA" => "Y",
                "COMPONENT_TEMPLATE" => "reviews",
                "AJAX_MODE" => "Y"
            ),
            false
        );
        break;
    case "oneclickcart":

        $APPLICATION->IncludeComponent(
            "nbrains:main.feedback",
            "buy-one-click-cart",
            array(
                "EMAIL_TO" => "sale@polimer-vrn.ru",
                "EVENT_MESSAGE_ID" => array(
                    0 => "104",
                ),
                "IBLOCK_ID" => "23",
                "IBLOCK_TYPE" => "feedback",
                "OK_TEXT" => "Спасибо, ваше сообщение принято.",
                "PROPERTY_CODE" => array(
                    0 => "FIO",
                    1 => "EMAIL",
                    2 => "PHONE",
                    3 => "STORE",
					4 => "RULE",
                    5 => "PRODUCT_CART",
                ),
                "USE_CAPTCHA" => "Y",
                "COMPONENT_TEMPLATE" => "buy-one-click-cart",
                "AJAX_MODE" => "Y"
            ),
            false
        );
        break;
}
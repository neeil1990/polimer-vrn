<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Авторизация");
?>

<?$APPLICATION->IncludeComponent(
    "bitrix:main.profile",
    "personal-user-security",
    array(
        "USER_PROPERTY_NAME" => "",
        "SET_TITLE" => "Y",
        "AJAX_MODE" => "Y",
        "USER_PROPERTY" => array(
        ),
        "SEND_INFO" => "N",
        "CHECK_RIGHTS" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "AJAX_OPTION_HISTORY" => "N",
        "COMPONENT_TEMPLATE" => "personal-user-data"
    ),
    false
);?>



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
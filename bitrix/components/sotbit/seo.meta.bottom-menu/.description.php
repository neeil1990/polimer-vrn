<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentDescription = array(
    "NAME" => GetMessage("SM_NAME"),
    "DESCRIPTION" => GetMessage("SM_DESCRIPTION"),
    "ICON" => "/images/icon.png",
    "PATH" => array(
        "ID" => GetMessage("MAIN_GROUP_NAME_SOTBIT"),
        "CHILD" => array(
            "ID" => "sotbit.seo.meta",
            "NAME" => GetMessage("MAIN_MENU_NAME_SOTBIT")
        )
    ),
);
?>
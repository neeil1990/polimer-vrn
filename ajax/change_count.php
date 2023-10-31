<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog") && isset($_GET["id"]) && !empty($_GET["id"]) && isset($_GET["quant"]) && !empty($_GET["quant"]))
{
    $arFields = array(
        "QUANTITY" => $_GET["quant"]
    );
    if(CSaleBasket::Update($_GET["id"], $arFields))
    {
        echo "success";
    }
    else
    {
        echo "error";
    }
}
else
{
    echo "error";
}?>
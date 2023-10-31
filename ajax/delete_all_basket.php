<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog"))
{
    if (CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID(), false))
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



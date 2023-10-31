<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog") && isset($_GET['cupon']) && !empty($_GET['cupon'])){
    $cupon = CCatalogDiscount::SetCoupon(trim(strip_tags($_GET['cupon'])));
    print $cupon;
}else{
    print false;
}

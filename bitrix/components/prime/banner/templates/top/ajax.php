<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!CModule::IncludeModule("prime.smartbanners"))
    return false;

Nb_class::statUpdate($_REQUEST['ID'],$_REQUEST['FIELD_BANNER']);

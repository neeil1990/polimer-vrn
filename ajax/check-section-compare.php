<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>

<?php
$error = json_encode(array("error" => false));
if($_SESSION["CATALOG_COMPARE_LIST"] and $_REQUEST['action'] == "CHECK_SECTION_COMPARE"){
    foreach($_SESSION["CATALOG_COMPARE_LIST"] as $compare){
        foreach($compare['ITEMS'] as $item){
            if($_REQUEST['sec_id'] != $item['IBLOCK_SECTION_ID']){
                $error = json_encode(array("error" => true));
                break;
            }
        }
    }
}
echo $error;
?>
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;


if($arResult['SECTION']['ID'] == 0 AND $APPLICATION->GetCurPage() != '/catalog/'){
    define("PATH_TO_404", "/404.php");
    @define("ERROR_404","Y");
}
?>
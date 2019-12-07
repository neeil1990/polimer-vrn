<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

//-------------------------------------------------------------------------------------------
$langPrefix = 'SOTBIT_SCHEMA_BLOCK_THING_';
$tabControl->AddEditField($key ? $key."[additionalType]" : "additionalType", GetMessage($langPrefix.'additionalType'), false, false,
    ( isset($arFields["additionalType"]) & !empty($arFields["additionalType"]) ? $arFields["additionalType"] : "" ));
$tabControl->AddEditField($key ? $key."[alternateName]" : "alternateName", GetMessage($langPrefix.'alternateName'), false, false,
    ( isset($arFields["alternateName"]) & !empty($arFields["alternateName"]) ? $arFields["alternateName"] : "" ));
$tabControl->AddEditField($key ? $key."[description]" : "description", GetMessage($langPrefix.'description'), false, false,
    ( isset($arFields["description"]) & !empty($arFields["description"]) ? $arFields["description"] : "" ));
$tabControl->AddEditField($key ? $key."[image]" : "image", ( $key == "localbusiness" ? substr_replace(GetMessage($langPrefix.'image'), "*: ", -2) : GetMessage($langPrefix.'image') ), ( $key == "localbusiness" ? true : false), false,
    ( isset($arFields["image"]) & !empty($arFields["image"]) ? $arFields["image"] : "" ));
$tabControl->AddEditField($key ? $key."[name]" : "name", GetMessage($langPrefix.'name'), true, false,
    ( isset($arFields["name"]) & !empty($arFields["name"]) ? $arFields["name"] : "" ));
$tabControl->AddEditField($key ? $key."[sameAs]" : "sameAs", GetMessage($langPrefix.'sameAs'), false, false,
    ( isset($arFields["sameAs"]) & !empty($arFields["sameAs"]) ? $arFields["sameAs"] : "" ));
$tabControl->AddEditField($key ? $key."[url]" : "url", GetMessage($langPrefix.'url'), false, false,
    ( isset($arFields["url"]) & !empty($arFields["url"]) ? $arFields["url"] : "" ));
$tabControl->AddEditField($key ? $key."[potentialAction]" : "potentialAction", GetMessage($langPrefix.'potentialAction'), false, false,
    ( isset($arFields["potentialAction"]) & !empty($arFields["potentialAction"]) ? $arFields["potentialAction"] : "" ));
$tabControl->AddEditField($key ? $key."[mainEntityOfPage]" : "mainEntityOfPage", GetMessage($langPrefix.'mainEntityOfPage'), false, false,
    ( isset($arFields["mainEntityOfPage"]) & !empty($arFields["mainEntityOfPage"]) ? $arFields["mainEntityOfPage"] : "" ));
$tabControl->AddEditField($key ? $key."[disambiguatingDescription]" : "disambiguatingDescription", GetMessage($langPrefix.'disambiguatingDescription'), false, false,
    ( isset($arFields["disambiguatingDescription"]) & !empty($arFields["disambiguatingDescription"]) ? $arFields["disambiguatingDescription"] : "" ));
$tabControl->AddEditField($key ? $key."[identifier]" : "identifier", GetMessage($langPrefix.'identifier'), false, false,
    ( isset($arFields["identifier"]) & !empty($arFields["identifier"]) ? $arFields["identifier"] : "" ));
?>
<?
$langPrefix = 'SOTBIT_SCHEMA_CONTACTPOINT_';
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

//-------------------------------------------------------------------------------------------
$tabControl->AddEditField($key ? $key."[contactOption]" : "contactOption", GetMessage($langPrefix.'contactOption'), false, false,
    ( isset($arFields["contactOption"]) & !empty($arFields["contactOption"]) ? $arFields["contactOption"] : "" ));
$tabControl->AddEditField($key ? $key."[contactType]" : "contactType", GetMessage($langPrefix.'contactType'), true, false,
    ( isset($arFields["contactType"]) & !empty($arFields["contactType"]) ? $arFields["contactType"] : "" ));
$tabControl->AddEditField($key ? $key."[email]" : "email", GetMessage($langPrefix.'email'), true, false,
    ( isset($arFields["email"]) & !empty($arFields["email"]) ? $arFields["email"] : "" ));
$tabControl->AddEditField($key ? $key."[faxNumber]" : "faxNumber", GetMessage($langPrefix.'faxNumber'), false, false,
    ( isset($arFields["faxNumber"]) & !empty($arFields["faxNumber"]) ? $arFields["faxNumber"] : "" ));
$tabControl->AddEditField($key ? $key."[hoursAvailable]" : "hoursAvailable", GetMessage($langPrefix.'hoursAvailable'), false, false,
    ( isset($arFields["hoursAvailable"]) & !empty($arFields["hoursAvailable"]) ? $arFields["hoursAvailable"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "productSupported",
    array(
        '' => '...',
        'product' => 'Product',
    ),
    $key, $langPrefix, $arFields);


$tabControl->AddEditField($key ? $key."[telephone]" : "telephone", GetMessage($langPrefix.'telephone'), true, false,
    ( isset($arFields["telephone"]) & !empty($arFields["telephone"]) ? $arFields["telephone"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "areaServed",
    array(
        '' => '...',
        'place' => 'Place'
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[availableLanguage]" : "availableLanguage", GetMessage($langPrefix.'availableLanguage'), false, false,
    ( isset($arFields["availableLanguage"]) & !empty($arFields["availableLanguage"]) ? $arFields["availableLanguage"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "serviceArea",
    array(
        '' => '...',
        'place' => 'Place'
    ),
    $key, $langPrefix, $arFields);

require (dirname(__FILE__).'/thing.php');
?>
<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$langPrefix = 'SOTBIT_SCHEMA_RATING_';

//----------------------------------------------------------------------------------
if(isset($_POST['key']) || isset($_POST['entity']))
    $key = $_POST['key'] == 'ENTITIES' ? $_POST['entity'] : $_POST['key'];
if(!isset($key) || empty($key)) {
    if(isset($arFields["ENTITIES"]) && !empty($arFields["ENTITIES"]))
    {
        $key = strtolower($arFields["ENTITIES"]["@type"]);
        $arFields = $arFields["ENTITIES"];
    }
    else
        $key = strtolower($arFields["@type"]);
}

$langPrefix = 'SOTBIT_SCHEMA_BLOCK_RATING_';
$tabControl->AddEditField($key ? $key."[bestRating]" : "bestRating", Loc::getmessage($langPrefix.'bestRating'), false, false,
    ( isset($arFields["bestRating"]) & !empty($arFields["bestRating"]) ? $arFields["bestRating"] : "" ));
$tabControl->AddEditField($key ? $key."[ratingValue]" : "ratingValue", Loc::getmessage($langPrefix.'ratingValue'), true, false,
    ( isset($arFields["ratingValue"]) & !empty($arFields["ratingValue"]) ? $arFields["ratingValue"] : "" ));
$tabControl->AddEditField($key ? $key."[worstRating]" : "worstRating", Loc::getmessage($langPrefix.'worstRating'), false, false,
    ( isset($arFields["worstRating"]) & !empty($arFields["worstRating"]) ? $arFields["worstRating"] : "" ));

if(isset($arFields["author"]["@type"]) && !empty($arFields["author"]["@type"]))
{
    $entitiName = strtolower($arFields["author"]["@type"]);
}
else
{
    $entitiName = "";
}
$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "author",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields, $entitiName);
?>
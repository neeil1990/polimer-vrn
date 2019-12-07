<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$langPrefix = 'SOTBIT_SCHEMA_PLACE_';

//-------------------------------------------------------------------------------------------
$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "containsPlace",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "events",
    array(
        '' => '...',
        'events' => 'Events',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("faxNumber", Loc::getMessage($langPrefix.'faxNumber'), false, false,
    ( isset($arFields["faxNumber"]) & !empty($arFields["faxNumber"]) ? $arFields["faxNumber"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "geo",
    array(
        '' => '...',
        'geocoordinates' => 'GeoCoordinates',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isAccessibleForFree",
    array(
        'N' => 'N',
        'Y' => 'Y',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("maps", Loc::getMessage($langPrefix.'maps'), false, false,
    ( isset($arFields["maps"]) & !empty($arFields["maps"]) ? $arFields["maps"] : "" ));
$tabControl->AddEditField("interactionCount", Loc::getMessage($langPrefix.'interactionCount'), false, false,
    ( isset($arFields["interactionCount"]) & !empty($arFields["interactionCount"]) ? $arFields["interactionCount"] : "" ));
$tabControl->AddEditField("isicV4", Loc::getMessage($langPrefix.'isicV4'), false, false,
    ( isset($arFields["isicV4"]) & !empty($arFields["isicV4"]) ? $arFields["isicV4"] : "" ));
$tabControl->AddEditField("logo", Loc::getMessage($langPrefix.'logo'), false, false,
    ( isset($arFields["logo"]) & !empty($arFields["logo"]) ? $arFields["logo"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "openingHoursSpecification",
    array(
        '' => '...',
        'openinghoursspecification' => 'OpeningHoursSpecification',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("photo", Loc::getMessage($langPrefix.'photo'), false, false,
    ( isset($arFields["photo"]) & !empty($arFields["photo"]) ? $arFields["photo"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "review",
    array(
        '' => '...',
        'review' => 'Review',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("telephone", Loc::getMessage($langPrefix.'telephone'), false, false,
    ( isset($arFields["telephone"]) & !empty($arFields["telephone"]) ? $arFields["telephone"] : "" ));
$tabControl->AddEditField("hasMap", Loc::getMessage($langPrefix.'hasMap'), false, false,
    ( isset($arFields["hasMap"]) & !empty($arFields["hasMap"]) ? $arFields["hasMap"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "aggregateRating",
    array(
        '' => '...',
        'review' => 'Review',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("globalLocationNumber", Loc::getMessage($langPrefix.'globalLocationNumber'), false, false,
    ( isset($arFields["globalLocationNumber"]) & !empty($arFields["globalLocationNumber"]) ? $arFields["globalLocationNumber"] : "" ));

$tabControl->AddEditField("additionalProperty", Loc::getMessage($langPrefix.'additionalProperty'), false, false,
    ( isset($arFields["additionalProperty"]) & !empty($arFields["additionalProperty"]) ? $arFields["additionalProperty"] : "" ));

if(isset($arFields["address"]["@type"]) && !empty($arFields["address"]["@type"]))
{
    $entitiName = strtolower($arFields["address"]["@type"]);
}
else
{
    $entitiName = "";
}

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "address",
    array(
        '' => '...',
//        'text' => 'Text',
        'postaladdress' => 'PostalAddress',
    ),
    $key, $langPrefix, $arFields, $entitiName);

$tabControl->AddEditField("branchCode", Loc::getMessage($langPrefix.'branchCode'), false, false,
    ( isset($arFields["branchCode"]) & !empty($arFields["branchCode"]) ? $arFields["branchCode"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "containedInPlace",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "specialOpeningHoursSpecification",
    array(
        '' => '...',
        'openinghoursspecification' => 'OpeningHoursSpecification',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("amenityFeature", Loc::getMessage($langPrefix.'amenityFeature'), false, false,
    ( isset($arFields["amenityFeature"]) & !empty($arFields["amenityFeature"]) ? $arFields["amenityFeature"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "smokingAllowed",
    array(
        'N' => 'N',
        'Y' => 'Y',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("maximumAttendeeCapacity", Loc::getMessage($langPrefix.'maximumAttendeeCapacity'), false, false,
    ( isset($arFields["maximumAttendeeCapacity"]) & !empty($arFields["maximumAttendeeCapacity"]) ? $arFields["maximumAttendeeCapacity"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "publicAccess",
    array(
        'N' => 'N',
        'Y' => 'Y',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "containedIn",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField("map", Loc::getMessage($langPrefix.'map'), false, false,
    ( isset($arFields["map"]) & !empty($arFields["map"]) ? $arFields["map"] : "" ));
$tabControl->AddEditField("photos", Loc::getMessage($langPrefix.'photos'), false, false,
    ( isset($arFields["photos"]) & !empty($arFields["photos"]) ? $arFields["photos"] : "" ));

require (dirname(__FILE__).'/thing.php');

?>
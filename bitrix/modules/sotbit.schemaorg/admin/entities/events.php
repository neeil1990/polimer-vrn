<?
$arFields = \SotbitSchema::getArFields();
//-----------------------------------------------------------------------------------------------------------------------------------------------
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

if(preg_match("/\[itemListElement\]\[\d*\]\[/iu", $key) == 0)
    $key = SotbitSchema::checkKey($key);

$aTabs = array(
    array(
        "DIV" => $_POST['entity'],
        "ICON" => "main_user_edit",
    ),);
$langPrefix = 'SOTBIT_SCHEMA_EVENTS_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Events"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->BeginCustomField("remove-this_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row tr-btn-remove-this-entity">
        <td colspan="2" style="text-align: left">
            <input type="button" value="<?=GetMessage($langPrefix."MINUS");?>" class="remove-this-entity"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("remove-this_BLOCK");
$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "organizer",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "contributor",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "attendee",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[doorTime]" : "doorTime", GetMessage($langPrefix.'doorTime'), false, false,
    ( isset($arFields["doorTime"]) & !empty($arFields["doorTime"]) ? $arFields["doorTime"] : "" ));
$tabControl->AddEditField($key ? $key."[eventStatus]" : "eventStatus", GetMessage($langPrefix.'eventStatus'), false, false,
    ( isset($arFields["eventStatus"]) & !empty($arFields["eventStatus"]) ? $arFields["eventStatus"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isAccessibleForFree",
    array(
        'N' => 'N',
        'Y' => 'Y',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "performer",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[StartDate]" : "StartDate", GetMessage($langPrefix.'StartDate'), true, false,
    ( isset($arFields["StartDate"]) & !empty($arFields["StartDate"]) ? $arFields["StartDate"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "subEvent",
    array(
        '' => '...',
        'event' => 'Event',
    ),
    $key, $langPrefix, $arFields, true);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "superEvent",
    array(
        '' => '...',
        'event' => 'Event',
    ),
    $key, $langPrefix, $arFields, true);

$tabControl->AddEditField($key ? $key."[typicalAgeRange]" : "typicalAgeRange", GetMessage($langPrefix.'typicalAgeRange'), false, false,
    ( isset($arFields["typicalAgeRange"]) & !empty($arFields["typicalAgeRange"]) ? $arFields["typicalAgeRange"] : "" ));

//$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "recordedIn",
//    array(
//        '' => '...',
//        'creativework' => 'CreativeWork',
//    ),
//    $key, $langPrefix, $arFields);
//
//$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "workPerformed",
//    array(
//        '' => '...',
//        'creativework' => 'CreativeWork',
//    ),
//    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "aggregateRating",
    array(
        '' => '...',
        'aggregaterating' => 'AggregateRating',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "review",
    array(
        '' => '...',
        'review' => 'Review',
    ),
    $key, $langPrefix, $arFields, true);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "location",
    array(
        '' => '...',
//        'text' => 'Text',
        'place' => 'Place',
//        'postaladdress' => 'PostalAddress',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "offers",
    array(
        '' => '...',
        'offer' => 'Offer',
    ),
    $key, $langPrefix, $arFields, true);

//$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "workFeatured",
//    array(
//        '' => '...',
//        'creativework' => 'CreativeWork',
//    ),
//    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "actor",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields, true);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "director",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[inLanguage]" : "inLanguage", GetMessage($langPrefix.'inLanguage'), false, false,
    ( isset($arFields["inLanguage"]) & !empty($arFields["inLanguage"]) ? $arFields["inLanguage"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "sponsor",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields, true);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "translator",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "composer",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "funder",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "about",
    array(
        '' => '...',
        'thing' => 'Thing',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "audience",
    array(
        '' => '...',
        'audience' => 'Audience',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[maximumAttendeeCapacity]" : "maximumAttendeeCapacity", GetMessage($langPrefix.'maximumAttendeeCapacity'), false, false,
    ( isset($arFields["maximumAttendeeCapacity"]) & !empty($arFields["maximumAttendeeCapacity"]) ? $arFields["maximumAttendeeCapacity"] : "" ));
$tabControl->AddEditField($key ? $key."[remainingAttendeeCapacity]" : "remainingAttendeeCapacity", GetMessage($langPrefix.'remainingAttendeeCapacity'), false, false,
    ( isset($arFields["remainingAttendeeCapacity"]) & !empty($arFields["remainingAttendeeCapacity"]) ? $arFields["remainingAttendeeCapacity"] : "" ));

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>
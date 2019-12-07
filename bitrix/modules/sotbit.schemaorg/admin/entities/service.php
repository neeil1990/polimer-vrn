<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

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

$aTabs = array(
    array(
        "DIV" => $_POST['entity'],
        "ICON" => "main_user_edit",
    ),);
$langPrefix = 'SOTBIT_SCHEMA_SERVICE_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Service"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->AddEditField($key ? $key."[award]" : "award", Loc::getMessage($langPrefix.'award'), false, false,
    ( isset($arFields["award"]) & !empty($arFields["award"]) ? $arFields["award"] : "" ));
$tabControl->AddEditField($key ? $key."[availableChannel]" : "availableChannel", Loc::getMessage($langPrefix.'availableChannel'), false, false,
    ( isset($arFields["availableChannel"]) & !empty($arFields["availableChannel"]) ? $arFields["availableChannel"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "brand",
    array(
        '' => '...',
        'brand' => 'Brand',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "hoursAvailable",
    array(
        '' => '...',
        'openinghoursspecification' => 'OpeningHoursSpecification',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[logo]" : "logo", Loc::getMessage($langPrefix.'logo'), false, false,
    ( isset($arFields["logo"]) & !empty($arFields["logo"]) ? $arFields["logo"] : "" ));
$tabControl->AddEditField($key ? $key."[serviceType]" : "serviceType", Loc::getMessage($langPrefix.'serviceType'), false, false,
    ( isset($arFields["serviceType"]) & !empty($arFields["serviceType"]) ? $arFields["serviceType"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "category",
    array(
        '' => '...',
        'text' => 'Text',
        'thing' => 'Thing',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "provider",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "aggregateRating",
    array(
        '' => '...',
        'aggregaterating' => 'AggregateRating',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "serviceOutput",
    array(
        '' => '...',
        'thing' => 'Thing',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "review",
    array(
        '' => '...',
        'review' => 'Review',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[hasOfferCatalog]" : "hasOfferCatalog", Loc::getMessage($langPrefix.'hasOfferCatalog'), false, false,
    ( isset($arFields["hasOfferCatalog"]) & !empty($arFields["hasOfferCatalog"]) ? $arFields["hasOfferCatalog"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "areaServed",
    array(
        '' => '...',
        'text' => 'Text',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "offers",
    array(
        '' => '...',
        'offer' => 'Offer',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[providerMobility]" : "providerMobility", Loc::getMessage($langPrefix.'providerMobility'), false, false,
    ( isset($arFields["providerMobility"]) & !empty($arFields["providerMobility"]) ? $arFields["providerMobility"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isRelatedTo",
    array(
        '' => '...',
        'service' => 'Service',
        'product' => 'Product',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "isSimilarTo",
    array(
        '' => '...',
        'service' => 'Service',
        'product' => 'Product',
    ),
    $key, $langPrefix, $arFields);

//$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "audience",
//    array(
//        '' => '...',
//        'audience' => 'Audience',
//    ),
//    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "broker",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "produces",
    array(
        '' => '...',
        'thing' => 'Thing',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "serviceArea",
    array(
        '' => '...',
        'text' => 'Text',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

//$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "serviceAudience",
//    array(
//        '' => '...',
//        'audience' => 'Audience',
//    ),
//    $key, $langPrefix, $arFields);

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>
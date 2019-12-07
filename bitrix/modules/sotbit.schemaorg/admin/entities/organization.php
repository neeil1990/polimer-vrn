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
$langPrefix = 'SOTBIT_SCHEMA_ORGANIZATION_';
$tabControl = new CAdminForm("tabControl", $aTabs);

$tabControl->AddSection($langPrefix."SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Organization"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->AddEditField($key ? $key."[award]" : "award", GetMessage($langPrefix.'award'), false, false,
    ( isset($arFields["award"]) & !empty($arFields["award"]) ? $arFields["award"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "brand",
    array(
        '' => '...',
        'brand' => 'Brand',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "contactPoint",
    array(
        '' => '...',
        'contactpoint' => 'ContactPoint',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "department",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[duns]" : "duns", GetMessage($langPrefix.'duns'), false, false,
    ( isset($arFields["duns"]) & !empty($arFields["duns"]) ? $arFields["duns"] : "" ));
$tabControl->AddEditField($key ? $key."[email]" : "email", GetMessage($langPrefix.'email'), false, false,
    ( isset($arFields["email"]) & !empty($arFields["email"]) ? $arFields["email"] : "" ));
$tabControl->AddEditField($key ? $key."[employee]" : "employee", GetMessage($langPrefix.'employee'), false, false,
    ( isset($arFields["employee"]) & !empty($arFields["employee"]) ? $arFields["employee"] : "" ));

//$tabControl->AddEditField($key ? $key."[event]" : "event", GetMessage($langPrefix.'event'), false, false,
//    ( isset($arFields["event"]) & !empty($arFields["event"]) ? $arFields["event"] : "" ));
$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "event",
    array(
        '' => '...',
        'event' => 'Event',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[faxNumber]" : "faxNumber", GetMessage($langPrefix.'faxNumber'), false, false,
    ( isset($arFields["faxNumber"]) & !empty($arFields["faxNumber"]) ? $arFields["faxNumber"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "founder",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[foundingDate]" : "foundingDate", GetMessage($langPrefix.'foundingDate'), false, false,
    ( isset($arFields["foundingDate"]) & !empty($arFields["foundingDate"]) ? $arFields["foundingDate"] : "" ));
$tabControl->AddEditField($key ? $key."[dissolutionDate]" : "dissolutionDate", GetMessage($langPrefix.'dissolutionDate'), false, false,
    ( isset($arFields["dissolutionDate"]) & !empty($arFields["dissolutionDate"]) ? $arFields["dissolutionDate"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "hasPOS",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[interactionCount]" : "interactionCount", GetMessage($langPrefix.'interactionCount'), false, false,
    ( isset($arFields["interactionCount"]) & !empty($arFields["interactionCount"]) ? $arFields["interactionCount"] : "" ));
$tabControl->AddEditField($key ? $key."[isicV4]" : "isicV4", GetMessage($langPrefix.'isicV4'), false, false,
    ( isset($arFields["isicV4"]) & !empty($arFields["isicV4"]) ? $arFields["isicV4"] : "" ));
$tabControl->AddEditField($key ? $key."[legalName]" : "legalName", GetMessage($langPrefix.'legalName'), false, false,
    ( isset($arFields["legalName"]) & !empty($arFields["legalName"]) ? $arFields["legalName"] : "" ));
$tabControl->AddEditField($key ? $key."[logo]" : "logo", GetMessage($langPrefix.'logo'), false, false,
    ( isset($arFields["logo"]) & !empty($arFields["logo"]) ? $arFields["logo"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "makesOffer",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[naics]" : "naics", GetMessage($langPrefix.'naics'), false, false,
    ( isset($arFields["naics"]) & !empty($arFields["naics"]) ? $arFields["naics"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "owns",
    array(
        '' => '...',
        'product' => 'Product',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "reviews",
    array(
        '' => '...',
        'reviews' => 'Reviews',
    ),
    $key, $langPrefix, $arFields);

//Demand

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "subOrganization",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[taxID]" : "taxID", GetMessage($langPrefix.'taxID'), false, false,
    ( isset($arFields["taxID"]) & !empty($arFields["taxID"]) ? $arFields["taxID"] : "" ));
$tabControl->AddEditField($key ? $key."[telephone]" : "telephone", GetMessage($langPrefix.'telephone'), false, false,
    ( isset($arFields["telephone"]) & !empty($arFields["telephone"]) ? $arFields["telephone"] : "" ));
$tabControl->AddEditField($key ? $key."[vatID]" : "vatID", GetMessage($langPrefix.'vatID'), false, false,
    ( isset($arFields["vatID"]) & !empty($arFields["vatID"]) ? $arFields["vatID"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "member",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "memberOf",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "foundingLocation",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "aggregateRating",
    array(
        '' => '...',
        'aggregaterating' => 'AggregateRating',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "parentOrganization",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[globalLocationNumber]" : "globalLocationNumber", GetMessage($langPrefix.'globalLocationNumber'), false, false,
    ( isset($arFields["globalLocationNumber"]) & !empty($arFields["globalLocationNumber"]) ? $arFields["globalLocationNumber"] : "" ));
$tabControl->AddEditField($key ? $key."[numberOfEmployees]" : "numberOfEmployees", GetMessage($langPrefix.'numberOfEmployees'), false, false,
    ( isset($arFields["numberOfEmployees"]) & !empty($arFields["numberOfEmployees"]) ? $arFields["numberOfEmployees"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "review",
    array(
        '' => '...',
        'review' => 'Review',
    ),
    $key, $langPrefix, $arFields);

//OfferCatalog

$tabControl->AddEditField($key ? $key."[address]" : "address", GetMessage($langPrefix.'address'), false, false,
    ( isset($arFields["address"]) & !empty($arFields["address"]) ? $arFields["address"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "areaServed",
    array(
        '' => '...',
        'text' => 'Text',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "location",
    array(
        '' => '...',
        'text' => 'Text',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

//$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "offeredBy",
//    array(
//        '' => '...',
//        'person' => 'Person',
//        'offer' => 'Offer',
//    ),
//    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "sponsor",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[leiCode]" : "leiCode", GetMessage($langPrefix.'leiCode'), false, false,
    ( isset($arFields["leiCode"]) & !empty($arFields["leiCode"]) ? $arFields["leiCode"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "funder",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);


$tabControl->AddEditField($key ? $key."[publishingPrinciples]" : "publishingPrinciples", GetMessage($langPrefix.'publishingPrinciples'), false, false,
    ( isset($arFields["publishingPrinciples"]) & !empty($arFields["publishingPrinciples"]) ? $arFields["publishingPrinciples"] : "" ));
$tabControl->AddEditField($key ? $key."[awards]" : "awards", GetMessage($langPrefix.'awards'), false, false,
    ( isset($arFields["awards"]) & !empty($arFields["awards"]) ? $arFields["awards"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "members",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "serviceArea",
    array(
        '' => '...',
        'place' => 'Place',
        'geoshape' => 'GeoShape',
    ),
    $key, $langPrefix, $arFields);

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>
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
$langPrefix = 'SOTBIT_SCHEMA_PERSON_';
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->AddSection("OG_COMMON_SETTINGS", getMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Person"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
$tabControl->AddEditField($key ? $key."[additionalName]" : "additionalName", Loc::getMessage($langPrefix.'additionalName'), false, false,
    ( isset($arFields["additionalName"]) & !empty($arFields["additionalName"]) ? $arFields["additionalName"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "affiliation",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[alumniOf]" : "alumniOf", Loc::getMessage($langPrefix.'alumniOf'), false, false,
    ( isset($arFields["alumniOf"]) & !empty($arFields["alumniOf"]) ? $arFields["alumniOf"] : "" ));
$tabControl->AddEditField($key ? $key."[award]" : "award", Loc::getMessage($langPrefix.'award'), false, false,
    ( isset($arFields["award"]) & !empty($arFields["award"]) ? $arFields["award"] : "" ));
$tabControl->AddEditField($key ? $key."[birthDate]" : "birthDate", Loc::getMessage($langPrefix.'birthDate'), false, false,
    ( isset($arFields["birthDate"]) & !empty($arFields["birthDate"]) ? $arFields["birthDate"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "brand",
    array(
        '' => '...',
        'brand' => 'Brand',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "children",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "colleague",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "contactPoint",
    array(
        '' => '...',
        'contactpoint' => 'ContactPoint',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[deathDate]" : "deathDate", Loc::getMessage($langPrefix.'deathDate'), false, false,
    ( isset($arFields["deathDate"]) & !empty($arFields["deathDate"]) ? $arFields["deathDate"] : "" ));
$tabControl->AddEditField($key ? $key."[duns]" : "duns", Loc::getMessage($langPrefix.'duns'), false, false,
    ( isset($arFields["duns"]) & !empty($arFields["duns"]) ? $arFields["duns"] : "" ));
$tabControl->AddEditField($key ? $key."[email]" : "email", Loc::getMessage($langPrefix.'email'), false, false,
    ( isset($arFields["email"]) & !empty($arFields["email"]) ? $arFields["email"] : "" ));
$tabControl->AddEditField($key ? $key."[familyName]" : "familyName", Loc::getMessage($langPrefix.'familyName'), false, false,
    ( isset($arFields["familyName"]) & !empty($arFields["familyName"]) ? $arFields["familyName"] : "" ));
$tabControl->AddEditField($key ? $key."[faxNumber]" : "faxNumber", Loc::getMessage($langPrefix.'faxNumber'), false, false,
    ( isset($arFields["faxNumber"]) & !empty($arFields["faxNumber"]) ? $arFields["faxNumber"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "follows",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[givenName]" : "givenName", Loc::getMessage($langPrefix.'givenName'), false, false,
    ( isset($arFields["givenName"]) & !empty($arFields["givenName"]) ? $arFields["givenName"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "hasPOS",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "homeLocation",
    array(
        '' => '...',
        'contactpoint' => 'ContactPoint',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[honorificPrefix]" : "honorificPrefix", Loc::getMessage($langPrefix.'honorificPrefix'), false, false,
    ( isset($arFields["honorificPrefix"]) & !empty($arFields["honorificPrefix"]) ? $arFields["honorificPrefix"] : "" ));
$tabControl->AddEditField($key ? $key."[honorificSuffix]" : "honorificSuffix", Loc::getMessage($langPrefix.'honorificSuffix'), false, false,
    ( isset($arFields["honorificSuffix"]) & !empty($arFields["honorificSuffix"]) ? $arFields["honorificSuffix"] : "" ));
$tabControl->AddEditField($key ? $key."[interactionCount]" : "interactionCount", Loc::getMessage($langPrefix.'interactionCount'), false, false,
    ( isset($arFields["interactionCount"]) & !empty($arFields["interactionCount"]) ? $arFields["interactionCount"] : "" ));
$tabControl->AddEditField($key ? $key."[isicV4]" : "isicV4", Loc::getMessage($langPrefix.'isicV4'), false, false,
    ( isset($arFields["isicV4"]) & !empty($arFields["isicV4"]) ? $arFields["isicV4"] : "" ));
$tabControl->AddEditField($key ? $key."[jobTitle]" : "jobTitle", Loc::getMessage($langPrefix.'jobTitle'), false, false,
    ( isset($arFields["jobTitle"]) & !empty($arFields["jobTitle"]) ? $arFields["jobTitle"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "knows",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "makesOffer",
    array(
        '' => '...',
        'offer' => 'Offer',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[naics]" : "naics", Loc::getMessage($langPrefix.'naics'), false, false,
    ( isset($arFields["naics"]) & !empty($arFields["naics"]) ? $arFields["naics"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "nationality",
    array(
        '' => '...',
        'country' => 'Country',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "owns",
    array(
        '' => '...',
        'product' => 'Product',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "parent",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "performerIn",
    array(
        '' => '...',
        'event' => 'Event',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "relatedTo",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

//Demand

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "sibling",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "spouse",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[taxID]" : "taxID", Loc::getMessage($langPrefix.'taxID'), false, false,
    ( isset($arFields["taxID"]) & !empty($arFields["taxID"]) ? $arFields["taxID"] : "" ));
$tabControl->AddEditField($key ? $key."[telephone]" : "telephone", Loc::getMessage($langPrefix.'telephone'), false, false,
    ( isset($arFields["telephone"]) & !empty($arFields["telephone"]) ? $arFields["telephone"] : "" ));
$tabControl->AddEditField($key ? $key."[vatID]" : "vatID", Loc::getMessage($langPrefix.'vatID'), false, false,
    ( isset($arFields["vatID"]) & !empty($arFields["vatID"]) ? $arFields["vatID"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "workLocation",
    array(
        '' => '...',
        'contactpoint' => 'ContactPoint',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "worksFor",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "memberOf",
    array(
        '' => '...',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "netWorth",
    array(
        '' => '...',
        'pricespecification' => 'PriceSpecification',
        'monetaryamount' => 'MonetaryAmount',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[weight]" : "weight", Loc::getMessage($langPrefix.'weight'), false, false,
    ( isset($arFields["weight"]) & !empty($arFields["weight"]) ? $arFields["weight"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "birthPlace",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "deathPlace",
    array(
        '' => '...',
        'place' => 'Place',
    ),
    $key, $langPrefix, $arFields);

$tabControl->AddEditField($key ? $key."[globalLocationNumber]" : "globalLocationNumber", Loc::getMessage($langPrefix.'globalLocationNumber'), false, false,
    ( isset($arFields["globalLocationNumber"]) & !empty($arFields["globalLocationNumber"]) ? $arFields["globalLocationNumber"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "hasOfferCatalog",
    array(
        '' => '...',
        'offercatalog' => 'OfferCatalog',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "address",
    array(
        '' => '...',
        'text' => 'Text',
        'postaladdress' => 'PostalAddress',
    ),
    $key, $langPrefix, $arFields, $entitiName);

$tabControl->AddEditField($key ? $key."[gender]" : "gender", Loc::getMessage($langPrefix.'gender'), false, false,
    ( isset($arFields["gender"]) & !empty($arFields["gender"]) ? $arFields["gender"] : "" ));

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "sponsor",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields, $entitiName);

$tabControl = SotbitSchema::makeSingleDropDownField($tabControl, "funder",
    array(
        '' => '...',
        'person' => 'Person',
        'organization' => 'Organization',
    ),
    $key, $langPrefix, $arFields, $entitiName);

$tabControl->AddEditField($key ? $key."[publishingPrinciples]" : "publishingPrinciples", Loc::getMessage($langPrefix.'publishingPrinciples'), false, false,
    ( isset($arFields["publishingPrinciples"]) & !empty($arFields["publishingPrinciples"]) ? $arFields["publishingPrinciples"] : "" ));
$tabControl->AddEditField($key ? $key."[awards]" : "awards", Loc::getMessage($langPrefix.'awards'), false, false,
    ( isset($arFields["awards"]) & !empty($arFields["awards"]) ? $arFields["awards"] : "" ));

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "colleagues",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "parents",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

$tabControl = SotbitSchema::makeMultipleDropDownField($tabControl, "siblings",
    array(
        '' => '...',
        'person' => 'Person',
    ),
    $key, $langPrefix, $arFields);

require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>
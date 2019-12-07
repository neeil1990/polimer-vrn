<?
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Sotbit\Schemaorg\Helper\SchemaHelper;
use Sotbit\Schemaorg\SchemaPageMetaTable;
use Sotbit\Schemaorg\SchemaCategoryTable;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$langPrefix = 'SOTBIT_SCHEMA_EDIT_';
if(!Loader::includeModule('sotbit.schemaorg'))
    die;
if(isset($_REQUEST['refresh'])) {
    LocalRedirect("/bitrix/admin/sotbit.schema_settings.php?ENTITY_ID=".$_REQUEST['ENTITY_ID']."&lang=".LANG);
}
$id_module = 'sotbit.schemaorg';
$errors = array();

global $APPLICATION;

//-----------------------------------------------------------------------------------------------------------------------------------------------
//  save options
if((isset($_REQUEST['save']) && !empty($_REQUEST['save']) || (isset($_REQUEST['apply']) && !empty($_REQUEST['apply'])))) {
    $arEntity = SotbitSchema::arrayFilterRecursive($_POST[$_POST["ENTITIES"]]);
    $arError = array();
    $arFields = array_filter(array_keys(SchemaPageMetaTable::getMap()), function($val) {
        return $val;
    });

    $arFields = array_intersect_key($_REQUEST, array_flip($arFields));

    $arEntity = SotbitSchema::normMulEntities($arEntity);

    SotbitSchema::validateEntities($arEntity);

    if(is_array($arEntity) && count($arEntity) >= 1)
    {
        $arEntity["@context"] = "http://schema.org/";
        $arFields['ENTITY_TYPE'] = $arEntity['@type'];
        $arFields["ENTITIES"] = serialize($arEntity);
    }else{
        array_push($arError, GetMessage($langPrefix."ENTITY_NOT_SELECTED"));
    }

    if(preg_match('/^\d+$/', $_REQUEST['SORT']) !== 1)
        array_push($arError, GetMessage($langPrefix."WRONG_NUM_SORT"));
    if(preg_match('/^\/[\w\-\,\.\=\+\|\@\#\$\%\^\&\*\(\)\{\}\[\}\!\/]*\/*[\w\-\,\.\=\+\|\@\#\$\%\^\&\*\(\)\{\}\[\}\!\/]*$/', $_REQUEST['URL']) !== 1)
        if($_REQUEST['URL'] != "")
            array_push($arError, GetMessage($langPrefix."WRONG_PAGE_URL"));
    if(!isset($_REQUEST['NAME']) || empty($_REQUEST['NAME']) || preg_match('/^\s+$/', $_REQUEST['NAME']) == 1 ){
        $arFields['NAME'] = "";
        array_push($arError, GetMessage($langPrefix."WRONG_RULE_NAME"));
    }
    if(is_array(SotbitSchema::getEntitiesError()) && !empty(SotbitSchema::getEntitiesError()))
        array_push($arError, SotbitSchema::getEntitiesError());

    $option = \CUserOptions::getOption("main.ui.filter", 'tbl_page_list', array());
    if(isset($_REQUEST["SECTIONS"]))
    {
        $arFields["CATEGORY_ID"] = $_REQUEST["SECTIONS"];
    }
    else if (isset($option["filters"]["tmp_filter"]["fields"]["CATEGORY_ID"]))
    {
        $arFields["CATEGORY_ID"] = $option["filters"]["tmp_filter"]["fields"]["CATEGORY_ID"];
    }else{
        $arFields["CATEGORY_ID"] = 0;
    }

    $arFields["TIMESTAMP_X"] = new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );
    $arFields["ACTIVE"] = true;
    if( count($arError) == 0 && SchemaPageMetaTable::getList(array('filter' => array('ID' => $arFields["ID"])))->fetch() !== false)
    {
        if(empty($arFields["SITE_ID"]))
            unset($arFields["SITE_ID"]);

        $db = SchemaPageMetaTable::update($arFields["ID"], $arFields);
    }
    else if(count($arError) == 0)
    {
        $arFields["DATE_CREATE"] = new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );
        if(!isset($arFields["SORT"]) || empty($arFields["SORT"]))
            $arFields["SORT"] = 100;
        $db = SchemaPageMetaTable::Add($arFields);
    }

    if(isset($db) && !empty($db) && $db->isSuccess()) {
        if(isset($_REQUEST['save']) && !empty($_REQUEST['save'])) {
            LocalRedirect('/bitrix/admin/sotbit.schema_url_list.php?lang='.SITE_ID.'&SITE_ID='.$_REQUEST['SITE_ID']);
        }
        else {
            $_SESSION['SAVE_SCHEMA'][$db->getId()] = true;
            LocalRedirect($APPLICATION->GetCurPageParam("ID=".$db->getId()."&CATEGORY_ID=".$arFields["CATEGORY_ID"], array("ID", "CATEGORY_ID")));
        }
    }
    else {
        if(isset($db) && !empty($db))
            $errors = $db->getErrorMessages();
    }

}else {
    $arFields = array();
    if ($_REQUEST['ID']) {
        $arFields = SchemaPageMetaTable::getList(array(
            'filter' => array('ID' => intval($_REQUEST['ID'])),
        ))->fetch();
        $arFields['ENTITIES'] = unserialize($arFields['ENTITIES']);
    } else if (!empty($_POST)) {
        $arFields = $_POST;
    }
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
//-----------------------------------------------------------------------------------------------------------------------------------------------
// if necessary that show message error or success
if(isset($_SESSION['SAVE_SCHEMA'][intval($_REQUEST['ID'])])) {
    unset($_SESSION['SAVE_SCHEMA'][intval($_REQUEST['ID'])]);
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => Loc::getMessage($langPrefix."SUCCESS_SAVE"),
        "TYPE" => "OK"
    ));
}
if(!empty($errors)){
    //Display database fill errors
    CAdminMessage::ShowMessage(implode('<br>', $errors));
    $arFields["ENTITIES"] = unserialize($arFields["ENTITIES"]);
}
if(count($arError) != 0) {
    //Display entities fill errors
    $errMsg = "";
    foreach ($arError as $err)
        if(is_array($err))
        {
            $errMsg .= Loc::getMessage($langPrefix."ENTITIES_NOT_FILLED") . "<br>";
            $errMsg .= "- " . implode('<br>- ', array_keys($err));
        }
        else
        {
            $errMsg .= $err . '<br>';
        }
    CAdminMessage::ShowMessage($errMsg);

//                Loc::getMessage($langPrefix."ENTITIES_NOT_FILLED") :
//                                                Loc::getMessage($langPrefix."ENTITY_NOT_FILLED") ) . "<br>".implode('<br>', array_keys($arError)

    if(isset($arFields["ENTITIES"]) && preg_match('/^a:\d+:\{/', $arFields["ENTITIES"]) === 1)
        $arFields["ENTITIES"] = unserialize($arFields["ENTITIES"]);
    else if(!empty($arFields["ENTITIES"]))
    {
        switch ($arFields["ENTITIES"])
        {
            case 'product':
                $arFields = array_merge($arFields, ["ENTITIES" => ["@type" => "Product"]]);
                break;
            case 'localbusiness':
                $arFields = array_merge($arFields, ["ENTITIES" => ["@type" => "LocalBusiness"]]);
                break;
            case 'breadcrumblist':
                $arFields = array_merge($arFields, ["ENTITIES" => ["@type" => "BreadcrumbList"]]);
                break;
        }
    }
}


if(!$USER->CanDoOperation('view_other_settings') && !$USER->CanDoOperation('edit_other_settings'))
    $APPLICATION->AuthForm(GetMessage($langPrefix."ACCESS_DENIED"));
Loc::loadMessages(__FILE__);
//-----------------------------------------------------------------------------------------------------------------------------------------------
CJSCore::Init(array("jquery"));
?>
    <link rel="stylesheet" href="/bitrix/css/sotbit.schemaorg/style.css">
    <script type="text/javascript" src="/bitrix/js/sotbit.schemaorg/script.js"></script>
<?

$sectionLinc = array();

$rsSections = SchemaCategoryTable::GetList(array(
    'filter' => array()
));

$arSections = array();
while ( $section = $rsSections->Fetch() )
{
    if(!isset($section["PARENT_ID"]) || empty($section["PARENT_ID"]))
        $section["DEPTH_LVL"] = 1;

    $sectionLinc["REFERENCE"][] = $section["NAME"] . " [" . $section["ID"] . "]";
    $sectionLinc["REFERENCE_ID"][] = $section["ID"];
    $arSections[] = $section;
}

function depthLevel($arr, $id, $depth = 1){
    foreach($arr as $el)
    {
        if($el["ID"] == $id)
            depthLevel($arr, $el["PARENT_ID"], ++$depth);
    }
    return $depth;
}

foreach ($arSections as $key => $section)
{
    if(isset($section["DEPTH_LVL"]) && !empty($section["DEPTH_LVL"]))
        $sectionLinc["REFERENCE"][$key] = str_repeat(".", $section["DEPTH_LVL"]) ." ". $sectionLinc["REFERENCE"][$key];
    else
    {
        $arSections[$key]["DEPTH_LVL"] = depthLevel($arSections, $section["ID"]);
        $sectionLinc["REFERENCE"][$key] = str_repeat(".", $arSections[$key]["DEPTH_LVL"]) ." ". $sectionLinc["REFERENCE"][$key];
    }
}

if(count($sectionLinc) > 0){
    array_unshift($sectionLinc["REFERENCE"], "...");
    array_unshift($sectionLinc["REFERENCE_ID"],0);
}else{
    $sectionLinc = array();
    $sectionLinc["REFERENCE"] = array();
    $sectionLinc["REFERENCE_ID"] = array();
    array_push($sectionLinc["REFERENCE"], "...");
    array_push($sectionLinc["REFERENCE_ID"], 0);
}

$option = \CUserOptions::getOption("main.ui.filter", 'tbl_page_list', array());

//$sectionSelected = htmlspecialcharsEx($_REQUEST["CATEGORY_ID"]);
if(!empty($arFields["CATEGORY_ID"]))
    $sectionSelected = $arFields["CATEGORY_ID"];
else
    $sectionSelected = $option["filters"]["tmp_filter"]["fields"]["CATEGORY_ID"];

$aTabs = array(
    array(
        "DIV" => "general_tab",
        "TAB" => Loc::getMessage($langPrefix."GENERAL_EDIT"),
        "ICON" => "main_user_edit",
//        "TITLE" => Loc::getMessage($langPrefix."GENERAL_TITLE")
    )
);

$APPLICATION->setTitle(GetMessage($langPrefix."PAGE_TITLE"));

$flag = false;
$aMenu = array(
    array(
        "TEXT" => GetMessage( $langPrefix."EDIT_BACK" ),
        "TITLE" => GetMessage( $langPrefix."EDIT_BACK_TITLE" ),
        "ICON" => "btn_list",
        "LINK" => '/bitrix/admin/sotbit.schema_url_list.php?lang='.SITE_ID.(isset($_REQUEST['SITE_ID']) ? '&SITE_ID='.$_REQUEST['SITE_ID'] : '').(isset($_REQUEST['CATEGORY_ID']) ? '&CATEGORY_ID='.$_REQUEST['CATEGORY_ID'] : ''),
    ));

?>
    <div class="adm-info-message-wrap entity-is-empty adm-info-message-red" style="display: none;">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage($langPrefix."ENTITY_IS_EMPTY")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
<?
$context = new CAdminContextMenu( $aMenu );
$context->Show();
$tabControl = new CAdminForm("tabControl", $aTabs);
$tabControl->Begin();
$tabControl->BeginNextFormTab();
$tabControl->AddSection($langPrefix."SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->AddEditField("NAME", GetMessage($langPrefix.'NAME'), true, false, (isset($arFields['NAME'])) ? $arFields['NAME'] : "" );
$tabControl->AddEditField("URL", GetMessage($langPrefix.'URL'), false, false, (isset($arFields['URL'])) ? $arFields['URL'] : "" );
$tabControl->AddEditField("SORT", GetMessage($langPrefix.'SORT'), false, false, (isset($arFields['SORT'])) ? $arFields['SORT'] : 100 );

$tabControl->BeginCustomField( "SECTIONS", GetMessage( $langPrefix.'EDIT_SECTIONS' ), false );
?>
    <tr id="SECTIONS">
        <td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
        <td width="60%">
            <?echo SelectBoxFromArray('SECTIONS', htmlspecialcharsEx($sectionLinc), htmlspecialcharsEx($sectionSelected),'',false,'','style="min-width: 350px;"');?>
        </td>
    </tr>
<?
$tabControl->EndCustomField( "SECTIONS" );

$tabControl->AddSection($langPrefix."SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->AddDropDownField("ENTITIES", GetMessage($langPrefix."MAIN_ENTITY"), true,
    array(
        '' => '...',
        'product' => 'Product',
        'localbusiness' => 'LocalBusiness',
        'breadcrumblist' => 'BreadcrumbList',
    ),
    ( (isset($arFields["ENTITIES"]) && !empty($arFields["ENTITIES"]["@type"]))? strtolower($arFields["ENTITIES"]["@type"]) : ""),
    array('class="select_entity"')
);
$tabControl->BeginCustomField("ENTITIES_BLOCK", '', false);
if(isset($arFields["ENTITIES"]["@type"]) && !empty($arFields["ENTITIES"]["@type"]))
{
    if (file_exists(dirname(__FILE__) . '/entities/' . strtolower($arFields["ENTITIES"]["@type"]) . '.php')) {
        $path = dirname(__FILE__) . '/entities/' . strtolower($arFields["ENTITIES"]["@type"]) . '.php';
        echo SotbitSchema::getBlockContent($arFields, $path, false);
    }
}
else
{
    echo '<tr id="tr_ENTITIES_BLOCK" class="adm-detail-file-row">'.
        '<td colspan="2" style="text-align: center">'.
        '</td>'.
        '</tr>';
}
$tabControl->EndCustomField("ENTITIES_BLOCK");
$tabControl->Buttons($arButtonsParams);
$tabControl->arParams["FORM_ACTION"] = $APPLICATION->GetCurPageParam();

if( SotbitSchema::returnDemo() == 2){
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SOTBIT_CRM_DEMO")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}

if( SotbitSchema::returnDemo() == 3 || SotbitSchema::returnDemo() == 0)
{
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SOTBIT_CRM_DEMO_END")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

$tabControl->Show();
$tabControl->ShowWarnings($tabControl->GetName(), $message);

?>
<script>
    $(document).ready(function(){
        var initVal = $('#tr_NAME .adm-detail-content-cell-r input').val();

        $('#tr_NAME .adm-detail-content-cell-r input').on('input', function () {
           if($(this).val() == ""){
               $('.adm-info-message-wrap.entity-is-empty').css({'display' : 'block'});
           }else{
               $('.adm-info-message-wrap.entity-is-empty').css({'display' : 'none'});
           }
        });

        if(initVal == "")
        {
            if($('.adm-info-message-wrap.adm-info-message-red .adm-info-message-title').length = 2 &&
                $($('.adm-info-message-wrap.adm-info-message-red')[1]).css('display') == 'none'
            )
            {
                if(
                    $($('.adm-info-message-wrap.adm-info-message-red .adm-info-message-title')[0]).html() !=
                    $($('.adm-info-message-wrap.adm-info-message-red .adm-info-message-title')[1]).html()
                )
                    $('.adm-info-message-wrap.entity-is-empty').css({'display' : 'block'});
            }else
                // $('.adm-info-message-wrap.entity-is-empty').css({'display' : 'block'});
        }
    });
</script>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
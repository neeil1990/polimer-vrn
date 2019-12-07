<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

/** @global CUser $USER */

use Bitrix\Catalog;
use Bitrix\Currency;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sotbit\Schemaorg\SchemaPageMetaTable;
use Sotbit\Schemaorg\SchemaCategoryTable;
use Sotbit\Schemaorg\Helper\SchemaMetaAction;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

Loader::includeModule("iblock");
Loader::includeModule("sotbit.schemaorg");
IncludeModuleLangFile(__FILE__);

if(isset($_REQUEST['sessid']) && $_REQUEST['sessid'] == bitrix_sessid()) {
    if($_REQUEST['action']){
        SchemaMetaAction::groupAction($_REQUEST['ID'], $_REQUEST['action']['action_button_tbl_page_list']);
    }
    else if($_REQUEST['type'] == 'E') {
        if(isset($_REQUEST['action_button_tbl_page_list']))
            SchemaMetaAction::simplePageAction($_REQUEST['ID'], $_REQUEST['action_button_tbl_page_list']);
    }
    else if($_REQUEST['type'] == 'S')
    {
        SchemaMetaAction::simpleCategoryAction($_REQUEST['ID'], $_REQUEST['action_button_tbl_page_list']);
    }else{
        if(isset($_REQUEST["FIELDS"]))
            SchemaMetaAction::groupAction($_REQUEST["FIELDS"], $_REQUEST['action_button_tbl_page_list']);
        else
            SchemaMetaAction::groupAction($_REQUEST['ID'], $_REQUEST['action_button_tbl_page_list']);
    }
}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");

$arFilter = array(
//    "category" => $_REQUEST['CATEGORY_ID'],
    "CHECK_PERMISSIONS" => "Y",
    "MIN_PERMISSION" => "R",
);
$sTableID = "tbl_page_list";
$oSort = new CAdminSorting($sTableID, "timestamp_x", "desc");
$lAdmin = new CAdminUiList($sTableID, $oSort);
$lAdmin->bMultipart = true;

$sectionItems = array(
    "" => GetMessage("IBLOCK_ALL"),
    "0" => GetMessage("IBLOCK_UPPER_LEVEL"),
);

$rs = SchemaCategoryTable::getList(array(
    'order' => array("ID"=>"asc"),
    'select' => array('ID', 'NAME'),
    'filter' => array('SITE_ID' => $_REQUEST['SITE_ID'])
));

while($ar = $rs->fetch())
    $sectionItems[$ar["ID"]] = $ar["NAME"];

$arFilter = array();

if(empty($_POST) && isset($_REQUEST['CATEGORY_ID']) && isset($_REQUEST['apply_filter']))
    $arFilter['CATEGORY_ID'] = intval($_REQUEST['CATEGORY_ID']);

$arFilter['SITE_ID'] = $_REQUEST['SITE_ID'];

//////////////////////////filter columns
$arFields = array_filter(array_keys(SchemaPageMetaTable::getMap()), function($val) {
    return $val != 'ACTIVE';
});

$langPrefix = 'SOTBIT_SCHEMA_URL_LIST_';

$filterFields = array(
    array(
        'id' => 'ID',
        'name' => Loc::getMessage($langPrefix.'ID'),
        'type' => 'number',
        'filterable' => "",
        'default' => true,
    ),
    array(
        'id' => 'NAME',
        'name' => Loc::getMessage($langPrefix.'NAME'),
        "filterable" => "",
        "quickSearch" => "",
        "default" => true
    ),
    array(
        'id' => 'ACTIVE',
        "name" => Loc::getMessage($langPrefix.'ACTIVE'),
        "type" => "list",
        "items" => array(
            "Y" => Loc::getMessage("IBLOCK_YES"),
            "N" => Loc::getMessage("IBLOCK_NO")
        )
    ),
    array(
        'id' => 'URL',
        "name" => Loc::getMessage($langPrefix.'URL'),
        "filterable" => "",
        "default" => true
    ),
    array(
        'id' => 'CATEGORY_ID',
        "name" => Loc::getMessage($langPrefix.'CATEGORY_ID'),
        "type" => "list",
        "items" => $sectionItems,
        "filterable" => "",
    ),
    array(
        'id' => 'TIMESTAMP_X',
        "name" => Loc::getMessage($langPrefix.'TIMESTAMP_X'),
        "type" => "date",
        "filterable" => ""
    ),
    array(
        'id' => 'DATE_CREATE',
        "name" => Loc::getMessage($langPrefix.'DATE_CREATE'),
        "type" => "date",
        "filterable" => ""
    ),
    array(
        'id' => 'SITE_ID',
        "name" => Loc::getMessage($langPrefix.'SITE_ID'),
        "filterable" => "",
    ),
    array(
        'id' => 'SORT',
        "name" => Loc::getMessage($langPrefix.'SORT'),
        "filterable" => "",
        "default" => true
    )
);

$lAdmin->AddFilter(array_merge($filterFields, array(array(
    "id" => "FIND",
    "name" => 'find',
    "type" => "string",
    "quickSearch" => "",
))), $arFilter);

unset($arFilter['FIND']);

$rsSite = Bitrix\Main\SiteTable::getList(array(
    'order' => array('SORT' => 'ASC'),
    'select' => array(
        'LID',
        'NAME'
    )
));

$arSites = array();
while($arSite = $rsSite->fetch()) {
    $arSites[$arSite['LID']] = "[$arSite[LID]] $arSite[NAME]";
}

// Handle edit action (check for permission before save!)
if($lAdmin->EditAction()) {
}


CJSCore::Init(array('date'));
// List header
$arHeader = array(
    array(
        "id" => "ID",
        "content" => Loc::getMessage($langPrefix.'ID'),
        "sort" => "ID",
        "align" => "center",
        "default" => true
    ),
    array(
        "id" => "NAME",
        "content" => Loc::getMessage($langPrefix.'NAME'),
        "sort" => "NAME",
        "default" => true
    ),
    array(
        "id" => "SORT",
        "content" => Loc::getMessage($langPrefix.'SORT'),
        "sort" => "SORT",
        "default" => true
    ),
    array(
        "id" => "ACTIVE",
        "content" => Loc::getMessage($langPrefix.'ACTIVE'),
        "sort" => "ACTIVE",
        "default" => true
    ),
    array(
        "id" => "TIMESTAMP_X",
        "content" => Loc::getMessage($langPrefix.'TIMESTAMP_X'),
        "sort" => "TIMESTAMP_X",
    ),
    array(
        "id" => "DATE_CREATE",
        "content" => Loc::getMessage($langPrefix.'DATE_CREATE'),
        "sort" => "DATE_CREATE",
    ),
    array(
        "id" => "SITE_ID",
        "content" => Loc::getMessage($langPrefix.'SITE_ID'),
        "sort" => "SITE_ID",
    )
);

$lAdmin->AddHeaders($arHeader);
$lAdmin->AddVisibleHeaderColumn('ID');
$arSelectedFields = $lAdmin->GetVisibleHeaderColumns();

$sort = array("ID"=>"ASC");

if(isset($_REQUEST['by']) && isset($_REQUEST['order']))
    $sort = array($_REQUEST['by'] => $_REQUEST['order']);

$rsData = SotbitSchema::getMixedList(array("ID"=>"ASC"), $arFilter);
$rsData = new CAdminUiResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->SetNavigationParams($rsData, array("BASE_LINK" => '/bitrix/admin/sotbit.schema_url_list.php'));
$boolIBlockElementAdd = CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $find_section_section, "section_element_bind");
$arRows = array();
$mainEntityEdit = false;

while($arRes = $rsData->NavNext(false)) {
    $arLinkParams = array(
        'lang' => LANGUAGE_ID,
        'SITE_ID' => $_REQUEST['SITE_ID']
    );

    $bReadOnly = true;

    $itemId = $arRes['ID'];
    $itemType = $arRes['TYPE'];
    if($itemType == "S") {
        $arLinkParams['CATEGORY_ID'] = $arRes['ID'];
        $arLinkParams['apply_filter'] = 'Y';

        $arLink = array();

        array_map(function($key, $value) use (&$arLink) {
            $arLink[] = $key."=".$value;
        }, array_keys($arLinkParams), $arLinkParams);

        $url_section = '/bitrix/admin/sotbit.schema_url_list.php?'.implode('&', $arLink);
    } else {
        $arLinkParams['ID'] = $arRes['ID'];
        if(isset($_REQUEST['CATEGORY_ID']))
            $arLinkParams['CATEGORY_ID'] = $_REQUEST['CATEGORY_ID'];

        $arLink = array();
        array_map(function($key, $value) use (&$arLink) {
            $arLink[] = $key."=".$value;
        }, array_keys($arLinkParams), $arLinkParams);

        $url_element = '/bitrix/admin/sotbit.schema_edit.php?'.implode('&', $arLink);
    }

    $bReadOnly = false;
    if(!$bReadOnly)
        $mainEntityEdit = true;

    $row = $lAdmin->AddRow($itemType.$itemId, $arRes, $url_element, GetMessage("IBLIST_A_LIST"));

    $arRows[$itemType.$itemId] = $row;

    if($itemType == "S")
        $row->AddViewField("NAME", '<a href="'.CHTTP::URN2URI($url_section).'" class="adm-list-table-icon-link" title="'.GetMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.htmlspecialcharsbx($arRes['NAME']).'</span></a>');
    else
        $row->AddViewField("NAME", '<a href="'.$url_element.'" title="'.GetMessage("IBLIST_A_EDIT").'">'.htmlspecialcharsbx($arRes['NAME']).'</a>');

    if(in_array('NAME', $arSelectedFields))
        $row->AddInputField("NAME", Array('size' => '35'));

    if($itemType != 'S') {
        if(in_array('ACTIVE', $arSelectedFields))
            $row->AddCheckField("ACTIVE");
    }

    if(in_array('SORT', $arSelectedFields))
        $row->AddInputField("SORT");
    if(in_array('TIMESTAMP_X', $arSelectedFields))
        $row->AddInputField("TIMESTAMP_X");
    if(in_array('DATE_CREATE', $arSelectedFields))
        $row->AddInputField("DATE_CREATE");
    if(in_array('SITE_ID', $arSelectedFields))
        $row->AddSelectField("SITE_ID", $arSites);

    if($itemType == "S")
        $row->AddViewField($itemType."ID", '<a href="/bitrix/admin/sotbit.schema_category_edit.php?lang=ru&SITE_ID='.$_REQUEST['SITE_ID'].'&ID='.$arRes['ID'].(isset($_REQUEST['CATEGORY_ID']) ? '&CATEGORY_ID='.$_REQUEST['CATEGORY_ID'] : '').'" title="'.GetMessage("IBLIST_A_EDIT").'">'.$itemId.'</a>');
    else
        $row->AddViewField("ID", '<a href="/bitrix/admin/sotbit.schema_edit.php?lang=ru&SITE_ID='.$_REQUEST['site'].'&ID='.$arRes['ID'].(isset($_REQUEST['CATEGORY_ID']) ? '&CATEGORY_ID='.$_REQUEST['CATEGORY_ID'] : '').'" title="'.GetMessage("IBLIST_A_EDIT").'">'.$itemId.'</a>');

    if($APPLICATION->GetGroupRight('sotbit.schemaorg') == "W") {
        $arActions = array();
        $arActions[] = array(
            "ICON" => "edit",
            "TEXT" => Loc::getMessage($langPrefix.'EDIT'),
            "ACTION" => $lAdmin->ActionRedirect('/bitrix/admin/sotbit.schema_'.(($itemType == 'S') ? 'category_' : '').'edit.php?lang=ru&SITE_ID='.$_REQUEST['SITE_ID'].'&ID='.$arRes['ID'].(isset($_REQUEST['CATEGORY_ID']) ? '&CATEGORY_ID='.$_REQUEST['CATEGORY_ID'] : '').''),
            "DEFAULT" => true,
        );
        $arActions[] = array(
            "ICON" => "delete",
            "TEXT" => Loc::getMessage($langPrefix.'DELETE'),
            "ACTION" => "if(confirm('".Loc::getMessage($langPrefix."CONFIRM_DEL_MESSAGE")."')) ".$lAdmin->ActionDoGroup($itemId, "delete", 'type='.$itemType),
        );

        if($itemType != "S") {
            if($arRes['ACTIVE'] == 'Y')
                $arActions[] = array(
                    "ICON" => "deactive",
                    "TEXT" => Loc::getMessage($langPrefix.'DEACTIVE'),
                    "ACTION" => $lAdmin->ActionDoGroup($itemId, "deactive", 'type='.$itemType),
                );
            else
                $arActions[] = array(
                    "ICON" => "active",
                    "TEXT" => Loc::getMessage($langPrefix.'ACTIVE'),
                    "ACTION" => $lAdmin->ActionDoGroup($itemId, "active", 'type='.$itemType),
                );
        }
        $row->AddActions($arActions);
    }
}

// List footer
$lAdmin->AddFooter(array(
    array(
        "title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
        "value" => $rsData->SelectedRowsCount()
    ),
    array(
        "counter" => true,
        "title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
        "value" => "0"
    ),
));
// Action bar
$arActions = array();
if($mainEntityEdit) {
    $arActions = array(
        "edit" => Loc::getMessage($langPrefix.'EDIT'),
        "delete" => Loc::getMessage($langPrefix.'DELETE'),
        "for_all" => true,
        "active" => Loc::getMessage($langPrefix."ACTIVE"),
        "deactive" => Loc::getMessage($langPrefix."DEACTIVE"),
    );
}
$chain = $lAdmin->CreateChain();
$chain->AddItem(array(
    "TEXT" => "NAME",
    "LINK" => htmlspecialcharsbx($sSectionUrl),
    "ONCLICK" => $lAdmin->ActionAjaxReload($sSectionUrl).';return false;',
));

//Check rights
if($APPLICATION->GetGroupRight('sotbit.schemaorg') == "W")
    $lAdmin->AddGroupActionTable($arActions);

$sLastFolder = '';
$sSectionUrl = CIBlock::GetAdminSectionListLink(46, array('find_section_section' => 0));
$chain = $lAdmin->CreateChain();
if($find_section_section > 0) {
    $sLastFolder = $sSectionUrl;
    $nav = CIBlockSection::GetNavChain($IBLOCK_ID, $find_section_section, array(
        'ID',
        'NAME'
    ));
    while($ar_nav = $nav->GetNext()) {
        $sSectionUrl = CIBlock::GetAdminSectionListLink($IBLOCK_ID, array('find_section_section' => $ar_nav["ID"]));
        $chain->AddItem(array(
            "TEXT" => $ar_nav["NAME"],
            "LINK" => htmlspecialcharsbx($sSectionUrl),
            "ONCLICK" => $lAdmin->ActionAjaxReload($sSectionUrl).';return false;',
        ));
        if($ar_nav["ID"] != $find_section_section)
            $sLastFolder = $sSectionUrl;
    }
}

$option = \CUserOptions::getOption("main.ui.filter", 'tbl_page_list', array());

if(!is_array($option['filters']['tmp_filter']['fields']))
    $option['filters']['tmp_filter']['fields'] = array();

$arr = array_intersect_key($option['filters']['tmp_filter']['fields'], array_flip(array_column($filterFields, 'id')));
$arr = array_filter($arr, function($v, $k) {
    return !empty($v) || $k == 'CATEGORY_ID';
}, ARRAY_FILTER_USE_BOTH);

$arr = array_merge($arr, array(
    'lang' => LANGUAGE_ID,
    'SITE_ID' => $_REQUEST['SITE_ID']
));
$arrLink = array();
if(!empty($arr)) {
    array_map(function($key, $value) use (&$arrLink) {
        $arrLink[] = $key."=".$value;
    }, array_keys($arr), $arr);
}
//
$lAdmin->ShowChain($chain);

// toolbar
$boolBtnNew = false;
$aContext = array();
$aContext[] = array(
    "TEXT" => Loc::getMessage($langPrefix.'CREATE_PAGE'),
    "ICON" => ($boolBtnNew ? "" : "btn_new"),
    "LINK" => '/bitrix/admin/sotbit.schema_edit.php?'.implode('&', $arrLink)

);
$aContext[] = array(
    "TEXT" => Loc::getMessage($langPrefix.'CREATE_CATEGORY'),
    "ICON" => "btn_new",
    "LINK" => '/bitrix/admin/sotbit.schema_category_edit.php?'.implode('&', $arrLink)
);
$lAdmin->setContextSettings(array("pagePath" => '/bitrix/admin/sotbit.schema_url_list.php'));
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
//We need javascript not in excel mode
CJSCore::Init('file_input');

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

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(array("default_action" => $sec_list_url));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
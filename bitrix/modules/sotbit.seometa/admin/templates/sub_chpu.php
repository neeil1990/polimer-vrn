<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\SectionUrlTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

global $APPLICATION, $DB;

$moduleId = 'sotbit.seometa';
if (
    !defined('B_ADMIN_SUBCHPU')
    || 1 != B_ADMIN_SUBCHPU
    || !defined('B_ADMIN_SUBCHPU_LIST')
) {
    return '';
}

$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if (
    $POST_RIGHT == "D"
    || !Loader::includeModule('iblock')
    || !Loader::includeModule($moduleId)
) {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$CCSeoMeta= new CCSeoMeta();
if (!$CCSeoMeta->getDemo()) {
    return '';
}

if ($_REQUEST['mode'] == 'list' || $_REQUEST['mode'] == 'frame') {
    CFile::DisableJSFunction();
}

IncludeModuleLangFile(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/subelement.php');

$strSubElementAjaxPath = '/bitrix/admin/seometa_subchpu_admin.php?lang='.LANGUAGE_ID.'&ID='.intval($_REQUEST['ID']);
$strSubElementAjaxPath = trim($strSubElementAjaxPath);
$sTableID = SeometaUrlTable::getTableName();
$arHideFields = ['ID'];
$lAdmin = new CAdminSubList($sTableID, false, $strSubElementAjaxPath, false);
$parentID = 0;
if (!empty($_REQUEST["parent"])) {
    $parentID = $_REQUEST["parent"];
}

$ParentUrl = '';
if (!empty($parentID)) {
    $ParentUrl = '&section=' . $parentID;
}

$arFilterChpu["CATEGORY_ID"] = intval($parentID);
$arFilterChpu['CONDITION_ID'] = $ID;
if(!$condition) {
    $conditionRes = ConditionTable::getById($ID);
    $condition = $conditionRes->fetch();
}

if ($lAdmin->EditAction()) {
    foreach ($_POST['FIELDS'] as $ID => $arFields) {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID,1));
        if ($ID <= 0 || !$lAdmin->IsUpdated($ID)) {
            continue;
        }

        if ($TYPE == "P") {
            $DB->StartTransaction();
            if (!SeometaUrlTable::Update($ID, $arFields)) {
                if ($ex = $APPLICATION->GetException()) {
                    $lAdmin->AddUpdateError($ex->GetString(), $ID);
                } else {
                    $lAdmin->AddUpdateError(str_replace("#ID#", $ID, GetMessage("SEO_META_SAVE_ERROR")),
                        $ID);
                }

                $DB->Rollback();
            } else {
                $DB->Commit();
            }
        } else {
            $DB->StartTransaction();
            if (!SectionUrlTable::Update($ID, $arFields)) {
                if ($ex = $APPLICATION->GetException()) {
                    $lAdmin->AddUpdateError($ex->GetString(), $ID);
                } else {
                    $lAdmin->AddUpdateError(str_replace("#ID#", $ID, GetMessage("SEO_META_SAVE_ERROR")),
                        $ID);
                }

                $DB->Rollback();
            } else {
                $DB->Commit();
            }
        }
    }
}

if ($arID = $lAdmin->GroupAction()) {
    if ($_REQUEST['action_target'] == 'selected') {
        $arID = [];
        $dbResultList = SeometaUrlTable::GetList(
            ["ID" => $order],
            [],
            false,
            false,
            ["ID"]
        );
        while ($arResult = $dbResultList->Fetch()){
            $arID[] = "P".$arResult['ID'];
        }

        $rsSection = SectionUrlTable::getList([
            'limit' => null,
            'offset' => null,
            'select' => ["*"],
            "filter" => $filter
        ]);
        while ($arSection = $rsSection->Fetch()) {
            $arSection["T"] = "S";
            $arSection['ID'] = "S" . $arSection['ID'];
            $arID[] = $arSection;
        }
    }

    foreach ($arID as $ID) {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID,1));
        if (empty($ID)) {
            continue;
        }

        switch ($_REQUEST['action']) {
            case "delete":
                @set_time_limit(0);
                if ($TYPE == "P") {
                    $DB->StartTransaction();
                    if (!SeometaUrlTable::Delete($ID)) {
                        $DB->Rollback();

                        if ($ex = $APPLICATION->GetException()) {
                            $lAdmin->AddGroupError($ex->GetString(), $ID);
                        } else {
                            $lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("SEO_META_DEL_ERROR")),
                                $ID);
                        }
                    } else {
                        $DB->Commit();
                    }
                } else {
                    $result = SectionUrlTable::delete($ID);
                    if (!$result->isSuccess()) {
                        $lAdmin->AddGroupError(GetMessage("SEO_META_DEL_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"),
                            $ID);
                    }
                }
                break;
            case "activate":
            case "deactivate":
                $arFields = [
                    "ACTIVE" => $_REQUEST['action'] == "activate" ? "Y" : "N"
                ];
                if ($TYPE == "P") {
                    if (!SeometaUrlTable::Update($ID, $arFields)) {
                        if ($ex = $APPLICATION->GetException()) {
                            $lAdmin->AddGroupError($ex->GetString(), $ID);
                        } else {
                            $lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("SEO_META_SAVE_ERROR")),
                                $ID);
                        }
                    }
                } else {
                    $result=SectionUrlTable::update($ID, [
                        'ACTIVE' => $arFields["ACTIVE"],
                    ]);
                    if (!$result->isSuccess()) {
                        $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"),
                            $ID);
                    }
                }
                break;
        }
    }
}

$lAdmin->AddHeaders([
    [
        "id"    =>"ID",
        "content"  =>GetMessage("SEO_META_TABLE_ID"),
        "align"    =>"right",
        "default"  =>true,
    ],
    [
        "id"    =>"NAME",
        "content"  =>GetMessage("SEO_META_TABLE_TITLE"),
        "default"  =>true,
    ],
    [
        "id"    =>"ACTIVE",
        "content"  =>GetMessage("SEO_META_TABLE_ACTIVE"),
        "default"  =>true,
    ],
    [
        "id"    =>"SORT",
        "content"  =>GetMessage("SEO_META_TABLE_SORT"),
        "default"  =>true,
    ],
    [
        "id"    =>"DATE_CHANGE",
        "content"  =>GetMessage("SEO_META_TABLE_DATE_CHANGE"),
        "default"  =>true,
    ],
    [
        "id"    =>"SITE_ID",
        "content"  =>GetMessage("SEO_META_TABLE_SITE_ID"),
        "default"  =>true,
    ],
    [
        "id"    =>"REAL_URL",
        "content"  =>GetMessage("SEO_META_TABLE_REAL_URL"),
        "default"  =>true,
    ],
    [
        "id"    =>"NEW_URL",
        "content"  =>GetMessage("SEO_META_TABLE_NEW_URL"),
        "default"  =>true,
    ],
    [
        "id"    =>"iblock_id",
        "content"  =>GetMessage("SEO_META_TABLE_IBLOCK_ID"),
        "sort"    =>"iblock_id",
        "default"  =>true,
    ],
    [
        "id"    =>"section_id",
        "content"  =>GetMessage("SEO_META_TABLE_SECTION_ID"),
        "sort"    =>"section_id",
        "default"  =>true,
    ],
    [
        "id"    =>"PRODUCT_COUNT",
        "content"  =>GetMessage("SEO_META_TABLE_PRODUCT_COUNT"),
        "sort"    =>"PRODUCT_COUNT",
        "default"  =>true,
    ],
    [
        "id"    =>"PROPERTIES",
        "content"  =>GetMessage("SEO_META_TABLE_PROPERTIES"),
        "default"  =>true,
    ],
]);

$filterValues = [];
$usePageNavigation = true;
if (!empty($_REQUEST['mode']) && $_REQUEST['mode'] == 'excel') {
    $usePageNavigation = false;
} else {
    $navyParams = CDBResult::GetNavParams(
        CAdminResult::GetNavSize(
            $sTableID,
            [
                'nPageSize' => 20,
                'sNavID' => $APPLICATION->GetCurPage() . '?ENTITY_ID=' . $ENTITY_ID
            ]
        )
    );

    if ($navyParams['SHOW_ALL']) {
        $usePageNavigation = false;
        $navyParams['SIZEN'] = 0;
    } else {
        $navyParams['PAGEN'] = (int)$navyParams['PAGEN'];
        $navyParams['SIZEN'] = (int)$navyParams['SIZEN'];
    }
}

$selectFields = $lAdmin->GetVisibleHeaderColumns();
$arrFields = [
    'ID',
    'NAME',
    'ACTIVE',
    'SORT',
    'DATE_CHANGE',
    'SITE_ID',
    'REAL_URL',
    'NEW_URL',
    'iblock_id',
    'section_id',
    'PRODUCT_COUNT',
    'PROPERTIES'
];
foreach ($selectFields as $k => $field) {
    if (!in_array($field, $arrFields)) {
        unset($selectFields[$k]);
    }
}

$sort = $by;
if (!in_array($by,$arrFields)) {
    $by = 'ID';
}

$order = mb_strtoupper($order);

if (!in_array('ID', $selectFields)) {
    $selectFields[] = 'ID';
}

$filterValues['CONDITION_ID'] = $_REQUEST['ID'];
$getListParams = [
    'select' => $selectFields,
    'filter' => $filterValues,
    'order' => ["ID" => $order]
];
unset($filterValues, $selectFields);
if ($usePageNavigation) {
    $getListParams['limit'] = $navyParams['SIZEN'];
    $getListParams['offset'] = $navyParams['SIZEN'] * ($navyParams['PAGEN'] - 1);
}

$totalCount = 0;
$countQuery = new Query(SeometaUrlTable::getEntity());
$countQuery->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
$countQuery->setFilter($getListParams['filter']);
$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
unset($countQuery);
$totalCount = (int)$totalCount['CNT'];
if ($totalCount > 0) {
    $totalPages = $totalCount;
    if ($navyParams['SIZEN'] != 0) {
        $totalPages = ceil($totalCount / $navyParams['SIZEN']);
    }

    if ($navyParams['PAGEN'] > $totalPages) {
        $navyParams['PAGEN'] = $totalPages;
    }

    $getListParams['limit'] = $navyParams['SIZEN'];
    $getListParams['offset'] = $navyParams['SIZEN'] * ($navyParams['PAGEN'] - 1);
} else {
    $navyParams['PAGEN'] = 1;
    $getListParams['limit'] = $navyParams['SIZEN'];
    $getListParams['offset'] = 0;
}

$ids = [];
$rsData = SeometaUrlTable::getList($getListParams);
while ($arRes = $rsData->Fetch()) {
    $arRes["T"] = "P";
    $arRes['REAL_URL'] = Encoding::convertEncodingToCurrent(rawurldecode($arRes['REAL_URL']));
    $arResult[] = $arRes;
}

$rsData = new CAdminSubResult($arResult, $sTableID, $lAdmin->GetListUrl(true));
$rsData->NavPageNomer = $navyParams['PAGEN'];
$rsData->NavRecordCount = $totalCount;
if ($usePageNavigation) {
    $rsData->NavStart($navyParams['SIZEN'], $navyParams['SHOW_ALL'], $navyParams['PAGEN']);
    $rsData->NavPageCount = $totalPages;

} else {
    $rsData->NavPageCount = 1;
    $rsData->NavStart();
}

$request = Application::getInstance()->getContext()->getRequest();
$protocol = ($request->isHttps() ? 'https' : 'http') . '://';
if(empty($SitesAll)){
    $SitesAll = [];
    $rsSites = CSite::GetList($by = "sort",
        $order = "desc",
        []
    );

    while ($arSite = $rsSites->Fetch()) {
        $SitesAll[$arSite['LID']] = $arSite;
    }
}
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));
while ($arRes = $rsData->NavNext(true, "")) {
    $row =& $lAdmin->AddRow($arRes['T'].$arRes['ID'], $arRes);
    $row->AddInputField("NAME", ["size"=>20]);
    $row->AddInputField("SORT", ["size"=>20]);
    $row->AddCheckField("ACTIVE");
    $arActions = [];
    if ($arRes['T'] == 'P') {
        $props = unserialize($arRes['PROPERTIES']);
        $pr = '';
        if (is_array($props)) {
            foreach ($props as $code => $value) {
                $lastCode = '';
                if ($code == 'FILTER') {
                    $lastCode = $code;
                    $keys = array_keys($value);
                    $code = $keys[0];
                    unset($keys);
                }

                $name = CIBlockProperty::GetByID($code)->fetch();
                $glue = ', ';
                if (!$name) {
                    $priceItem = CIBlockPriceTools::GetCatalogPrices($arRes['iblock_id'], [$code])[$code];
                    $name['NAME'] = $priceItem['TITLE'];
                    $glue = ' - ';
                }

                if (empty($name['NAME'])) {
                    $pr .= $code . ' - ' . implode(', ', $value) . '; ';
                } elseif ($lastCode == 'FILTER') {
                    $keys = array_keys(current($value));
                    $pr .= 'FILTER_' . $name['NAME'] . ' - '
                        . (in_array('FROM', $keys) ? 'FROM' : $keys[0])
                        . '-' . implode(', ', current($value)) . '; ';
                } else {
                    $pr .= $name['NAME'] . ' - ' . implode($glue, $value) . '; ';
                }
            }
        }

        $row->AddViewField("PROPERTIES", $pr);
        $iblock = CIBlock::GetByID($arRes['iblock_id'])->fetch();
        $section = CIBlockSection::GetByID($arRes['section_id'])->fetch();
        $row->AddViewField("iblock_id", '<a target="_blank" href="iblock_edit.php?type='.$iblock['IBLOCK_TYPE_ID'].'&lang='.LANG.'&ID='.$arRes['iblock_id'].'&admin=Y">'.$iblock['NAME'].'</a>');
        $row->AddViewField("section_id", '<a target="_blank" href="iblock_section_edit.php?IBLOCK_ID='.$arRes['iblock_id'].'&type='.$iblock['IBLOCK_TYPE_ID'].'&ID='.$arRes['section_id'].'&lang='.LANG.'&find_section_section='.$section['IBLOCK_SECTION_ID'].'">'.$section['NAME'].'</a>');
        $row->AddViewField("NAME", '<a href=\'sotbit.seometa_chpu_edit.php?ID='.$arRes['ID'].'&lang='.LANG.$ParentUrl.'\'>'.$arRes['NAME'].'</a>');
        $row->AddViewField("SORT", $arRes['SORT']);
        $arActions[] = [
            "ICON"=>"edit",
            "DEFAULT"=>true,
            "TEXT"=>GetMessage("SEO_META_EDIT"),
            "ACTION"=>$lAdmin->ActionRedirect('sotbit.seometa_chpu_edit.php?ID='.$arRes['ID'].'&lang='.LANG.$ParentUrl.'">'.$arRes['NAME'].'</a>'),
        ];
    } else {
        $row->AddViewField("NAME", '<a href="sotbit.seometa_edit.php?ID='.$_REQUEST['ID'].'&parent='.$arRes['ID'].'&tabControl_active_tab=edit4&lang='.LANG.'" class="adm-list-table-icon-link" title="'.GetMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$arRes['NAME'].'</span></a>');
        $arActions[] = [
            "ICON"=>"edit",
            "DEFAULT"=>true,
            "TEXT"=>GetMessage("SEO_META_EDIT"),
            "ACTION"=>$lAdmin->ActionRedirect("sotbit.seometa_section_chpu_edit.php?parent=".$arRes['ID'])
        ];
    }

    if($condition['SITES']) {
        $siteUrl = $SitesAll[$arRes['SITE_ID']]['SERVER_NAME'];
        $row->AddViewField("REAL_URL", '<a href=\''. $protocol . $siteUrl . Encoding::convertEncodingToCurrent($arRes['REAL_URL']) .'\'>'.Encoding::convertEncodingToCurrent($arRes['REAL_URL']).'</a>');
        $row->AddViewField("NEW_URL", '<a href=\''. $protocol.  $siteUrl . Encoding::convertEncodingToCurrent($arRes['NEW_URL']) .'\'>'.Encoding::convertEncodingToCurrent($arRes['NEW_URL']).'</a>');
    }

    $arActions[] = ["SEPARATOR" => true];
    if (is_set($arActions[count($arActions) - 1], "SEPARATOR")) {
        unset($arActions[count($arActions) - 1]);
    }

    $row->AddActions($arActions);
}

if (isset($row)) {
    unset($row);
}

$lAdmin->AddFooter([
    ["title"=>GetMessage("SEO_META_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()],
    ["counter"=>true, "title"=>GetMessage("SEO_META_LIST_CHECKED"), "value"=>"0"],
]);

$lAdmin->AddGroupActionTable([
    "delete"=>GetMessage("SEO_META_LIST_DELETE"),
    "activate"=>GetMessage("SEO_META_LIST_ACTIVATE"),
    "deactivate"=>GetMessage("SEO_META_LIST_DEACTIVATE"),
]);

$aContext = [
    [
        "TEXT"=>GetMessage("SEO_META_POST_ADD_TEXT"),
        "LINK"=>"sotbit.seometa_chpu_edit.php?&lang=".LANG.$ParentUrl,
        "TITLE"=>GetMessage("SEO_META_POST_ADD_TITLE"),
        "ICON"=>"btn_new",
    ]
];

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();
$lAdmin->DisplayList();
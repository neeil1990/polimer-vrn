<?

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\Orm\SeometaUrlTable;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

global $APPLICATION, $DB;

//<editor-fold desc="Functions">
function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    return count($lAdmin->arFilterErrors)==0;
}
//</editor-fold>

$moduleId = 'sotbit.seometa';

$CCSeoMeta= new CCSeoMeta();
if (
        !Loader::includeModule('iblock')
        || !Loader::includeModule($moduleId)
        || !$CCSeoMeta->getDemo()
) {
    return '';
}

$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if ($POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

if ($_REQUEST['mode'] == 'list' || $_REQUEST['mode'] == 'frame') {
    CFile::DisableJSFunction();
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/subelement.php');

$sTableID = 'tbl_iblock_el_search' . md5(SeometaUrlTable::getTableName());
//$sTableID = "b_sotbit_seometa_chpu";
$arHideFields = ['ID'];
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);
$FilterArr = [
    "find",
    "find_id",
    "find_name",
    "find_new_url",
    "find_real_url",
];

$arFilter = [];
$FilterArrInit = [];
if($_REQUEST['set_filter'] != '' && CheckFilter())
{
    $isSet = false;
    foreach ($FilterArr as $key) {
        if($_REQUEST[$key] != '') {
            $charset = mb_strtolower(mb_detect_encoding($_REQUEST[$key], ['utf-8', 'cp1251']));
            if(mb_strtoupper($charset) == 'UTF-8' && mb_strtoupper(LANG_CHARSET) != 'UTF-8'){
                $_REQUEST[$key] = mb_convert_encoding($_REQUEST[$key], 'cp1251', 'UTF-8');
            }
        }

        if($key == 'find' && $_REQUEST[$key] != '') {
            $isSet = true;
            $arFilter[mb_strtoupper(mb_substr($_REQUEST['find_type'], 5))] = $_REQUEST[$key];
        } else if($_REQUEST[$key] != '') {
            $isSet = true;
            $arFilter[mb_strtoupper(mb_substr($key, 5))] = $_REQUEST[$key];
        }

        if($isSet) {
            $isSet = false;
            $FilterArrInit[] = $key;
        }
    }
}

if($_SESSION['SEO_META']['CHPU_LIST']['SITE_ID']) {
    $site_id = $_SESSION['SEO_META']['CHPU_LIST']['SITE_ID'];
}

$lAdmin->InitFilter($FilterArrInit);

$parentID = $_REQUEST["parent"] ?? 0;
$ParentUrl = '';
if ($parentID > 0) {
    $ParentUrl = '&section=' . $parentID;
}

$arFilterChpu["CATEGORY_ID"] = intval($parentID);
$arFilterChpu['CONDITION_ID'] = $ID;

$reloadUrl = $APPLICATION->GetCurPage() .'?lang='. LANGUAGE_ID;
if($arFilter) {
    foreach ($arFilter as $key => $item) {
        $reloadUrl .= '&'. $key .'='. $item;
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
        "id"    =>"DATE_CHANGE",
        "content"  =>GetMessage("SEO_META_TABLE_DATE_CHANGE"),
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
    [
        "id"    =>"SITE_ID",
        "content"  =>GetMessage("SEO_META_TABLE_SITE_ID"),
        "default"  =>true,
    ],
]);


$usePageNavigation = true;
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'excel')
{
    $usePageNavigation = false;
}
else
{
    $navyParams = CDBResult::GetNavParams(
        CAdminResult::GetNavSize(
            $sTableID,
            [
                'nPageSize' => 20,
                'sNavID' => $reloadUrl
            ]
        )
    );

    if ($navyParams['SHOW_ALL'])
    {
        $usePageNavigation = false;
        $navyParams['SIZEN'] = 0;
    }
    else
    {
        $navyParams['PAGEN'] = (int)$navyParams['PAGEN'];
        $navyParams['SIZEN'] = (int)$navyParams['SIZEN'];
    }
}
$selectFields = $lAdmin->GetVisibleHeaderColumns();

$headerFields = [
    'ID',
    'NAME',
    'DATE_CHANGE',
    'REAL_URL',
    'NEW_URL',
    'iblock_id',
    'section_id',
    'PRODUCT_COUNT',
    'PROPERTIES',
    'SITE_ID'
];

foreach($selectFields as $k => $field)
{
    if(!in_array($field, $headerFields))
    {
        unset($selectFields[$k]);
    }
}

$sort = $by;
if(!in_array($by, $headerFields))
{
    $by = 'ID';
}

$order = mb_strtoupper($order);
$filterValues = [];
if($site_id && is_array($site_id))
{
    $filterValues['LOGIC'] = 'OR';

    $filterItem = [];
    foreach ($arFilter as $key => $value) {
        $filterItem = [$key => $value];
    }

    foreach ($site_id as $item) {
        $baseFilter = [
            'ACTIVE' => 'Y',
            '%SITE_ID' => $item
        ];

        if($filterItem) {
            $baseFilter = array_merge($baseFilter, $filterItem);
        }

        $filterValues[] = $baseFilter;
    }

    $reloadUrl .= '&' . http_build_query($site_id, 'site_id_');
}else{
    $arFilter['SITE_ID'] = $site_id;
    $filterValues = $arFilter;
}

if (!in_array('ID', $selectFields))
{
    $selectFields[] = 'ID';
}

$getListParams = [
    'select' => $selectFields,
    'filter' => $filterValues,
    'order' => ["ID" => $order]
];

unset($filterValues, $selectFields);
if ($usePageNavigation)
{
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

if ($totalCount > 0)
{
    $totalPages = ceil($totalCount / $navyParams['SIZEN']);
    if ($navyParams['PAGEN'] > $totalPages)
    {
        $navyParams['PAGEN'] = $totalPages;
    }

    $getListParams['limit'] = $navyParams['SIZEN'];
    $getListParams['offset'] = $navyParams['SIZEN']*($navyParams['PAGEN']-1);
}
else
{
    $navyParams['PAGEN'] = 1;
    $getListParams['limit'] = $navyParams['SIZEN'];
    $getListParams['offset'] = 0;
}

$ids = [];
$rsData = SeometaUrlTable::getList($getListParams);
while($arRes = $rsData->Fetch())
{
    $arRes["T"] = "P";
    $arRes['REAL_URL'] = str_replace(' ', '%20', rawurldecode($arRes['REAL_URL']));
    $arResult[] = $arRes;
}

$rsData = new CAdminResult($arResult, $sTableID);
if ($usePageNavigation)
{
    $rsData->NavStart($navyParams['SIZEN'], $navyParams['SHOW_ALL'], $navyParams['PAGEN']);
    $rsData->NavRecordCount = $totalCount;
    $rsData->NavPageCount = $totalPages;
    $rsData->NavPageNomer = $navyParams['PAGEN'];
}
else
{
    $rsData->NavPageCount = 1;
    $rsData->NavPageNomer = $navyParams['PAGEN'];
    $rsData->NavRecordCount = $totalCount;
    $rsData->NavStart();
}

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));
while($arRes = $rsData->NavNext(true, ""))
{
    $row =& $lAdmin->AddRow($arRes['T'].$arRes['ID'], $arRes);
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
                if (empty($name['NAME'])) {
                    $pr .= $code . ' - ' . implode(', ',
                            $value) . '; ';
                } elseif ($lastCode == 'FILTER') {
                        $keys = array_keys(current($value));
                        $pr .= 'FILTER_' . $name['NAME'] . ' - ' . ((in_array('FROM',
                                $keys)) ? 'FROM' : $keys[0]) . '-' . implode(', ',
                                current($value)) . '; ';
                } else {
                    $pr .= $name['NAME'] . ' - ' . implode(', ',
                            $value) . '; ';
                }
            }
        }

        $row->AddViewField("PROPERTIES", $pr);
        $iblock = CIBlock::GetByID($arRes['iblock_id'])->fetch();
        $section = CIBlockSection::GetByID($arRes['section_id'])->fetch();
        $row->AddViewField("iblock_id",
            '<a target="_blank" href="iblock_edit.php?type=' . $iblock['IBLOCK_TYPE_ID'] . '&lang=' . LANG . '&ID=' . $arRes['iblock_id'] . '&admin=Y">' . $iblock['NAME'] . '</a>');
        $row->AddViewField("section_id",
            $section['NAME']);
        $row->AddViewField("NAME",
            $arRes['NAME']);
    } else {
        $row->AddViewField("NAME", '<a href="sotbit.seometa_edit.php?ID='.$_REQUEST['ID'].'&parent='.$arRes['ID'].'&tabControl_active_tab=edit4&lang='.LANG.'" class="adm-list-table-icon-link" title="'.GetMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$arRes['NAME'].'</span></a>');
    }

    $row->AddActions([
        [
            "DEFAULT" => "Y",
            "TEXT" => loc::getMessage("SEO_META_TABLE_SELECT"),
            "ACTION"=>"javascript:SelEl('".CUtil::JSEscape($arRes['ID'])."')",
        ],
    ]);
}

if (isset($row)) {
    unset($row);
}

$lAdmin->CheckListMode();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");

$oFilter = new CAdminFilter(
    $sTableID."_filter",
    [
        'ID' => Loc::getMessage("SEO_META_TABLE_ID"),
        'TITLE' =>Loc::getMessage("SEO_META_TABLE_TITLE"),
        'NEW_URL' =>Loc::getMessage("SEO_META_TABLE_NEW_URL"),
        'REAL_URL' =>Loc::getMessage("SEO_META_TABLE_REAL_URL"),
    ]
);

?>
    <script type="text/javascript">
        var arClearHiddenFields = [],
            blockedFilter = false;

        function applyFilter(el)
        {
            if (blockedFilter)
                return false;
            <?=$sTableID."_filter";?>.OnSet('<?=CUtil::JSEscape($sTableID); ?>', '<?=CUtil::JSEscape($reloadUrl); ?>');
            return false;
        }

        function deleteFilter(el)
        {
            if (blockedFilter)
                return false;
            if (0 < arClearHiddenFields.length)
            {
                for (var index = 0; index < arClearHiddenFields.length; index++)
                {
                    if (undefined != window[arClearHiddenFields[index]])
                    {
                        if ('ClearForm' in window[arClearHiddenFields[index]])
                        {
                            window[arClearHiddenFields[index]].ClearForm();
                        }
                    }
                }
            }
            <?=$sTableID."_filter"?>.OnClear('<?=CUtil::JSEscape($sTableID); ?>', '<?=CUtil::JSEscape($reloadUrl)?>');
            return false;
        }

        function SelEl(id)
        {
            <?=$_REQUEST['tabControl_active_tab'] != '' ? 'let selectedTab = "'. $_REQUEST['tabControl_active_tab'] .'"' : ''?>

            let newUrl = window.opener.location.href;
            let strPos = newUrl.indexOf('tabControl_active_tab');
            if(strPos !== -1) {
                newUrl = newUrl.slice(0,strPos) + 'tabControl_active_tab=' + selectedTab;
            } else {
                newUrl += '&tabControl_active_tab=' + selectedTab;
            }

            newUrl += '&chpu_id=' + id + '&action=chpu_link_add';

            window.opener.location.href = newUrl;
            window.close();
        }
    </script>
<?

if( CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0)
{
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO_END")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
    return '';
}

?>
    <form name="find_form" method="get" action="<?=$APPLICATION->GetCurPage();?>">
        <?
        $oFilter->Begin();
        ?>
        <tr>
            <td><b><?=Loc::getMessage("SEO_META_FIND")?>:</b></td>
            <td>
                <input type="text" size="25" name="find" value="<?=htmlspecialchars($find)?>" title="<?=Loc::getMessage("SEO_META_FIND_TITLE")?>">
                <?
                $arr = [
                    "reference" => [
                        Loc::getMessage("SEO_META_TABLE_ID"),
                        Loc::getMessage("SEO_META_TABLE_NAME"),
                        Loc::getMessage("SEO_META_TABLE_NEW_URL"),
                        Loc::getMessage("SEO_META_TABLE_REAL_URL")
                    ],
                    "reference_id" => [
                        "find_id",
                        "find_name",
                        "find_new_url",
                        "find_real_url"
                    ]
                ];
                echo SelectBoxFromArray("find_type", $arr, $find_type, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?=Loc::getMessage("SEO_META_TABLE_ID")?>:</td>
            <td>
                <input type="text" name="find_id" size="60" value="<?echo htmlspecialchars($find_id)?>">
            </td>
        </tr>
        <tr>
            <td><?=Loc::getMessage("SEO_META_TABLE_NAME")?>:</td>
            <td>
                <input type="text" name="find_name" size="60" value="<?echo htmlspecialchars($find_name)?>">
            </td>
        </tr>
        <tr>
            <td><?=Loc::getMessage("SEO_META_TABLE_NEW_URL")?>:</td>
            <td>
                <input type="text" name="find_new_url" size="60" value="<?echo htmlspecialchars($find_new_url)?>">
            </td>
        </tr>
        <tr>
            <td><?=Loc::getMessage("SEO_META_TABLE_REAL_URL")?>:</td>
            <td>
                <input type="text" name="find_real_url" size="60" value="<?echo htmlspecialchars($find_real_url)?>">
            </td>
        </tr>
        <?
        $oFilter->Buttons();
        ?>
        <span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="set_filter" value="<?= Loc::getMessage('SEO_META_FIND_TITLE'); ?>" title="<?= GetMessage("admin_lib_filter_set_butt_title"); ?>" onclick="return applyFilter(this);"></span>
        <span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="del_filter" value="<?= Loc::getMessage('SEO_META_TABLE_CLEAR_FILTER'); ?>" title="<?= GetMessage("admin_lib_filter_clear_butt_title"); ?>" onclick="return deleteFilter(this);"></span>
        <?
        $oFilter->End();
        ?>
    </form>

<?
if( CCSeoMeta::ReturnDemo() == 2){
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}

$lAdmin->DisplayList();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
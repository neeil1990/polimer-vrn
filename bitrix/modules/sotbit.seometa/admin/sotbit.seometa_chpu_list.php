<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\Text\Encoding;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SectionUrlTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

global $APPLICATION;

CJSCore::Init(array("jquery"));
$error = '';
$id_module='sotbit.seometa';
$arResult = [];
Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
if ($POST_RIGHT == "D" || !Loader::includeModule($id_module) || !Loader::includeModule('iblock')) {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$sTableID = "b_sotbit_seometa_chpu";
$order = $_REQUEST['order'] ?: 'asc';
$by = $_REQUEST['by'] ?: 'ID';
$oSort = new CAdminSorting($sTableID, $by, $order);
$lAdmin = new CAdminList($sTableID, $oSort);

function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    return count($lAdmin->arFilterErrors)==0;
}

$FilterArr = [
    "find",
    "find_id",
    "find_name",
    "find_active",
    "find_in_sitemap",
    "find_site_id"
];

$parentID = 0;
if (!empty($_REQUEST["parent"])) {
    $parentID = $_REQUEST["parent"];
}

$ParentUrl = '';
if (!empty($parentID)) {
    $ParentUrl = "&section=" . $parentID;
}

$lAdmin->InitFilter($FilterArr);
$arFilter = [];
if (CheckFilter()) {
    if ($find != '' && $find_type == 'id') {
        $arFilter['ID'] = $find;
    } elseif ($find_id != '') {
        $arFilter['ID'] = $find_id;
    }

    $arFilter['NAME'] = $find_name;
    $arFilter['ACTIVE'] = $find_active;
    $arFilter['IN_SITEMAP'] = $find_in_sitemap;
    $arFilter["CATEGORY_ID"] = $parentID;
    $arFilter["SITE_ID"] = $find_site_id;

    if (empty($arFilter['ID'])) {
        unset($arFilter['ID']);
    }
    if (empty($arFilter['NAME'])) {
        unset($arFilter['NAME']);
    }
    if (empty($arFilter['ACTIVE'])) {
        unset($arFilter['ACTIVE']);
    }
    if (empty($arFilter['IN_SITEMAP'])) {
        unset($arFilter['IN_SITEMAP']);
    }
    if (empty($arFilter["SITE_ID"])) {
        unset($arFilter["SITE_ID"]);
    }
    if ($arFilter['CATEGORY_ID'] === '') {
        unset($arFilter['CATEGORY_ID']);
    }
}

if ($lAdmin->EditAction() && isset($FIELDS) && is_array($FIELDS)) {
    foreach ($FIELDS as $ID => $arFields) {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID,1));
        if (!$lAdmin->IsUpdated($ID)) {
            continue;
        }

        if ($ID > 0) {
            if ($TYPE == "P") {
                foreach ($arFields as $key => $value) {
                    $arData[$key] = $value;
                }

                $result = SeometaUrlTable::update($ID,$arData);
                if (!$result->isSuccess()) {
                    $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR") . " " . Loc::getMessage("SEO_META_NO_ZAPIS"),
                        $ID);
                }
            } else {
                foreach ($arFields as $key => $value) {
                    $arData[$key] = $value;
                }

                $arData['DATE_CHANGE'] = new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
                $result = SectionUrlTable::update($ID, $arData);
                if (!$result->isSuccess()) {
                    $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR") . " " . Loc::getMessage("SEO_META_NO_ZAPIS"),
                        $ID);
                }
            }
        } else {
            $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR") . " " . Loc::getMessage("SEO_META_NO_ZAPIS"),
                $ID);
        }
    }
}

if ($arID = $lAdmin->GroupAction()) {
    if ($_REQUEST['action_target'] == 'selected') {
        $rsData = SeometaUrlTable::getList([
            'select' => [
                'ID',
                'NAME',
                'ACTIVE',
                'REAL_URL',
                'NEW_URL',
                'DATE_CHANGE'
            ],
            'filter' => $arFilter,
            'order' => [$by => $order],
        ]);

        while ($arRes = $rsData->Fetch()) {
            $arRes["T"] = "S";
            $arRes['ID'] = "P" . $arRes['ID'];
            $arID[] = $arRes['ID'];
        }

        if (!isset($filter)) {
            $filter = [];
        }

        $rsSection = SectionUrlTable::getList([
            'limit' => null,
            'offset' => null,
            'select' => ["*"],
            "filter" => $filter
        ]);
        while($arSection = $rsSection->Fetch()) {
            $arSection["T"]="S";
            $arSection['ID']="S".$arSection['ID'];
            $arID[]=$arSection;
        }
    }

    foreach ($arID as $ID) {
        $TYPE = mb_substr($ID, 0, 1);
        $ID = intval(mb_substr($ID,1));

        if (mb_strlen($ID) <= 0) {
            continue;
        }

        $ID = IntVal($ID);
        switch ($_REQUEST['action']) {
            case "delete":
                if ($TYPE == "P") {
                    $result = SeometaUrlTable::delete($ID);
                    if (!$result->isSuccess()) {
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_DEL_ERROR") . " " . Loc::getMessage("SEO_META_NO_ZAPIS"),
                            $ID);
                    }
                } else {
                    $result = SectionUrlTable::deleteSections($ID, true);
                    if (!$result->isSuccess()) {
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_DEL_ERROR") . " " . Loc::getMessage("SEO_META_NO_ZAPIS"),
                            $ID);
                    }
                }
                break;
            case "activate":
            case "deactivate":
                $arFields["ACTIVE"] = ($_REQUEST['action'] == "activate" ? "Y" : "N");
                if ($TYPE == "P") {
                    $result = SeometaUrlTable::update($ID,
                        [
                            'ACTIVE' => $arFields["ACTIVE"],
                        ]);
                    if (!$result->isSuccess()) {
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR") . " " . Loc::getMessage("SEO_META_NO_ZAPIS"),
                            $ID);
                    }
                } else {
                    $result = SectionUrlTable::update($ID,
                        [
                            'ACTIVE' => $arFields["ACTIVE"],
                        ]);
                    if (!$result->isSuccess()) {
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR") . " " . Loc::getMessage("SEO_META_NO_ZAPIS"),
                            $ID);
                    }
                }
                break;
        }
    }
}

$show = "all";
if (isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"] == "all") {
    unset($arFilter["CATEGORY_ID"]);
    $show = "all";
} elseif (isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"] == "section") {
    $show = "section";
    unset($arFilter["CATEGORY_ID"]);
}

$filter = $arFilter;
if ($show == "all" || $show == "section") {
    if (isset($arFilter["CATEGORY_ID"])) {
        $filter["PARENT_CATEGORY_ID"] = $arFilter["CATEGORY_ID"];
        unset($filter["CATEGORY_ID"]);
    }

    if (isset($arFilter["IN_SITEMAP"])) {
        unset($filter["IN_SITEMAP"]);
    }
    if (isset($arFilter["SITE_ID"])) {
        unset($filter["SITE_ID"]);
    }

    $rsSection = SectionUrlTable::getList([
        'select' => ["*"],
        'limit' => null,
        'offset' => null,
        'filter' => $filter
    ]);
    while ($arSection = $rsSection->Fetch()) {
        $arSection["T"] = "S";
        $arResult[] = $arSection;
    }
    unset($rsSection);
}

if (!isset($arFilter['CATEGORY_ID'])) {
    $arFilter['CATEGORY_ID'] = 0;
}

$rsData = SeometaUrlTable::getList([
    'select' => [
        'ID',
        'NAME',
        'CONDITION_ID',
        'ACTIVE',
        'REAL_URL',
        'NEW_URL',
        'IN_SITEMAP',
        'iblock_id',
        'section_id',
        'PRODUCT_COUNT',
        'DATE_CHANGE',
        'PROPERTIES',
        'SITE_ID'
    ],
    'filter' => $arFilter,
    'order' => [$by => $order],
]);

if (isset($rsData)) {
    $keyURL = array_flip(['REAL_URL', 'NEW_URL']);
    while ($arRes = $rsData->Fetch()) {
        $arRes["T"] = "P";
        if($arKey = array_intersect_key($arRes, $keyURL)){
            foreach ($arKey as $urlKey => $url){
                $url = rawurldecode($url);
                $url = Encoding::convertEncoding($url, "utf-8", LANG_CHARSET, $error);
                $arRes[$urlKey] = $url;
            }
        }
        $arResult[] = $arRes;
    }
}

$rs = new CDBResult;
$rs->InitFromArray($arResult);
$rsData = new CAdminResult($rs, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("SEO_META_NAV")));
$lAdmin->AddHeaders([
    [
        "id" => "ID",
        "content" => Loc::getMessage("SEO_META_TABLE_ID"),
        "sort" => "ID",
        "align" => "right",
        "default" => true,
    ],
    [
        "id" => "NAME",
        "content" => Loc::getMessage("SEO_META_TABLE_TITLE"),
        "sort" => "NAME",
        "default" => true,
    ],
    [
        "id" => "CONDITION_ID",
        "content" => Loc::getMessage("SEO_META_TABLE_CONDITION_ID"),
        "sort" => "CONDITION_ID",
        "default" => true,
    ],
    [
        "id" => "ACTIVE",
        "content" => Loc::getMessage("SEO_META_TABLE_ACTIVE"),
        "sort" => "ACTIVE",
        "default" => true,
    ],
    [
        "id"    =>"SITE_ID",
        "content"  =>Loc::getMessage("SEO_META_TABLE_SITE_ID"),
        "sort" => "SITE_ID",
        "default"  =>true,
    ],
    [
        "id" => "REAL_URL",
        "content" => Loc::getMessage("SEO_META_TABLE_REAL_URL"),
        "sort" => "REAL_URL",
        "default" => true,
    ],
    [
        "id" => "NEW_URL",
        "content" => Loc::getMessage("SEO_META_TABLE_NEW_URL"),
        "sort" => "NEW_URL",
        "default" => true,
    ],
    [
        "id" => "IN_SITEMAP",
        "content" => Loc::getMessage("SEO_META_TABLE_IN_SITEMAP"),
        "sort" => "IN_SITEMAP",
        "default" => true,
    ],
    [
        "id" => "iblock_id",
        "content" => Loc::getMessage("SEO_META_TABLE_IBLOCK_ID"),
        "sort" => "iblock_id",
        "default" => true,
    ],
    [
        "id" => "section_id",
        "content" => Loc::getMessage("SEO_META_TABLE_SECTION_ID"),
        "sort" => "section_id",
        "default" => true,
    ],
    [
        "id" => "PRODUCT_COUNT",
        "content" => Loc::getMessage("SEO_META_TABLE_PRODUCT_COUNT"),
        "sort" => "PRODUCT_COUNT",
        "default" => true,
    ],
    [
        "id" => "DATE_CHANGE",
        "content" => Loc::getMessage("SEO_META_TABLE_DATE_CHANGE"),
        "sort" => "DATE_CHANGE",
        "default" => true,
    ],
    [
        "id" => "PROPERTIES",
        "content" => Loc::getMessage("SEO_META_TABLE_PROPERTIES"),
        "default" => true,
    ],
]);

while ($arRes = $rsData->NavNext(true, "f_")) {
    $row =& $lAdmin->AddRow($f_T.$f_ID, $arRes);
    $row->AddInputField("NAME", ["size"=>20]);
    $row->AddCheckField("ACTIVE");

    if ($f_T == "S") {
        $row->AddViewField("NAME",
            '<a href="sotbit.seometa_chpu_list.php?parent=' . $f_ID
            . '&lang=' . LANG . '" class="adm-list-table-icon-link" title="'
            . Loc::getMessage("IBLIST_A_LIST")
            . '"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'
            . $f_NAME . '</span></a>'
        );
    } else {
        $iblock = CIBlock::GetByID($arRes['iblock_id'])->fetch();
        $section = CIBlockSection::GetByID($arRes['section_id'])->fetch();
        $props = unserialize($arRes['PROPERTIES']);
        $pr = '';
        if (is_array(($props))) {
            foreach ($props as $code => $value) {
                $name = CIBlockProperty::GetByID($code)->fetch();
                $pr .= $name['NAME'] . ' - ' . implode(', ', $value) . '; ';
            }
            $row->AddViewField("PROPERTIES", $pr);
        }

        $row->AddViewField("iblock_id", '<a target="_blank" href="iblock_edit.php?type='.$iblock['IBLOCK_TYPE_ID'].'&lang='.LANG.'&ID='.$arRes['iblock_id'].'&admin=Y">'.$iblock['NAME'].'</a>');
        $row->AddViewField("section_id", '<a target="_blank" href="iblock_section_edit.php?IBLOCK_ID='.$arRes['iblock_id'].'&type='.$iblock['IBLOCK_TYPE_ID'].'&ID='.$arRes['section_id'].'&lang='.LANG.'&find_section_section='.$section['IBLOCK_SECTION_ID'].'">'.$section['NAME'].'</a>');
        $row->AddViewField("NAME", '<a href="sotbit.seometa_chpu_edit.php?ID='.$f_ID.'&lang='.LANG.$ParentUrl.'">'.$f_NAME.'</a>');
        $cond = ConditionTable::getById($f_CONDITION_ID)->fetch();
        $row->AddViewField("CONDITION_ID", $cond ? '<a href="/bitrix/admin/sotbit.seometa_edit.php?ID='.$cond['ID'].'&lang='.LANG.'" target="_blank">#'.$cond['ID'].' - '.$cond['NAME'].'</a>' : '');
        $row->AddViewField("IN_SITEMAP", $f_IN_SITEMAP == 'Y' ? Loc::getMessage("SEO_META_POST_YES") : Loc::getMessage("SEO_META_POST_NO"));
        $row->AddViewField("DATE_CHANGE", $arRes['DATE_CHANGE']);
    }

    $arActions = [];
    if ($f_T == 'P') {
        $arActions[] = [
            "ICON" => "edit",
            "DEFAULT" => true,
            "TEXT" => Loc::getMessage("SEO_META_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect("sotbit.seometa_chpu_edit.php?ID=" . $f_ID . $ParentUrl)
        ];
    } else {
        $arActions[] = [
            "ICON" => "edit",
            "DEFAULT" => true,
            "TEXT" => Loc::getMessage("SEO_META_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect("sotbit.seometa_section_chpu_edit.php?ID=" . $f_ID)
        ];
    }

    if ($POST_RIGHT >= "W") {
        $arActions[] = [
            "ICON" => "delete",
            "TEXT" => Loc::getMessage("SEO_META_DEL"),
            "ACTION" => "if(confirm('" . Loc::getMessage('SEO_META_DEL_CONF') . "')) " . $lAdmin->ActionDoGroup($f_T . $f_ID, "delete")
        ];
    }

    $arActions[] = ["SEPARATOR" => true];
    if (is_set($arActions[count($arActions) - 1], "SEPARATOR")) {
        unset($arActions[count($arActions) - 1]);
    }
    $row->AddActions($arActions);
}

$lAdmin->AddFooter(
    [
        [
            "title" => Loc::getMessage("SEO_META_LIST_SELECTED"),
            "value" => $rsData->SelectedRowsCount()
        ],
        [
            "counter" => true,
            "title" => Loc::getMessage("SEO_META_LIST_CHECKED"),
            "value" => "0"
        ],
    ]
);

$lAdmin->AddGroupActionTable([
    "delete" => Loc::getMessage("SEO_META_LIST_DELETE"),
    "activate" => Loc::getMessage("SEO_META_LIST_ACTIVATE"),
    "deactivate" => Loc::getMessage("SEO_META_LIST_DEACTIVATE"),
]);

if ($parentID > 0) {
    $Section = SectionUrlTable::getById($parentID)->Fetch();
    $aContext = [
        [
            "TEXT" => Loc::getMessage("SEO_META_POST_ADD_TEXT"),
            "LINK" => "sotbit.seometa_chpu_edit.php?lang=" . LANG . $ParentUrl,
            "TITLE" => Loc::getMessage("SEO_META_POST_ADD_TITLE"),
            "ICON" => "btn_new",
        ],
        [
            "TEXT" => Loc::getMessage("SEO_META_SECTION_ADD"),
            "LINK" => "sotbit.seometa_section_chpu_edit.php?parent=" . $parentID . "&lang=" . LANG,
            "TITLE" => Loc::getMessage("SEO_META_SECTION_ADD"),
            "ICON" => "btn_sect_new",
        ],
        [
            "TEXT" => Loc::getMessage("SEO_META_SECTION_UP"),
            "LINK" => "sotbit.seometa_chpu_list.php?parent=" . $Section['PARENT_CATEGORY_ID'] . "&lang=" . LANG,
            "TITLE" => Loc::getMessage("SEO_META_SECTION_UP"),
            "ICON" => "btn_sect_new",
        ],
        [
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "LINK"=>"javascript:exportCHPU();",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "ICON"=>"btn_download",
        ],
        [
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "LINK"=>"sotbit.seometa_import_excel.php?lang=" . LANG . "&entity=chpu",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "ICON"=>"btn_upload",
        ],
    ];
} else {
    $aContext = [
        [
            "TEXT" => Loc::getMessage("SEO_META_POST_ADD_TEXT"),
            "LINK" => "sotbit.seometa_chpu_edit.php?lang=" . LANG . $ParentUrl,
            "TITLE" => Loc::getMessage("SEO_META_POST_ADD_TITLE"),
            "ICON" => "btn_new",
        ],
        [
            "TEXT" => Loc::getMessage("SEO_META_SECTION_ADD"),
            "LINK" => "sotbit.seometa_section_chpu_edit.php?parent=" . $parentID . "&lang=" . LANG,
            "TITLE" => Loc::getMessage("SEO_META_SECTION_ADD"),
            "ICON" => "btn_sect_new",
        ],
        [
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "LINK"=>"javascript:exportCHPU();",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "ICON"=>"btn_download",
        ],
        [
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "LINK"=>"sotbit.seometa_import_excel.php?lang=" . LANG . "&entity=chpu",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "ICON"=>"btn_upload",
        ],
    ];
}

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();
$APPLICATION->SetTitle(Loc::getMessage("SEO_META_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
    $sTableID . "_filter",
    [
        Loc::getMessage("SEO_META_ID"),
        Loc::getMessage("SEO_META_NAME"),
        Loc::getMessage("SEO_META_ACTIVE"),
        Loc::getMessage("SEO_META_IN_SITEMAP"),
        Loc::getMessage("SEO_META_SITE_ID"),
    ]
);

if (CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO_END")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

?>

<form name="find_form" method="get" action="<?=$APPLICATION->GetCurPage();?>">
    <?
        $oFilter->Begin();
    ?>
    <tr>
        <td><b><?= Loc::getMessage("SEO_META_FIND") ?>:</b></td>
        <td>
            <input type="text" size="25" name="find" value="<?= htmlspecialchars($find) ?>" title="<?= Loc::getMessage("SEO_META_FIND_TITLE") ?>">
            <?
                $arr = [
                    "reference" => [
                        "ID",
                    ],
                    "reference_id" => [
                        "id",
                    ]
                ];
                echo SelectBoxFromArray("find_type", $arr, $find_type, "", "");
            ?>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage("SEO_META_ID") ?>:</td>
        <td>
            <input type="text" name="find_id" size="47" value="<?= htmlspecialchars($find_id)?>">
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage("SEO_META_NAME") ?>:</td>
        <td>
            <input type="text" name="find_name" size="47" value="<?= htmlspecialchars($find_name)?>">
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage("SEO_META_ACTIVE") ?>:</td>
        <td>
            <?
                $arr = [
                    "reference" => [
                        Loc::getMessage("SEO_META_POST_NO_MATTER"),
                        Loc::getMessage("SEO_META_POST_YES"),
                        Loc::getMessage("SEO_META_POST_NO"),
                    ],
                    "reference_id" => [
                        "",
                        "Y",
                        "N",
                    ]
                ];
                echo SelectBoxFromArray("find_active", $arr, $find_active, "", "");
            ?>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage("SEO_META_IN_SITEMAP") ?>:</td>
        <td>
            <?
                $arr = [
                    "reference" => [
                        Loc::getMessage("SEO_META_POST_NO_MATTER"),
                        Loc::getMessage("SEO_META_POST_YES"),
                        Loc::getMessage("SEO_META_POST_NO"),
                    ],
                    "reference_id" => [
                        "",
                        "Y",
                        "N",
                    ]
                ];
                echo SelectBoxFromArray("find_in_sitemap", $arr, $find_in_sitemap, "", "");
            ?>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage("SEO_META_SITE_ID") ?>:</td>
        <td>
            <input type="text" name="find_site_id" size="47" value="<?= htmlspecialchars($find_site_id)?>">
        </td>
    </tr>
    <?
        $oFilter->Buttons(["table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "find_form"]);
        $oFilter->End();
    ?>
</form>

    <script>
        function exportCHPU(offset = 0, limit = 100, newFile = 1, count = 0, totalCount = 0) {
            const node = BX('seochpu_export');
            if(node.style.display === 'block'){
                const nodeProgress = BX('sitemap_progress');
                const nodeProgressStart = BX('sitemap_progress_start');
                nodeProgress.innerHTML = nodeProgressStart.innerHTML;
            }else{
                node.style.display = 'block';
            }
            const exportCHPU = BX.ajax.runAction('sotbit:seometa.excelExportImport.exportCHPU', {
                data: {offset, limit, newFile, count, totalCount}
            }).then(response => {
                let count = response.data.COUNT;
                if (count > 0) {
                    newFile = 0;
                    const nodeProgressStart = BX('sitemap_progress_start');
                    nodeProgressStart.innerHTML = response.data.PROGRESSBAR;
                    this.exportCHPU(response.data.OFFSET, 100, newFile, count, response.data.TOTAL_COUNT);
                } else {
                    const nodeProgress = BX('sitemap_progress');
                    nodeProgress.innerHTML = response.data.PROGRESSBAR;
                    let link = document.createElement('a');
                    link.href = response.data.PATH;
                    link.download = response.data.NAME;
                    link.click();
                    deleteFile();
                }
            }, error => {
                console.error(error);
            });
        }

        function deleteFile(){
            const sheetName = 'seometa_chpu';
            const deleteFile = BX.ajax.runAction('sotbit:seometa.excelExportImport.deleteFile', {
                data: {sheetName}
            }).then(response => {
            }, error => {
                console.error(error);
            });
        }
    </script>

    <div id="seochpu_export" style="display: none;">
        <div id="sitemap_progress">
            <?=SitemapRuntime::showProgress(Loc::getMessage('SEO_META_CHPU_RUN_INIT'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), 0)?>
        </div>
        <div id="sitemap_progress_start" style="display: none">
            <?=SitemapRuntime::showProgress(Loc::getMessage('SEO_META_CHPU_RUN_INIT'), Loc::getMessage('SEO_META_CHPU_RUN_TITLE'), 0)?>
        </div>
    </div>
<?
if (CCSeoMeta::ReturnDemo() == 2) {
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

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
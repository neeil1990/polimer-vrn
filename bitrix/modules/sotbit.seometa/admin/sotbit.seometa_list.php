<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SitemapSectionTable;

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$moduleId = 'sotbit.seometa';

Loc::loadMessages(__FILE__);
IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if (!Loader::includeModule($moduleId) || $POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$sTableID = "b_sotbit_seometa";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);
function CheckFilter(
) {
	global $FilterArr, $lAdmin;
	foreach ($FilterArr as $f) global $$f;
	return count($lAdmin->arFilterErrors)==0;
}

$FilterArr = [
	"find",
	"find_id",
	"find_name",
	"find_active",
];

$parentID = 0;
$ParentUrl = '';
if (!empty($_REQUEST["parent"])) {
	$parentID = $_REQUEST["parent"];
    $ParentUrl = '&section=' . $parentID;
}

$lAdmin->InitFilter($FilterArr);
$arFilter= [];
if (CheckFilter()) {
    if (!empty($find) && $find_type == 'id') {
        $arFilter['ID'] = $find;
    } elseif (!empty($find_id) != '') {
        $arFilter['ID'] = $find_id;
    }

    if(!empty($find_name)) {
        $arFilter['NAME'] = '%' . $find_name . '%';
    }

    if(!empty($find_active)) {
        $arFilter['ACTIVE'] = $find_active;
    }

    if(!empty($parentID)) {
        $arFilter["CATEGORY_ID"] = $parentID;
    }
}

if ($lAdmin->EditAction()) {
    foreach ($FIELDS as $ID => $arFields) {
		$TYPE = mb_substr($ID, 0, 1);
		$ID = intval(mb_substr($ID,1));
        if (!$lAdmin->IsUpdated($ID)) {
            continue;
        }

        if ($ID > 0) {
            foreach ($arFields as $key => $value) {
                $arData[$key] = $value;
            }

            $arData['DATE_CHANGE'] = new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
            if ($TYPE == "P") {
				$result = ConditionTable::update($ID,$arData);
            } else {
                $result = SitemapSectionTable::update($ID,$arData);
			}

            if (!$result->isSuccess()) {
                $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"),
                    $ID);
            }
        } else {
			$lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
		}
	}
}

if ($arID = $lAdmin->GroupAction()) {
    if ($_REQUEST['action_target'] == 'selected') {
        $arID = [];
        $rsData = ConditionTable::getList([
            'select' => [
                'ID',
                'NAME',
                'SORT',
                'ACTIVE',
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

        if (isset($arFilter["CATEGORY_ID"]) && !empty($arFilter["CATEGORY_ID"])) {
            $filter["PARENT_CATEGORY_ID"] = $arFilter["CATEGORY_ID"];
        }
        if (!isset($filter)) {
            $filter = [];
        }

		$rsSection = SitemapSectionTable::getList([
            'limit' =>null,
            'offset' => null,
            'select' => ["ID"],
            "filter" => $filter
        ]);
        while ($arSection = $rsSection->Fetch()) {
            $arSection["T"]="S";
            $arSection['ID']="S".$arSection['ID'];
            $arID[]=$arSection['ID'];
        }
	}

    foreach ($arID as $ID) {
		$TYPE = mb_substr($ID, 0, 1);
        $ID = mb_substr($ID, 1);
        if (mb_strlen($ID) <= 0) {
            continue;
        }

		$ID = IntVal($ID);
        switch ($_REQUEST['action']) {
			case "delete":
                if ($TYPE == "P") {
                    $result = ConditionTable::delete($ID);
                } else {
                    $result = SitemapSectionTable::deleteSection($ID);

                }

                if (!$result->isSuccess()) {
                    $lAdmin->AddGroupError(GetMessage("SEO_META_DEL_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"),
                        $ID);
                }
				break;
            case "run":
                $chpu = ConditionTable::generateUrlForCondition($ID);
                if (!$chpu) {
                    echo CAdminMessage::ShowMessage([
                        "MESSAGE" => Loc::getMessage('SEO_META_GENERATE_ERROR'),
                    ]);
                } else {
                    ConditionTable::update($ID, ['ACTIVE'=>'Y','DATE_CHANGE' => new Bitrix\Main\Type\DateTime()]);
                    echo CAdminMessage::ShowMessage([
                        "MESSAGE" => Loc::getMessage('SEO_META_GENERATE_SUCCESS'),
                        "TYPE" => "OK",
                    ]);
                }
                break;
			case "activate":
			case "deactivate":
                $arFields["ACTIVE"] = $_REQUEST['action'] == "activate" ? "Y" : "N";
                if ($TYPE == "P") {
                    $result = ConditionTable::update($ID, [
                        'ACTIVE' => $arFields["ACTIVE"],
                    ]);
                } else {
                    $result = SitemapSectionTable::update($ID, [
                        'ACTIVE' => $arFields["ACTIVE"],
                    ]);
                }

                if (!$result->isSuccess()) {
                    $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR") . " " . GetMessage("SEO_META_NO_ZAPIS"),
                        $ID);
                }
				break;
			case "copy":
                if ($TYPE == "P") {
                    $condition = ConditionTable::getById($ID)->fetch();
                    $arFields = [
                        "ACTIVE" => $condition['ACTIVE'],
                        "STRONG" => $condition['STRONG'],
                        "NAME" => $condition['NAME'],
                        "SORT" => $condition['SORT'],
                        "SITES" => $condition['SITES'],
                        "TYPE_OF_INFOBLOCK" => $condition['TYPE_OF_INFOBLOCK'],
                        "INFOBLOCK" => $condition['INFOBLOCK'],
                        "DATE_CHANGE" => new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                        "SECTIONS" => $condition['SECTIONS'],
                        "RULE" => $condition['RULE'],
                        "META" => $condition['META'],
                        "NO_INDEX" => $condition['NO_INDEX'],
                        "PRIORITY" => $condition['PRIORITY'],
                        "CHANGEFREQ" => $condition['CHANGEFREQ'],
                        "CATEGORY_ID" => $condition['CATEGORY_ID'],
                        "FILTER_TYPE" => $condition['FILTER_TYPE'],
                    ];
                    $result = ConditionTable::add($arFields);
                } else {
                    $condition = SitemapSectionTable::getById($ID)->fetch();
                    $arFields = [
                        "ACTIVE" => $condition['ACTIVE'],
                        "NAME" => $condition['NAME'],
                        "SORT" => $condition['SORT'],
                        "DESCRIPTION" => $condition['DESCRIPTION'],
                        "PARENT_CATEGORY_ID" => $condition['PARENT_CATEGORY_ID'],
                        "DATE_CREATE" => new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                        "DATE_CHANGE" => new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                    ];
                    $result = SitemapSectionTable::add($arFields);
				}

                if ($result->isSuccess()) {
                    $ID = $result->getId();
                } else {
                    $errors = $result->getErrorMessages();
                }
				break;
		}
	}
}

$show = "section";
if (!empty($_REQUEST["show_sp"]) && $_REQUEST["show_sp"] == "all") {
    $show = "all";
    unset($arFilter["CATEGORY_ID"]);
} elseif (!empty($_REQUEST["show_sp"]) && $_REQUEST["show_sp"] == "section") {
    $show = "section";
    unset($arFilter["CATEGORY_ID"]);
}

$filter = $arFilter;
$arResult = [];
if($show == "all" || $show == "section") {
    if (isset($arFilter["CATEGORY_ID"])) {
        $filter["PARENT_CATEGORY_ID"] = $arFilter["CATEGORY_ID"];
        unset($filter["CATEGORY_ID"]);
    }
    if(empty($filter)){
        $filter["PARENT_CATEGORY_ID"] = 0;
    }

    $rsSection = SitemapSectionTable::getList([
        'limit' => null,
        'offset' => null,
        'select' => ["*"],
        "filter" => $filter,
        'order' => [$by => $order],
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

$rsData=ConditionTable::getList([
	'select' => ['ID','NAME','SORT','ACTIVE','DATE_CHANGE'],
	'filter' =>$arFilter,
	'order' => [$by => $order],
]);
while ($arRes = $rsData->Fetch()) {
    $arRes["T"] = "P";
    $arResult[] = $arRes;
}

$rs = new CDBResult();
$rs->InitFromArray($arResult);
$rsData = new CAdminResult($rs, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));
$lAdmin->AddHeaders([
	[
        "id"    =>"ID",
		"content"  =>GetMessage("SEO_META_TABLE_ID"),
		"sort"    =>"ID",
		"align"    =>"right",
		"default"  =>true,
    ],
	[
        "id"    =>"NAME",
		"content"  =>GetMessage("SEO_META_TABLE_TITLE"),
		"sort"    =>"NAME",
		"default"  =>true,
    ],
	[
        "id"    =>"SORT",
		"content"  =>GetMessage("SEO_META_TABLE_SORT"),
		"sort"    =>"SORT",
		"align"    =>"right",
		"default"  =>true,
    ],
	[
        "id"    =>"ACTIVE",
		"content"  =>GetMessage("SEO_META_TABLE_ACTIVE"),
		"sort"    =>"ACTIVE",
		"default"  =>true,
    ],
	[
        "id"    =>"DATE_CHANGE",
		"content"  =>GetMessage("SEO_META_TABLE_DATE_CHANGE"),
		"sort"    =>"DATE_CHANGE",
		"default"  =>true,
    ],
]);

if ($parentID > 0) {
    $Section = SitemapSectionTable::getById($parentID)->Fetch();
    $aContext = [
        [
            "TEXT"=>GetMessage("SEO_META_POST_ADD_TEXT"),
            "LINK"=>"sotbit.seometa_edit.php?lang=".LANG.$ParentUrl,
            "TITLE"=>GetMessage("SEO_META_POST_ADD_TITLE"),
            "ICON"=>"btn_new",
        ],
        [
            "TEXT"=>GetMessage("SEO_META_SECTION_ADD"),
            "LINK"=>"sotbit.seometa_section_edit.php?parent=".$parentID."&lang=".LANG,
            "TITLE"=>GetMessage("SEO_META_SECTION_ADD"),
            "ICON"=>"btn_sect_new",
        ],
        [
            "TEXT"=>GetMessage("SEO_META_SECTION_UP"),
            "LINK"=>"sotbit.seometa_list.php?parent=".$Section['PARENT_CATEGORY_ID']."&lang=".LANG,
            "TITLE"=>GetMessage("SEO_META_SECTION_UP"),
            "ICON"=>"btn_sect_new",
        ],
        [
            "TEXT"=>GetMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "LINK"=>"javascript:exportCondition();",
            "TITLE"=>GetMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "ICON"=>"btn_download",
        ],
        [
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "LINK"=>"sotbit.seometa_import_excel.php?lang=" . LANG . "&entity=cond",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "ICON"=>"btn_upload",
        ],
    ];
    $row =& $lAdmin->AddRow(".", ["NAME" => Loc::getMessage("SEO_META_SECTION_UP")]);
    $showField = "<a href=\"sotbit.seometa_list.php?lang=". LANG . "&parent=". $Section['PARENT_CATEGORY_ID'] ."\"><span class=\"adm-submenu-item-link-icon fileman_icon_folder_up\" alt=\"".Loc::getMessage("SEO_META_SECTION_UP")."\"></span>&nbsp;<a href=\"sotbit.seometa_list.php?lang=". LANG . "&parent=". $Section['PARENT_CATEGORY_ID'] ."\">..</a>";
    $row->AddField("NAME", $showField);
    $row->AddField("LOGIC_NAME", $showField);
    $row->AddField("SIZE", "");
    $row->AddField("DATE", "");
    $row->AddField("TYPE", "");
    $row->AddField("PERMS_B", "");
} else {
    $aContext = [
        [
            "TEXT"=>GetMessage("SEO_META_POST_ADD_TEXT"),
            "LINK"=>"sotbit.seometa_edit.php?lang=".LANG.$ParentUrl,
            "TITLE"=>GetMessage("SEO_META_POST_ADD_TITLE"),
            "ICON"=>"btn_new",
        ],
        [
            "TEXT"=>GetMessage("SEO_META_SECTION_ADD"),
            "LINK"=>"sotbit.seometa_section_edit.php?parent=".$parentID."&lang=".LANG,
            "TITLE"=>GetMessage("SEO_META_SECTION_ADD"),
            "ICON"=>"btn_sect_new",
        ],
        [
            "TEXT"=>GetMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "LINK"=>"javascript:exportCondition();",
            "TITLE"=>GetMessage("SEO_META_SECTION_EXCEL_DOWNLOAD"),
            "ICON"=>"btn_download",
        ],
        [
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "LINK"=>"sotbit.seometa_import_excel.php?lang=" . LANG . "&entity=cond",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_EXCEL_UPLOAD"),
            "ICON"=>"btn_upload",
        ],
    ];
}

$lAdmin->AddAdminContextMenu($aContext, false);
while($arRes = $rsData->NavNext(true, "f_")) {
    $row =& $lAdmin->AddRow($f_T . $f_ID, $arRes);
    $row->AddInputField("NAME", array("size" => 20));
    if ($f_T == "S") {
        $row->AddViewField("NAME",
            '<a href="sotbit.seometa_list.php?parent=' . $f_ID . '&lang=' . LANG . '" class="adm-list-table-icon-link" title="' . GetMessage("IBLIST_A_LIST") . '"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">' . $f_NAME . '</span></a>');
    } else {
        $row->AddViewField("NAME",
            '<a href="sotbit.seometa_edit.php?ID=' . $f_ID . '&lang=' . LANG . $ParentUrl . '">' . $f_NAME . '</a>');
    }

    $row->AddInputField("SORT", ["size" => 20]);
    $row->AddCheckField("ACTIVE");
    $arActions = [];
    if ($f_T == 'P') {
        $arActions[] = [
            "ICON" => "edit",
            "DEFAULT" => true,
            "TEXT" => GetMessage("SEO_META_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect("sotbit.seometa_edit.php?ID=" . $f_ID . $ParentUrl)
        ];
        $arActions[] = [
            "ICON" => "move",
            "TEXT" => GetMessage("SEO_META_RUN"),
            "ACTION" => $lAdmin->ActionDoGroup($f_T . $f_ID, "run")
        ];
    } else {
        $arActions[] = [
            "ICON" => "edit",
            "DEFAULT" => true,
            "TEXT" => GetMessage("SEO_META_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect("sotbit.seometa_section_edit.php?ID=" . $f_ID)
        ];
    }

    $arActions[] = [
        "ICON" => "copy",
        "DEFAULT" => true,
        "TEXT" => GetMessage("SEO_META_COPY"),
        "ACTION" => $lAdmin->ActionDoGroup($f_T . $f_ID, "copy", 'parent=' . $parent)
    ];
    if ($POST_RIGHT >= "W") {
        $arActions[] = [
            "ICON" => "delete",
            "TEXT" => GetMessage("SEO_META_DEL"),
            "ACTION" => "if(confirm('" . GetMessage('SEO_META_DEL_CONF') . "')) " . $lAdmin->ActionDoGroup($f_T . $f_ID,
                    "delete")
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
		["title"=>GetMessage("SEO_META_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()],
		["counter"=>true, "title"=>GetMessage("SEO_META_LIST_CHECKED"), "value"=>"0"],
    ]
);
$lAdmin->AddGroupActionTable([
	"delete" => GetMessage("SEO_META_LIST_DELETE"),
	"copy" => GetMessage("SEO_META_LIST_COPY"),
	"activate" => GetMessage("SEO_META_LIST_ACTIVATE"),
	"deactivate" => GetMessage("SEO_META_LIST_DEACTIVATE"),
]);
$lAdmin->CheckListMode();
$APPLICATION->SetTitle(GetMessage("SEO_META_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$oFilter = new CAdminFilter(
	$sTableID."_filter",
	[
		GetMessage("SEO_META_ID"),
		GetMessage("SEO_META_NAME"),
		GetMessage("SEO_META_ACTIVE"),
    ]
);
?>
<form name="find_form" method="get" action="<?= $APPLICATION->GetCurPage();?>">
	<?$oFilter->Begin();?>
	<tr>
	    <td><b><?=GetMessage("SEO_META_FIND")?>:</b></td>
	    <td>
	        <input type="text" size="25" name="find" value="<?= htmlspecialchars($find)?>" title="<?=GetMessage("SEO_META_FIND_TITLE")?>">
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
		<td><?=GetMessage("SEO_META_ID")?>:</td>
		<td>
    		<input type="text" name="find_id" size="47" value="<?= htmlspecialchars($find_id)?>">
	    </td>
    </tr>
	<tr>
        <td><?=GetMessage("SEO_META_NAME")?>:</td>
        <td>
            <input type="text" name="find_name" size="47" value="<?= htmlspecialchars($find_name)?>">
        </td>
	</tr>
	<tr>
	    <td><?=GetMessage("SEO_META_ACTIVE")?>:</td>
        <td>
        <?
            $arr = [
                "reference" => [
                    GetMessage("SEO_META_POST_YES"),
                    GetMessage("SEO_META_POST_NO"),
                ],
                "reference_id" => [
                    "Y",
                    "N",
                ]
            ];
            echo SelectBoxFromArray("find_active", $arr, $find_active, "", "");
        ?>
        </td>
	</tr>
	<?
	$oFilter->Buttons(["table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPageParam(),"form"=>"find_form"]);
	$oFilter->End();
	?>
</form>

<script>
    function exportCondition(offset = 0, limit = 100, newFile = 1, count = 0, totalCount = 0) {
        const node = BX('seocond_export');
        if(node.style.display === 'block'){
            const nodeProgress = BX('sitemap_progress');
            const nodeProgressStart = BX('sitemap_progress_start');
            nodeProgress.innerHTML = nodeProgressStart.innerHTML;
        }else{
            node.style.display = 'block';
        }
        const exportCondition = BX.ajax.runAction('sotbit:seometa.ExcelExportImport.exportCondition', {
            data: {offset, limit, newFile, count, totalCount}
        }).then(response => {
            let count = response.data.COUNT;
            if (count > 0) {
                newFile = 0;
                const nodeProgressStart = BX('sitemap_progress_start');
                nodeProgressStart.innerHTML = response.data.PROGRESSBAR;
                this.exportCondition(response.data.OFFSET, 100, newFile, count, response.data.TOTAL_COUNT);
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
        const sheetName = 'seometa_condition';
        const deleteFile = BX.ajax.runAction('sotbit:seometa.ExcelExportImport.deleteFile', {
            data: {sheetName}
        }).then(response => {
        }, error => {
            console.error(error);
        });
    }
</script>


<div id="seocond_export" style="display: none;">
    <div id="sitemap_progress">
        <?=SitemapRuntime::showProgress(Loc::getMessage('SEO_META_COND_RUN_INIT'), Loc::getMessage('SEO_META_COND_RUN_TITLE'), 0)?>
    </div>
    <div id="sitemap_progress_start" style="display: none">
        <?=SitemapRuntime::showProgress(Loc::getMessage('SEO_META_COND_RUN_INIT'), Loc::getMessage('SEO_META_COND_RUN_TITLE'), 0)?>
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

$lAdmin->DisplayList();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>

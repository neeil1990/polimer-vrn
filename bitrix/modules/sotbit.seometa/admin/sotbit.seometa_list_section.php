<?

use Bitrix\Landing\Internals\LandingTable;
use Bitrix\Main\Loader;
use Sotbit\Seometa\Orm\ConditionTable;

IncludeModuleLangFile(__FILE__);

CUtil::JSPostUnescape();

global $APPLICATION;
global $USER;

$moduleId = 'sotbit.seometa';
Loader::includeModule($moduleId);

IncludeModuleLangFile(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/iblock/classes/general/subelement.php');

$strSubElementAjaxPath = '/bitrix/admin/synpaw.seofilter_admin.php?lang=' . urlencode(LANGUAGE_ID) . '&SECTION_ID=' .
    $GLOBALS['SPW_SECTION_ID'] . '&INFOBLOCK=' . $GLOBALS['SPW_INFOBLOCK'];

$sTableID = 'tbl_sf_landings_' . md5('.');
$arHideFields = ['SECTION_ID', 'INFOBLOCK'];
$by = $_REQUEST['by'] ?: 'ID';
$byOrder = $_REQUEST['order'] ?: 'DESC';
$lAdmin = new CAdminSubList($sTableID, false, $strSubElementAjaxPath, $arHideFields);
$arFilterFields = ['SECTION_ID', 'INFOBLOCK'];
$lAdmin->InitFilter($arFilterFields);
$arFilter = [
    'INFOBLOCK' => $GLOBALS['SPW_INFOBLOCK'],
];
//TODO: what is it -- CSynPawPermission?
if (CSynPawPermission::canWrite() && ($arID = $lAdmin->GroupAction())) {
    if ($_REQUEST['action_target'] == 'selected') {
        $arID = [];
        $dbResultList = LandingTable::getList([
            'select' => ["ID"],
            'order' => [$by => $order],
            'filter' => $arFilter
        ]);
        while ($arResult = $dbResultList->Fetch()) {
            $arID[] = $arResult['ID'];
        }
    }

    foreach ($arID as $ID) {
        if (mb_strlen($ID) <= 0) {
            continue;
        }

        switch ($_REQUEST['action']) {
            case "delete":
                @set_time_limit(0);
                $DB->StartTransaction();
                if (!LandingTable::delete($ID)) {
                    $DB->Rollback();
                    if ($ex = $APPLICATION->GetException()) {
                        $lAdmin->AddGroupError($ex->GetString(),
                            $ID);
                    } else {
                        $lAdmin->AddGroupError(str_replace("#ID#",
                            $ID,
                            GetMessage("LANDING_ERROR_DELETE")),
                            $ID);
                    }
                } else {
                    $DB->Commit();
                }
                break;
            case "activate":
            case "deactivate":
                $arFields = [
                    "ACTIVE" => (($_REQUEST['action'] == "activate") ? "Y" : "N")
                ];
                if (!LandingTable::update($ID,
                    $arFields)) {
                    if ($ex = $APPLICATION->GetException()) {
                        $lAdmin->AddGroupError($ex->GetString(),
                            $ID);
                    } else {
                        $lAdmin->AddGroupError(str_replace("#ID#",
                            $ID,
                            GetMessage("LANDING_ERROR_UPDATE")),
                            $ID);
                    }
                }
                break;
        }
    }
}

$lAdmin->AddHeaders([
    [
        "id" => "ID",
        "content" => "ID",
        "sort" => "ID",
        "default" => true
    ],
    [
        "id" => "ACTIVE",
        "content" => GetMessage("LANDING_ENTITY_ACTIVE_FIELD"),
        "sort" => "ACTIVE",
        "default" => true
    ],
    [
        "id" => "DATE_CHANGE",
        "content" => GetMessage('LANDING_ENTITY_DATE_CHANGE_FIELD'),
        "sort" => "DATE_CHANGE",
        "default" => true
    ],
    [
        "id" => "CREATED_BY",
        "content" => GetMessage('LANDING_ENTITY_CREATED_BY_FIELD'),
        "sort" => "CREATED_BY",
        "default" => false
    ],
    [
        "id" => "DATE_CREATE",
        "content" => GetMessage('LANDING_ENTITY_DATE_CREATE_FIELD'),
        "sort" => "DATE_CREATE",
        "default" => false
    ],
    [
        "id" => "INFOBLOCK",
        "content" => GetMessage('LANDING_ENTITY_INFOBLOCK_FIELD'),
        "sort" => "INFOBLOCK",
        "default" => false
    ],

]);

$arSelectFieldsMap = [
    "ID" => true,
    "ACTIVE" => false,
    "INFOBLOCK" => false,
    "DATE_CHANGE" => false,
    "CREATED_BY" => false,
    "DATE_CREATE" => false,
];
$arSelectFields = $lAdmin->GetVisibleHeaderColumns();
if (!in_array('ID', $arSelectFields)) {
    $arSelectFields[] = 'ID';
}

$arSelectFields = array_values($arSelectFields);
$arSelectFieldsMap = array_merge($arSelectFieldsMap, array_fill_keys($arSelectFields, true));
$arNavParams = (isset($_REQUEST['mode']) && 'excel' == $_REQUEST["mode"]
    ? false
    : ["nPageSize" => CAdminSubResult::GetNavSize($sTableID, 20, $lAdmin->GetListUrl(true))]
);
$dbLandingFilter = [
    'order' => [$by => $byOrder],
    'count_total' => true,
    'filter' => $arFilter,
    'select' => $arSelectFields
];
$dbResultList = ConditionTable::getList($dbLandingFilter);
$dbResultList = new CAdminSubResult($dbResultList, $sTableID, $lAdmin->GetListUrl(true));
$dbResultList->NavStart();
$lAdmin->NavText($dbResultList->GetNavPrint(htmlspecialcharsbx(GetMessage("SYNPAW_SEOFILTER_NAV"))));
$arRows = [];
$arUserID = [];
while ($arLanding = $dbResultList->Fetch()) {
    $edit_url = "/bitrix/admin/synpaw.seofilter_edit.php?bxpublic=Y&ID={$arLanding['ID']}&lang=".LANGUAGE_ID."&INFOBLOCK=".$GLOBALS['SPW_INFOBLOCK']."&SECTION_ID=".$GLOBALS['SPW_SECTION_ID']. "&TEMPLATE=". $arLanding['TEMPLATE'];
    $copy_url = $edit_url . '&COPY=Y';
    $arLanding['ID'] = (int)$arLanding['ID'];
    if ($arSelectFieldsMap['CREATED_BY']) {
        $arLanding['CREATED_BY'] = (int)$arLanding['CREATED_BY'];
        if (0 < $arLanding['CREATED_BY']) {
            $arUserID[$arLanding['CREATED_BY']] = true;
        }
    }

    if ($arSelectFieldsMap['MODIFIED_BY']) {
        $arLanding['MODIFIED_BY'] = (int)$arLanding['MODIFIED_BY'];
        if (0 < $arLanding['MODIFIED_BY']) {
            $arUserID[$arLanding['MODIFIED_BY']] = true;
        }
    }

    $arRows[$arLanding['ID']] = $row =& $lAdmin->AddRow($arLanding['ID'], $arLanding, $edit_url, '', true);
    if ($arSelectFieldsMap['DATE_CREATE']) {
        $row->AddCalendarField("DATE_CREATE", false);
    }

    if ($arSelectFieldsMap['DATE_CHANGE']) {
        $row->AddCalendarField("DATE_CHANGE", false);
    }

    $row->AddField("ID", $arLanding['ID']);
    if ($arSelectFieldsMap['ACTIVE']) {
        $row->AddCheckField("ACTIVE");
    }

    if ($arSelectFieldsMap['TEMPLATE']) {
        $row->AddCheckField("TEMPLATE", $arLanding["TEMPLATE"]);
    }

    $arActions = [];
    if (CSynPawPermission::canWrite()) {
        $arActions[] = [
            "ICON" => "edit",
            "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_EDIT"),
            "DEFAULT" => true,
            "ACTION" => "(new BX.CAdminDialog({
                'content_url': '$edit_url',
                'draggable': true,
                'width': '900',
                'min_width': '900',
                'resizable': true,
                'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
            })).Show();",
        ];

        $arActions[] = [
            "ICON" => "copy",
            "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_COPY"),
            "DEFAULT" => true,
            "ACTION" => "(new BX.CAdminDialog({
                'content_url': '$copy_url',
                'draggable': true,
                'width': '900',
                'min_width': '900',
                'resizable': true,
                'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
            })).Show();",
        ];

        $arActions[] = ["SEPARATOR" => true];
        $arActions[] = [
            "ICON" => "delete",
            "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_DEL"),
            "ACTION" => "if(confirm('" . \CUtil::JSEscape(GetMessage('SYNPAW_SEOFILTER_BTN_DEL_CONF')) . "')) " . $lAdmin->ActionDoGroup($arLanding['ID'],
                    "delete")
        ];
    }

    $row->AddActions($arActions);
}

// END WHILE

if (isset($row)) {
    unset($row);
}

$arUserList = [];
$strNameFormat = CSite::GetNameFormat(true);
if ($arSelectFieldsMap['CREATED_BY'] || $arSelectFieldsMap['MODIFIED_BY']) {
    if (!empty($arUserID)) {
        $byUser = 'ID';
        $byOrder = 'ASC';
        $rsUsers = CUser::GetList(
            $byUser,
            $byOrder,
            ['ID' => implode(' | ', array_keys($arUserID))],
            [
                'FIELDS' => [
                    'ID',
                    'LOGIN',
                    'NAME',
                    'LAST_NAME',
                    'SECOND_NAME',
                    'EMAIL'
                ]
            ]
        );
        while ($arOneUser = $rsUsers->Fetch()) {
            $arOneUser['ID'] = (int)$arOneUser['ID'];
            $arUserList[$arOneUser['ID']] = '<a href="/bitrix/admin/user_edit.php?lang=' . LANGUAGE_ID . '&ID=' . $arOneUser['ID'] . '">'
                . \CUser::FormatName($strNameFormat, $arOneUser) . '</a>';
        }
    }

    /**
     * @var $row CAdminSubListRow
     */
    foreach ($arRows as &$row) {
        if ($arSelectFieldsMap['CREATED_BY']) {
            $strCreatedBy = '';
            if (0 < $row->arRes['CREATED_BY'] && isset($arUserList[$row->arRes['CREATED_BY']])) {
                $strCreatedBy = $arUserList[$row->arRes['CREATED_BY']];
            }

            $row->AddViewField("CREATED_BY", $strCreatedBy);
        }
        if ($arSelectFieldsMap['MODIFIED_BY']) {
            $strModifiedBy = '';
            if (0 < $row->arRes['MODIFIED_BY'] && isset($arUserList[$row->arRes['MODIFIED_BY']])) {
                $strModifiedBy = $arUserList[$row->arRes['MODIFIED_BY']];
            }

            $row->AddViewField("MODIFIED_BY", $strModifiedBy);
        }
    }

    if (isset($row)) {
        unset($row);
    }
}

$lAdmin->AddFooter(
    [
        [
            "title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
            "value" => $dbResultList->SelectedRowsCount()
        ],
        [
            "counter" => true,
            "title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
            "value" => "0"
        ],
    ]
);

if (CSynPawPermission::canWrite()) {
    $lAdmin->AddGroupActionTable(
        [
            "delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
            "activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
            "deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
        ]
    );
}

if (!isset($_REQUEST["mode"]) || ('excel' != $_REQUEST["mode"] && 'subsettings' != $_REQUEST["mode"])) {
    ?>
    <script type="text/javascript">
        function ShowNewSFLanding(iblockId, sectionId, template) {
            var PostParams = {
                lang: '<?= LANGUAGE_ID; ?>',
                INFOBLOCK: iblockId,
                SECTION_ID: sectionId,
                TEMPLATE: template,
                id: 0,
                bxpublic: 'Y',
                sessid: BX.bitrix_sessid()
            };
            (new BX.CAdminDialog({
                'content_url': '/bitrix/admin/synpaw.seofilter_edit.php',
                'content_post': PostParams,
                'draggable': true,
                'resizable': true,
                'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
            })).Show();
        }
    </script><?

    $aContext = [];
    if (CSynPawPermission::canWrite()) {
        $menu = [];
        $menu[] = [
            "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD"),
            "TITLE" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD"),
            "LINK" => "javascript:ShowNewSFLanding({$GLOBALS['SPW_INFOBLOCK']},{$GLOBALS['SPW_SECTION_ID']},'N')",
        ];
        $menu[] = [
            "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD_TEMPLATE"),
            "TITLE" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD_TEMPLATE"),
            "LINK" => "javascript:ShowNewSFLanding({$GLOBALS['SPW_INFOBLOCK']},{$GLOBALS['SPW_SECTION_ID']},'Y')",
        ];
        $aContext[] = [
            "ICON" => "btn_new",
            "TEXT" => htmlspecialcharsex(GetMessage("SYNPAW_SEOFILTER_BTN_GROUP_ADD")),
            "MENU" => $menu
        ];
    }

    $aContext[] = [
        "ICON" => "btn_sub_refresh",
        "TEXT" => htmlspecialcharsex(GetMessage("SYNPAW_SEOFILTER_BTN_REFRESH")),
        "LINK" => "javascript:" . $lAdmin->ActionAjaxReload($lAdmin->GetListUrl(true)),
        "TITLE" => GetMessage("SYNPAW_SEOFILTER_BTN_REFRESH"),
    ];

    $lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>
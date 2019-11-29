<?
use Sotbit\Seometa\ConditionTable;
use Sotbit\Seometa\SitemapSectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);

CUtil::JSPostUnescape();

global $APPLICATION;
global $USER;

$id_module='sotbit.seometa';
Loader::includeModule($id_module);

global $APPLICATION;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");


IncludeModuleLangFile(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/iblock/classes/general/subelement.php');

$strSubElementAjaxPath = '/bitrix/admin/synpaw.seofilter_admin.php?lang=' . urlencode(LANGUAGE_ID) . '&SECTION_ID=' .
    $GLOBALS['SPW_SECTION_ID'] . '&INFOBLOCK=' . $GLOBALS['SPW_INFOBLOCK'];

$sTableID = 'tbl_sf_landings_' . md5('.');
$arHideFields = array('SECTION_ID', 'INFOBLOCK');

$by = isset($_REQUEST['by'])?$_REQUEST['by']:'ID';
$byOrder = isset($_REQUEST['order'])?$_REQUEST['order']:'DESC';

$lAdmin = new CAdminSubList($sTableID, false, $strSubElementAjaxPath, $arHideFields);

$arFilterFields = array('SECTION_ID', 'INFOBLOCK');

$lAdmin->InitFilter($arFilterFields);

$arFilter = array(
    'INFOBLOCK' => $GLOBALS['SPW_INFOBLOCK'],
    //'SECTION_ID' => $GLOBALS['SPW_SECTION_ID']
);

if (CSynPawPermission::canWrite() && ($arID = $lAdmin->GroupAction()))
{
    if ($_REQUEST['action_target']=='selected')
    {
        $arID = array();
        $dbResultList = LandingTable::getList(array(
            'select' => array("ID"),
            'order' =>array($by => $order),
                'filter' => $arFilter
        ));
        while ($arResult = $dbResultList->Fetch())
            $arID[] = $arResult['ID'];
    }

    foreach ($arID as $ID)
    {
        if (strlen($ID) <= 0)
            continue;

        switch ($_REQUEST['action'])
        {
            case "delete":
                @set_time_limit(0);
                $DB->StartTransaction();
                if (!LandingTable::delete($ID))
                {
                    $DB->Rollback();

                    if ($ex = $APPLICATION->GetException())
                        $lAdmin->AddGroupError($ex->GetString(), $ID);
                    else
                        $lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("LANDING_ERROR_DELETE")), $ID);
                }
                else
                {
                    $DB->Commit();
                }
                break;
            case "activate":
            case "deactivate":
                $arFields = array(
                    "ACTIVE" => (($_REQUEST['action']=="activate") ? "Y" : "N")
                );
                if (!LandingTable::update($ID, $arFields))
                {
                    if ($ex = $APPLICATION->GetException())
                        $lAdmin->AddGroupError($ex->GetString(), $ID);
                    else
                        $lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("LANDING_ERROR_UPDATE")), $ID);
                }
                break;
        }
    }
}

$lAdmin->AddHeaders(array(
    array(
        "id" => "ID",
        "content" => "ID",
        "sort" => "ID",
        "default" => true
    ),
    array(
        "id" => "ACTIVE",
        "content" => GetMessage("LANDING_ENTITY_ACTIVE_FIELD"),
        "sort" => "ACTIVE",
        "default" => true
    ),
    array(
        "id" => "DATE_CHANGE",
        "content" => GetMessage('LANDING_ENTITY_DATE_CHANGE_FIELD'),
        "sort" => "DATE_CHANGE",
        "default" => true
    ),
    array(
        "id" => "CREATED_BY",
        "content" => GetMessage('LANDING_ENTITY_CREATED_BY_FIELD'),
        "sort" => "CREATED_BY",
        "default" => false
    ),
    array(
        "id" => "DATE_CREATE",
        "content" => GetMessage('LANDING_ENTITY_DATE_CREATE_FIELD'),
        "sort" => "DATE_CREATE",
        "default" => false
    ),
    array(
        "id" => "INFOBLOCK",
        "content" => GetMessage('LANDING_ENTITY_INFOBLOCK_FIELD'),
        "sort" => "INFOBLOCK",
        "default" => false
    ),

));

$arSelectFieldsMap = array(
    "ID" => true,
    "ACTIVE" => false,
    "INFOBLOCK" => false,

    "DATE_CHANGE" => false,
    "CREATED_BY" => false,
    "DATE_CREATE" => false,
);

$arSelectFields = $lAdmin->GetVisibleHeaderColumns();
if (!in_array('ID', $arSelectFields))
    $arSelectFields[] = 'ID';

$arSelectFields = array_values($arSelectFields);
$arSelectFieldsMap = array_merge($arSelectFieldsMap, array_fill_keys($arSelectFields, true));

$arNavParams = (isset($_REQUEST['mode']) && 'excel' == $_REQUEST["mode"]
    ? false
    : array("nPageSize" => CAdminSubResult::GetNavSize($sTableID, 20, $lAdmin->GetListUrl(true)))
);

$dbLandingFilter = array(
    'order' => array($by => $byOrder),
    'count_total' => true,
    'filter' => $arFilter,
    'select' => $arSelectFields
);
$dbResultList = ConditionTable::getList($dbLandingFilter);
$dbResultList = new CAdminSubResult($dbResultList, $sTableID, $lAdmin->GetListUrl(true));
$dbResultList->NavStart();
$lAdmin->NavText($dbResultList->GetNavPrint(htmlspecialcharsbx(GetMessage("SYNPAW_SEOFILTER_NAV"))));

$arRows = array();
$arUserID = array();
while ($arLanding = $dbResultList->Fetch()) {
    $edit_url = "/bitrix/admin/synpaw.seofilter_edit.php?bxpublic=Y&ID={$arLanding['ID']}&lang=".LANGUAGE_ID."&INFOBLOCK=".$GLOBALS['SPW_INFOBLOCK']."&SECTION_ID=".$GLOBALS['SPW_SECTION_ID']. "&TEMPLATE=". $arLanding['TEMPLATE'];
    $copy_url = $edit_url . '&COPY=Y';
    $arLanding['ID'] = (int)$arLanding['ID'];

    if ($arSelectFieldsMap['CREATED_BY']) {
        $arLanding['CREATED_BY'] = (int)$arLanding['CREATED_BY'];
        if (0 < $arLanding['CREATED_BY'])
            $arUserID[$arLanding['CREATED_BY']] = true;
    }

    if ($arSelectFieldsMap['MODIFIED_BY']) {
        $arLanding['MODIFIED_BY'] = (int)$arLanding['MODIFIED_BY'];
        if (0 < $arLanding['MODIFIED_BY'])
            $arUserID[$arLanding['MODIFIED_BY']] = true;
    }

    $arRows[$arLanding['ID']] = $row =& $lAdmin->AddRow($arLanding['ID'], $arLanding, $edit_url, '', true);
    if ($arSelectFieldsMap['DATE_CREATE'])
        $row->AddCalendarField("DATE_CREATE", false);
    if ($arSelectFieldsMap['DATE_CHANGE'])
        $row->AddCalendarField("DATE_CHANGE", false);

    $row->AddField("ID", $arLanding['ID']);
    if ($arSelectFieldsMap['ACTIVE'])
        $row->AddCheckField("ACTIVE");
    if ($arSelectFieldsMap['TEMPLATE'])
        $row->AddCheckField("TEMPLATE", $arLanding["TEMPLATE"]);


    $arActions = array();
    if (CSynPawPermission::canWrite()) {
        $arActions[] = array(
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
        );

        $arActions[] = array(
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
        );

        $arActions[] = array("SEPARATOR" => true);
        $arActions[] = array("ICON" => "delete", "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_DEL"), "ACTION" => "if(confirm('" . \CUtil::JSEscape(GetMessage('SYNPAW_SEOFILTER_BTN_DEL_CONF')) . "')) " . $lAdmin->ActionDoGroup($arLanding['ID'], "delete"));
    }

    $row->AddActions($arActions);
}

// END WHILE

if (isset($row))
    unset($row);

$arUserList = array();
$strNameFormat = CSite::GetNameFormat(true);

if ($arSelectFieldsMap['CREATED_BY'] || $arSelectFieldsMap['MODIFIED_BY']) {
    if (!empty($arUserID)) {
        $byUser = 'ID';
        $byOrder = 'ASC';
        $rsUsers = CUser::GetList(
            $byUser,
            $byOrder,
            array('ID' => implode(' | ', array_keys($arUserID))),
            array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL'))
        );
        while ($arOneUser = $rsUsers->Fetch()) {
            $arOneUser['ID'] = (int)$arOneUser['ID'];
            $arUserList[$arOneUser['ID']] = '<a href="/bitrix/admin/user_edit.php?lang=' . LANGUAGE_ID . '&ID=' . $arOneUser['ID'] . '">' . \CUser::FormatName($strNameFormat, $arOneUser) . '</a>';
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
    if (isset($row))
        unset($row);
}

$lAdmin->AddFooter(
    array(
        array(
            "title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
            "value" => $dbResultList->SelectedRowsCount()
        ),
        array(
            "counter" => true,
            "title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
            "value" => "0"
        ),
    )
);

if (CSynPawPermission::canWrite()) {
    $lAdmin->AddGroupActionTable(
        array(
            "delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
            "activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
            "deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
        )
    );
}

if (!isset($_REQUEST["mode"]) || ('excel' != $_REQUEST["mode"] && 'subsettings' != $_REQUEST["mode"])) {
    ?>
    <script type="text/javascript">
        function ShowNewSFLanding(iblockId, sectionId, template) {
            var PostParams = {
                lang: '<? echo LANGUAGE_ID; ?>',
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

    $aContext = array();
    if (CSynPawPermission::canWrite()) {
        $menu = array();
        $menu[] = array(
            "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD"),
            "TITLE" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD"),
            "LINK" => "javascript:ShowNewSFLanding({$GLOBALS['SPW_INFOBLOCK']},{$GLOBALS['SPW_SECTION_ID']},'N')",
//            "SHOW_TITLE" => true
        );
        $menu[] = array(
            "TEXT" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD_TEMPLATE"),
            "TITLE" => GetMessage("SYNPAW_SEOFILTER_BTN_ADD_TEMPLATE"),
            "LINK" => "javascript:ShowNewSFLanding({$GLOBALS['SPW_INFOBLOCK']},{$GLOBALS['SPW_SECTION_ID']},'Y')",
//            "SHOW_TITLE" => true
        );
        $aContext[] = array(
            "ICON" => "btn_new",
            "TEXT" => htmlspecialcharsex(GetMessage("SYNPAW_SEOFILTER_BTN_GROUP_ADD")),
            "MENU" => $menu
        );
    }

    $aContext[] = array(
        "ICON" => "btn_sub_refresh",
        "TEXT" => htmlspecialcharsex(GetMessage("SYNPAW_SEOFILTER_BTN_REFRESH")),
        "LINK" => "javascript:" . $lAdmin->ActionAjaxReload($lAdmin->GetListUrl(true)),
        "TITLE" => GetMessage("SYNPAW_SEOFILTER_BTN_REFRESH"),
    );

    $lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>
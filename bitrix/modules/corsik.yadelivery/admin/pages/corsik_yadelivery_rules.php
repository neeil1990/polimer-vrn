<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Corsik\YaDelivery\Helper;
use Corsik\YaDelivery\Table\RulesTable;

global $APPLICATION, $by, $order;

$messages = Loc::loadLanguageFile(__FILE__);
Loader::includeModule("iblock");
Loader::includeModule("corsik.yadelivery");
$module_id = 'corsik.yadelivery';

if (defined("SM_VERSION")) {
    $version = constant("SM_VERSION");
}


if ($version >= 19 || !isset($version)) {

    $sTableID = "corsik_yadelivery_rules";
    $editPage = 'corsik_yadelivery_rule_edit.php';
    $rulesList = new RulesTable();
    $ruleLink = '/bitrix/admin/' . $editPage . '?lang=' . LANGUAGE_ID;
    $aAdditionalMenu = Helper::getDefaultRulesLink($ruleLink);
    $oSort = new CAdminUiSorting($sTableID, "sort", "DESC");
    $arOrder = (strtoupper($by) === "ID" ? [$by => $order] : [$by => $order, "ID" => "ASC"]);
    $lAdmin = new CAdminUiList($sTableID, $oSort);

    $arFilterFields = [
        "find_id",
        "find_active",
        "find_sort",
        "find_name",
        "find_type",
        "find_rules",
        "find_site_id",
    ];

    $lAdmin->InitFilter($arFilterFields);

    $arFilter = [
        "ID" => $find_id,
        "ACTIVE" => $find_active,
        "SORT" => $find_sort,
        "NAME" => $find_name,
        "TYPE" => $find_type,
        "RULES" => $find_rules,
        "SITE_ID" => $find_site_id
    ];

    $filterFields = [
        [
            "id" => "ID",
            "name" => "ID",
            "default" => true
        ],
        [
            "id" => "NAME",
            "name" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_NAME"),
            "filterable" => "?",
            "quickSearch" => "?",
            "default" => true,
        ],
        [
            "id" => "ACTIVE",
            "name" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ACTIVE"),
            "type" => "list",
            "items" => [
                "Y" => Loc::getMessage("IBLOCK_YES"),
                "N" => Loc::getMessage("IBLOCK_NO")
            ],
            "filterable" => ""
        ],
        [
            "id" => "TYPE",
            "name" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_TYPE"),
            "type" => "list",
            "items" => [
                "price" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_TYPE_PRICE"),
                "weight" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_TYPE_WEIGHT")
            ],
            "filterable" => ""
        ],
        [
            "id" => "RULE",
            "name" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE"),
//        "filterable" => "?"
        ],
        [
            "id" => "SITE_ID",
            "name" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SITE_ID"),
//        "filterable" => "?"
        ],
    ];

    $arFilter = [];
    $lAdmin->AddFilter($filterFields, $arFilter);

    if ($arID = $lAdmin->GroupAction()) {
        foreach ($arID as $ID) {
            if (strlen($ID) <= 0)
                continue;

            switch ($_REQUEST['action']) {
                case "delete":
                    $res = $rulesList->delete($ID);
                    break;
                case "activate":
                case "deactivate":
                    $arFields = ["ACTIVE" => ($_REQUEST['action'] == "activate" ? "Y" : "N")];
                    $res = $rulesList->update($ID, $arFields);
                    if (!$res->isSuccess()) {
                        $lAdmin->AddGroupError(Loc::getMessage("IBLOCK_ADM_UPD_ERROR") . $res->getErrorMessages(), $ID);
                    }
                    break;
            }
        }
    }

    $arHeader = [
        [
            "id" => "ID",
            "content" => 'ID',
            "sort" => "ID",
            "default" => true,
        ],
        [
            "id" => "NAME",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_NAME"),
            "sort" => "NAME",
            "default" => true,
        ],
        [
            "id" => "TYPE",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_TYPE"),
            "sort" => "TYPE",
            "default" => true,
        ],
        [
            "id" => "SORT",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SORT"),
            "sort" => "SORT",
            "default" => true,
            "align" => "right",
        ],
        [
            "id" => "ACTIVE",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ACTIVE"),
            "sort" => "ACTIVE",
            "default" => true,
            "align" => "center",
        ],
        [
            "id" => "RULE",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE"),
            "sort" => "RULE",
        ],
        [
            "id" => "SITE_ID",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SITE_ID"),
            "sort" => "SITE_ID",
        ],
    ];

    $lAdmin->AddHeaders($arHeader);
    $queryList = [
        'order' => $arOrder,
        'select' => ['*'],
        'filter' => $arFilter
    ];

    $dbResultList = $rulesList->getList($queryList);
    $rsIBlocks = new CAdminUiResult($dbResultList, $sTableID);
    $rsIBlocks->NavStart();

    $lAdmin->SetNavigationParams($rsIBlocks);

    while ($dbrs = $rsIBlocks->NavNext(true, "f_")) {
        $row =& $lAdmin->AddRow($f_ID, $dbrs);
        $editAction = Helper::getEditRuleLink($ruleLink, $f_ID);
        /**
         * Здесь только столбы которые необходимо изменить
         */
        $row->AddViewField("NAME", '<a href="javascript:void(0);" onclick="' . $editAction . '">' . $f_NAME . '</a>');
        $row->AddCheckField("ACTIVE");
        $row->AddViewField("TYPE", Loc::getMessage("CORSIK_DELIVERY_SERVICE_TYPE_" . strtoupper($f_TYPE)));

        $arActions = [];
        $arActions[] = [
            "ICON" => "edit",
            "TEXT" => Loc::getMessage("MAIN_ADMIN_MENU_EDIT"),
            "ACTION" => $editAction,
        ];

        $arActions[] = [
            "ICON" => "delete",
            "TEXT" => Loc::getMessage("MAIN_ADMIN_MENU_DELETE"),
            "ACTION" => "if(confirm('" . GetMessageJS("CORSIK_DELIVERY_SERVICE_ALERT_DELETE") . "')) " . $lAdmin->ActionDoGroup($f_ID, "delete", "&type=" . htmlspecialcharsbx($type) . "&lang=" . LANGUAGE_ID),
        ];


        if (count($arActions))
            $row->AddActions($arActions);
    }

    $lAdmin->AddFooter(
        [
            ["title" => Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"), "value" => $rsIBlocks->SelectedRowsCount()],
            ["counter" => true, "title" => Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value" => "0"],
        ]
    );

    $aContext = [
        [
            "ICON" => "btn_new",
            "TEXT" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ADD_BTN_RULE"),
            "DISABLE" => true,
            "MENU" => $aAdditionalMenu,
        ],
    ];

    $lAdmin->AddAdminContextMenu($aContext, false);
    $lAdmin->AddGroupActionTable([
        "delete" => true,
        "activate" => Loc::getMessage("MAIN_ADMIN_LIST_ACTIVATE"),
        "deactivate" => Loc::getMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
    ]);
    $lAdmin->CheckListMode();
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
    require_once dirname(__DIR__) . '/includes/contact_banner.php';
    $lAdmin->DisplayFilter($filterFields);
    $lAdmin->DisplayList();

} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    print(Loc::getMessage("CORSIK_DELIVERY_SERVICE_UPDATE_BITRIX"));
}
$APPLICATION->SetTitle(Loc::getMessage("CORSIK_DELIVERY_SERVICE_CONDITIONS"));
?>
    <script type="text/javascript" src="/bitrix/js/<?= $module_id ?>/admin/admin.rules.js"></script>
<?
//require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");

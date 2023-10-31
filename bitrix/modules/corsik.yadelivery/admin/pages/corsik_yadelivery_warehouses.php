<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Corsik\YaDelivery\Table\WarehousesTable;

global $APPLICATION, $by, $order;

Loc::loadLanguageFile(__FILE__);
Loader::includeModule("iblock");
Loader::includeModule("corsik.yadelivery");
if (defined("SM_VERSION")) {
    $version = constant("SM_VERSION");
}

$APPLICATION->SetTitle(Loc::getMessage("CORSIK_DELIVERY_SERVICE_WAREHOUSES"));

if ($version >= 19 || !isset($version)) {
    $sTableID = "corsik_yadelivery_warehouses";
    $warehousesList = new WarehousesTable();

    $oSort = new CAdminUiSorting($sTableID, "sort", "DESC");
    $arOrder = (strtoupper($by) === "ID" ? [$by => $order] : [$by => $order, "ID" => "ASC"]);
    $lAdmin = new CAdminUiList($sTableID, $oSort);

    $arFilterFields = [
        "find_id",
        "find_active",
        "find_sort",
        "find_name",
        "find_zone_id",
        "find_coordinates",
        "find_site_id",
    ];

    $lAdmin->InitFilter($arFilterFields);
    $arFilter = [
        "ID" => $find_id,
        "ACTIVE" => $find_active,
        "SORT" => $find_sort,
        "NAME" => $find_name,
        "ZONE_ID" => $find_zone_id,
        "COORDINATES" => $find_coordinates,
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
            "default" => true
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
            "id" => "ZONE_ID",
            "name" => Loc::getMessage("CORSIK_YANDEX_ZONE_MAP"),
//        "filterable" => "?"
        ],
        [
            "id" => "COORDINATES",
            "name" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_COORDINATES"),
//        "filterable" => "?"
        ],
        [
            "id" => "SITE_ID",
            "name" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_SITE_ID"),
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
                    $res = $warehousesList->delete($ID);
                    break;
                case "activate":
                case "deactivate":
                    $arFields = ["ACTIVE" => ($_REQUEST['action'] == "activate" ? "Y" : "N")];
                    $res = $warehousesList->update($ID, $arFields);
                    if (!$res->isSuccess()) {
                        $lAdmin->AddGroupError(GetMessage("IBLOCK_ADM_UPD_ERROR") . $res->getErrorMessages(), $ID);
                    }
                    break;
            }
        }
    }

    /**
     * Заголовок
     */
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
            "id" => "ZONE_ID",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SITE_ID"),
            "sort" => "ZONE_ID",
        ],
        [
            "id" => "COORDINATES",
            "content" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_COORDINATES"),
            "sort" => "COORDINATES",
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

    $dbResultList = $warehousesList->getList($queryList);
    $rsIBlocks = new CAdminUiResult($dbResultList, $sTableID);
    $rsIBlocks->NavStart();

    $lAdmin->SetNavigationParams($rsIBlocks);

    while ($dbrs = $rsIBlocks->NavNext(true, "f_")) {
        $row =& $lAdmin->AddRow($f_ID, $dbrs, 'corsik_yadelivery_warehouse_edit.php?ID=' . $f_ID . '&lang=' . LANGUAGE_ID, Loc::getMessage("IBLOCK_ADM_TO_EDIT"));

        /**
         * Здесь только столбы которые необходимо изменить
         */
        $row->AddCheckField("ACTIVE");
        $row->AddViewField("NAME", '<a href="corsik_yadelivery_warehouse_edit.php?ID=' . $f_ID . '&lang=' . LANGUAGE_ID . '">' . $f_NAME . '</a>');

        $arActions = [];
        $arActions[] = [
            "ICON" => "edit",
            "TEXT" => Loc::getMessage("MAIN_ADMIN_MENU_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect("corsik_yadelivery_warehouse_edit.php?ID=" . $f_ID . "&lang=" . LANGUAGE_ID),
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
            "TEXT" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ADD_BTN_ZONE"),
            "LINK" => "corsik_yadelivery_warehouse_edit.php?lang=" . LANGUAGE_ID,
        ],
    ];

    $lAdmin->AddAdminContextMenu($aContext);

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
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");

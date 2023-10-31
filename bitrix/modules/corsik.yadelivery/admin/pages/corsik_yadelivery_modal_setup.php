<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Corsik\YaDelivery\Helper;

global $APPLICATION;

Loc::loadLanguageFile(__FILE__);
$module_id = Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID");
Loader::includeModule('main');
Loader::includeModule($module_id);
Extension::load(["ui.hint", "jquery2"]);

$request = Context::getCurrent()->getRequest();
$helper = Helper::getInstance();

$APPLICATION->SetTitle(Loc::getMessage("CORSIK_DELIVERY_SERVICE_MODAL_SETUP"));

$tabs = [
    [
        'DIV' => 'tab-1',
        'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_MODAL_SETUP"),
        'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_MODAL_SETUP")
    ],
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
$helper::showPublicErrors();

$arrOptions = [
    ["display_mode_modal", Loc::getMessage("CORSIK_DELIVERY_SERVICE_DISPLAY_MODE_MODAL"), "full_mode", ["selectbox", [
        'full_mode' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DISPLAY_FULL_MODE"),
        'light_mode' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DISPLAY_LIGHT_MODE")
    ]]
    ],
    Loc::getMessage("CORSIK_DELIVERY_SERVICE_BALLOON_PRESET"),
    ["show_warehouses", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_WAREHOUSES"), "N", ["checkbox", "Y"]],
    ["show_warehouses_title", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_WAREHOUSES_TITLE"), "N", ["checkbox", "Y"]],
    ["note" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_BALLOON_PRESET_NODE")],
    ["warehouses_preset", Loc::getMessage("CORSIK_DELIVERY_SERVICE_WAREHOUSES_PRESET"), "islands#blueStretchyIcon", ["text", "40"]],
    ["point_start_preset", Loc::getMessage("CORSIK_DELIVERY_SERVICE_POINT_START_PRESET"), "islands#blueDeliveryIcon", ["text", "40"]],
    ["point_stop_preset", Loc::getMessage("CORSIK_DELIVERY_SERVICE_POINT_STOP_PRESET"), "islands#blueHomeIcon", ["text", "40"]],
    Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_OTHER_SETTINGS"),
    ["show_alert_calculate", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_ALERT_CALCULATE"), "Y", ["checkbox", "Y"]],
    ["show_full_modal_header", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_MODAL_HEADER"), "Y", ["checkbox", "Y"]],
    ["show_full_modal_footer", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_MODAL_FOOTER"), "Y", ["checkbox", "Y"]],
    ["show_calculate_price", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_CALCULATE_PRICE"), "Y", ["checkbox", "Y"]],
    ["show_route_calculate", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_ROUTE_CALCULATE"), "Y", ["checkbox", "Y"]],
];

$tabControl = new CAdminTabControl('tabControl', $tabs);
if ($request->getRequestMethod() == "POST" && (($request->getPost("save") || $request->getPost("apply")))) {
    if (!check_bitrix_sessid())
        throw new ArgumentException("Bad sessid.");

    foreach ($arrOptions as $option) {
        if (is_array($option)) {
            if ($option[0] === 'yandex_map_api_key') {
                $helper->__AdmSettingsSaveOption('fileman', $option);
            } else {
                $helper->__AdmSettingsSaveOption($module_id, $option);
            }
        }
    }
}

?>
<?php
require_once dirname(__DIR__) . '/includes/contact_banner.php';
?>
    <form name="ya_delivery" method="POST"
          action="<? echo $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>"
          ENCTYPE="multipart/form-data">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>"/>
        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        foreach ($arrOptions as $option) {
            if ($option[0] === 'yandex_map_api_key') {
                $helper->__AdmSettingsDrawRow('fileman', $option);
            } else {
                $helper->__AdmSettingsDrawRow($module_id, $option);
            }

        }
        $tabControl->Buttons(['btnApply' => false]);
        $tabControl->End();
        ?>
    </form>

    <script>
		BX.ready(() => {
			BX.UI.Hint.init(BX('adm-workarea'));
		});
    </script>
<?
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

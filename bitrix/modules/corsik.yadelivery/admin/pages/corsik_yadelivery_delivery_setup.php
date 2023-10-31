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

$APPLICATION->SetTitle(Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY"));

$tabs = [
	[
		'DIV' => 'tab-1',
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_SETUP"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_SETUP"),
	],
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
$helper::showPublicErrors();

$arrOptions = [
	["enable_delivery", Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_JS_ENABLE"), "Y", ["checkbox", "Y"]],
	//    ["console_logs", Loc::getMessage("CORSIK_DELIVERY_SERVICE_CONSOLE_LOGS"), "Y", ["checkbox", "Y", ""]],
	["note" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_YANDEX_INFO")],
	["yandex_map_api_key", Loc::getMessage("CORSIK_DELIVERY_SERVICE_YANDEX_API_KEY"), "", ["text", "40"]],
	Loc::getMessage("CORSIK_DELIVERY_SERVICE_CALCULATE_SETUP"),
	["disabled_delivery_out", Loc::getMessage("CORSIK_DELIVERY_SERVICE_DISABLED_OUT_DELIVERY"), "N", ["checkbox", "Y"]],
	["disabled_save_order", Loc::getMessage("CORSIK_DELIVERY_SERVICE_DISABLED_SAVE_ORDER"), "N", ["checkbox", "Y"]],
	[
		"delivery_route_building_method",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_ROUTE_BUILDING_METHOD"),
		"total_base",
		[
			"selectbox",
			[
				'route' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ROUTE_METHOD_ROAD"),
				'linear' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ROUTE_METHOD_LINEAR"),
			],
		],
	],
	[
		"delivery_calculation_type",
		Loc::getMessage("CORSIK_DELIVERY_TYPE_ZONE_CALCULATE"),
		"closest_to_address",
		[
			"selectbox",
			[
				'closest_to_address' => Loc::getMessage("CORSIK_DELIVERY_TYPE_ZONE_CLOSEST_TO_ADDRESS"),
				'closest_to_warehouse' => Loc::getMessage("CORSIK_DELIVERY_TYPE_ZONE_CLOSEST_TO_WAREHOUSE"),
			],
		],
	],
	[
		"delivery_calculation_total",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_CALCULATE"),
		"total_base",
		[
			"selectbox",
			[
				'total_base' => Loc::getMessage("CORSIK_DELIVERY_TOTAL_CALCULATE_BASE"),
				'total_discount' => Loc::getMessage("CORSIK_DELIVERY_TOTAL_CALCULATE_DISCOUNT"),
				'total_internal' => Loc::getMessage("CORSIK_DELIVERY_TOTAL_CALCULATE_INTERNAL"),
				'total_discount_internal' => Loc::getMessage("CORSIK_DELIVERY_TOTAL_CALCULATE_INTERNAL_DISCOUNT"),
			],
		],
	],
	[
		"reset_delivery_by_location",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_RESET_DELIVERY_BY_LOCATION"),
		"N",
		["checkbox", "N"],
	],
	["auto_calculate_delivery", Loc::getMessage("CORSIK_DELIVERY_SERVICE_AUTO_CALCULATE"), "N", ["checkbox", "N"]],
	[
		"currency",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_CURRENCY_FORMAT_STRING"),
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_CURRENCY"),
		["text", "15"],
	],
	//    ["required_house", Loc::getMessage("CORSIK_DELIVERY_SERVICE_REQUIRED_HOUSE"), "N", ["checkbox", "N"]],
	Loc::getMessage("CORSIK_DELIVERY_SERVICE_YANDEX_ACTIONS_TITLE"),
	["point_draggable", Loc::getMessage("CORSIK_DELIVERY_SERVICE_POINT_DRAGGABLE"), "Y", ["checkbox", "Y"]],
	["point_selection", Loc::getMessage("CORSIK_DELIVERY_SERVICE_POINT_SELECTION"), "Y", ["checkbox", "Y"]],
	[
		"event_selection",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_POINT_EVENT_SELECTION"),
		"N",
		[
			"selectbox",
			[
				'click' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_POINT_EVENT_CLICK"),
				'dblclick' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_POINT_EVENT_DBLCLICK"),
			],
		],
	],
];
$tabControl = new CAdminTabControl('tabControl', $tabs);
if ($request->getRequestMethod() == "POST" && (($request->getPost("save") || $request->getPost("apply"))))
{
	if (!check_bitrix_sessid())
		throw new ArgumentException("Bad sessid.");

	foreach ($arrOptions as $option)
	{
		if (is_array($option))
		{
			if ($option[0] === 'yandex_map_api_key')
			{
				$helper->__AdmSettingsSaveOption('fileman', $option);
			}
			else
			{
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
		<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>" />
		<?
		$tabControl->Begin();
		$tabControl->BeginNextTab();
		foreach ($arrOptions as $option)
		{
			if ($option[0] === 'yandex_map_api_key')
			{
				$helper->__AdmSettingsDrawRow('fileman', $option);
			}
			else
			{
				$helper->__AdmSettingsDrawRow($module_id, $option);
			}

		}
		$tabControl->Buttons(['btnApply' => false]);
		$tabControl->End();
		?>
	</form>

	<script type="text/javascript" src="/bitrix/js/<?= $module_id ?>/admin/admin.main.js"></script>
	<script>
		BX.ready(() => {
			BX.UI.Hint.init(BX('adm-workarea'));
			const setup = window.PagesSetup;
			setup.toggleNextParameters('enable_delivery');
			setup.togglePointSelection();
		});
	</script>
<?
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

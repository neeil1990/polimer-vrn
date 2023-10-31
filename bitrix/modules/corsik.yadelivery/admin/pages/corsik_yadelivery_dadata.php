<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Corsik\YaDelivery\Admin\TabControl;
use Corsik\YaDelivery\Handler;
use Corsik\YaDelivery\Helper;
use Corsik\YaDelivery\Options;

global $APPLICATION;

Loc::loadLanguageFile(__FILE__);
$module_id = Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID");
Loader::includeModule('main');
Loader::includeModule($module_id);
Extension::load(["ui.hint", "jquery2"]);

$request = Context::getCurrent()->getRequest();
$helper = Helper::getInstance();
$handler = Handler::getInstance();
$arSites = Handler::getSites();
$tabsPrefix = "tabSite";
$adminTabControlName = 'tabControl';
$adminTabSubControlName = 'sitesTab';
$dadata = 'dadata';
$yandex = 'yandex';

$APPLICATION->SetTitle(Loc::getMessage("CORSIK_DELIVERY_SERVICE_DADATA_SETUP"));

$tabs = [
	[
		'DIV' => 'tab-1',
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_SETUP"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_SETUP"),
	],
	[
		'DIV' => "tab_$yandex",
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_YANDEX_TITLE"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_YANDEX_TITLE"),
	],
	[
		'DIV' => "tab_$dadata",
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DADATA_TITLE"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DADATA_TITLE"),
	],
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
$helper::showPublicErrors();
$arMainOptions = [
	Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_SETUP"),
	[
		"type_prompts",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_TYPE_PROMPTS"),
		"yandex",
		[
			"selectbox",
			[
				$yandex => Loc::getMessage("CORSIK_DELIVERY_SERVICE_YANDEX"),
				$dadata => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DADATA"),
			],
		],
	],
	["count_row", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_COUNT_ROW"), "5", ["text", "30"]],
	[
		"note" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_SETUP_DESCRIPTION", [
			"#YANDEX#" => "tab_yandex",
			"#DADATA#" => "tab_dadata",
		]),
	],
];

$arDadataOptions = [
	["note" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_API_KEY_DADATA_INFO")],
	["enable_dadata", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DADATA"), "Y", ["checkbox", "Y"]],
	["api_key_dadata", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_API_KEY_DADATA"), "", ["text", "30"]],
	[
		"division_dadata",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DIVISION"),
		"yandex",
		[
			"selectbox",
			[
				"administrative" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DIVISION_ADMINISTRATIVE"),
				"municipal" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_DIVISION_MUNICIPAL"),
			],
		],
	],
	[
		"log_path",
		Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_LOG_PATH"),
		"location_logs.txt",
		["hidden", "30"],
	],
	//    ["address_restriction_common", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_RESTRICTION_ADDRESS"), "", ["text", "30"]],
];
$addressPropertyID = "";
foreach (Options::getTypePayers() as $typePayer)
{
	$isAddress = $arLocationOptions = [];
	$locAddress = Option::get($module_id, "enable_location_json_address_" . $typePayer['ID']);
	if ($arSites[$typePayer['LID']])
	{
		$arDadataOptions[] = Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_TYPE_PAYER", [
			"#SITE#" => "{$arSites[$typePayer['LID']]} - [{$typePayer['LID']}]",
			"#TYPE#" => $typePayer['NAME'],
		]);
		$arDadataOptions[] = ["type_payer_" . $typePayer['ID'], "type_payer", $typePayer['ID'], ["hidden", "30"]];
		$arPayerOptions[$typePayer['ID']]['NAME'] = $typePayer['NAME'];
		foreach (Options::getPropertiesOrder($typePayer['ID']) as $prop)
		{
			if ($prop['CODE'] !== 'LOCATION')
			{
				$default = "";
				if ($prop['IS_ADDRESS'] == 'Y')
				{
					$addressPropertyID = $prop['ID'];
					$isAddress[$typePayer['ID']] = $prop['ID'];
					$default = 'ADDRESS';
				}
				$arDadataOptions[] = [$prop['ID'], $prop['NAME'], $default, ["selectbox", Options::DaDataType()]];
				$arPayerOptions[$typePayer['ID']]['PROPERTIES'][$prop['ID']] = $prop['NAME'];
			}
		}

		$arLocationOptions[] = [
			"enable_location_options_" . $typePayer['ID'],
			Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_LOCATION_OPTIONS_ENABLE"),
			"Y",
			["checkbox", "Y", ""],
		];

		if (!empty($locAddress) && !is_numeric($locAddress) && !empty($arPayerOptions[$typePayer['ID']]['PROPERTIES']))
		{
			$arrAddress = array_intersect_key($arPayerOptions[$typePayer['ID']]['PROPERTIES'], array_flip(Helper::JsonDecode($locAddress)));
			$arLocationOptions[] = [
				"enable_location_address_" . $typePayer['ID'],
				Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_OPTIONS_AFTER_LOCATION"),
				$isAddress[$typePayer['ID']],
				["selectbox", $arrAddress],
			];
		}

		$arDadataOptions = array_merge($arDadataOptions, $arLocationOptions);
		$arDadataOptions[] = [
			"suggestions_only_" . $typePayer['ID'],
			Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_ONLY"),
			"",
			["multiselectbox", $arPayerOptions[$typePayer['ID']]['PROPERTIES']],
		];
	}
}

$arYandexOptions = [];
foreach ($arSites as $siteID => $siteName)
{
	foreach (Options::getTypePayers($siteID) as $payer)
	{
		$properties = $handler->getOrderPropertiesToSelect($payer['ID']);
		$arYandexOptions[] = [
			"yandex-address_{$siteID}_{$payer['ID']}",
			Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_YANDEX_ADDRESS"),
			$addressPropertyID,
			[
				"selectbox",
				$properties,
			],
		];
	}
}

$arOptions = array_merge($arMainOptions, $arDadataOptions, $arYandexOptions);

$tabControl = new CAdminTabControl($adminTabControlName, $tabs);
$tabsWrapper = new TabControl($adminTabControlName, $adminTabSubControlName);
if ($request->getRequestMethod() == "POST")
{
	if (!check_bitrix_sessid())
	{
		throw new ArgumentException("Bad sessid.");
	}

	if ($request->getPost("reset"))
	{
		Helper::resetOptions($arOptions, ["type_prompts", "enable_dadata", 'api_key_dadata'], true);
	}

	if ($request->getPost("save"))
	{
		$typePayer = 0;
		$addressProps = [];

		foreach ($arOptions as $arOption)
		{
			if (!is_array($arOption))
			{
				continue;
			}

			$helper->__AdmSettingsSaveOption($module_id, $arOption);

			if ($arOption[1] == 'type_payer')
			{
				$typePayer = $arOption[2];
			}

			if ($_POST[$arOption[0]] == 'ADDRESS')
			{
				$addressProps[$typePayer][] = $arOption[0];
			}
		}

		foreach ($addressProps as $idPayer => $payerProps)
		{
			if (!isset($_POST["enable_location_address_$idPayer"]) || count($payerProps) == 1)
			{
				Option::set($module_id, "enable_location_address_$idPayer", $payerProps[0]);
			}

			if (count($addressProps[$idPayer]) > 1)
			{
				Option::set($module_id, "enable_location_json_address_$idPayer", Json::encode($payerProps));
			}
			else
			{
				Option::delete($module_id, ['name' => "enable_location_json_address_$idPayer"]);
			}
		}
	}

	$url = $APPLICATION->GetCurPage() . "?lang=" . LANGUAGE_ID . "&" . $tabControl->ActiveTabParam();
	$tabsWrapper->getControlParams($request, 'getPost');
}

$subValue = $tabsWrapper->getControlParams($request, 'getQuery');
require_once dirname(__DIR__) . '/includes/contact_banner.php';
?>
	<form name="ya_delivery" method="POST"
			action="<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>"
			ENCTYPE="multipart/form-data">
		<?= bitrix_sessid_post() ?>
		<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>" />
		<?= $tabsWrapper->getHiddenInput($subValue); ?>
		<?
		$tabControl->Begin();

		//Main options
		$tabControl->BeginNextTab();
		foreach ($arMainOptions as $option)
		{
			$helper->__AdmSettingsDrawRow($module_id, $option);
		}

		//Yandex options
		$tabControl->BeginNextTab();
		?>
		<tr>
			<td colspan="2">
				<?
				$tabsWrapper->setParentTabName("tab_$yandex");
				$payersTabsControl = new CAdminViewTabControl($tabsWrapper->getSubTabsName(), $tabsWrapper->getSitesTabs());
				$payersTabsControl->Begin();
				foreach ($arSites as $siteID => $siteName)
				{
					$payersTabsControl->BeginNextTab();
					foreach (Options::getTypePayers($siteID) as $payer)
					{
						?>
						<table class="adm-detail-content-table edit-table"><?
							$helper->__AdmSettingsDrawRow($module_id, Loc::getMessage("CORSIK_DELIVERY_SERVICE_SUGGESTIONS_PAYER_TITLE", ["#NAME#" => $payer["NAME"]]));
							foreach ($arYandexOptions as $option)
							{
								[$optionName, $optionsSiteID, $optionPayersID] = explode("_", $option[0]);
								if ($optionsSiteID === $siteID && $optionPayersID === $payer['ID'])
								{
									$helper->__AdmSettingsDrawRow($module_id, $option);
								}
							}
							?>
						</table>
						<?
					}
				}
				$payersTabsControl->End();
				?>
			</td>
		</tr>

		<?
		//Dadata options
		$tabControl->BeginNextTab();
		foreach ($arDadataOptions as $option)
		{
			$helper->__AdmSettingsDrawRow($module_id, $option);
		}

		$tabControl->Buttons(['btnApply' => false]);
		?>
		<input type="submit" value="<?= GetMessage("admin_lib_sett_reset") ?>" name="reset"
				onclick="top.window.location='corsik_yadelivery_dadata.php?lang=ru'">
		<?
		$tabControl->End();
		$tabsWrapper->getTabsScripts();
		?>
	</form>
	<script type="text/javascript" src="/bitrix/js/<?= $module_id ?>/admin/admin.main.js"></script>
	<script>
		BX.ready(() => {
			BX.UI.Hint.init(BX("adm-workarea"));
			const setup = window.PagesSetup;
		});
	</script>
<?

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

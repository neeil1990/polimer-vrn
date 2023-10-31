<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Corsik\YaDelivery\Admin\Scripts;
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
$tabsPrefix = "tabSite";
$adminTabControlName = 'tabControl';
$adminTabSubControlName = 'payersTab';
$arSites = Handler::getSites();
$APPLICATION->SetTitle(Loc::getMessage("CORSIK_DELIVERY_SERVICE_ADDITIONAL_FEATURES"));
$arrOptionsAdditionalFields = [];
$arrSaveOptions = [];
$arrExcludedTab = [];

foreach ($arSites as $siteID => $siteName)
{
	$payers = Options::getTypePayers($siteID);
	if (count($payers) > 0)
	{
		foreach ($payers as $payer)
		{
			$suffix = "{$siteID}_{$payer["ID"]}";
			$arrPayerOrderProps = array_reduce(Options::getPropertiesOrder($payer['ID']), function ($acc, $prop) {
				if ($prop['IS_LOCATION'] !== 'Y')
				{
					$acc[$prop['ID']] = $prop['NAME'];
				}

				return $acc;
			}, []);
			$arOption = [
				//                ["enable_extra_address_field_{$suffix}", Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_INPUT_ADDRESS"), "Y", ["checkbox", "Y"]],
				[
					"additional_delivery_fields_{$suffix}",
					Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_ADDITIONAL_FIELDS"),
					"",
					["multiselectbox", $arrPayerOrderProps],
				],
				[
					"hidden_additional_delivery_fields_{$suffix}",
					Loc::getMessage("CORSIK_DELIVERY_SERVICE_HIDDEN_ADDITIONAL_FIELDS"),
					"Y",
					["checkbox", "Y"],
				],
			];
			$arrOptionsAdditionalFields[$siteID][$payer['ID']] = $arOption;
			$arrSaveOptions[] = $arOption;
		}
	}
	else
	{
		$arrExcludedTab[] = $siteID;
	}
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
$helper::showPublicErrors();

$tabsWrapper = new TabControl($adminTabControlName, $adminTabSubControlName);

$tabControl = new CAdminTabControl($adminTabControlName, $tabsWrapper->getSitesTabs($arrExcludedTab));
if ($request->getRequestMethod() == "POST" && (($request->getPost("save") || $request->getPost("apply"))))
{
	if (!check_bitrix_sessid())
	{
		throw new ArgumentException("Bad sessid.");
	}
	$helper->__AdmSettingsSaveOptions($module_id, array_merge(...$arrSaveOptions));
	$tabsWrapper->getControlParams($request, 'getPost');
}

$subValue = $tabsWrapper->getControlParams($request, 'getQuery');

require_once dirname(__DIR__) . '/includes/contact_banner.php';
?>
	<form name="ya_delivery" method="POST"
			action="<? echo $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>"
			ENCTYPE="multipart/form-data">
		<?= bitrix_sessid_post() ?>
		<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>" />
		<?= $tabsWrapper->getHiddenInput($subValue); ?>

		<?
		$tabControl->Begin();
		foreach ($arSites as $siteID => $siteName)
		{
			/**
			 * TAB ADDITIONAL_FIELDS
			 */
			if (!in_array($siteID, $arrExcludedTab))
			{
				$tabControl->BeginNextTab();
				?>
				<tr>
					<td colspan="2">
						<?
						$payersTabs = $tabsWrapper->getPayersTabs($siteID);
						$payersTabsControl = new CAdminViewTabControl($tabsWrapper->getSubTabsName($siteID), $payersTabs);
						$payersTabsControl->Begin();
						foreach (Options::getTypePayers($siteID) as $payer)
						{
							$payersTabsControl->BeginNextTab();
							?>
							<table class="adm-detail-content-table edit-table"><?
							/**
							 * PROFILES OPTIONS
							 */
							foreach ($arrOptionsAdditionalFields[$siteID][$payer['ID']] as $option)
							{
								$helper->__AdmSettingsDrawRow($module_id, $option);
							}
							?></table><?
						}
						$payersTabsControl->End();
						?>
					</td>
				</tr>
				<?
			}
			/**
			 * END TAB ADDITIONAL_FIELDS
			 */
		}
		$tabControl->Buttons(['btnApply' => false]);
		$tabControl->End();
		?>
	</form>
<?php

$tabsWrapper->getTabsScripts();
Scripts::getAdminMainJS();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

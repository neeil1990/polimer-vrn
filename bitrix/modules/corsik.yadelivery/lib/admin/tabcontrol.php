<?php

namespace Corsik\YaDelivery\Admin;

use Bitrix\Main\Localization\Loc;
use Corsik\YaDelivery\Handler;
use Corsik\YaDelivery\Options;

class TabControl
{
	public string $mainTabName = "tabControl";
	public string $subTabName = "subTabControl";
	public string $activePrefix = "active_tab";
	public string $parentTabName = "tabSite";
	protected ?string $moduleID;

	public function __construct($mainTab, $subTab = "")
	{
		$this->parentTabName = "tabSite";
		$this->mainTabName = $mainTab;
		$this->subTabName = $subTab;
		$this->moduleID = Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID");
	}

	public function getControlParams($request, $transport)
	{
		global $APPLICATION;

		$isPost = $transport === 'getPost';
		$activeTabControl = "{$this->mainTabName}_{$this->activePrefix}";
		$currentParentTab = $request->$transport($activeTabControl);
		$activeTabSubControl = "{$this->subTabName}_{$currentParentTab}_{$this->activePrefix}";
		$requestTabSubControl = $isPost ? $this->subTabName : $activeTabSubControl;
		$currentSubControlTab = $request->$transport($requestTabSubControl);
		$subParams = !!$currentSubControlTab ? "&{$activeTabSubControl}={$currentSubControlTab}" : "";
		$url = "{$activeTabControl}={$currentParentTab}{$subParams}";

		if ($isPost)
		{
			LocalRedirect($APPLICATION->GetCurPageParam($url, [$activeTabControl, $activeTabSubControl]));
		}

		return $currentSubControlTab;
	}

	public function setSubTabsName(string $subTabName): void
	{
		$this->subTabName = $subTabName;
	}

	public function getPayersTabs(
		$siteID = false
	): array
	{
		$arrPayersTabs = [];
		foreach (Options::getTypePayers($siteID) as $val)
		{
			$div = "{$this->getSubTabsName()}-{$val["ID"]}";
			$tab = "[{$val["ID"]}] " . htmlspecialcharsbx($val["NAME"]);
			$title = "[" . htmlspecialcharsbx($val["ID"]) . "] " . htmlspecialcharsbx($val["NAME"]);
			$arrPayersTabs[] = ["DIV" => $div, "TAB" => $tab, "TITLE" => $title];
		}

		return $arrPayersTabs;
	}

	public function setParentTabName(string $tabName): void
	{
		$this->parentTabName = $tabName;
	}

	public function getSitesTabs(array $excludedSites = []): array
	{
		$tabs = [];
		foreach (Handler::getSites() as $siteID => $siteName)
		{
			if (!in_array($siteID, $excludedSites))
			{
				$tabs[] = [
					'DIV' => "{$this->parentTabName}_{$siteID}",
					'TAB' => "[$siteID] $siteName",
					'TITLE' => "[$siteID] $siteName",
				];
			}
		}

		return $tabs;
	}

	public function getSubTabsName(string $parentActiveTab = null): string
	{
		$tabName = $this->parentTabName ? "{$this->subTabName}_{$this->parentTabName}" : $this->subTabName;

		return $parentActiveTab ? "{$tabName}_{$parentActiveTab}" : $tabName;
	}

	public function getHiddenInput($selectedTab): string
	{
		return "<input id='{$this->subTabName}' type='hidden' name='{$this->subTabName}' value='$selectedTab' />";
	}

	public
	function getTabsScripts()
	{
		?>
		<script>
			BX.ready(() => {
				BX.UI.Hint.init(BX('adm-workarea'));
				$(document).on('click', '.adm-detail-tab', function() {
					const tabID = this.id.substring(9);
					const activeSubControl = $(`#${tabID}`).find('.adm-detail-subtab-active');
					if (activeSubControl.length)
					{
						const subControlID = activeSubControl[0].id.substring(9);
						$('#<?=$this->subTabName?>').val(subControlID);
					}
				});
				$(document).on('click', '.adm-detail-subtabs', function() {
					$('#<?=$this->subTabName?>').val(this.id.substring(9));
				});
			});
		</script><?
	}
}

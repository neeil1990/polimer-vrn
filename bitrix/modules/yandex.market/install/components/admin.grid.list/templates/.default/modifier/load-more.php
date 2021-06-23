<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Yandex\Market;

/** @var $this \CBitrixComponentTemplate */
/** @var $component \Yandex\Market\Components\AdminGridList */

$this->addExternalJs($templateFolder . '/scripts/loadmore.js');

$adminList = $component->getViewList();

if (preg_match('/<a class=".*?adm-nav-page-next.*?"(.*?)>/s', $adminList->sNavText, $linkMatches))
{
	$nextPageLinkAttributes = $linkMatches[1];

	if (preg_match('/\.GetAdminList\((.*?)\)/i', $nextPageLinkAttributes, $attributeMatches))
	{
		$nextUrl = Market\Data\TextString::getSubstring($attributeMatches[1], 1, -1);
		$nextUrl = htmlspecialcharsback($nextUrl);
		$pluginOptions = [
			'grid' => $arParams['GRID_ID'],
			'url' => $nextUrl,
		];

		// build button

		$buttonHtml =
			'<a class="adm-nav-page yamarket-navigation-load-more" href="javascript:void(0)" id="yamarketAdminListLoadMore">'
				. Loc::getMessage('YANDEX_MARKET_T_ADMIN_GRID_LIST_LOAD_MORE')
			. '</a>'
			. '<script>
				(function() {
					var element = document.getElementById(\'yamarketAdminListLoadMore\');
					var AdminList = BX.namespace(\'YandexMarket.AdminList\');
					
					new AdminList.LoadMore(element, ' . Json::encode($pluginOptions) . ');
				})();
			</script>';

		$adminList->sNavText = preg_replace('/(<a class=".*?adm-nav-page-next".*?>.*?<\\/a>)/s', '$1' . $buttonHtml, $adminList->sNavText , 1);
	}
}
<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

/** @var $this \CBitrixComponentTemplate */
/** @var $component \Yandex\Market\Components\AdminGridList */

$adminList = $component->getViewList();
$pagerFixed = isset($arParams['PAGER_FIXED']) ? (int)$arParams['PAGER_FIXED'] : 0;

if ($pagerFixed > 0 && Market\Data\TextString::getPosition($adminList->sNavText, 'adm-nav-pages-number-block') !== false)
{
	$adminList->sNavText .= '<script>
		(function() {
			var block = document.querySelector(".adm-nav-pages-number-block");
			
			if (!block) { return; }
			
			block.style.height = block.clientHeight + "px";
			block.firstElementChild.style.display = "none";
		})();
	</script>';
}

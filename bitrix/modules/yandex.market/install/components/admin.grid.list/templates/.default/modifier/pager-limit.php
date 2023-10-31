<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Yandex\Market;

/** @var $this \CBitrixComponentTemplate */
/** @var $component \Yandex\Market\Components\AdminGridList */

$adminList = $component->getViewList();
$limitTop = isset($arParams['PAGER_LIMIT']) ? (int)$arParams['PAGER_LIMIT'] : 0;

if ($limitTop > 0 && Market\Data\TextString::getPosition($adminList->sNavText, 'adm-nav-pages-number') !== false)
{
	$adminList->sNavText .= '<script>
		(function() {
			var limitTop = ' . $limitTop . ';
			var select = document.querySelector(".adm-nav-pages-number select");
			var options;
			var optionIndex;
			var option;
			var optionLimit;
			
			if (select) {
				options = select.querySelectorAll("option");
				
				for (optionIndex = options.length - 1; optionIndex >= 0; optionIndex--) {
					option = options[optionIndex];
					optionLimit = parseInt(option.value, 10);
					
					if (optionLimit === 0 || optionLimit > limitTop) {
						option.parentElement.removeChild(option);
					}
				}
			}
		})();
	</script>';
}
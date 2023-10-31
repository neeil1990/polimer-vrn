<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market\Ui\UserField;

?>
<table class="edit-table js-yamarket-basket-confirm-summary__field" width="100%" data-plugin="OrderView.BasketConfirmForm">
	<tr>
		<td class="adm-detail-content-cell-l" width="40%" align="right" valign="middle">
			<strong><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_REASON') ?></strong>
		</td>
		<td class="adm-detail-content-cell-r" width="60%">
			<?= UserField\View\Select::getControl($arResult['ITEMS_CHANGE_REASON'], null, [
				'class' => 'js-yamarket-basket-confirm-form__input',
				'name' => 'YAMARKET_ORDER[BASKET_CONFIRM][REASON]',
				'data-name' => 'REASON',
			]) ?>
		</td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">
			<strong><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_PRODUCTS') ?></strong>
		</td>
		<td class="adm-detail-content-cell-r js-yamarket-basket-confirm-form__products" width="60%"></td>
	</tr>
</table>
<?php
echo BeginNote();
echo Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_FORM_INTRO');
echo EndNote();
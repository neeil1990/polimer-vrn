<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

$hasActions = !empty($arResult['ORDER_ACTIONS']);

?>
<div class="yamarket-shipment-submit">
	<?php
	if ($hasActions)
	{
		Market\Ui\Assets::loadMessages([
			'T_TRADING_ORDER_VIEW_SHIPMENT_SUBMIT_FAIL',
			'T_TRADING_ORDER_VIEW_SHIPMENT_SUBMIT_DATA_INVALID',
			'T_TRADING_ORDER_VIEW_SHIPMENT_SUBMIT_VALIDATION_CONFIRM',
		]);

		Market\Ui\Assets::loadPlugin('OrderView.ShipmentSubmit');

		$inputs = [
			'INTERNAL_ID' => $arResult['ORDER_INTERNAL_ID'],
			'ID' => $arResult['ORDER_EXTERNAL_ID'],
			'SETUP_ID' => $arResult['SETUP_ID'],
			'ACCOUNT_NUMBER' => $arResult['ORDER_ACCOUNT_NUMBER'],
			'AUTO_FINISH' => !empty($arResult['ORDER']['AUTO_FINISH']) ? 'Y' : 'N',
		];

		foreach ($inputs as $inputName => $inputValue)
		{
			?>
			<input
				class="js-yamarket-order__input"
				type="hidden"
				name="YAMARKET_ORDER[<?= $inputName ?>]"
				value="<?= htmlspecialcharsbx($inputValue) ?>"
				data-name="<?= $inputName ?>"
			/>
			<?php
		}
		?>
		<input
			class="yamarket-shipment-submit__button adm-btn-green js-plugin-click"
			type="button"
			value="<?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_SUBMIT'); ?>"
			data-plugin="OrderView.ShipmentSubmit"
			data-url="<?= Market\Ui\Admin\Path::getModuleUrl('trading_shipment_submit') ?>"
		/>
		<?php
	}

	if (!empty($arResult['PRINT_DOCUMENTS']))
	{
		Market\Ui\Assets::loadPlugins([
			'lib.dialog',
			'lib.printdialog',
			'OrderView.ShipmentPrint',
		]);

		Market\Ui\Assets::loadMessages([
			'PRINT_DIALOG_SUBMIT',
			'PRINT_DIALOG_WINDOW_BLOCKED',
		]);

		$printItems = Market\Utils::jsonEncode($arResult['PRINT_DOCUMENTS'], JSON_UNESCAPED_UNICODE);
		$printUrl = Market\Ui\Admin\Path::getModuleUrl('trading_order_print', [
			'view' => 'dialog',
			'setup' => $arResult['SETUP_ID'],
			'id' => $arResult['ORDER_EXTERNAL_ID'],
		]);

		?>
		<button
			class="yamarket-shipment-submit__button yamarket-btn adm-btn adm-btn-menu <?= $arResult['PRINT_READY'] ? '' : 'is--hidden'; ?> js-plugin"
			type="button"
			data-plugin="OrderView.ShipmentPrint"
			data-items="<?= htmlspecialcharsbx($printItems); ?>"
			data-url="<?= htmlspecialcharsbx($printUrl); ?>"
		><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_PRINT'); ?></button>
		<?php
	}

	if ($hasActions)
	{
		?>
		<div class="yamarket-shipment-submit__result js-yamarket-shipment-submit__message"></div>
		<?php
	}
	?>
</div>

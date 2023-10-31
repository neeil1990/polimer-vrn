<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;

$hasFewShipments = (count($arResult['SHIPMENT']) > 1);
$allowBoxEdit = isset($arResult['ORDER_ACTIONS'][TradingEntity\Operation\Order::BOX]);

if ($allowBoxEdit)
{
	Market\Ui\Assets::loadPlugins([
		'Ui.ModalForm',
		'OrderView.BoxSize',
		'OrderView.BoxSizePackSelect',
		'OrderView.Box',
		'OrderView.BoxCollection',
		'OrderView.Shipment',
		'OrderView.ShipmentCollection',
		'OrderView.DimensionsToggle',
	]);

	Market\Ui\Assets::loadMessages([
		'T_TRADING_ORDER_VIEW_BOX_SIZE_DENSITY_LESS_MINIMAL',
		'T_TRADING_ORDER_VIEW_BOX_SIZE_DENSITY_MORE_MAXIMUM',
		'T_TRADING_ORDER_VIEW_BOX_SIZE_INPUT_NOT_FOUND',
		'T_TRADING_ORDER_VIEW_BOX_SIZE_SIZE_MUST_BE_POSITIVE',
		'T_TRADING_ORDER_VIEW_BOX_PACK_MODAL_ADD',
		'T_TRADING_ORDER_VIEW_BOX_PACK_MODAL_EDIT',
		'T_TRADING_ORDER_VIEW_BOX_PACK_EDIT',
		'T_TRADING_ORDER_VIEW_BOX_PACK_SAVE',
	]);
}

$baseInputName = 'YAMARKET_ORDER[SHIPMENT]';
$shipmentIndex = 0;
$useDimensions = true;

?>
<div class="yamarket-shipments-header">
	<h2 class="yamarket-shipments-header__inline yamarket-shipments-title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENTS_TITLE'); ?></h2>
	<?php
	if ($allowBoxEdit)
	{
		$useDimensions = (CUserOptions::GetOption('yamarket_order_view', 'use_dimensions', 'N', $USER->GetID()) === 'Y');

		?>
		<input type="hidden" name="YAMARKET_ORDER[USE_DIMENSIONS]" value="N" />
		<label class="yamarket-shipments-header__inline">
			<input
				class="adm-designed-checkbox js-plugin"
				type="checkbox"
				name="YAMARKET_ORDER[USE_DIMENSIONS]"
				value="Y"
				<?= $useDimensions ? 'checked' : ''; ?>
				data-plugin="OrderView.DimensionsToggle"
				data-shipment-element="#YAMARKET_SHIPMENT_COLLECTION"
			/>
			<span class="adm-designed-checkbox-label"></span>
			<?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENTS_DIMENSIONS_TOGGLE'); ?>
		</label>
		<?php
	}
	?>
</div>
<div
	class="yamarket-shipments-layout js-yamarket-shipment-collection <?= $allowBoxEdit ? 'js-yamarket-order__field' : ''; ?>"
	data-plugin="OrderView.ShipmentCollection"
	data-name="SHIPMENT"
	id="YAMARKET_SHIPMENT_COLLECTION"
>
	<?php
	$dismissCookieName = 'YAMARKET_ORDER_VIEW_SHIPMENT_HELP_HIDE';

	if ($allowBoxEdit && $APPLICATION->get_cookie($dismissCookieName) !== 'Y')
	{
		Market\Ui\Assets::loadPlugin('Ui.Dismiss');
		$dismissCookieNameWithPrefix = Main\Config\Option::get('main', 'cookie_name', 'BITRIX_SM') . '_' . $dismissCookieName;

		?>
		<div class="yamarket-shipments-layout__help yamarket-dismiss-parent">
			<button class="yamarket-dismiss js-plugin-click" data-plugin="Ui.Dismiss" data-cookie="<?= $dismissCookieNameWithPrefix; ?>">&#215;</button>
			<?php
			echo BeginNote('style="max-width: 550px;"');
			echo Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_EDIT_NOTE', [
				'#SERVICE_NAME#' => $arResult['SERVICE_NAME'],
			]);
			echo EndNote();
			?>
		</div>
		<?php
	}
	?>
	<div class="yamarket-shipments-layout__main">
		<?php
		foreach ($arResult['SHIPMENT'] as $shipment)
		{
			$shipmentInputName = $baseInputName . '[' . $shipmentIndex . ']';
			$boxIndex = 0;
			$isBoxesEmpty = empty($shipment['BOX']);
			$boxesIterator = $isBoxesEmpty ? [] : (array)$shipment['BOX'];

			if ($isBoxesEmpty && $allowBoxEdit)
			{
				$boxesIterator[] = [
					'PLACEHOLDER' => true,
				];
			}

			?>
			<div class="js-yamarket-shipment" data-plugin="OrderView.Shipment" data-id="<?= $shipment['ID']; ?>">
				<?php
				if ($allowBoxEdit)
				{
					?>
					<input
						class="js-yamarket-shipment__input"
					   	type="hidden"
					   	name="<?= $shipmentInputName . '[ID]' ?>"
					   	value="<?= htmlspecialcharsbx($shipment['ID']) ?>"
					   	data-name="ID"
					/>
					<?php
				}

				if ($hasFewShipments)
				{
					?>
					<h3 class="yamarket-shipment-title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT', [ '#ID#' => $shipment['ID'] ]); ?></h3>
					<?php
				}
				?>
				<div class="adm-s-order-table-ddi js-yamarket-shipment__child" data-plugin="OrderView.BoxCollection" data-name="BOX">
					<?php
					foreach ($boxesIterator as $box)
					{
						$boxInputName = $shipmentInputName . '[BOX][' . $boxIndex . ']';

						include __DIR__ . '/box.php';

						++$boxIndex;
					}

					if ($allowBoxEdit)
					{
						?>
						<a href="#" class="yamarket-boxes-add js-yamarket-box__add">
							+&nbsp;<span class="yamarket-boxes-add__text"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_SHIPMENT_BOX_ADD'); ?></span>
						</a>
						<?php
					}
					?>
				</div>
			</div>
			<?php

			++$shipmentIndex;
		}
		?>
	</div>
</div>
<?php

namespace Yandex\Market\Ui\Trading\OrderView;

use Bitrix\Main;
use Yandex\Market\Ui;

class CrmCancelReason extends CancelReason
{
	public function initialize(array $orderInfo)
	{
		Ui\Assets::loadPlugin('OrderView.Crm.CancelReason');

		$options = $this->makeOptions($orderInfo);

		$assets = Main\Page\Asset::getInstance();
		$assets->addString(sprintf(
			'<script> 
				BX.ready(function() {
					setTimeout(function() {
						new BX.YandexMarket.OrderView.Crm.CancelReason(%s); // delay after core events
					}, 0);
				});
			</script>',
			Main\Web\Json::encode($options)
		));
	}

	protected function makeOptions(array $orderInfo)
	{
		return [
			'entityId' => $orderInfo['ORDER_ID'],
			'variants' => $this->makeVariants(),
		];
	}

	protected function makeVariants()
	{
		$cancelReason = $this->setup->getService()->getCancelReason();
		$result = [];

		foreach ($cancelReason->getVariants() as $variant)
		{
			$result[] = [
				'ID' => $variant,
				'VALUE' => htmlspecialcharsbx($cancelReason->getTitle($variant)),
			];
		}

		return $result;
	}
}
<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\Settings;

use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\Settings\Action
{
	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function extendValueDeliveryOptions(array $field, $value, $raw, array $row)
	{
		if (empty($raw['ID'])) { return $value; }

		$value[0]['service'] = $this->environment->getDelivery()->debugData($raw['ID']);

		return $value;
	}
}
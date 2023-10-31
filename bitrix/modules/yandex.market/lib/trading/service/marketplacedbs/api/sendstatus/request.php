<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\SendStatus;

use Yandex\Market;

class Request extends Market\Api\Partner\SendStatus\Request
{
	public function buildResponse($data)
	{
		return new Response($data);
	}

	protected function convertPayload($payload)
	{
		$result = [];

		if (isset($payload['realDeliveryDate']))
		{
			$realDeliveryDate = Market\Data\Date::convertForService($payload['realDeliveryDate'], Market\Data\Date::FORMAT_DEFAULT_SHORT);

			Market\Utils\Field::setChainValue($result, 'delivery.dates.realDeliveryDate', $realDeliveryDate);
		}

		return $result;
	}
}
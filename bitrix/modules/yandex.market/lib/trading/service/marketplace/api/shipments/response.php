<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Shipments;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response extends Market\Api\Reference\ResponseWithResult
{
	use Market\Reference\Concerns\HasOnce;

	/** @return TradingService\Marketplace\Model\ShipmentCollection */
	public function getShipmentCollection()
	{
		return $this->once('loadShipmentCollection');
	}

	/** @noinspection PhpUnused */
	protected function loadShipmentCollection()
	{
		$serviceResult = $this->getResult();

		Market\Reference\Assert::notNull($serviceResult['shipments'], 'response["result"]["shipments"]');
		Market\Reference\Assert::isArray($serviceResult['shipments'], 'response["result"]["shipments"]');

		$paging = $this->getPaging();

		$collection = TradingService\Marketplace\Model\ShipmentCollection::initialize($serviceResult['shipments']);
		$collection->setPaging($paging);

		return $collection;
	}

	/** @return Market\Api\Model\Paging */
	public function getPaging()
	{
		return $this->once('loadPaging');
	}

	/** @noinspection PhpUnused */
	protected function loadPaging()
	{
		$serviceResult = $this->getResult();
		$data = (array)$serviceResult['paging'];

		return new Market\Api\Model\Paging($data);
	}

	protected function getResult()
	{
		$serviceResult = $this->getField('result');

		Market\Reference\Assert::notNull($serviceResult, 'response["result"]');
		Market\Reference\Assert::isArray($serviceResult, 'response["result"]');

		return $serviceResult;
	}
}

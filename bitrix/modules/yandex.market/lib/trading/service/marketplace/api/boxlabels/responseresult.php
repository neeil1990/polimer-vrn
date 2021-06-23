<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\BoxLabels;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class ResponseResult extends Market\Api\Reference\Model
{
	public function getOrderId()
	{
		return (int)$this->getField('orderId');
	}

	public function getPlacesNumber()
	{
		return (int)$this->getField('placesNumber');
	}

	public function getUrl()
	{
		return (string)$this->getField('url');
	}

	public function getParcelBoxLabels()
	{
		return $this->getChildCollection('parcelBoxLabels');
	}

	protected function getChildCollectionReference()
	{
		return [
			'parcelBoxLabels' => TradingService\Marketplace\Model\Box\LabelDataCollection::class,
		];
	}
}
<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Item extends Market\Api\Model\Order\Item
{
	public function getFeedId()
	{
		return (int)$this->getRequiredField('feedId');
	}

	public function getPartnerWarehouseId()
	{
		return (string)$this->getRequiredField('partnerWarehouseId');
	}

	/** @return string|null */
	public function getBundleId()
	{
		return $this->getField('bundleId');
	}

	/** @return Item\PromoCollection|null */
	public function getPromos()
	{
		return $this->getChildCollection('promos');
	}

	/** @return Item\InstanceCollection|null */
	public function getInstances()
	{
		return $this->getChildCollection('instances');
	}

	protected function getChildCollectionReference()
	{
		return parent::getChildCollectionReference() + [
			'promos' => Item\PromoCollection::class,
			'instances' => Item\InstanceCollection::class,
		];
	}

	public function getRequiredInstanceTypes()
	{
		return (array)$this->getField('requiredInstanceTypes');
	}
}
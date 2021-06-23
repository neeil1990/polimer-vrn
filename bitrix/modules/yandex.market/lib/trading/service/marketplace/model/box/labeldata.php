<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Box;

use Bitrix\Main;
use Yandex\Market;

class LabelData extends Market\Api\Reference\Model
{
	public function getBoxId()
	{
		$url = $this->getUrl();
		$result = null;

		if (preg_match('#/boxes/(\d+)/label#', $url, $match))
		{
			$result = (int)$match[1];
		}

		return $result;
	}

	public function getOrderId()
	{
		return (int)$this->getField('orderId');
	}

	public function getOrderNum()
	{
		return (string)$this->getField('orderNum');
	}

	public function getNumber()
	{
		$fulfilmentId = $this->getFulfilmentId();
		$result = null;

		if (preg_match('#-(\d+)$#', $fulfilmentId, $match))
		{
			$result = (int)$match[1];
		}

		return $result;
	}

	public function getFulfilmentId()
	{
		return (string)$this->getField('fulfilmentId');
	}

	public function getPlace()
	{
		return (string)$this->getField('place');
	}

	public function getWeight()
	{
		return (string)$this->getField('weight');
	}

	public function getSupplierName()
	{
		return (string)$this->getField('supplierName');
	}

	public function getDeliveryServiceName()
	{
		return (string)$this->getField('deliveryServiceName');
	}

	public function getDeliveryServiceId()
	{
		return (string)$this->getField('deliveryServiceId');
	}

	public function getUrl()
	{
		return (string)$this->getField('url');
	}
}
<?php

namespace Yandex\Market\Api\Partner\Orders;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Partner\Reference\Response
{
	protected $pager;
	protected $orderCollection;

	protected static function includeMessages()
	{
		parent::includeMessages();
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function validate()
	{
		$result = parent::validate();

		if (!$result->isSuccess())
		{
			// nothing
		}
		else if ($orderError = $this->validateOrdersData())
		{
			$result->addError($orderError);
		}

		return $result;
	}

	public function getOrderCollection()
	{
		if ($this->orderCollection === null)
		{
			$this->orderCollection = $this->loadOrderCollection();
		}

		return $this->orderCollection;
	}

	protected function loadOrderCollection()
	{
		$dataList = (array)$this->getField('orders');
		$pager = $this->getPager();

		$collection = Market\Api\Model\OrderCollection::initialize($dataList);
		$collection->setPager($pager);

		return $collection;
	}

	protected function validateOrdersData()
	{
		$result = null;
		$dataList = $this->getField('orders');

		if (!is_array($dataList))
		{
			$message = static::getLang('API_ORDERS_RESPONSE_HASNT_ORDERS');
			$result = new Main\Error($message);
		}

		return $result;
	}

	public function getPager()
	{
		if ($this->pager === null)
		{
			$this->pager = $this->loadPager();
		}

		return $this->pager;
	}

	protected function loadPager()
	{
		$data = (array)$this->getField('pager');

		return new Market\Api\Model\Pager($data);
	}
}

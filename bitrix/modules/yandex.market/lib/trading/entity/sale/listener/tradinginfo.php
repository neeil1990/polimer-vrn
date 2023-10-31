<?php

namespace Yandex\Market\Trading\Entity\Sale\Listener;

use Yandex\Market\Trading\Setup as TradingSetup;

class TradingInfo
{
	protected $setup;
	protected $orderInfo;

	public function __construct(TradingSetup\Model $setup, array $orderInfo)
	{
		$this->setup = $setup;
		$this->orderInfo = $orderInfo;
	}

	public function getSetup()
	{
		return $this->setup;
	}

	public function getExternalId()
	{
		return $this->orderInfo['EXTERNAL_ORDER_ID'];
	}

	public function getInternalId()
	{
		return $this->orderInfo['INTERNAL_ORDER_ID'];
	}

	public function getAccountNumber()
	{
		return $this->orderInfo['ACCOUNT_NUMBER'];
	}

	public function getSiteId()
	{
		return $this->orderInfo['SITE_ID'];
	}

	public function getProcedurePayload($isImmediate = false)
	{
		return [
			'internalId' => $this->getInternalId(),
			'orderId' => $this->getExternalId(),
			'orderNum' => $this->getAccountNumber(),
			'immediate' => $isImmediate,
		];
	}
}
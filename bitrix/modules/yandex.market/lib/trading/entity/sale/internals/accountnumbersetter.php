<?php

namespace Yandex\Market\Trading\Entity\Sale\Internals;

use Bitrix\Main;
use Bitrix\Sale;

class AccountNumberSetter
{
	protected $handlerKey;
	protected $order;
	protected $accountNumber;

	public function __construct(Sale\OrderBase $order, $accountNumber)
	{
		$this->order = $order;
		$this->accountNumber = $accountNumber;
	}

	public function onBeforeOrderAccountNumberSet($orderId)
	{
		if ($this->order->getId() !== $orderId) { return false; }

		return $this->accountNumber;
	}

	public function install()
	{
		if ($this->handlerKey !== null) { return; }

		$this->handlerKey = Main\EventManager::getInstance()->addEventHandler(
			'sale',
			'OnBeforeOrderAccountNumberSet',
			[ $this, 'onBeforeOrderAccountNumberSet' ]
		);
	}

	public function release()
	{
		$this->order = null;
		$this->accountNumber = null;

		if ($this->handlerKey !== null)
		{
			Main\EventManager::getInstance()->removeEventHandler('sale', 'OnBeforeOrderAccountNumberSet', $this->handlerKey);
			$this->handlerKey = null;
		}
	}
}
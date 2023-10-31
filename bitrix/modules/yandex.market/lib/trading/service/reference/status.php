<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Status
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * @param string $status
	 * @param string $version
	 *
	 * @return string
	 */
	abstract public function getTitle($status, $version = '');

	/**
	 * @return string[]
	 */
	abstract public function getVariants();

	public function isCanceled($status, $subStatus = null)
	{
		return false;
	}

	public function getStatusOrder($status)
	{
		$order = $this->getProcessOrder();

		return isset($order[$status]) ? $order[$status] : null;
	}

	public function getProcessOrder()
	{
		return [];
	}

	public function hasSubstatus($status)
	{
		return false;
	}

	public function getSubStatusOrder($subStatus)
	{
		$order = $this->getSubStatusProcessOrder();

		return isset($order[$subStatus]) ? $order[$subStatus] : null;
	}

	public function getSubStatusProcessOrder()
	{
		return [];
	}
}
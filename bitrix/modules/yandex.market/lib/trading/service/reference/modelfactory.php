<?php

namespace Yandex\Market\Trading\Service\Reference;

use Bitrix\Main;
use Yandex\Market;

class ModelFactory
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	/** @return Market\Api\Model\Cart */
	public function getCartClassName()
	{
		return Market\Api\Model\Cart::class;
	}

	/** @return Market\Api\Model\OrderFacade */
	public function getOrderFacadeClassName()
	{
		return Market\Api\Model\OrderFacade::class;
	}

	/** @return Market\Api\Model\Order */
	public function getOrderClassName()
	{
		return Market\Api\Model\Order::class;
	}

	public function getEntityFacadeClassName($entityType)
	{
		if ($entityType === Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER)
		{
			return $this->getOrderFacadeClassName();
		}

		throw new Main\NotSupportedException(sprintf('unsupported entity type %s', $entityType));
	}
}
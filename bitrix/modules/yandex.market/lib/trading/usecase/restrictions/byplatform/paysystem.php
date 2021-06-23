<?php

namespace Yandex\Market\Trading\UseCase\Restrictions\ByPlatform;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale') || !class_exists(Sale\Services\Base\Restriction::class)) { return; }

class PaySystem extends Sale\Services\Base\Restriction
{
	public static function getClassTitle()
	{
		return Rule::getClassTitle();
	}

	public static function getClassDescription()
	{
		return Rule::getClassDescription();
	}

	public static function check($params, array $restrictionParams, $serviceId = 0)
	{
		return Rule::check($params, $restrictionParams);
	}

	protected static function extractParams(Sale\Internals\Entity $entity)
	{
		if (!($entity instanceof Sale\Payment)) { return []; }

		$collection = $entity->getCollection();

		if (!($collection instanceof Sale\PaymentCollection)) { return []; }

		$order = $collection->getOrder();

		if (!($order instanceof Sale\Order)) { return []; }

		return Rule::extractParams($order);
	}

	public static function isAvailable()
	{
		return Rule::isAvailable(Rule::ENTITY_TYPE_PAY_SYSTEM);
	}

	public static function getParamsStructure($entityId = 0)
	{
		return Rule::getParamsStructure(Rule::ENTITY_TYPE_PAY_SYSTEM, $entityId);
	}
}
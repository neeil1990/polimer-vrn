<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Reference\Assert;

class CourierRegistry extends TradingEntity\Reference\CourierRegistry
{
	const IPOL_SDEK_COURIER = 'IpolSdek:Courier';

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getTypes()
	{
		return [
			static::IPOL_SDEK_COURIER,
		];
	}

	protected function makeCourier($type)
	{
		$classParts = explode(':', $type);
		$className = __NAMESPACE__ . '\\Courier\\' . implode('\\', $classParts);

		Assert::classExists($className);
		Assert::isSubclassOf($className, Courier::class);

		return new $className($this->environment);
	}
}
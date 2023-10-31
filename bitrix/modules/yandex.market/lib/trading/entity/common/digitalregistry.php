<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market\Trading\Entity\Reference as TradingReference;
use Yandex\Market\Reference\Assert;

class DigitalRegistry extends TradingReference\DigitalRegistry
{
	const ASD_ISALE = 'asd.isale';
	const KEYS_IBLOCK = 'keysIblock';

	public function getTypes()
	{
		return [
			static::KEYS_IBLOCK,
			static::ASD_ISALE,
		];
	}

	public function makeDigital($type, array $settings = [])
	{
		/** @var TradingReference\Digital $digital */
		$namespace = __NAMESPACE__ . '\\Digital';
		$className = $namespace . '\\' . ucfirst(str_replace('.', '', $type));

		Assert::classExists($className);
		Assert::isSubclassOf($className, TradingReference\Digital::class);

		return new $className($this->environment, $settings);
	}
}
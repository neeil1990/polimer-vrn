<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Reference\Assert;

class OutletRegistry extends TradingEntity\Reference\OutletRegistry
{
	const IPOL_SDEK_PICKUP = 'IpolSdek:Pvz';
	const IPOL_SDEK_POSTAMAT = 'IpolSdek:Postamat';
	const SDEK_FILE = 'Sdek:File';

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getTypes()
	{
		return [
			static::IPOL_SDEK_PICKUP,
			static::IPOL_SDEK_POSTAMAT,
			static::SDEK_FILE,
		];
	}

	protected function makeOutlet($type)
	{
		$classParts = explode(':', $type);
		$className = __NAMESPACE__ . '\\Outlet\\' . implode('\\', $classParts);

		Assert::classExists($className);
		Assert::isSubclassOf($className, Outlet::class);

		return new $className($this->environment);
	}
}
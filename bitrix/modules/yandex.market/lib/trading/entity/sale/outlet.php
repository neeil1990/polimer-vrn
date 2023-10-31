<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market\Trading\Entity as TradingEntity;

/** @property Environment $environment */
abstract class Outlet extends TradingEntity\Reference\Outlet
{
	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}
}
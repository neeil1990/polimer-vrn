<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class AbstractActivity
{
	protected $provider;
	protected $environment;

	public function __construct(TradingService\Reference\Provider $provider, TradingEntity\Reference\Environment $environment)
	{
		$this->provider = $provider;
		$this->environment = $environment;
	}

	abstract public function getTitle();

	public function getSourceType()
	{
		return TradingEntity\Registry::ENTITY_TYPE_ORDER;
	}

	/** @return array|null */
	public function getFilter()
	{
		return null;
	}

	public function getSort()
	{
		return 500;
	}

	public function useGroup()
	{
		return false;
	}
}
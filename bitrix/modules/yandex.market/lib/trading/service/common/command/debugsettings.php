<?php

namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class DebugSettings
{
	protected $provider;
	protected $environment;

	public function __construct(
		TradingService\Reference\Provider $provider,
		TradingEntity\Reference\Environment $environment
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
	}

	public function execute()
	{
		$options = $this->provider->getOptions();
		$result = [];

		$result += [
			'STORES' => $options->getProductStores(),
		];

		if ($options->isProductStoresTrace())
		{
			$result += [
				'TRACE' => 'Y',
			];
		}

		if ($options instanceof TradingService\Marketplace\Options && $options->usePushStocks())
		{
			$result += [
				'PUSH' => 'Y',
			];
		}

		return $result;
	}
}
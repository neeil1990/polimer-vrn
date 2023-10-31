<?php

namespace Yandex\Market\Trading\Service\Marketplace\Command;

use Yandex\Market\Data;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class AmountsPackRatio
{
	protected $provider;
	protected $environment;

	public function __construct(
		TradingService\Marketplace\Provider $provider,
		TradingEntity\Reference\Environment $environment
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
	}

	public function execute(array $amounts)
	{
		$productIds = array_column($amounts, 'ID');
		$options = $this->provider->getOptions();

		$ratioMap = $this->environment->getPack()->getRatio($productIds, [
			'SOURCES' => $options->getPackRatioSources(),
			'SITE_ID' => $options->getSiteId(),
		]);

		return $this->applyRatio($amounts, $ratioMap);
	}

	protected function applyRatio(array $amounts, array $ratioMap)
	{
		foreach ($amounts as &$amount)
		{
			if (!isset($ratioMap[$amount['ID']])) { continue; }

			$ratio = $ratioMap[$amount['ID']];

			if (isset($amount['QUANTITY_LIST']))
			{
				foreach ($amount['QUANTITY_LIST'] as &$quantity)
				{
					$quantity = (int)floor($quantity / $ratio);
				}
				unset($quantity);
			}

			if (isset($amount['QUANTITY']))
			{
				$amount['QUANTITY'] = (int)floor($amount['QUANTITY'] / $ratio);
			}
		}
		unset($amount);

		return $amounts;
	}
}
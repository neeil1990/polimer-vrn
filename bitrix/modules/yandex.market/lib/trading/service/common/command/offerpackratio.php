<?php

namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class OfferPackRatio
{
	protected $provider;
	protected $environment;

	public function __construct(
		TradingService\Common\Provider $provider,
		TradingEntity\Reference\Environment $environment
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
	}

	public function make(array $productIds)
	{
		$options = $this->provider->getOptions();

		return $this->environment->getPack()->getRatio($productIds, [
			'SOURCES' => $options->getPackRatioSources(),
			'SITE_ID' => $options->getSiteId(),
		]);
	}
}
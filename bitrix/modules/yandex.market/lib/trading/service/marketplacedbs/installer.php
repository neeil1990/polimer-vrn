<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market\State;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service\Marketplace;

class Installer extends Marketplace\Installer
{
	public function tweak(TradingEntity\Reference\Environment $environment, $siteId, array $context = [])
	{
		parent::tweak($environment, $siteId, $context);
		$this->tweakSelfTestOutOfStock($context);
	}

	protected function tweakSelfTestOutOfStock(array $context)
	{
		Assert::notNull($context['SETUP_ID'], 'context["SETUP_ID"]');

		$name = 'self_test_out_of_stock_' . $context['SETUP_ID'];
		$options = $this->provider->getOptions()->getSelfTestOption();

		if ($options->isOutOfStock())
		{
			State::set($name, 0);
		}
		else
		{
			State::remove($name);
		}
	}
}
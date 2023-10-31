<?php
namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Export;

class SkuMap
{
	protected $provider;
	protected $environment;
	protected $feedPrimary;

	public function __construct(
		TradingService\Common\Provider $provider,
		TradingEntity\Reference\Environment $environment
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
		$this->feedPrimary = new FeedPrimary();
	}

	public function make(array $productIds)
	{
		$options = $this->provider->getOptions();
		$optionMap = $options->getProductSkuMap();
		$optionPrefix = $options->getProductSkuPrefix();
		$result = null;

		if (!empty($optionMap))
		{
			$result = $this->environment->getProduct()->getSkuMap($productIds, $optionMap);
		}

		if ($optionPrefix !== '')
		{
			if ($result === null)
			{
				$result = array_combine($productIds, $productIds);
			}

			$result = array_map(static function($sku) use ($optionPrefix) {
				return $optionPrefix . $sku;
			}, $result);
		}

		return $this->resolveFeedDuplicates($result);
	}

	protected function resolveFeedDuplicates(array $skuMap = null)
	{
		if (empty($skuMap)) { return $skuMap; }
		if (!($this->provider instanceof TradingService\Marketplace\Provider)) { return $skuMap; }

		$feeds = $this->provider->getOptions()->getProductFeeds();
		$exported = $this->feedPrimary->exported(array_values($skuMap), $feeds);

		list($newMap, $deleted) = $this->unsetSkuWithOtherElement($skuMap, $exported);

		if (empty($deleted) || !$this->feedPrimary->canUsePrimaryAsSku($feeds)) { return $skuMap; }

		return $newMap;
	}

	protected function unsetSkuWithOtherElement(array $skuMap, array $exported)
	{
		$deleted = [];

		foreach ($skuMap as $productId => $sku)
		{
			if (!isset($exported[$sku])) { continue; }

			if (!in_array($productId, $exported[$sku], true))
			{
				$deleted[] = $sku;
				unset($skuMap[$productId]);
			}
		}

		return [$skuMap, $deleted];
	}
}
<?php

namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Export;

class OfferMap
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

	public function make(array $offerIds)
	{
		$offerMap = $this->mapOffers($offerIds);
		$offerMap = $this->resolveFeedDuplicates($offerMap);

		return $offerMap;
	}

	protected function mapOffers(array $offerIds)
	{
		$options = $this->provider->getOptions();
		$skuMap = $options->getProductSkuMap();
		$skuPrefix = $options->getProductSkuPrefix();
		$maps = [];

		if ($skuPrefix !== '')
		{
			$prefixMap = $this->mapOfferWithPrefix($offerIds, $skuPrefix);
			$offerIds = array_values($prefixMap);

			$maps[] = $prefixMap;
		}

		if (!empty($skuMap))
		{
			$maps[] = $this->environment->getProduct()->getOfferMap($offerIds, $skuMap);
		}

		return $this->combineOfferMaps($maps);
	}

	protected function mapOfferWithPrefix($offerIds, $prefix)
	{
		$prefixLength = mb_strlen($prefix);
		$result = [];

		foreach ($offerIds as $offerId)
		{
			if (mb_strpos($offerId, $prefix) === 0)
			{
				$unPrefixed = mb_substr($offerId, $prefixLength);

				if (isset($result[$unPrefixed])) { unset($result[$unPrefixed]); }

				$result[$offerId] = $unPrefixed;
			}
			else
			{
				$prefixed = $prefix . $offerId;

				if (isset($result[$prefixed])) { continue; }

				$result[$offerId] = $offerId;
			}
		}

		return $result;
	}

	protected function combineOfferMaps(array $maps)
	{
		if (empty($maps)) { return null; }

		$first = array_shift($maps);
		$second = $this->combineOfferMaps($maps);

		if ($second === null) { return $first; }

		$result = [];

		foreach ($first as $originId => $offerId)
		{
			if (!isset($second[$offerId])) { continue; }

			$result[$originId] = $second[$offerId];
		}

		return $result;
	}

	protected function resolveFeedDuplicates(array $offerMap = null)
	{
		if (empty($offerMap)) { return $offerMap; }
		if (!($this->provider instanceof TradingService\Marketplace\Provider)) { return $offerMap; }

		$feeds = $this->provider->getOptions()->getProductFeeds();
		$exported = $this->feedPrimary->exported(array_keys($offerMap), $feeds);

		list($newMap, $replaced) = $this->unsetSkuWithOtherElement($offerMap, $exported);

		if (empty($replaced) || !$this->feedPrimary->canUsePrimaryAsSku($feeds)) { return $offerMap; }

		return $newMap;
	}

	protected function unsetSkuWithOtherElement(array $offerMap, array $exported)
	{
		$replaced = [];

		foreach ($offerMap as $sku => $productId)
		{
			if (!isset($exported[$sku])) { continue; }

			if (!in_array((int)$productId, $exported[$sku], true))
			{
				$replaced[] = $sku;
				$offerMap[$sku] = $exported[$sku][0];
			}
		}

		return [$offerMap, $replaced];
	}
}
<?php

namespace Yandex\Market\Trading\Service\Marketplace\Command;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class FeedExists
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

	public function filterProducts(array $productIds)
	{
		$feeds = $this->provider->getOptions()->getProductFeeds();

		if (empty($feeds)) { return $productIds; }

		return $this->queryExists($feeds, $productIds);
	}

	public function splitProducts(array $productIds)
	{
		$feeds = $this->provider->getOptions()->getProductFeeds();

		if (empty($feeds))
		{
			return [$productIds, []];
		}

		return $this->splitExists($feeds, $productIds);
	}

	protected function queryExists(array $feeds, array $productIds, $field = 'ELEMENT_ID')
	{
		$result = [];

		foreach (array_chunk($productIds, 500) as $productChunk)
		{
			$query = Market\Export\Run\Storage\OfferTable::getList([
				'filter' => [
					'=SETUP_ID' => $feeds,
					'=' . $field => $productChunk,
					'=STATUS' => Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				],
				'select' => [ $field ],
				'group' => [ $field ],
			]);

			while ($row = $query->fetch())
			{
				$result[] = $row[$field];
			}
		}

		return $result;
	}

	protected function splitExists(array $feeds, array $productIds, $field = 'ELEMENT_ID')
	{
		$exportedMap = [];
		$deletedMap = [];

		foreach (array_chunk($productIds, 500) as $productChunk)
		{
			$query = Market\Export\Run\Storage\OfferTable::getList([
				'filter' => [
					'=SETUP_ID' => $feeds,
					'=' . $field => $productChunk,
				],
				'select' => [ $field, 'STATUS' ],
			]);

			while ($row = $query->fetch())
			{
				$value = $row[$field];

				if ((int)$row['STATUS'] === Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS)
				{
					$exportedMap[$value] = true;
				}
				else if (!isset($exportedMap[$value]))
				{
					$deletedMap[$value] = true;
				}
			}
		}

		if (!empty($deletedMap))
		{
			$deletedMap = array_diff_key($deletedMap, $exportedMap);
		}

		return [array_keys($exportedMap), array_keys($deletedMap)];
	}
}
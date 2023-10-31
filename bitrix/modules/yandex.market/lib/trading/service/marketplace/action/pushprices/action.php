<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\PushPrices;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
 * @property Request $request
 */
class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;

	protected $pushStore;

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		if (!$this->needProcess()) { return; }

		$productIds = $this->getProducts();

		if (empty($productIds)) { return; }

		$chunkSize = $this->getOffersChunkSize();
		$sentCount = 0;

		foreach ($this->makePricesChunk($productIds, $chunkSize) as $pricesChunk)
		{
			$offers = $this->buildOffers($pricesChunk);

			$this->sendOffers($offers);
			$this->commitChanged($pricesChunk);

			$sentCount += count($offers);

			if ($this->isExceedRateLimit($sentCount))
			{
				$offset = $this->findOffset($productIds, $pricesChunk);

				$this->collectBreak($offset);
				return;
			}
		}

		$this->collectNext($productIds);
	}

	protected function needProcess()
	{
		return (
			$this->request->getAction() !== Market\Trading\State\PushAgent::ACTION_REFRESH // is not refresh mode
			|| $this->request->getOffset() > 0 // already started
			|| $this->needRefresh() // changed external entities
		);
	}

	protected function needRefresh()
	{
		if ($this->request->isForce()) { return true; }

		$date = $this->request->getTimestamp();
		$context = $this->getPriceContext();

		if ($date === null) { return true; }

		return $this->environment->getPrice()->needRefresh($date, $context);
	}

	protected function getProducts()
	{
		$timestamp = null;

		if ($this->request->getAction() !== Market\Trading\State\PushAgent::ACTION_REFRESH)
		{
			$timestamp = $this->request->getTimestamp();
		}

		return $this->environment->getPrice()->getChanged(
			$this->getPriceContext(),
			$timestamp,
			$this->request->getOffset(),
			$this->request->getLimit()
		);
	}

	protected function feedExists($productIds)
	{
		$command = new TradingService\Marketplace\Command\FeedExists(
			$this->provider,
			$this->environment
		);

		return $command->filterProducts($productIds);
	}

	protected function collectNext($productIds)
	{
		$offset = $this->request->getOffset();
		$limit = $this->request->getLimit();
		$found = count($productIds);

		if ($found < $limit) { return; }

		$this->response->setField('hasNext', true);
		$this->response->setField('offset', $offset + $limit);
	}

	protected function findOffset($productIds, $prices)
	{
		$lastPrice = end($prices);

		if ($lastPrice === false) { return count($productIds); }

		$searchPriceId = (string)(isset($lastPrice['~ID']) ? $lastPrice['~ID'] : $lastPrice['ID']);
		$offset = 0;

		foreach ($productIds as $productId)
		{
			if ($searchPriceId === (string)$productId) { break; }

			++$offset;
		}

		return $offset;
	}

	protected function collectBreak($sentCount)
	{
		$offset = $this->request->getOffset();

		$this->response->setField('hasNext', true);
		$this->response->setField('needBreak', true);
		$this->response->setField('offset', $offset + $sentCount);
	}

	protected function getPackRatio($productIds)
	{
		$command = new TradingService\Common\Command\OfferPackRatio(
			$this->provider,
			$this->environment
		);

		return $command->make($productIds);
	}

	protected function makePricesChunk($productIds, $chunkSize)
	{
		$skuMap = $this->getSkuMap($productIds);
		$productIds = $this->skuExists($productIds, $skuMap);
		$productIds = $this->feedExists($productIds);
		$packRatio = $this->getPackRatio($productIds);
		$ready = [];
		$used = [];

		foreach (array_chunk($productIds, $chunkSize) as $productIdsChunk)
		{
			$prices = $this->getPrices($productIdsChunk, $packRatio);
			$prices = $this->applyPricesSku($prices, $skuMap, $used);
			$prices = $this->applyPricesRatio($prices, $packRatio);
			$prices = $this->filterValid($prices);

			$used += array_column($prices, 'ID', 'ID');

			$prices = $this->filterChanged($prices);

			if (empty($prices)) { continue; }

			array_push($ready, ...$prices);

			$readyCount = count($ready);

			if ($readyCount > $chunkSize)
			{
				yield array_slice($ready, 0, $chunkSize);
				array_splice($ready, 0, $chunkSize);
			}
			else if ($readyCount === $chunkSize)
			{
				yield $ready;
				$ready = [];
			}
		}

		if (!empty($ready))
		{
			yield $ready;
		}
	}

	protected function getPrices($productIds, $packRatio)
	{
		$quantities = $this->combinePriceQuantities($packRatio);
		$context = $this->getPriceContext();

		return $this->environment->getPrice()->getPrices($productIds, $quantities, $context);
	}

	protected function combinePriceQuantities($packRatio)
	{
		$result = [];

		foreach ($packRatio as $productId => $ratio)
		{
			$result[$productId] = [$ratio];
		}

		return $result;
	}

	protected function getPriceContext()
	{
		$serviceCode = $this->provider->getServiceCode();
		$siteId = $this->getSiteId();
		$anonymousUser = $this->environment->getUserRegistry()->getAnonymousUser($serviceCode, $siteId);
		$options = $this->provider->getOptions();

		return [
			'SITE_ID' => $siteId,
			'USER_ID' => $anonymousUser->getId(),
			'SKU_MAP' => $options->getProductSkuMap(),
			'SOURCE' => $options->getPriceSource(),
			'PRICE_TYPE' => $options->getPriceTypes(),
			'USE_DISCOUNT' => $options->usePriceDiscount(),
			'CURRENCY' => Market\Data\Currency::getCurrency('RUB'),
		];
	}

	protected function applyPricesSku($prices, $skuMap, $used = [])
	{
		if ($skuMap === null) { return $prices; }

		$result = [];

		foreach ($prices as $price)
		{
			if (!isset($skuMap[$price['ID']])) { continue; }

			$sku = trim($skuMap[$price['ID']]);

			if (isset($used[$sku])) { continue; }

			$price['~ID'] = $price['ID'];
			$price['ID'] = $sku;

			$result[] = $price;
			$used[$sku] = true;
		}

		return $result;
	}

	protected function applyPricesRatio($prices, $packRatio)
	{
		if (empty($packRatio)) { return $prices; }

		$fields = [
			'PRICE',
			'BASE_PRICE',
		];

		foreach ($prices as &$price)
		{
			if (!isset($packRatio[$price['ID']])) { continue; }

			$ratio = $packRatio[$price['ID']];

			foreach ($fields as $field)
			{
				if (!isset($price[$field])) { continue; }

				$price[$field] = Market\Data\Price::round($price[$field] * $ratio);
			}
		}
		unset($price);

		return $prices;
	}

	protected function getSkuMap($productIds)
	{
		$command = new TradingService\Common\Command\SkuMap(
			$this->provider,
			$this->environment
		);

		return $command->make($productIds);
	}

	protected function skuExists($productIds, $skuMap)
	{
		return $skuMap === null ? $productIds : array_keys($skuMap);
	}

	protected function filterValid($prices)
	{
		foreach ($prices as $key => $price)
		{
			$value = round($price['PRICE'], 2);

			if ($value <= 0.0)
			{
				$this->provider->getLogger()->warning(self::getMessage('PRICE_LESS_OR_EQUAL_NULL', [
					'#ID#' => $price['ID'],
				]));

				unset($prices[$key]);
			}
		}

		return $prices;
	}

	protected function filterChanged($prices)
	{
		return $this->getPushStore()->filterChanged($prices);
	}

	protected function buildOffers($prices)
	{
		$result = [];
		$useTaxSystem = ($this->provider->getOptions()->getTaxSystem() !== '');

		foreach ($prices as $price)
		{
			$priceValue = round($price['PRICE'], 2);
			$offer = [
				'id' => (string)$price['ID'],
				'price' => [
					'currencyId' => 'RUR',
					'value' => $priceValue,
				],
			];

			if (!empty($price['BASE_PRICE']) && $price['BASE_PRICE'] > $priceValue)
			{
				$basePrice = round($price['BASE_PRICE'], 2);
				$discount = $basePrice - $priceValue;
				$discountPercent = 100 * floor($discount) / ceil($basePrice);

				if ($discount >= 1 && $discountPercent >= 5 && $discountPercent <= 75)
				{
					$offer['price']['discountBase'] = $basePrice;
				}
			}

			if ($useTaxSystem && isset($price['VAT_RATE']))
			{
				$rate = $this->formatVatRate($price['VAT_RATE']);

				if ($rate !== null)
				{
					$offer['price']['vat'] = $rate;
				}
			}

			$result[] = $offer;
		}

		return $result;
	}

	protected function formatVatRate($rate)
	{
		static $map = [
			2 => 0.1,
			6 => 0.0,
			7 => 0.2,
		];
		$index = array_search((float)$rate, $map, true);

		return $index !== false ? $index : null;
	}

	protected function getOffersChunkSize()
	{
		return (int)Market\Config::getOption('push_prices_chunk', 50);
	}

	protected function isExceedRateLimit($sentCount)
	{
		return ($sentCount >= $this->getOffersRateLimit());
	}

	protected function getOffersRateLimit()
	{
		return (int)Market\Config::getOption('push_prices_rate_limit', 50);
	}

	protected function sendOffers($offers)
	{
		$request = $this->createOffersRequest($offers);

		$result = $request->send();

		Market\Exceptions\Api\Facade::handleResult($result, self::getMessage('SEND_FAILED'));
	}

	protected function createOffersRequest($offers)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();

		if ($options->getPricesMode() === $options::PRICES_MODE_BUSINESS)
		{
			$request = new TradingService\Marketplace\Api\SendPrices\Business\Request();
			$request->setBusinessId($options->getBusinessId());

			$offers = $this->convertOfferForBusiness($offers);
		}
		else
		{
			$request = new TradingService\Marketplace\Api\SendPrices\Campaign\Request();
			$request->setCampaignId($options->getCampaignId());
		}

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setOffers($offers);

		return $request;
	}

	protected function convertOfferForBusiness($offers)
	{
		foreach ($offers as &$offer)
		{
			$offer['offerId'] = $offer['id'];
			unset($offer['id']);
		}
		unset($offer);

		return $offers;
	}

	protected function commitChanged($prices)
	{
		$this->getPushStore()->commit($prices);
	}

	protected function getPushStore()
	{
		if ($this->pushStore === null)
		{
			$this->pushStore = $this->creatPushStore();
		}

		return $this->pushStore;
	}

	protected function creatPushStore()
	{
		return new Market\Trading\State\PushStore(
			$this->provider->getOptions()->getSetupId(),
			Market\Trading\Entity\Registry::ENTITY_TYPE_PRICE,
			'ID',
			'PRICE'
		);
	}
}
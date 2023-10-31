<?php

namespace Yandex\Market\Trading\Service\Common\Action\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Common\Action\HttpAction
{
	use Market\Reference\Concerns\HasLang;
	use TradingService\Common\Concerns\Action\HasMeaningfulProperties;

	/** @var Request */
	protected $request;
	/** @var TradingEntity\Reference\User */
	protected $user;
	/** @var TradingEntity\Reference\Order */
	protected $order;
	protected $basketMap = [];
	protected $basketPackRatio = [];
	protected $basketProducts = [];
	protected $basketErrors = [];
	protected $basketInvalidProducts = [];
	protected $basketInvalidData = [];
	protected $filledProperties = [];
	protected $relatedProperties = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::CART;
	}

	public function process()
	{
		$this->createUser();
		$this->createOrder();

		$this->initializeOrder();
		$this->fillOrder();
		$this->finalizeOrder();

		$this->verify();

		$this->collectResponse();
	}

	protected function createUser()
	{
		$this->user = $this->getAnonymousUser();
	}

	protected function getAnonymousUser()
	{
		$userRegistry = $this->environment->getUserRegistry();

		return $userRegistry->getAnonymousUser($this->provider->getServiceCode(), $this->getSiteId());
	}

	protected function createOrder()
	{
		$orderRegistry = $this->environment->getOrderRegistry();
		$userId = $this->getUserId();
		$siteId = $this->getSiteId();
		$currency = $this->getCurrency();

		$this->order = $orderRegistry->createOrder($siteId, $userId, $currency);
	}

	protected function initializeOrder()
	{
		$calculationMode = $this->getPriceCalculationMode();

		$this->fillPersonType();
		$this->order->setCalculationMode($calculationMode);
		$this->order->initialize();
	}

	protected function getPriceCalculationMode()
	{
		return TradingEntity\Operation\PriceCalculation::PRODUCT | TradingEntity\Operation\PriceCalculation::DELIVERY;
	}

	protected function fillOrder()
	{
		$this->fillXmlId();
		$this->fillProfile();
		$this->fillRegion();
		$this->fillProperties();
		$this->fillBasket();
	}

	protected function finalizeOrder()
	{
		$this->order->finalize();
	}

	protected function fillXmlId()
	{
		$platform = $this->getPlatform();
		$setupId = $this->provider->getOptions()->getSetupId();

		$this->order->fillXmlId(null, $platform);
		$this->order->fillTradingSetup($setupId, $platform);
	}

	protected function fillPersonType()
	{
		$personType = $this->provider->getOptions()->getPersonType();

		$this->order->setPersonType($personType);
	}

	protected function fillProfile()
	{
		$options = $this->provider->getOptions();
		$profileId = (string)$options->getProfileId();
		$values = null;

		if ($profileId !== '')
		{
			$profile = $this->environment->getProfile();
			$values = $profile->getValues($profileId);
		}

		if (!empty($values))
		{
			$this->order->fillProperties($values);
		}
	}

	protected function fillRegion()
	{
		$location = $this->environment->getLocation();
		$requestRegion = $this->request->getCart()->getDelivery()->getRegion();
		$locationId = $location->getLocation($requestRegion->getFields());

		if ($locationId === null)
		{
			$this->handleRegionNotFoundLocation($requestRegion);
			return;
		}

		$meaningfulValues = $location->getMeaningfulValues($locationId);
		$meaningfulValues = $this->sanitizeRegionMeaningfulValues($meaningfulValues);

		$setLocationResult = $this->order->setLocation($locationId);

		$this->handleRegionSetLocationResult($setLocationResult);

		if (!empty($meaningfulValues))
		{
			$this->setMeaningfulPropertyValues($meaningfulValues);
		}
	}

	protected function handleRegionNotFoundLocation(Market\Api\Model\Region $region)
	{
		$error = $this->makeRegionNotFoundLocationError($region);

		$this->provider->getLogger()->debug($error);
	}

	protected function makeRegionNotFoundLocationError(Market\Api\Model\Region $region)
	{
		return new Main\Error(static::getLang('TRADING_ACTION_CART_LOCATION_NOT_FOUND', [
			'#ID#' => $region->getId(),
			'#NAME#' => $region->getName(),
		]));
	}

	protected function handleRegionSetLocationResult(Main\Result $result)
	{
		foreach ($result->getErrors() as $error)
		{
			$this->provider->getLogger()->debug($error);
		}
	}

	protected function sanitizeRegionMeaningfulValues($meaningfulValues)
	{
		return $meaningfulValues;
	}

	protected function fillProperties()
	{
		// nothing by default
	}

	protected function fillRelatedProperties()
	{
		if (!empty($this->relatedProperties))
		{
			$this->order->fillProperties($this->relatedProperties, true);

			$this->filledProperties += $this->relatedProperties;
			$this->relatedProperties = [];
		}
	}

	protected function fillBasket()
	{
		$items = $this->request->getCart()->getItems();
		$offerMap = $this->getOfferMap($items);
		$packRatio = $this->getPackRatio($items, $offerMap);
		$allProductData = $this->getBasketData($items, $offerMap, $packRatio);

		/** @var Market\Api\Model\Cart\Item $item */
		foreach ($items as $itemIndex => $item)
		{
			$offerId = $item->getOfferId();
			$productId = $this->getProductId($offerId, $offerMap);

			if ($productId === null)
			{
				$this->basketErrors[$itemIndex] = static::getLang('TRADING_ACTION_CART_ITEM_SKU_NOT_FOUND');
			}
			else
			{
				$meaningfulValues = $item->getMeaningfulValues();
				$ratio = isset($packRatio[$productId]) ? $packRatio[$productId] : 1;
				$quantity = $item->getCount() * $ratio;
				$data = isset($allProductData[$productId]) ? $allProductData[$productId] : null;
				$dataKeyWithQuantity = $productId . '|' . $quantity;

				if (isset($allProductData[$dataKeyWithQuantity]))
				{
					$data = ($data !== null)
						? $data + $allProductData[$dataKeyWithQuantity]
						: $allProductData[$dataKeyWithQuantity];
				}

				if (!empty($meaningfulValues))
				{
					$data = ($data !== null)
						? $data + $meaningfulValues
						: $meaningfulValues;
				}

				if (isset($data['ERROR']))
				{
					$addResult = new Main\Result();
					$dataError = $data['ERROR'] instanceof Main\Error
						? $data['ERROR']
						: new Main\Error($data['ERROR']);

					$addResult->addError($dataError);
				}
				else
				{
					$data = $this->extendBasketData($item, $data);

					$addResult = $this->order->addProduct($productId, $quantity, $data);
				}

				$addData = $addResult->getData();

				$this->basketPackRatio[$itemIndex] = $ratio;
				$this->basketProducts[$itemIndex] = $productId;

				if (isset($addData['BASKET_CODE']))
				{
					$this->basketMap[$itemIndex] = $addData['BASKET_CODE'];
				}
				else
				{
					$this->basketInvalidProducts[$itemIndex] = $productId;
					$this->basketInvalidData[$itemIndex] = $data;
				}

				if (!$addResult->isSuccess())
				{
					$this->basketErrors[$itemIndex] = implode(PHP_EOL, $addResult->getErrorMessages());
				}
			}
		}
	}

	protected function getOfferMap(Market\Api\Model\Cart\ItemCollection $items)
	{
		$offerIds = $items->getOfferIds();
		$command = new TradingService\Common\Command\OfferMap(
			$this->provider,
			$this->environment
		);

		return $command->make($offerIds);
	}

	protected function getPackRatio(Market\Api\Model\Cart\ItemCollection $items, $offerMap = null)
	{
		$productIds = $offerMap !== null ? array_values($offerMap) : $items->getOfferIds();
		$command = new TradingService\Common\Command\OfferPackRatio(
			$this->provider,
			$this->environment
		);

		return $command->make($productIds);
	}

	protected function getBasketData(Market\Api\Model\Cart\ItemCollection $items, $offerMap = null, $packRatio = null)
	{
		$context = $this->makeBasketContext();
		$productIds = $offerMap !== null ? array_values($offerMap) : $items->getOfferIds();
		$quantities = $items->getQuantities($offerMap);
		$quantities = $this->applyQuantitiesRatio($quantities, $packRatio);

		if (empty($productIds)) { return []; }

		return $this->mergeBasketData([
			$this->getProductData($productIds, $quantities, $context),
			$this->getPriceData($productIds, $quantities, $context),
			$this->getStoreData($productIds, $quantities, $context)
		]);
	}

	protected function applyQuantitiesRatio($quantities, $packRatio)
	{
		foreach ($quantities as $productId => $productQuantities)
		{
			if (!isset($packRatio[$productId])) { continue; }

			$productRatio = $packRatio[$productId];

			foreach ($productQuantities as &$productQuantity)
			{
				$productQuantity *= $productRatio;
			}
			unset($productQuantity);

			$quantities[$productId] = $productQuantities;
		}

		return $quantities;
	}

	protected function makeBasketContext()
	{
		return [
			'USER_ID' => $this->getUserId(),
			'SITE_ID' => $this->getSiteId(),
			'CURRENCY' => $this->getCurrency(),
		];
	}

	protected function mergeBasketData($dataList)
	{
		if (empty($dataList)) { return []; }

		$result = array_shift($dataList);

		foreach ($dataList as $dataChain)
		{
			foreach ($dataChain as $key => $productData)
			{
				if (isset($result[$key]))
				{
					$result[$key] += $productData;
				}
				else
				{
					$result[$key] = $productData;
				}
			}
		}

		return $result;
	}

	protected function getProductData($productIds, $quantities, $context)
	{
		$product = $this->environment->getProduct();

		return $product->getBasketData($productIds, $quantities, $context);
	}

	protected function getPriceData($productIds, $quantities, $context)
	{
		$options = $this->provider->getOptions();
		$price = $this->environment->getPrice();
		$context += [
			'SOURCE' => $options->getPriceSource(),
			'PRICE_TYPE' => $options->getPriceTypes(),
			'USE_DISCOUNT' => $options->usePriceDiscount(),
		];

		return $price->getBasketData($productIds, $quantities, $context);
	}

	protected function getStoreData($productIds, $quantities, $context)
	{
		$options = $this->provider->getOptions();
		$storeEntity = $this->environment->getStore();
		$context += [
			'TRACE' => $options->isProductStoresTrace(),
			'STORES' => $options->getProductStores(),
		];

		return $storeEntity->getBasketData($productIds, $quantities, $context);
	}

	protected function extendBasketData(Market\Api\Model\Cart\Item $item, $data)
	{
		return $data;
	}

	protected function verify()
	{
		$validationResult = $this->validate();

		if ($validationResult->isSuccess()) { return; }

		$this->provider->getLogger()->warning(
			implode(PHP_EOL, $validationResult->getErrorMessages()),
			$this->makeErrorsContext($validationResult)
		);
	}

	protected function makeErrorsContext(Market\Result\Base $result)
	{
		$result = array_filter([
			'items' => $this->makeItemsContext($result)
		]);

		if (empty($result)) { return $result; }

		$now = new Main\Type\DateTime();

		$result += array_filter([
			'settings' => $this->makeSettingsContext(),
			'now' => $now->toString(),
			'timezone' => $now->format('P'),
		]);

		return $result;
	}

	protected function makeItemsContext(Market\Result\Base $result)
	{
		$command = new TradingService\Common\Command\DebugBasketItems(
			$this->provider,
			$this->environment,
			$this->getPlatform(),
			$this->order,
			$this->request->getCart()->getItems(),
			$this->basketMap,
			$this->basketProducts,
			$this->basketPackRatio
		);

		if (!$command->need($result)) { return null; }

		return $command->execute();
	}

	protected function makeSettingsContext()
	{
		$command = new TradingService\Common\Command\DebugSettings(
			$this->provider,
			$this->environment
		);

		return $command->execute();
	}

	protected function validate()
	{
		return $this->validateBasket();
	}

	protected function validateBasket()
	{
		$items = $this->request->getCart()->getItems();
		$result = new Market\Result\Base();

		/** @var Market\Api\Model\Cart\Item $item */
		foreach ($items as $itemIndex => $item)
		{
			$ratio = isset($this->basketPackRatio[$itemIndex]) ? $this->basketPackRatio[$itemIndex] : 1;
			$offerCount = $item->getCount() * $ratio;
			$basketCount = null;

			if (isset($this->basketMap[$itemIndex]))
			{
				$basketCode = $this->basketMap[$itemIndex];
				$basketResult = $this->order->getBasketItemData($basketCode);

				if ($basketResult->isSuccess())
				{
					$basketData = $basketResult->getData();
					$basketCount = (float)$basketData['QUANTITY'];
				}
				else if (empty($this->basketErrors[$itemIndex]))
				{
					$this->basketErrors[$itemIndex] = implode(', ', $basketResult->getErrorMessages());
				}
			}

			if ($basketCount === null)
			{
				$message = static::getLang('TRADING_ACTION_CART_BASKET_NOT_FOUND', [
					'#ITEM_NAME#' => $this->getItemName($item),
					'#COUNT#' => $offerCount,
					'#BASKET_ERROR#' => isset($this->basketErrors[$itemIndex]) ? $this->basketErrors[$itemIndex] : '',
				]);
				$result->addError(new Market\Error\Base($message, 'OFFER_NOT_EXISTS'));
			}
			else if (!Market\Data\Quantity::equal($offerCount, $basketCount))
			{
				$message = static::getLang('TRADING_ACTION_CART_BASKET_COUNT_NOT_MATCH', [
					'#ITEM_NAME#' => $this->getItemName($item),
					'#COUNT#' => $offerCount,
					'#BASKET_COUNT#' => $basketCount,
					'#BASKET_ERROR#' => isset($this->basketErrors[$itemIndex]) ? $this->basketErrors[$itemIndex] : '',
				]);
				$result->addError(new Market\Error\Base($message, 'COUNT_NOT_MATCH'));
			}
		}

		return $result;
	}

	protected function collectResponse()
	{
		$this->collectDelivery();
		$this->collectItems();
		$this->collectPaymentMethods();
		$this->collectTaxSystem();
	}

	protected function collectDelivery()
	{
		$this->response->setField('cart.deliveryCurrency', $this->request->getCart()->getCurrency());
		$this->response->setField('cart.deliveryOptions', []);
	}

	protected function collectTaxSystem()
	{
		$taxSystem = $this->getTaxSystem();

		if ($taxSystem !== '')
		{
			$this->response->setField('cart.taxSystem', $taxSystem);
		}
	}

	protected function collectItems()
	{
		$items = $this->request->getCart()->getItems();
		$hasValidItems = false;
		$hasTaxSystem = ($this->getTaxSystem() !== '');
		$disabledKeys = [];

		if (!$hasTaxSystem)
		{
			$disabledKeys['vat'] = true;
		}

		/** @var Market\Api\Model\Cart\Item $item */
		foreach ($items as $itemIndex => $item)
		{
			$offerId = $item->getOfferId();
			$responseItem = [
				'offerId' => $offerId,
				'count' => 0,
				'delivery' => false,
				'vat' => 'NO_VAT',
			];

			if (isset($this->basketMap[$itemIndex]))
			{
				$basketCode = $this->basketMap[$itemIndex];
				$basketResult = $this->order->getBasketItemData($basketCode);
				$basketData = $basketResult->getData();
				$basketQuantity = isset($basketData['QUANTITY']) ? (float)$basketData['QUANTITY'] : null;

				if ($basketQuantity > 0 && $basketResult->isSuccess())
				{
					$hasValidItems = true;
					$ratio = isset($this->basketPackRatio[$itemIndex]) ? $this->basketPackRatio[$itemIndex] : 1;

					$responseItem['count'] = Market\Data\Quantity::round($basketQuantity / $ratio);
					$responseItem['delivery'] = true;
					$responseItem['price'] = Market\Data\Price::round($basketData['PRICE'] * $ratio);
					$responseItem['vat'] = Market\Data\Vat::convertForService($basketData['VAT_RATE']);
				}
			}

			$responseItem = array_diff_key($responseItem, $disabledKeys);

			$this->response->pushField('cart.items', $responseItem);
		}

		if (!$hasValidItems)
		{
			$this->response->setField('cart.items', []);
		}
	}

	protected function collectPaymentMethods()
	{
		$this->response->setField('cart.paymentMethods', []);
	}

	protected function getProductId($offerId, $offerMap)
	{
		$result = null;

		if ($offerMap === null)
		{
			$result = $offerId;
		}
		else if (isset($offerMap[$offerId]))
		{
			$result = $offerMap[$offerId];
		}

		return $result;
	}

	protected function getUserId()
	{
		return $this->user->getId();
	}

	protected function getCurrency()
	{
		$requestCurrency = $this->request->getCart()->getCurrency();
		$normalizedCurrency = Market\Data\Currency::getCurrency($requestCurrency);

		if ($normalizedCurrency === false)
		{
			$result = $requestCurrency;
		}
		else
		{
			$result = $normalizedCurrency;
		}

		return $result;
	}

	protected function getTaxSystem()
	{
		return (string)$this->provider->getOptions()->getTaxSystem();
	}

	protected function getItemName(Market\Api\Model\Cart\Item $item)
	{
		$offerName = (string)$item->getOfferName();

		if ($offerName !== '')
		{
			$result = sprintf('[%s] %s', $item->getOfferId(), $item->getOfferName());
		}
		else
		{
			$offerId = $item->getOfferId();

			$result = static::getLang('TRADING_ACTION_CART_ITEM_NAME_FALLBACK', [
				'#OFFER_ID#' => $offerId,
			], $offerId);
		}

		return $result;
	}
}
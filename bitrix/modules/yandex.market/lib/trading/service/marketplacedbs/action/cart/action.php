<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\Cart;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Marketplace\Action\Cart\Action
{
	use TradingService\MarketplaceDbs\Concerns\Action\HasRegionHandler;
	use TradingService\MarketplaceDbs\Concerns\Action\HasAddress;

	/** @var TradingService\MarketplaceDbs\Provider */
	protected $provider;
	/** @var Request */
	protected $request;
	/** @var TradingService\Common\Helper\Timeout */
	protected $timeout;
	/** @var array<string, bool> */
	protected $usedPaymentMethodMap = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function __construct(
		TradingService\MarketplaceDbs\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		Main\HttpRequest $request,
		Main\Server $server
	)
	{
		parent::__construct($provider, $environment, $request, $server);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function sanitizeRegionMeaningfulValues($meaningfulValues)
	{
		if ($this->request->getCart()->getDelivery()->getAddress() !== null)
		{
			$meaningfulValues = array_diff_key($meaningfulValues, [
				'LAT' => true,
				'LON' => true,
			]);
		}

		return $meaningfulValues;
	}

	protected function fillProperties()
	{
		$this->fillAddressProperties();
	}

	protected function collectResponse()
	{
		$this->collectDeliveryDefaults();
		$this->collectTaxSystem();
		$this->collectItems();
		$this->collectDelivery();
		$this->collectPaymentMethods();

		$this->applySelfTest();
	}

	protected function collectDeliveryDefaults()
	{
		$this->response->setField('cart.deliveryCurrency', $this->request->getCart()->getCurrency());
		$this->response->setField('cart.deliveryOptions', []);
	}

	protected function collectDelivery()
	{
		if (!$this->hasCollectedItems()) { return; }

		$delivery = $this->environment->getDelivery();
		$deliveryOptions = $this->provider->getOptions()->getDeliveryOptions();
		$processedDeliveries = [];
		$hasCalculated = false;
		$isInterrupted = false;
		$timeout = $this->getTimeout();
		$timeoutGap = 0.5; // seconds

		foreach ($this->getCalculationDeliveries() as $deliveryId)
		{
			$timeout->tick();

			$processedDeliveries[] = $deliveryId;

			if ($delivery->isCompatible($deliveryId, $this->order))
			{
				$deliveryOption = $deliveryOptions->getItemByServiceId($deliveryId);
				$calculationResult = $delivery->calculate($deliveryId, $this->order);

				$this->extendDeliveryCalculation($deliveryId, $calculationResult, $deliveryOption);
				$this->sanitizeDeliveryCalculation($deliveryId, $calculationResult, $deliveryOption);
				$this->validateDeliveryCalculation($calculationResult);

				if ($calculationResult->isSuccess())
				{
					$hasCalculated = true;
					$responseOption = $this->makeDeliveryOption($deliveryId, $calculationResult, $deliveryOption);

					$this->response->pushField('cart.deliveryOptions', $responseOption);

					$this->storeDeliveryOptionUsage($responseOption);
				}

				if ($this->needLogDeliveryCalculationResult($deliveryId))
				{
					$this->logDeliveryCalculationResult($deliveryId, $calculationResult);
				}
			}

			if ($timeout->check($timeoutGap) && $hasCalculated)
			{
				$isInterrupted = true;
				break;
			}
		}

		if ($isInterrupted && $this->needLogInterruptOfDeliveryCalculation($processedDeliveries))
		{
			$this->logInterruptOfDeliveryCalculation();
		}
	}

	protected function getCalculationDeliveries()
	{
		$options = $this->provider->getOptions();
		$compatibleIds = $this->getRestrictedDeliveries();

		if (empty($compatibleIds))
		{
			$result = [];
		}
		else if ($options->isDeliveryStrict())
		{
			$deliveryOptions = $options->getDeliveryOptions();
			$configuredIds = $deliveryOptions->getServiceIds();

			$result = array_intersect($compatibleIds, $configuredIds);
		}
		else
		{
			$result = $compatibleIds;
		}

		return $result;
	}

	protected function getRestrictedDeliveries()
	{
		$delivery = $this->environment->getDelivery();

		return $delivery->getRestricted($this->order);
	}

	protected function needLogDeliveryCalculationResult($deliveryId)
	{
		$configuredDeliveries = $this->provider->getOptions()->getDeliveryOptions()->getServiceIds();

		return in_array($deliveryId, $configuredDeliveries, true);
	}

	protected function logDeliveryCalculationResult($deliveryId, TradingEntity\Reference\Delivery\CalculationResult $calculationResult)
	{
		$logger = $this->provider->getLogger();
		$prefix = sprintf('[%s] %s: ', $deliveryId, $calculationResult->getServiceName());

		foreach ($calculationResult->getErrors() as $error)
		{
			$logger->warning($prefix . $error->getMessage());
		}

		foreach ($calculationResult->getWarnings() as $warning)
		{
			$logger->debug($prefix . $warning->getMessage());
		}
	}

	protected function needLogInterruptOfDeliveryCalculation($processedDeliveries)
	{
		$options = $this->provider->getOptions();
		$configuredDeliveries = $options->getDeliveryOptions()->getServiceIds();

		if (!empty($configuredDeliveries))
		{
			$notProcessedDeliveries = array_diff($configuredDeliveries, $processedDeliveries);
			$result = !empty($notProcessedDeliveries);
		}
		else
		{
			$result = count($processedDeliveries) === 0;
		}

		return $result;
	}

	protected function logInterruptOfDeliveryCalculation()
	{
		$logger = $this->provider->getLogger();
		$message = static::getLang('TRADING_ACTION_CART_INTERRUPT_CALCULATED_DELIVERIES');

		$logger->warning($message);
	}

	protected function extendDeliveryCalculation(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$this->extendDeliveryCalculationType($deliveryId, $calculationResult, $deliveryOption);
		$this->extendDeliveryCalculationServiceName($deliveryId, $calculationResult, $deliveryOption);
		$this->extendDeliveryCalculationDatesFromOption($deliveryId, $calculationResult, $deliveryOption);
		$this->extendDeliveryCalculationDateDefaults($deliveryId, $calculationResult, $deliveryOption);
		$this->extendDeliveryCalculationDateIntervals($deliveryId, $calculationResult, $deliveryOption);
		$this->extendDeliveryCalculationOutlet($deliveryId, $calculationResult, $deliveryOption);
	}

	protected function extendDeliveryCalculationType(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		if ($deliveryOption !== null)
		{
			$calculationResult->setDeliveryType($deliveryOption->getType());
		}
		else if ($calculationResult->isSuccess())
		{
			$serviceDelivery = $this->provider->getDelivery();
			$environmentDelivery = $this->environment->getDelivery();
			$calculationType = $calculationResult->getDeliveryType();
			$supportedTypes = $serviceDelivery->getTypes();

			if ($calculationType === null || !in_array($calculationType, $supportedTypes, true))
			{
				$type = $environmentDelivery->suggestDeliveryType($deliveryId, $supportedTypes);

				if ($type === null)
				{
					$type = $serviceDelivery->getDefaultType();
				}

				$calculationResult->setDeliveryType($type);
			}
		}
	}

	protected function extendDeliveryCalculationServiceName(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		if ($deliveryOption === null) { return; }

		$name = $deliveryOption->getName();

		if ($name !== '')
		{
			$calculationResult->setServiceName($name);
		}
	}

	protected function extendDeliveryCalculationDatesFromOption(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		if ($deliveryOption === null) { return; }

		$dateFrom = $calculationResult->getDateFrom();
		$daysFrom = $deliveryOption->getDaysFrom();
		$dateTo = $calculationResult->getDateTo();
		$daysTo = $deliveryOption->getDaysTo();

		if ($daysFrom === null && $daysTo !== null)
		{
			$daysFrom = 0;
		}

		if ($dateFrom === null && $daysFrom !== null)
		{
			$dateFrom = new Main\Type\DateTime();
			$dateFrom->add('P' . $daysFrom . 'D');

			$calculationResult->setDateFrom($dateFrom);
		}

		if ($dateTo === null && $daysTo !== null)
		{
			$dateTo = new Main\Type\DateTime();
			$dateTo->add('P' . $daysTo . 'D');

			$calculationResult->setDateTo($dateTo);
		}
	}

	protected function extendDeliveryCalculationDateDefaults(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		if ($calculationResult->getDateFrom() === null && $calculationResult->getDateTo() !== null)
		{
			$dateFrom = new Main\Type\DateTime();

			$calculationResult->setDateFrom($dateFrom);
		}
	}

	protected function extendDeliveryCalculationDateIntervals(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		try
		{
			if ($deliveryOption === null) { return; }

			$dateFrom = $calculationResult->getDateFrom();
			$dateTo = $calculationResult->getDateTo();

			if ($dateFrom === null) { return; }

			$readyDate = $this->getDeliveryOptionReadyDate($deliveryOption);
			$daysLimit = $this->getDeliveryOptionDateIntervalsDaysLimit();

			$command = new TradingService\MarketplaceDbs\Command\DeliveryIntervalsMake(
				$deliveryOption,
				$dateFrom,
				$dateTo
			);
			$command->setMinDate($readyDate);
			$command->setMaxDaysCount($daysLimit);

			if ($command->canExecute())
			{
				$intervals = $command->execute();

				$calculationResult->setDateIntervals($intervals);
			}
		}
		catch (Main\SystemException $exception)
		{
			$message = static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_SERVICE_MAKE_INTERVALS_FAILED', [
				'#ERROR#' => $exception->getMessage(),
			]);

			$calculationResult->addWarning(new Market\Error\Base($message));
		}
	}

	protected function getDeliveryOptionReadyDate(TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption)
	{
		$command = new TradingService\MarketplaceDbs\Command\DeliveryOptionReadyDate($deliveryOption);

		return $command->execute();
	}

	protected function extendDeliveryCalculationOutlet(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$outlets = $deliveryOption !== null ? $deliveryOption->getOutlets() : null;

		if (!empty($outlets))
		{
			$calculationResult->setOutlets($outlets);
		}
		else if ($calculationResult->getOutlets() === null)
		{
			$stores = $calculationResult->getStores();
			$storeField = (string)$this->provider->getOptions()->getOutletStoreField();

			if (!empty($stores) && $storeField !== '')
			{
				$storesMap = $this->environment->getStore()->mapStores($storeField, $stores);
				$calculationResult->setOutlets(array_values($storesMap));
			}
		}
	}

	protected function sanitizeDeliveryCalculation(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$this->sanitizeDeliveryCalculationDatesIntervals($deliveryId, $calculationResult, $deliveryOption);
		$this->sanitizeDeliveryCalculationDatesByIntervals($deliveryId, $calculationResult, $deliveryOption);
	}

	protected function sanitizeDeliveryCalculationDatesIntervals(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		try
		{
			$intervals = $calculationResult->getDateIntervals();

			if ($intervals === null) { return; }

			$daysLimit = $this->getDeliveryOptionDateIntervalsDaysLimit();

			$command = new TradingService\MarketplaceDbs\Command\DeliveryIntervalsNormalize($intervals);
			$command->setMinDuration(2);
			$command->setMaxDuration(8);
			$command->setMaxTimesCount(5);
			$command->setMaxDaysCount($daysLimit);

			if ($deliveryOption !== null)
			{
				$readyDate = $this->getDeliveryOptionReadyDate($deliveryOption);
				$command->setMinDate($readyDate);
			}

			$intervals = $command->execute();

			$calculationResult->setDateIntervals($intervals);
		}
		catch (Main\SystemException $exception)
		{
			$message = static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_SERVICE_NORMALIZE_INTERVALS_FAILED', [
				'#ERROR#' => $exception->getMessage(),
			]);

			$calculationResult->addWarning(new Market\Error\Base($message));
		}
	}

	protected function sanitizeDeliveryCalculationDatesByIntervals(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$intervals = $calculationResult->getDateIntervals();

		if ($intervals === null) { return; }

		$dateFrom = $calculationResult->getDateFrom();
		$dateTo = $calculationResult->getDateTo();
		$firstInterval = reset($intervals);
		$lastInterval = end($intervals);

		if (
			isset($firstInterval['date'])
			&& ($dateFrom === null || Market\Data\Date::compare($firstInterval['date'], $dateFrom) === 1)
		)
		{
			$calculationResult->setDateFrom($firstInterval['date']);
		}

		if (
			isset($lastInterval['date'])
			&& ($dateTo === null || Market\Data\Date::compare($lastInterval['date'], $dateTo) === 1)
		)
		{
			$calculationResult->setDateTo($lastInterval['date']);
		}
	}

	protected function validateDeliveryCalculation(TradingEntity\Reference\Delivery\CalculationResult $calculationResult)
	{
		if (!$calculationResult->isSuccess()) { return; }

		$this->validateDeliveryCalculationDateFrom($calculationResult);
		$this->validateDeliveryCalculationPickup($calculationResult);
	}

	protected function validateDeliveryCalculationDateFrom(TradingEntity\Reference\Delivery\CalculationResult $calculationResult)
	{
		$dateFrom = $calculationResult->getDateFrom();

		if ($dateFrom === null)
		{
			$message = static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_DATE_EMPTY');
			$calculationResult->addError(new Market\Error\Base($message));
		}
		else if (!$this->matchDeliveryLimitDate($dateFrom))
		{
			$limitDate = $this->getDeliveryLimitDate();
			$message = static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_DATE_EXCEED_LIMIT', [
				'#DATE#' => Market\Data\Date::format($dateFrom),
				'#LIMIT#' => Market\Data\Date::format($limitDate),
			]);

			$calculationResult->addError(new Market\Error\Base($message));
		}
	}

	protected function matchDeliveryLimitDate(Main\Type\Date $date)
	{
		$limitDate = $this->getDeliveryLimitDate();

		return Market\Data\Date::compare($date, $limitDate) === -1;
	}

	protected function getDeliveryLimitDate()
	{
		$result = new Main\Type\Date();
		$result->add('P31D');

		return $result;
	}

	protected function validateDeliveryCalculationPickup(TradingEntity\Reference\Delivery\CalculationResult $calculationResult)
	{
		if ($calculationResult->getDeliveryType() !== TradingService\MarketplaceDbs\Delivery::TYPE_PICKUP) { return; }

		$outlets = $calculationResult->getOutlets();

		if (empty($outlets))
		{
			$message = static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_PICKUP_EMPTY_OUTLET');
			$calculationResult->addError(new Market\Error\Base($message));
		}
	}

	protected function makeDeliveryOption(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$result = [
			'id' => (string)$deliveryId,
			'type' => $calculationResult->getDeliveryType(),
			'serviceName' => $this->makeDeliveryOptionServiceName($calculationResult),
			'price' => Market\Data\Price::round($calculationResult->getPrice()),
		];
		$result += array_filter([
			'dates' => $this->makeDeliveryOptionDates($calculationResult),
			'outlets' => $this->makeDeliveryOptionOutlets($calculationResult),
		]);
		$result += [
			'paymentMethods' => $this->makeDeliveryOptionPaymentMethods($deliveryId),
		];

		return $result;
	}

	protected function makeDeliveryOptionServiceName(TradingEntity\Reference\Delivery\CalculationResult $calculationResult)
	{
		$name = $calculationResult->getServiceName();
		$nameLength = Market\Data\TextString::getLength($name);
		$lengthLimit = 50;

		if ($nameLength > $lengthLimit)
		{
			$suffix = static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_SERVICE_NAME_TRUNCATE_SUFFIX', null, '...');
			$suffixLength = Market\Data\TextString::getLength($suffix);
			$leftLength = $lengthLimit - $suffixLength;

			$name = Market\Data\TextString::getSubstring($name, 0, $leftLength) . $suffix;

			$calculationResult->addWarning(new Market\Error\Base(
				static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_SERVICE_NAME_TRUNCATE_LOG', [ '#LIMIT#' => $lengthLimit ])
			));
		}

		return $name;
	}

	protected function makeDeliveryOptionDates(TradingEntity\Reference\Delivery\CalculationResult $calculationResult)
	{
		/** @var Main\Type\Date $dateFrom */
		$dateFrom = $calculationResult->getDateFrom();
		$dateTo = $calculationResult->getDateTo();
		$result = [
			'fromDate' => Market\Data\Date::convertForService($dateFrom, Market\Data\Date::FORMAT_DEFAULT_SHORT),
		];

		if ($dateTo !== null && Market\Data\Date::compare($dateTo, $dateFrom) !== -1)
		{
			$result['toDate'] = Market\Data\Date::convertForService($dateTo, Market\Data\Date::FORMAT_DEFAULT_SHORT);
		}

		if ($calculationResult->getDeliveryType() === TradingService\MarketplaceDbs\Delivery::TYPE_DELIVERY)
		{
			$dateIntervals = $calculationResult->getDateIntervals();

			if ($dateIntervals !== null)
			{
				$result['intervals'] = $this->formatDeliveryOptionDateIntervals($dateIntervals);
			}
			else if ($dateTo !== null)
			{
				$result['intervals'] = $this->emulateDeliveryOptionDateIntervals($dateFrom, $dateTo);
			}
		}

		return array_filter($result);
	}

	protected function formatDeliveryOptionDateIntervals(array $intervals)
	{
		$result = [];

		foreach ($intervals as $interval)
		{
			$resultInterval = [
				'date' => Market\Data\Date::convertForService($interval['date'], Market\Data\Date::FORMAT_DEFAULT_SHORT),
			];

			if (isset($interval['fromTime'], $interval['toTime']))
			{
				$resultInterval['fromTime'] = Market\Data\Time::format($interval['fromTime']);
				$resultInterval['toTime'] = Market\Data\Time::format($interval['toTime']);
			}

			$result[] = $resultInterval;
		}

		return $result;
	}

	protected function emulateDeliveryOptionDateIntervals(Main\Type\Date $from, Main\Type\Date $to)
	{
		$iterator = clone $from;
		$iterateCount = 0;
		$iterateLimit = $this->getDeliveryOptionDateIntervalsDaysLimit();
		$result = [];

		do
		{
			$result[] = [
				'date' => Market\Data\Date::convertForService($iterator, Market\Data\Date::FORMAT_DEFAULT_SHORT),
			];

			$iterator->add('P1D');
		}
		while (
			++$iterateCount < $iterateLimit
			&& Market\Data\Date::compare($iterator, $to) !== 1
		);

		return $result;
	}

	protected function getDeliveryOptionDateIntervalsDaysLimit()
	{
		return 7;
	}

	protected function makeDeliveryOptionOutlets(TradingEntity\Reference\Delivery\CalculationResult $calculationResult)
	{
		return ($calculationResult->getDeliveryType() === TradingService\MarketplaceDbs\Delivery::TYPE_PICKUP)
			? array_map(static function($code) { return [ 'code' => (string)$code ]; }, (array)$calculationResult->getOutlets())
			: null;
	}

	protected function makeDeliveryOptionPaymentMethods($deliveryId)
	{
		$environmentPaySystem = $this->environment->getPaySystem();
		$servicePaySystem = $this->provider->getPaySystem();
		$options = $this->provider->getOptions();
		$paySystemOptions = $options->getPaySystemOptions();
		$methodMap = [];

		foreach ($this->getCompatiblePaySystems($deliveryId) as $paySystemId)
		{
			$itemOptions = $paySystemOptions->getItemsByPaySystemId($paySystemId);

			if (!empty($itemOptions))
			{
				foreach ($itemOptions as $itemOption)
				{
					$method = $itemOption->getMethod();
					$methodMap[$method] = true;
				}
			}
			else if (!$options->isPaySystemStrict() && !$this->isInternalPaySystem($paySystemId))
			{
				$meaningfulMap = $servicePaySystem->getMethodMeaningfulMap();
				$suggestMethods = $environmentPaySystem->suggestPaymentMethod($paySystemId, $meaningfulMap);

				if (empty($suggestMethods)) { continue; }

				foreach ($meaningfulMap as $method => $meaningfulMethod)
				{
					if (in_array($meaningfulMethod, $suggestMethods, true))
					{
						$methodMap[$method] = true;
					}
				}
			}
		}

		return array_keys($methodMap);
	}

	protected function getCompatiblePaySystems($deliveryId)
	{
		$paySystem = $this->environment->getPaySystem();

		return $paySystem->getCompatible($this->order, $deliveryId);
	}

	protected function isInternalPaySystem($paySystemId)
	{
		$options = $this->provider->getOptions();
		$subsidyPaySystemId = $options->getSubsidyPaySystemId();

		return ($subsidyPaySystemId !== '' && (int)$subsidyPaySystemId === (int)$paySystemId);
	}

	protected function storeDeliveryOptionUsage($responseOption)
	{
		if (!empty($responseOption['paymentMethods']))
		{
			$this->usedPaymentMethodMap += array_flip($responseOption['paymentMethods']);
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

		/** @var TradingService\Marketplace\Model\Cart\Item $item */
		foreach ($items as $itemIndex => $item)
		{
			$feedId = $item->getFeedId();
			$offerId = $item->getOfferId();
			$responseItem = [
				'feedId' => $feedId,
				'offerId' => $offerId,
				'delivery' => false,
				'count' => 0,
				'vat' => 'NO_VAT',
			];

			if (isset($this->basketMap[$itemIndex]))
			{
				$basketCode = $this->basketMap[$itemIndex];
				$basketResult = $this->order->getBasketItemData($basketCode);

				if ($basketResult->isSuccess())
				{
					$hasValidItems = true;
					$basketData = $basketResult->getData();
					$responseItem['delivery'] = true;
					$responseItem['count'] = (int)$basketData['QUANTITY'];
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

	protected function hasCollectedItems()
	{
		$items = $this->response->getField('cart.items');

		return is_array($items) && !empty($items);
	}

	protected function collectPaymentMethods()
	{
		$methods = array_merge(
			$this->getUsedPaySystemMethods(),
			$this->getConfiguredPaySystemMethods()
		);
		$methods = array_unique($methods);
		$methods = array_values($methods);

		$this->response->setField('cart.paymentMethods', $methods);
	}

	protected function getConfiguredPaySystemMethods()
	{
		$methodMap = [];

		foreach ($this->provider->getOptions()->getPaySystemOptions() as $paySystemOption)
		{
			$method = $paySystemOption->getMethod();
			$methodMap[$method] = true;
		}

		return array_keys($methodMap);
	}

	protected function getUsedPaySystemMethods()
	{
		return array_keys($this->usedPaymentMethodMap);
	}

	protected function getTimeout()
	{
		if ($this->timeout === null)
		{
			$this->timeout = $this->createTimeout();
		}

		return $this->timeout;
	}

	protected function createTimeout()
	{
		return new TradingService\Common\Helper\Timeout(5.5);
	}

	protected function applySelfTestOutOfStock()
	{
		if (!$this->provider->getOptions()->getSelfTestOption()->isOutOfStock()) { return; }

		$this->response->setField('cart.deliveryOptions', []);
		$this->response->setField('cart.items', []);
		$this->response->setField('cart.paymentMethods', []);

		$this->provider->getLogger()->warning(static::getLang(
			'TRADING_MARKETPLACE_CART_SELF_TEST_OUT_OF_STOCK_ON'
		));
	}
}
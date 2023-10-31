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

	protected function getPriceCalculationMode()
	{
		return TradingEntity\Operation\PriceCalculation::DELIVERY;
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
		$deliveryIds = $this->getCalculationDeliveries();
		$deliveryIds = $this->sortCalculationDeliveries($deliveryIds);
		$processedDeliveries = [];
		$hasCalculated = false;
		$isInterrupted = false;
		$timeout = $this->getTimeout();
		$timeoutGap = 0.5; // seconds

		foreach ($deliveryIds as $deliveryId)
		{
			$processedDeliveries[] = $deliveryId;
			$deliveryOption = $deliveryOptions->getItemByServiceId($deliveryId);

			if ($this->isAutoCalculated($deliveryId, $deliveryOption))
			{
				$responseOption = $this->emulateDeliveryOption($deliveryId, $deliveryOption);
				$this->storeDeliveryOptionUsage($responseOption);

				continue;
			}

			$timeout->tick();

			if ($delivery->isCompatible($deliveryId, $this->order))
			{
				$stopCalculation = false;
				$calculationResult = $delivery->calculate($deliveryId, $this->order);

				$this->extendDeliveryCalculation($deliveryId, $calculationResult, $deliveryOption);
				$this->sanitizeDeliveryCalculation($deliveryId, $calculationResult, $deliveryOption);
				$this->validateDeliveryCalculation($calculationResult);

				if ($calculationResult->isSuccess())
				{
					$hasCalculated = true;
					$responseOption = $this->makeDeliveryOption($deliveryId, $calculationResult, $deliveryOption);
					$stopCalculation = $this->isDeliveryTypeStandalone($calculationResult->getDeliveryType());

					$this->response->pushField('cart.deliveryOptions', $responseOption);

					$this->storeDeliveryOptionUsage($responseOption);
				}

				if ($this->needLogDeliveryCalculationResult($deliveryId))
				{
					$this->logDeliveryCalculationResult($deliveryId, $calculationResult);
				}

				if ($stopCalculation) { break; }
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

	protected function sortCalculationDeliveries($deliveryIds)
	{
		$sort = $this->mapDeliveryOptionsSort();

		if (empty($sort)) { return $deliveryIds; }

		uasort($deliveryIds, static function($deliveryIdA, $deliveryIdB) use ($sort) {
			$sortA = isset($sort[$deliveryIdA]) ? $sort[$deliveryIdA] : 500;
			$sortB = isset($sort[$deliveryIdB]) ? $sort[$deliveryIdB] : 500;

			if ($sortA === $sortB) { return 0; }

			return ($sortA < $sortB ? -1 : 1);
		});

		return $deliveryIds;
	}

	protected function mapDeliveryOptionsSort()
	{
		$result = [];

		/** @var TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption */
		foreach ($this->provider->getOptions()->getDeliveryOptions() as $deliveryOption)
		{
			if ($this->isDeliveryTypeStandalone($deliveryOption->getType()))
			{
				$result[$deliveryOption->getServiceId()] = 1;
			}
		}

		return $result;
	}

	protected function isDeliveryTypeStandalone($type)
	{
		return $type === TradingService\MarketplaceDbs\Delivery::TYPE_DIGITAL;
	}

	protected function getRestrictedDeliveries()
	{
		$delivery = $this->environment->getDelivery();

		return $delivery->getRestricted($this->order);
	}

	protected function isAutoCalculated($deliveryId, TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null)
	{
		if ($deliveryOption !== null)
		{
			return (
				$deliveryOption->isInvertible()
				&& $deliveryOption->getType() === TradingService\MarketplaceDbs\Delivery::TYPE_DELIVERY
			);
		}

		$result = false;
		$type = $this->environment->getDelivery()->suggestDeliveryType(
			$deliveryId,
			$this->provider->getDelivery()->getTypes()
		);

		if ($type === TradingService\MarketplaceDbs\Delivery::TYPE_DELIVERY)
		{
			$courier = $this->environment->getCourierRegistry()->resolveCourier($deliveryId);

			$result = ($courier instanceof TradingEntity\Reference\CourierInvertible);
		}

		return $result;
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
		$this->extendDeliveryCalculationDateReadyShift($deliveryId, $calculationResult, $deliveryOption);
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

		$this->applyDeliveryCalculationFixedPeriod($calculationResult, $deliveryOption);
		$this->applyDeliveryCalculationDefaultDays($calculationResult, $deliveryOption);
	}

	protected function applyDeliveryCalculationFixedPeriod(
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption
	)
	{
		if (!$deliveryOption->isFixedPeriod()) { return; }

		$periodFrom = $deliveryOption->getPeriodFrom();
		$periodTo = $deliveryOption->getPeriodTo();

		if ($periodFrom === null && $periodTo !== null)
		{
			$periodFrom = 0;
		}

		if ($periodFrom !== null && $periodTo === null)
		{
			$periodTo = $periodFrom;
		}

		if ($periodFrom !== null)
		{
			$dateFrom = new Main\Type\DateTime();
			$dateFrom->add('P' . $periodFrom . 'D');

			$calculationResult->setDateFrom($dateFrom);
		}

		if ($periodTo !== null)
		{
			$dateTo = new Main\Type\DateTime();
			$dateTo->add('P' . $periodTo . 'D');

			$calculationResult->setDateTo($dateTo);
		}
	}

	/** @noinspection PhpDeprecationInspection */
	protected function applyDeliveryCalculationDefaultDays(
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption
	)
	{
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

	protected function extendDeliveryCalculationDateReadyShift(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		try
		{
			$dateFrom = $calculationResult->getDateFrom();
			$dateTo = $calculationResult->getDateTo();

			if ($dateFrom === null) { return; }

			$readyDate = $deliveryOption !== null && $deliveryOption->increasePeriodOnWeekend()
				? $this->getDeliveryOptionReadyDate($deliveryOption)
				: $this->getDeliveryShipmentReadyDate();
			$now = new Main\Type\DateTime();
			$diff = Market\Data\Date::diff($now, $readyDate);

			if ($diff <= 0) { return; }

			$delayInterval = sprintf('P%sD', $diff);

			// from

			$dateFrom = clone $dateFrom;
			$dateFrom->add($delayInterval);

			$calculationResult->setDateFrom($dateFrom);

			// to

			if ($dateTo !== null)
			{
				$dateTo = clone $dateTo;
				$dateTo->add($delayInterval);

				$calculationResult->setDateTo($dateTo);
			}
		}
		catch (Main\SystemException $exception)
		{
			$message = static::getLang('TRADING_MARKETPLACE_CART_DELIVERY_SERVICE_READY_SHIFT_FAILED', [
				'#ERROR#' => $exception->getMessage(),
			]);

			$calculationResult->addWarning(new Market\Error\Base($message));
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
		return $this->getDeliveryShipmentReadyDate($deliveryOption);
	}

	protected function getDeliveryShipmentReadyDate(TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null)
	{
		$shipmentSchedule = $this->provider->getOptions()->getShipmentSchedule();

		$command = new TradingService\MarketplaceDbs\Command\DeliveryShipmentDate(
			$shipmentSchedule,
			$deliveryOption
		);
		$command->setCalculateDirection(true);
		$command->setCalculateOffset(0);

		return $command->execute();
	}

	protected function extendDeliveryCalculationOutlet(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$outlets = $this->deliveryOutlets($deliveryId, $deliveryOption);

		if (!empty($outlets))
		{
			if ($deliveryOption === null)
			{
				$calculationResult->setDeliveryType(TradingService\MarketplaceDbs\Delivery::TYPE_PICKUP);
			}

			$calculationResult->setOutlets($outlets);
		}
		else if ($calculationResult->getOutlets() === null)
		{
			$stores = $calculationResult->getStores();
			$storeField = (string)$this->provider->getOptions()->getOutletStoreField();

			if (!empty($stores) && $storeField !== '')
			{
				$storesMap = $this->environment->getStore()->mapStores($storeField, $stores);

				if ($deliveryOption === null)
				{
					$calculationResult->setDeliveryType(TradingService\MarketplaceDbs\Delivery::TYPE_PICKUP);
				}

				$calculationResult->setOutlets(array_values($storesMap));
			}
		}
	}

	protected function deliveryOutlets(
		$deliveryId,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		try
		{
			if ($deliveryOption !== null)
			{
				if ($deliveryOption->getType() !== TradingService\MarketplaceDbs\Delivery::TYPE_PICKUP) { return null; }

				$outletType = $deliveryOption->getOutletType();

				if ($deliveryOption->getOutletType() === $deliveryOption::OUTLET_TYPE_MANUAL)
				{
					return $deliveryOption->getOutlets();
				}

				$outlet = $this->environment->getOutletRegistry()->getOutlet($outletType);
			}
			else
			{
				$outlet = $this->environment->getOutletRegistry()->resolveOutlet($deliveryId);
			}

			if ($outlet === null) { return null; }

			$region = $this->request->getCart()->getDelivery()->getRegion();

			$result = $outlet->getOutlets($this->order, $deliveryId, $region);
		}
		catch (Main\SystemException $exception)
		{
			$this->provider->getLogger()->debug($exception);

			$result = null;
		}
		/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
		catch (\Throwable $exception)
		{
			$this->provider->getLogger()->warning($exception);

			$result = null;
		}

		return $result;
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

			$command = new TradingService\MarketplaceDbs\Command\DeliveryIntervalsNormalize($intervals);
			$command->setMaxTimesCount(5);

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

	protected function emulateDeliveryOption(
		$deliveryId,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$deliveryType = $deliveryOption !== null ? $deliveryOption->getType() : TradingService\Marketplace\Delivery::TYPE_DELIVERY;
		$paymentMethods = $this->makeDeliveryOptionPaymentMethods($deliveryId);
		$paymentMethods = $this->sanitizePaymentMethodsByDeliveryType($paymentMethods, $deliveryType);

		return [
			'id' => (string)$deliveryId,
			'type' => $deliveryType,
			'paymentMethods' => $paymentMethods,
		];
	}

	protected function makeDeliveryOption(
		$deliveryId,
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
	{
		$paymentMethods = $this->makeDeliveryOptionPaymentMethods($deliveryId);
		$paymentMethods = $this->sanitizePaymentMethodsByDeliveryType($paymentMethods, $calculationResult->getDeliveryType());

		$result = [
			'id' => (string)$deliveryId,
			'type' => $calculationResult->getDeliveryType(),
			'serviceName' => $this->makeDeliveryOptionServiceName($calculationResult),
			'price' => Market\Data\Price::round($calculationResult->getPrice()),
		];
		$result += array_filter([
			'dates' => $this->makeDeliveryOptionDates($calculationResult, $deliveryOption),
			'outlets' => $this->makeDeliveryOptionOutlets($calculationResult),
		]);
		$result += [
			'paymentMethods' => $paymentMethods,
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

	protected function makeDeliveryOptionDates(
		TradingEntity\Reference\Delivery\CalculationResult $calculationResult,
		TradingService\MarketplaceDbs\Options\DeliveryOption $deliveryOption = null
	)
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

			if (!empty($dateIntervals))
			{
				$useTime = ($deliveryOption === null || $deliveryOption->getIntervalFormat() === $deliveryOption::INTERVAL_FORMAT_TIME);
				$result['intervals'] = $this->formatDeliveryOptionDateIntervals($dateIntervals, $useTime);
			}
		}

		return $result;
	}

	protected function formatDeliveryOptionDateIntervals(array $intervals, $useTime = true)
	{
		$result = [];
		$usedDates = [];

		foreach ($intervals as $interval)
		{
			$resultInterval = [
				'date' => Market\Data\Date::convertForService($interval['date'], Market\Data\Date::FORMAT_DEFAULT_SHORT),
			];

			if ($useTime && isset($interval['fromTime'], $interval['toTime']))
			{
				$resultInterval['fromTime'] = Market\Data\Time::format($interval['fromTime']);
				$resultInterval['toTime'] = Market\Data\Time::format($interval['toTime']);
			}
			else if (isset($usedDates[$resultInterval['date']]))
			{
				continue;
			}

			$result[] = $resultInterval;
			$usedDates[$resultInterval['date']] = true;
		}

		return $result;
	}

	/** @deprecated */
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
		$usageMap = $servicePaySystem->getUsageMap();
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
					if ($itemOption->useMethod())
					{
						$method = $itemOption->getMethod();
						$methodMap[$method] = true;
					}
					else
					{
						$type = $itemOption->getType();

						if (!isset($usageMap[$type])) { continue; }

						$methodMap += array_flip($usageMap[$type]);
					}
				}
			}
			else if (!$options->isPaySystemStrict() && !$this->isInternalPaySystem($paySystemId))
			{
				$typeMeaningfulMap = $servicePaySystem->getTypeMeaningfulMap();
				$methodMeaningfulMap = $servicePaySystem->getMethodMeaningfulMap();
				$suggestMethods = $environmentPaySystem->suggestPaymentMethod($paySystemId, $methodMeaningfulMap);

				if (!empty($suggestMethods))
				{
					foreach ($methodMeaningfulMap as $method => $meaningfulMethod)
					{
						if (in_array($meaningfulMethod, $suggestMethods, true))
						{
							$methodMap[$method] = true;
						}
					}
				}
				else
				{
					$suggestType = $environmentPaySystem->suggestPaymentType($paySystemId);
					$type = $suggestType !== null ? array_search($suggestType, $typeMeaningfulMap, true) : false;

					if ($type === false || !isset($usageMap[$type])) { continue; }

					$methodMap += array_flip($usageMap[$type]);
				}
			}
		}

		return array_keys($methodMap);
	}

	protected function sanitizePaymentMethodsByDeliveryType($paymentMethods, $deliveryType)
	{
		$restrictedMap = $this->provider->getDelivery()->restrictedPaymentMethods();

		if (!isset($restrictedMap[$deliveryType])) { return $paymentMethods; }

		return array_values(array_intersect($paymentMethods, $restrictedMap[$deliveryType]));
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

	protected function hasCollectedItems()
	{
		$items = $this->response->getField('cart.items');

		return is_array($items) && !empty($items);
	}

	protected function collectPaymentMethods()
	{
		$methods = $this->getUsedPaySystemMethods();

		if (empty($methods))
		{
			$methods = $this->getConfiguredPaySystemMethods();
		}

		$methods = array_unique($methods);
		$methods = array_values($methods);

		$this->response->setField('cart.paymentMethods', $methods);
	}

	protected function getConfiguredPaySystemMethods()
	{
		$methodMap = [];
		$usageMap = $this->provider->getPaySystem()->getUsageMap();

		/** @var TradingService\MarketplaceDbs\Options\PaySystemOption $paySystemOption*/
		foreach ($this->provider->getOptions()->getPaySystemOptions() as $paySystemOption)
		{
			if ($paySystemOption->useMethod())
			{
				$method = $paySystemOption->getMethod();
				$methodMap[$method] = true;
			}
			else
			{
				$type = $paySystemOption->getType();

				if (!isset($usageMap[$type])) { continue; }

				$methodMap += array_flip($usageMap[$type]);
			}
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

	protected function applySelfTest()
	{
		$this->applySelfTestOutOfStock();
	}

	protected function applySelfTestOutOfStock()
	{
		$isKnownRequest = $this->isOutOfStockSelfTest();
		$isOptionOn = $this->provider->getOptions()->getSelfTestOption()->isOutOfStock();

		if (!$isKnownRequest && !$isOptionOn) { return; }

		$this->response->setField('cart.deliveryOptions', []);
		$this->response->setField('cart.items', []);
		$this->response->setField('cart.paymentMethods', []);

		if ($isOptionOn)
		{
			$this->increaseSelfTestOutOfStock();

			$this->provider->getLogger()->warning(static::getLang(
				'TRADING_MARKETPLACE_CART_SELF_TEST_OUT_OF_STOCK_ON'
			));
		}
	}

	protected function isOutOfStockSelfTest()
	{
		/** @var Market\Api\Model\Cart\Item $item */
		$items = $this->request->getCart()->getItems();
		$item = $items->offsetGet(0);

		if ($item === null || count($items) !== 1) { return false; }

		return (
			$item->getCount() === 99999.0
			&& (int)$item->getField('price') >= 10000000000
		);
	}

	protected function increaseSelfTestOutOfStock()
	{
		$setupId = $this->provider->getOptions()->getSetupId();
		$name = 'self_test_out_of_stock_' . $setupId;
		$limit = 5;
		$count = (int)Market\State::get($name);

		if ($count >= $limit)
		{
			Market\Trading\Settings\Table::delete([
				'SETUP_ID' => $setupId,
				'NAME' => 'SELF_TEST',
			]);

			Market\State::remove($name);
		}
		else
		{
			Market\State::set($name, $count + 1);
		}
	}
}
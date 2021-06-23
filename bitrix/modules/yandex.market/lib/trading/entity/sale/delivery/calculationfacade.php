<?php

namespace Yandex\Market\Trading\Entity\Sale\Delivery;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market\Trading\Entity as TradingEntity;

class CalculationFacade
{
	public static function mergeCalculationResult(TradingEntity\Reference\Delivery\CalculationResult $result, Sale\Delivery\CalculationResult $saleResult)
	{
		$result->setDateFrom(static::getDateFrom($saleResult));
		$result->setDateTo(static::getDateTo($saleResult));
		$result->setDateIntervals(static::getDateIntervals($saleResult));
		$result->setData($saleResult->getData());

		$errors = static::convertErrors($saleResult);

		if (!empty($errors))
		{
			$result->addErrors($errors);
		}

		return $result;
	}

	public static function mergeOrderData(TradingEntity\Reference\Delivery\CalculationResult $result, Sale\OrderBase $order)
	{
		$price = $order->getDeliveryPrice();
		$priceRounded = static::roundPrice($price);

		$result->setPrice($priceRounded);
	}

	public static function mergeDeliveryService(TradingEntity\Reference\Delivery\CalculationResult $result, Sale\Delivery\Services\Base $service)
	{
		$stores = Sale\Delivery\ExtraServices\Manager::getStoresList($service->getId());

		$result->setServiceName($service->getNameWithParent());
		$result->setStores($stores);
	}

	protected static function getDateFrom(Sale\Delivery\CalculationResult $saleResult)
	{
		$result = null;

		if (method_exists($saleResult, 'getPeriodFrom'))
		{
			$result = static::makePeriodDate($saleResult, $saleResult->getPeriodFrom());
		}

		if ($result === null)
		{
			$result = static::parsePeriodDescription($saleResult->getPeriodDescription());
		}

		return $result;
	}

	protected static function getDateTo(Sale\Delivery\CalculationResult $saleResult)
	{
		$result = null;

		if (method_exists($saleResult, 'getPeriodTo'))
		{
			$result = static::makePeriodDate($saleResult, $saleResult->getPeriodTo());
		}

		if ($result === null)
		{
			$result = static::parsePeriodDescription($saleResult->getPeriodDescription(), true);
		}

		return $result;
	}

	protected static function makePeriodDate(Sale\Delivery\CalculationResult $saleResult, $period)
	{
		if ((string)$period === '') { return null; }

		$period = (int)$period;

		if ($period < 0) { return null; }

		$type = $saleResult->getPeriodType();
		$interval = static::getPeriodInterval($period, $type);

		$result = new Main\Type\DateTime();
		$result->add($interval);

		return $result;
	}

	protected static function getPeriodInterval($period, $type)
	{
		$interval = 'P';

		if (
			$type === Sale\Delivery\CalculationResult::PERIOD_TYPE_HOUR
			|| $type === Sale\Delivery\CalculationResult::PERIOD_TYPE_MIN
		)
		{
			$interval .= 'T';
		}

		$interval .= (int)$period;

		if ($type === Sale\Delivery\CalculationResult::PERIOD_TYPE_MIN)
		{
			$interval .= 'M';
		}
		else
		{
			$interval .= $type;
		}

		return $interval;
	}

	protected static function parsePeriodDescription($text, $final = false)
	{
		$text = trim($text);

		if ($text === '') { return null; }

		list($from, $to) = Market\Utils\Delivery\PeriodParser::parse($text);
		$target = $final ? $to : $from;
		$result = null;

		if ($target !== null)
		{
			$result = new Main\Type\DateTime();
			$result->add($target);
		}

		return $result;
	}

	protected static function getDateIntervals(Sale\Delivery\CalculationResult $saleResult)
	{
		$saleData = $saleResult->getData();

		if (!isset($saleData['MARKET_INTERVALS']) || !is_array($saleData['MARKET_INTERVALS'])) { return null; }

		$intervals = [];

		foreach ($saleData['MARKET_INTERVALS'] as $saleInterval)
		{
			if (!isset($saleInterval['DATE'])) { continue; }

			$date = Market\Data\Date::sanitize($saleInterval['DATE']);
			$fromTime = isset($saleInterval['FROM_TIME']) ? Market\Data\Time::sanitize($saleInterval['FROM_TIME']) : null;
			$toTime = isset($saleInterval['TO_TIME']) ? Market\Data\Time::sanitize($saleInterval['TO_TIME']) : null;

			if ($date === null) { continue; }

			$interval = [
				'date' => $date,
			];

			if ($fromTime !== null && $toTime !== null)
			{
				$interval['fromTime'] = $fromTime;
				$interval['toTime'] = $toTime;
			}

			$intervals[] = $interval;
		}

		return $intervals;
	}

	protected static function convertErrors(Sale\Delivery\CalculationResult $saleResult)
	{
		$result = [];

		foreach ($saleResult->getErrors() as $error)
		{
			$result[] = new Market\Error\Base($error->getMessage(), $error->getCode());
		}

		return $result;
	}

	protected static function roundPrice($price)
	{
		if (method_exists(Sale\PriceMaths::class, 'roundPrecision'))
		{
			$result = Sale\PriceMaths::roundPrecision($price);
		}
		else
		{
			$result = roundEx($price, 2);
		}

		return $result;
	}
}
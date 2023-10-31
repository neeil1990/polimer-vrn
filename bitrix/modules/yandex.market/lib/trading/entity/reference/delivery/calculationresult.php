<?php

namespace Yandex\Market\Trading\Entity\Reference\Delivery;

use Yandex\Market;
use Bitrix\Main;

class CalculationResult extends Market\Result\Base
{
	protected $deliveryType;
	protected $serviceName;
	protected $price;
	protected $dateFrom;
	protected $dateTo;
	protected $dateIntervals;
	protected $outlets; /** Пункты выдачи сервиса */
	protected $stores; /** Склады Битрикс */

	/** @param string $name */
	public function setServiceName($name)
	{
		$this->serviceName = $name;
	}

	/** @return string|null */
	public function getServiceName()
	{
		return $this->serviceName;
	}

	/** @param float $price */
	public function setPrice($price)
	{
		$this->price = $price;
	}

	/** @return float|null */
	public function getPrice()
	{
		return $this->price;
	}

	public function setDateFrom(Main\Type\Date $date = null)
	{
		$this->dateFrom = $date;
	}

	/** @return Main\Type\Date|null */
	public function getDateFrom()
	{
		return $this->dateFrom;
	}

	public function setDateTo(Main\Type\Date $date = null)
	{
		$this->dateTo = $date;
	}

	/** @return Main\Type\Date|null */
	public function getDateTo()
	{
		return $this->dateTo;
	}

	/** @return array{date: Main\Type\Date, fromTime: string, toTime: string}[]|null */
	public function getDateIntervals()
	{
		return $this->dateIntervals;
	}

	/**  @param array{date: Main\Type\Date, fromTime: string, toTime: string}[]|null $intervals*/
	public function setDateIntervals(array $intervals = null)
	{
		$this->dateIntervals = $intervals;
	}

	/** @return string|null */
	public function getDeliveryType()
	{
		return $this->deliveryType;
	}

	public function setDeliveryType($deliveryType)
	{
		$this->deliveryType = $deliveryType;
	}

	/** @return string[]|null */
	public function getOutlets()
	{
		return $this->outlets;
	}

	/** @param string[] $outlets */
	public function setOutlets($outlets)
	{
		$this->outlets = (array)$outlets;
	}

	/** @return string[]|null */
	public function getStores()
	{
		return $this->stores;
	}

	/** @param string[] $stores */
	public function setStores($stores)
	{
		$this->stores = (array)$stores;
	}
}
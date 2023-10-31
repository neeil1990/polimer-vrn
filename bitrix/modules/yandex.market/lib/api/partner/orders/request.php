<?php

namespace Yandex\Market\Api\Partner\Orders;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $status;
	protected $substatus;
	protected $page;
	protected $pageSize;
	protected $fromDate;
	protected $toDate;
	protected $fromShipmentDate;
	protected $toShipmentDate;
	protected $fake;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/orders.json';
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function getQuery()
	{
		$result = [];
		$status = $this->getStatus();
		$subStatus = $this->getSubStatus();
		$page = $this->getPage();
		$pageSize = $this->getPageSize();
		$fromDate = $this->getFromDate();
		$toDate = $this->getToDate();
		$fromShipmentDate = $this->getFromShipmentDate();
		$toShipmentDate = $this->getToShipmentDate();
		$fake = $this->getFake();

		if ($status !== null)
		{
			$result['status'] = $status;
		}

		if ($subStatus !== null)
		{
			$result['substatus'] = $subStatus;
		}

		if ($page !== null)
		{
			$result['page'] = $page;
		}

		if ($pageSize !== null)
		{
			$result['pageSize'] = $pageSize;
		}

		if ($fromDate !== null)
		{
			$result['fromDate'] = Market\Data\Date::convertForService($fromDate, Market\Data\Date::FORMAT_DEFAULT_SHORT);
		}

		if ($toDate !== null)
		{
			$result['toDate'] = Market\Data\Date::convertForService($toDate, Market\Data\Date::FORMAT_DEFAULT_SHORT);
		}

		if ($fromShipmentDate !== null)
		{
			$result['supplierShipmentDateFrom'] = Market\Data\Date::convertForService($fromShipmentDate, Market\Data\Date::FORMAT_DEFAULT_SHORT);
		}

		if ($toShipmentDate !== null)
		{
			$result['supplierShipmentDateTo'] = Market\Data\Date::convertForService($toShipmentDate, Market\Data\Date::FORMAT_DEFAULT_SHORT);
		}

		if ($fake !== null)
		{
			$result['fake'] = $fake ? 'TRUE' : 'FALSE';
		}

		return $result;
	}

	public function processParameters(array $parameters)
	{
		foreach ($parameters as $name => $value)
		{
			switch ($name)
			{
				case 'status':
					$this->setStatus($value);
				break;

				case 'substatus':
					$this->setSubStatus($value);
				break;

				case 'page':
					$this->setPage($value);
				break;

				case 'pageSize':
					$this->setPageSize($value);
				break;

				case 'fromDate':
					$this->setFromDate($value);
				break;

				case 'toDate':
					$this->setToDate($value);
				break;

				case 'fromShipmentDate':
					$this->setFromShipmentDate($value);
				break;

				case 'toShipmentDate':
					$this->setToShipmentDate($value);
				break;

				case 'fake':
					$this->setFake($value);
				break;

				default:
					throw new Main\ArgumentException('unknown parameter ' . $name);
				break;
			}
		}
	}

	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function setSubStatus($substatus)
	{
		$this->substatus = $substatus;
	}

	public function getSubStatus()
	{
		return $this->substatus;
	}

	public function setPage($page)
	{
		$this->page = $page;
	}

	public function getPage()
	{
		return $this->page;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function setFromDate($date)
	{
		$this->fromDate = $date;
	}

	public function getFromDate()
	{
		return $this->fromDate;
	}

	public function setToDate($date)
	{
		$this->toDate = $date;
	}

	public function getToDate()
	{
		return $this->toDate;
	}

	public function setFromShipmentDate($date)
	{
		$this->fromShipmentDate = $date;
	}

	public function getFromShipmentDate()
	{
		return $this->fromShipmentDate;
	}

	public function setToShipmentDate($date)
	{
		$this->toShipmentDate = $date;
	}

	public function getToShipmentDate()
	{
		return $this->toShipmentDate;
	}

	public function setFake($value)
	{
		$this->fake = $value;
	}

	public function getFake()
	{
		return $this->fake;
	}
}

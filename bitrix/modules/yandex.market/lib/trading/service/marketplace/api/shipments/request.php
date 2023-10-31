<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Shipments;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	/** @var Main\Type\Date */
	protected $dateFrom;
	/** @var Main\Type\Date */
	protected $dateTo;
	/** @var string[] */
	protected $statuses;
	/** @var int[] */
	protected $orderIds;
	/** @var string */
	protected $pageToken;

	public function getUrl()
	{
		return $this->appendUrlQuery(parent::getUrl(), array_filter([
			'page_token' => $this->getPageToken(),
		]));
	}

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/first-mile/shipments.json';
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_PUT;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function getQuery()
	{
		return array_filter([
			'dateFrom' => Market\Data\Date::convertForService($this->getDateFrom(), 'Y-m-d'),
			'dateTo' => Market\Data\Date::convertForService($this->getDateTo(), 'Y-m-d'),
			'statuses' => $this->getStatuses(),
			'orderIds' => $this->getOrderIds(),
		]);
	}

	public function processParameters(array $parameters)
	{
		foreach ($parameters as $name => $value)
		{
			switch ($name)
			{
				case 'dateFrom':
					$this->setDateFrom($value);
				break;

				case 'dateTo':
					$this->setDateTo($value);
				break;

				case 'statuses':
					$this->setStatuses($value);
				break;

				case 'orderIds':
					$this->setOrderIds($value);
				break;

				case 'pageToken':
					$this->setPageToken($value);
				break;
			}
		}
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function getDateFrom()
	{
		Market\Reference\Assert::notNull($this->dateFrom, 'dateFrom');

		return $this->dateFrom;
	}

	public function setDateFrom(Main\Type\Date $dateFrom)
	{
		$this->dateFrom = $dateFrom;
	}

	public function getDateTo()
	{
		Market\Reference\Assert::notNull($this->dateFrom, 'dateTo');

		return $this->dateTo;
	}

	public function setDateTo(Main\Type\Date $dateTo)
	{
		$this->dateTo = $dateTo;
	}

	/** @return string[]|null */
	public function getStatuses()
	{
		return !empty($this->statuses) ? $this->statuses : null;
	}

	/** @param string[]|string $statuses */
	public function setStatuses($statuses)
	{
		$this->statuses = (array)$statuses;
	}

	/** @return int[]|null */
	public function getOrderIds()
	{
		return !empty($this->orderIds) ? $this->orderIds : null;
	}

	/** @param int[]|int $orderIds */
	public function setOrderIds($orderIds)
	{
		$this->orderIds = (array)$orderIds;
	}

	/** @return string|null */
	public function getPageToken()
	{
		return $this->pageToken;
	}

	/** @param string $pageToken */
	public function setPageToken($pageToken)
	{
		$this->pageToken = $pageToken;
	}
}

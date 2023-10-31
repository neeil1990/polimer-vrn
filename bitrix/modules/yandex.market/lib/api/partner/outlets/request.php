<?php

namespace Yandex\Market\Api\Partner\Outlets;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $page;
	protected $pageSize;
	protected $shopOutletCode;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/outlets.json';
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function getQuery()
	{
		$result = [
			'page' => $this->getPage(),
			'pageSize' => $this->getPageSize(),
			'shop_outlet_code' => $this->getShopOutletCode(),
		];

		return array_filter($result, static function($value) { return $value !== null; });
	}

	public function processParameters(array $parameters)
	{
		foreach ($parameters as $name => $value)
		{
			switch ($name)
			{
				case 'page':
					$this->setPage($value);
				break;

				case 'pageSize':
					$this->setPageSize($value);
				break;

				case 'shop_outlet_code':
					$this->setShopOutletCode($value);
				break;

				default:
					throw new Main\ArgumentException('unknown parameter ' . $name);
				break;
			}
		}
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

	public function getShopOutletCode()
	{
		return $this->shopOutletCode;
	}

	public function setShopOutletCode($shopOutletCode)
	{
		$this->shopOutletCode = $shopOutletCode;
	}
}

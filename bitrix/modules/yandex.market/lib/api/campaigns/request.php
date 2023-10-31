<?php
namespace Yandex\Market\Api\Campaigns;

use Bitrix\Main;
use Yandex\Market\Api\Partner;

class Request extends Partner\Reference\Request
{
	protected $page = 1;
	protected $pageSize = 50;

	public function getPath()
	{
		return '/campaigns.json';
	}

	public function getQuery()
	{
		return [
			'page' => $this->page,
			'pageSize' => $this->pageSize,
		];
	}

	public function setPage($page)
	{
		$this->page = (int)$page;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = (int)$pageSize;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function setCampaignId($campaignId)
	{
		throw new Main\NotSupportedException();
	}
}
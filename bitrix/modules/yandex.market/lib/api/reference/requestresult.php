<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

class RequestResult extends Market\Result\Base
{
	protected $response;

	public function setResponse(Market\Api\Reference\Response $response)
	{
		$this->response = $response;
	}

	/**
	 * @return Market\Api\Reference\Response|null
	 */
	public function getResponse()
	{
		return $this->response;
	}
}
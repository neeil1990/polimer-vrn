<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\BoxLabels;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Reference\ResponseWithResult
{
	protected $result;

	public function getResult()
	{
		if ($this->result === null)
		{
			$this->result = $this->loadResult();
		}

		return $this->result;
	}

	protected function loadResult()
	{
		$data = (array)$this->getField('result');

		return new ResponseResult($data);
	}
}
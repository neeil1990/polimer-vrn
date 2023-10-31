<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Orders;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Orders\Request
{
	protected $hasCis;

	public function getQuery()
	{
		$result = parent::getQuery();
		$hasCis = $this->getHasCis();

		if ($hasCis !== null)
		{
			$result['hasCis'] = $hasCis ? 'TRUE' : 'FALSE';
		}

		return $result;
	}

	public function getHasCis()
	{
		return $this->hasCis;
	}

	public function setHasCis($hasCis)
	{
		$this->hasCis = $hasCis;
	}

	public function processParameters(array $parameters)
	{
		parent::processParameters(array_diff_key($parameters, [
			'hasCis' => true,
		]));

		foreach ($parameters as $name => $value)
		{
			if ($name === 'hasCis')
			{
				$this->setHasCis($value);
			}
		}
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}

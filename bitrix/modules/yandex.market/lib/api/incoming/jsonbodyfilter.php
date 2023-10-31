<?php

namespace Yandex\Market\Api\Incoming;

use Bitrix\Main;

class JsonBodyFilter implements Main\Type\IRequestFilter
{
	public function filter(array $values)
	{
		try
		{
			$rawInput = file_get_contents('php://input');
			$postData = Main\Web\Json::decode($rawInput);

			if (!is_array($postData)) { return []; }

			$result = [
				'post' => $postData,
			];
		}
		catch (\Exception $exception)
		{
			$result = [];
		}

		return $result;
	}
}
<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendIdentifiers;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Reference\ResponseWithResult
{
	use Market\Reference\Concerns\HasMessage;

	public function validate()
	{
		$result = parent::validate();

		if (!$result->isSuccess()) { return $result; }

		if ($resultError = $this->validateResult())
		{
			$result->addError($resultError);
		}

		return $result;
	}

	protected function validateResult()
	{
		$responseResult = $this->getField('result');
		$result = null;

		if (!is_array($responseResult))
		{
			$message = self::getMessage('RESULT_NOT_SET');
			$result = new Main\Error($message);
		}
		else if (!isset($responseResult['items']))
		{
			$message = self::getMessage('RESULT_ITEMS_NOT_SET');
			$result = new Main\Error($message);
		}

		return $result;
	}
}
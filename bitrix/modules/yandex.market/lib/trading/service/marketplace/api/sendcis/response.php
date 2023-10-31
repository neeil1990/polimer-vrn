<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendCis;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Reference\ResponseWithResult
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function validate()
	{
		$result = parent::validate();

		if (!$result->isSuccess())
		{
			// nothing
		}
		else if ($resultError = $this->validateResult())
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
			$message = static::getLang('API_ORDER_CIS_RESPONSE_RESULT_NOT_SET');
			$result = new Main\Error($message);
		}
		else if (!isset($responseResult['items']))
		{
			$message = static::getLang('API_ORDER_CIS_RESPONSE_RESULT_ITEMS_NOT_SET');
			$result = new Main\Error($message);
		}

		return $result;
	}
}
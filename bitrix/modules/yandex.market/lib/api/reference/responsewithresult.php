<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

class ResponseWithResult extends Response
{
	const STATUS_OK = 'OK';
	const STATUS_ERROR = 'ERROR';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function validate()
	{
		$result = new Main\Result();

		if ($this->getStatus() !== static::STATUS_OK)
		{
			$responseError = $this->validateErrorResponse() ?: $this->getResponseUnknownError();

			$result->addError($responseError);
		}

		return $result;
	}

	public function getStatus()
	{
		return (string)$this->getField('status');
	}

	protected function getResponseUnknownError()
	{
		$message = static::getLang('API_RESPONSE_RESULT_UNKNOWN_ERROR', [
			'#STATUS#' => $this->getStatus(),
		]);

		return new Main\Error($message);
	}
}
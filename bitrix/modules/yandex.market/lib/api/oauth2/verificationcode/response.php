<?php

namespace Yandex\Market\Api\OAuth2\VerificationCode;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Response extends Market\Api\Reference\Response
{
	protected $state;

	public function getVerificationCode()
	{
		return $this->getField('code');
	}

	public function getState($key)
	{
		$stateList = $this->getStateList();

		return isset($stateList[$key]) ? $stateList[$key] : null;
	}

	public function getStateList()
	{
		if ($this->state === null)
		{
			$this->state = $this->parseStateList();
		}

		return $this->state;
	}

	protected function parseStateList()
	{
		$stateQuery = $this->getField('state');

		parse_str($stateQuery, $state);

		if (!is_array($state))
		{
			$state = (array)$state;
		}

		return $state;
	}

	public function validate()
	{
		$result = new Main\Result();

		if ($responseError = $this->validateErrorResponse())
		{
			$result->addError($responseError);
		}
		else if ($this->getState('sessid') !== bitrix_sessid())
		{
			$message = Market\Config::getLang('API_OAUTH_REQUEST_CODE_RESPONSE_SESSION_EXPIRED');

			$result->addError(new Main\Error($message));
		}
		else if (!$this->hasField('code'))
		{
			$message = Market\Config::getLang('API_OAUTH_REQUEST_CODE_RESPONSE_NOT_SET_CODE');

			$result->addError(new Main\Error($message));
		}

		return $result;
	}
}
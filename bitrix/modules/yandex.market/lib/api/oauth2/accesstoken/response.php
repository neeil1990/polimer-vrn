<?php

namespace Yandex\Market\Api\OAuth2\AccessToken;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Response extends Market\Api\Reference\Response
{
	const TOKEN_TYPE = 'bearer';

	/** @var Main\Type\DateTime */
	protected $initialDate;

	public function __construct($data)
	{
		parent::__construct($data);

		$this->initialDate = new Main\Type\DateTime();
	}

	public function validate()
	{
		$result = new Main\Result();

		if ($responseError = $this->validateErrorResponse())
		{
			$result->addError($responseError);
		}
		else
		{
			$testResults = [
				$this->validateTokenType(),
				$this->validateAccessToken(),
				$this->validateRefreshToken(),
				$this->validateExpiresSeconds()
			];

			foreach ($testResults as $testResult)
			{
				if ($testResult !== null)
				{
					$result->addError($testResult);
				}
			}
		}

		return $result;
	}

	public function getTokenType()
	{
		return (string)$this->getField('token_type');
	}

	protected function validateTokenType()
	{
		$result = null;

		if ($this->getTokenType() !== static::TOKEN_TYPE)
		{
			$message = Market\Config::getLang('API_OAUTH_ACCESS_TOKEN_INVALID_TOKEN_TYPE', [
				'#REQUIRED#' => static::TOKEN_TYPE,
				'#VALUE#' => $this->getTokenType()
			]);

			$result = new Main\Error($message);
		}

		return $result;
	}

	public function getAccessToken()
	{
		return (string)$this->getField('access_token');
	}

	protected function validateAccessToken()
	{
		$result = null;

		if ($this->getAccessToken() === '')
		{
			$message = Market\Config::getLang('API_OAUTH_ACCESS_TOKEN_EMPTY_TOKEN');

			$result = new Main\Error($message);
		}

		return $result;
	}

	public function getRefreshToken()
	{
		return (string)$this->getField('refresh_token');
	}

	protected function validateRefreshToken()
	{
		$result = null;

		if ($this->getRefreshToken() === '')
		{
			$message = Market\Config::getLang('API_OAUTH_ACCESS_TOKEN_EMPTY_REFRESH_TOKEN');

			$result = new Main\Error($message);
		}

		return $result;
	}

	public function getExpiresDate()
	{
		$result = clone $this->initialDate;
		$expireSeconds = $this->getExpiresSeconds();

		$result->add('T' . $expireSeconds . 'S');

		return $result;
	}

	public function getExpiresSeconds()
	{
		return (int)$this->getField('expires_in');
	}

	protected function validateExpiresSeconds()
	{
		$result = null;

		if ($this->getExpiresSeconds() <= 0)
		{
			$message = Market\Config::getLang('API_OAUTH_ACCESS_TOKEN_INVALID_EXPIRES_IN');

			$result = new Main\Error($message);
		}

		return $result;
	}
}
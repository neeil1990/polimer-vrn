<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

abstract class RequestTokenized extends Request
{
	protected $oauthToken;

	public function setOauthToken($oauthToken)
	{
		$this->oauthToken = $oauthToken;
	}

	public function getOauthToken()
	{
		if ($this->oauthToken === null)
		{
			throw new Main\ObjectPropertyException('token not set');
		}

		return $this->oauthToken;
	}

	protected function buildClient()
	{
		$result = parent::buildClient();
		$result->setHeader('Authorization', $this->getAuthorizationHeader());

		return $result;
	}

	public function getAuthorizationHeader()
	{
		return 'OAuth ' . $this->getOauthToken();
	}
}
<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

abstract class RequestClientTokenized extends RequestTokenized
{
	protected $oauthClientId;

	public function setOauthClientId($oauthClientId)
	{
		$this->oauthClientId = $oauthClientId;
	}

	public function getOauthClientId()
	{
		if ($this->oauthClientId === null)
		{
			throw new Main\ObjectPropertyException('clientId not set');
		}

		return $this->oauthClientId;
	}

	protected function buildClient()
	{
		$result = parent::buildClient();
		$result->setHeader('Authorization', $this->getAuthorizationHeader());

		return $result;
	}

	public function getAuthorizationHeader()
	{
		return
			'OAuth'
			. ' oauth_token="' . $this->getOauthToken() . '"'
			. ', oauth_client_id="' . $this->getOauthClientId() . '"';
	}
}
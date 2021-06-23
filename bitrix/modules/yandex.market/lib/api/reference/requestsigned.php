<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

abstract class RequestSigned extends Request
{
	protected $oauthClientId;
	protected $oauthClientPassword;

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

	public function setOauthClientPassword($oauthToken)
	{
		$this->oauthClientPassword = $oauthToken;
	}

	public function getOauthClientPassword()
	{
		if ($this->oauthClientPassword === null)
		{
			throw new Main\ObjectPropertyException('clientPassword not set');
		}

		return $this->oauthClientPassword;
	}

	protected function buildClient()
	{
		$result = parent::buildClient();

		$result->setAuthorization($this->getOauthClientId(), $this->getOauthClientPassword());

		return $result;
	}
}
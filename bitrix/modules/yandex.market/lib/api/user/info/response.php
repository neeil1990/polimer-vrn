<?php

namespace Yandex\Market\Api\User\Info;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Response extends Market\Api\Reference\Response
{
	public function getId()
	{
		return (string)$this->getField('id');
	}

	public function getLogin()
	{
		return (string)$this->getField('login');
	}

	public function validate()
	{
		$result = new Main\Result();

		if ($responseError = $this->validateErrorResponse())
		{
			$result->addError($responseError);
		}
		else if ($idError = $this->validateId())
		{
			$result->addError($idError);
		}

		return $result;
	}

	protected function validateId()
	{
		$result = null;

		if ($this->getId() === '')
		{
			$message = Market\Config::getLang('API_USER_INFO_ID_NOT_SET');
			$result = new Main\Error($message);
		}

		return $result;
	}
}
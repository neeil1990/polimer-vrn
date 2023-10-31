<?php

namespace Yandex\Market\Trading\Service\Common\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;

class Request extends Market\Trading\Service\Common\Action\SendRequest
{
	public function getStatus()
	{
		$status = (string)$this->getRequiredField('status');

		return ($status !== '' ? $status : null);
	}

	public function getExternalStatus()
	{
		$externalStatus = (string)$this->getField('externalStatus');

		return ($externalStatus !== '' ? $externalStatus : null);
	}
}
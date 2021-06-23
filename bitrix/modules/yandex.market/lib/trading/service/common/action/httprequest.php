<?php

namespace Yandex\Market\Trading\Service\Common\Action;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class HttpRequest extends TradingService\Reference\Action\HttpRequest
{
	public function getAuthToken()
	{
		$requestToken = $this->getAuthTokenFromHeader() ?: $this->getAuthTokenFromQuery();

		if ($requestToken === '')
		{
			throw new Market\Exceptions\Trading\InvalidOperation('Request hasn\'t auth token');
		}

		return $requestToken;
	}

	protected function getAuthTokenFromHeader()
	{
		$result = '';
		$serverVariants = [
			'REMOTE_USER',
			'REDIRECT_REMOTE_USER',
			'HTTP_AUTHORIZATION'
		];

		foreach ($serverVariants as $serverVariant)
		{
			$serverValue = (string)$this->server->get($serverVariant);

			if ($serverValue !== '')
			{
				$result = $serverValue;
				break;
			}
		}

		return $result;
	}

	protected function getAuthTokenFromQuery()
	{
		return (string)$this->request->getQuery('auth-token');
	}
}
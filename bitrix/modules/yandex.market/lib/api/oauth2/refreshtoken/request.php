<?php

namespace Yandex\Market\Api\OAuth2\RefreshToken;

use Yandex\Market;
use Bitrix\Main;

class Request extends Market\Api\Reference\RequestSigned
{
	protected $refreshToken;

	public function getHost()
	{
		return 'oauth.yandex.ru';
	}

	public function getPath()
	{
		return '/token';
	}

	public function getQuery()
	{
		return [
			'grant_type' => 'refresh_token',
			'refresh_token' => $this->getRefreshToken()
		];
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function setRefreshToken($token)
	{
		$this->refreshToken = $token;
	}

	public function getRefreshToken()
	{
		if ($this->refreshToken === null)
		{
			throw new Main\ObjectPropertyException('refreshToken not set');
		}

		return $this->refreshToken;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}
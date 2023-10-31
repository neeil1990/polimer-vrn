<?php

namespace Yandex\Market\Api\OAuth2\AccessToken;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Reference\RequestSigned
{
	protected $verificationCode;

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
			'grant_type' => 'authorization_code',
			'code' => $this->verificationCode
		];
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function setVerificationCode($code)
	{
		$this->verificationCode = $code;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}
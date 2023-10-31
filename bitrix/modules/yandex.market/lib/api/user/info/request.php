<?php

namespace Yandex\Market\Api\User\Info;

use Yandex\Market;

class Request extends Market\Api\Reference\RequestTokenized
{
	public function getHost()
	{
		return 'login.yandex.ru';
	}

	public function getPath()
	{
		return '/info';
	}

	public function getQuery()
	{
		return [
			'format' => 'json'
		];
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}
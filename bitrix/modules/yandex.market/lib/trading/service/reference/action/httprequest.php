<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Yandex\Market;
use Bitrix\Main;

class HttpRequest extends AbstractRequest
{
	protected $request;
	protected $server;

	public function __construct(Main\HttpRequest $request, Main\Server $server)
	{
		$fields = $request->getPostList()->toArray();

		$this->request = $request;
		$this->server = $server;

		parent::__construct($fields);
	}
}
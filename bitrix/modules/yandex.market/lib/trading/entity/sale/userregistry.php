<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;

class UserRegistry extends Market\Trading\Entity\Reference\UserRegistry
{
	/** @var Environment */
	protected $environment;

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	protected function createAnonymousUser($serviceCode, $siteId)
	{
		return new AnonymousUser($this->environment, $serviceCode, $siteId);
	}

	protected function createUser(array $data)
	{
		return new User($this->environment, $data);
	}
}
<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;

abstract class Environment extends Market\Trading\Entity\Reference\Environment
{
	public function createSite()
	{
		return new Site($this);
	}

	public function createRoute()
	{
		return new Route($this);
	}

	protected function createProduct()
	{
		return new Product($this);
	}

	protected function createPack()
	{
		return new Pack($this);
	}

	protected function createStore()
	{
		return new Store($this);
	}

	protected function createPrice()
	{
		return new Price($this);
	}

	protected function createUserGroupRegistry()
	{
		return new UserGroupRegistry($this);
	}

	protected function createDigitalRegistry()
	{
		return new DigitalRegistry($this);
	}

	protected function getRequiredModules()
	{
		return [ 'iblock', 'catalog' ];
	}
}
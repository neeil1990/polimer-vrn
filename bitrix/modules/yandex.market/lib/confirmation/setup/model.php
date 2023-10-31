<?php

namespace Yandex\Market\Confirmation\Setup;

use Yandex\Market;
use Bitrix\Main;

class Model extends Market\Reference\Storage\Model
{
	protected $behavior;

	public static function getDataClass()
	{
		return Table::class;
	}

	public function getDomain()
	{
		return (string)$this->getField('DOMAIN');
	}

	public function getContents()
	{
		return (string)$this->getField('CONTENTS');
	}

	public function install()
	{
		$domain = $this->getDomain();
		$contents = $this->getContents();

		$this->getBehavior()->install($domain, $contents);
	}

	public function uninstall()
	{
		$domain = $this->getDomain();
		$contents = $this->getContents();

		$this->getBehavior()->uninstall($domain, $contents);
	}

	/**
	 * @return Market\Confirmation\Behavior\Reference\Behavior
	 */
	public function getBehavior()
	{
		if ($this->behavior === null)
		{
			$this->behavior = $this->createBehavior();
		}

		return $this->behavior;
	}

	protected function createBehavior()
	{
		$code = (string)$this->getField('BEHAVIOR');

		return Market\Confirmation\Behavior\Manager::getBehavior($code);
	}
}
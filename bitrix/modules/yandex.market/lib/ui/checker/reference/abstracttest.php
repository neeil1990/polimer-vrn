<?php

namespace Yandex\Market\Ui\Checker\Reference;

use Yandex\Market;

abstract class AbstractTest
{
	use Market\Reference\Concerns\HasLang;

	/** @return string */
	public function getTitle()
	{
		return $this->getMessage('TITLE');
	}

	/** @return string */
	public function getDescription()
	{
		return $this->getMessage('DESCRIPTION', null, '');
	}

	/** @return Market\Result\Base */
	abstract public function test();

	protected function getMessage($key, $replaces = null, $fallback = null)
	{
		$prefix = $this->getLangPrefix();

		if ($fallback === null)
		{
			$fallback = $key;
		}

		return static::getLang($prefix . '_' . $key, $replaces, $fallback);
	}

	abstract protected function getLangPrefix();
}
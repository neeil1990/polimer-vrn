<?php

namespace Yandex\Market\Ui\Trading\OrderView;

use Yandex\Market\Trading\Setup as TradingSetup;

abstract class AbstractExtension
{
	protected $setup;

	public function __construct(TradingSetup\Model $setup)
	{
		$this->setup = $setup;
	}

	public function isSupported()
	{
		return true;
	}

	abstract public function initialize();
}
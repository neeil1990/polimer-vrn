<?php
namespace Yandex\Market\Ui\UserField;

use Yandex\Market\Trading\Facade;

/** @noinspection PhpUnused */
class TradingBusinessType extends ReferenceType
{
	protected static function fetchList($dataClass)
	{
		$values = parent::fetchList($dataClass);

		if (!empty($values)) { return $values; }

		$changed = Facade\Business::synchronize();

		if (!$changed) { return []; }

		return parent::fetchList($dataClass);
	}

	protected static function fetchFilter()
	{
		return [
			'=ACTIVE' => true,
		];
	}
}
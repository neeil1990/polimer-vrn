<?php

namespace Yandex\Market\Ui\Trading\Internals;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class PackTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_trading_pack';
	}

	public static function getMap()
	{
		$numberStrict = Market\Reference\Storage\Field\NumberStrict::getParameters();

		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('WIDTH', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('HEIGHT', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('DEPTH', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('WEIGHT', $numberStrict),
		];
	}
}
<?php
namespace Yandex\Market\Trading\Business;

use Bitrix\Main;
use Yandex\Market;

class Table extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_trading_business';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
			]),
			new Main\Entity\BooleanField('ACTIVE', [
				'values' => [static::BOOLEAN_N, static::BOOLEAN_Y],
				'default_value' => static::BOOLEAN_Y,
			]),

			new Main\Entity\ReferenceField('TRADING_SETTING', Market\Trading\Settings\Table::class, [
				'=ref.NAME' => [ '?', 'BUSINESS_ID' ],
				'=this.ID' => 'ref.VALUE',
			]),

			new Main\Entity\ReferenceField('TRADING', Market\Trading\Setup\Table::class, [
				'=this.TRADING_SETTING.SETUP_ID' => 'ref.ID',
			]),
		];
	}
}

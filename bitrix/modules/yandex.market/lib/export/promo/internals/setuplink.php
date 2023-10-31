<?php

namespace Yandex\Market\Export\Promo\Internals;

use Bitrix\Main;
use Yandex\Market;

class SetupLinkTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_promo_setup_link';
	}

	public static function getUfId()
	{
		return 'YAMARKET_EXPORT_PROMO_SETUP_LINK';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),

			new Main\Entity\IntegerField('PROMO_ID'),
			new Main\Entity\ReferenceField('PROMO', Market\Export\Promo\Table::getClassName(), [
				'=this.PROMO_ID' => 'ref.ID'
			]),

			new Main\Entity\IntegerField('SETUP_ID'),
			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::getClassName(), [
				'=this.SETUP_ID' => 'ref.ID'
			])
		];
	}
}
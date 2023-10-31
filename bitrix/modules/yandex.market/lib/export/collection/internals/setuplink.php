<?php
namespace Yandex\Market\Export\Collection\Internals;

use Bitrix\Main;
use Yandex\Market;

class SetupLinkTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_collection_setup_link';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),

			new Main\Entity\IntegerField('COLLECTION_ID'),
			new Main\Entity\ReferenceField('COLLECTION', Market\Export\Promo\Table::class, [
				'=this.PROMO_ID' => 'ref.ID'
			]),

			new Main\Entity\IntegerField('SETUP_ID'),
			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::class, [
				'=this.SETUP_ID' => 'ref.ID'
			])
		];
	}
}
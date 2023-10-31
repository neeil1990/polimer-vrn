<?php

namespace Yandex\Market\Export\Setup\Internals;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class GroupLinkTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_setup_group_link';
	}

	public static function getUfId()
	{
		return 'YAMARKET_EXPORT_SETUP_GROUP_LINK';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('GROUP_ID', [
				'primary' => true,
			]),
			new Main\Entity\ReferenceField('GROUP', GroupTable::class, [
				'=this.GROUP_ID' => 'ref.ID'
			]),
			new Main\Entity\StringField('SETUP_ID', [
				'primary' => true,
			]),
			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::class, [
				'=this.SETUP_ID' => 'ref.ID'
			]),
		];
	}
}

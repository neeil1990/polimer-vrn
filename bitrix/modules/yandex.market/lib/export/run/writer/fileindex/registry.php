<?php

namespace Yandex\Market\Export\Run\Writer\FileIndex;

use Bitrix\Main;
use Yandex\Market\Reference\Storage;

class RegistryTable extends Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_file_registry';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('SETUP_ID', [
				'primary' => true,
			]),
			new Main\Entity\StringField('FILE_NAME', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('FILE_SIZE', [
				'required' => true,
				'default_value' => 0,
			]),
		];
	}
}
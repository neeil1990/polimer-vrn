<?php
namespace Yandex\Market\Watcher\Track;

use Bitrix\Main;
use Yandex\Market\Glossary;
use Yandex\Market\Reference\Storage;

class StampTable extends Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_watcher_stamp';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\EnumField('SERVICE', [
				'primary' => true,
				'required' => true,
				'values' => [
					Glossary::SERVICE_EXPORT,
					Glossary::SERVICE_SALES_BOOST,
				],
			]),
			new Main\Entity\IntegerField('SETUP_ID', [
				'primary' => true,
				'required' => true,
			]),
			new Main\Entity\IntegerField('OFFSET', [
				'required' => true,
				'default_value' => 0,
			]),
			new Main\Entity\IntegerField('UNTIL', [
				'required' => true,
				'default_value' => 0,
			]),
			new Storage\Field\CanonicalDateTime('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}
}
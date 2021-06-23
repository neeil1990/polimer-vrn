<?php

namespace Yandex\Market\Trading\Settings;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Table extends Market\Reference\Storage\Table
{
	protected static $serializedOptionPrefix =  '__SERIALIZED__:';

	public static function getTableName()
	{
		return 'yamarket_trading_settings';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('SETUP_ID', [
				'required' => true,
				'primary' => true,
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
				'primary' => true,
			]),
			new Main\Entity\TextField('VALUE', [
				'required' => true,
				'save_data_modification' => [__CLASS__, 'getSaveDataModificationForValue'],
				'fetch_data_modification' => [__CLASS__, 'getFetchDataModificationForValue']
			]),

			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::getClassName(), [
				'=this.SETUP_ID' => 'ref.ID'
			]),
		];
	}

	public static function isValidData($data)
	{
		$result = true;

		if (array_key_exists('VALUE', $data) && Market\Utils\Value::isEmpty($data['VALUE']))
		{
			$result = false;
		}

		return $result;
	}

	public static function getSaveDataModificationForValue()
	{
		return [
			[__CLASS__, 'saveDataModificationForValue']
		];
	}

	public static function saveDataModificationForValue($value)
	{
		return is_scalar($value) ? $value : static::$serializedOptionPrefix . serialize($value);
	}

	public static function getFetchDataModificationForValue()
	{
		return [
			[__CLASS__, 'fetchDataModificationForValue']
		];
	}

	public static function fetchDataModificationForValue($value)
	{
		if (Market\Data\TextString::getPosition($value, static::$serializedOptionPrefix) === 0)
		{
			$serializedValue = Market\Data\TextString::getSubstring(
				$value,
				Market\Data\TextString::getLength(static::$serializedOptionPrefix)
			);
			$unserializedValue = unserialize($serializedValue);

			$result = $unserializedValue !== false ? $unserializedValue : null;
		}
		else
		{
			$result = $value;
		}

		return $result;
	}
}

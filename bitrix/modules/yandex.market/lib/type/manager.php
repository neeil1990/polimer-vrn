<?php

namespace Yandex\Market\Type;

use Bitrix\Main;

class Manager
{
	const TYPE_STRING = 'string';
	const TYPE_NUMBER = 'number';
	const TYPE_HTML = 'html';
	const TYPE_DATE = 'date';
	const TYPE_DATEPERIOD = 'dateperiod';
	const TYPE_CURRENCY = 'currency';
	const TYPE_URL = 'url';
	const TYPE_FILE = 'file';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_CATEGORY = 'category';
	const TYPE_VAT = 'vat';
	const TYPE_BARCODE = 'barcode';
	const TYPE_AGE = 'age';
	const TYPE_DIMENSIONS = 'dimensions';
	const TYPE_RECORDING_LENGTH = 'recordinglength';
	const TYPE_CONDITION = 'condition';
	const TYPE_DELIVERY_OPTIONS = 'deliveryoptions';
	const TYPE_DAYS = 'days';
	const TYPE_COUNT = 'count';
	const TYPE_TN_VED_CODE = 'tnVedCode';
	const TYPE_WEEKDAY = 'weekday';

	protected static $typeCache = [];

	/**
	 * @param $type
	 *
	 * @return AbstractType
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function getType($type)
	{
		$result = null;

		if (isset(static::$typeCache[$type]))
		{
			$result = static::$typeCache[$type];
		}
		else
		{
			$className = __NAMESPACE__ . '\\' . $type . 'Type';

			if (!class_exists($className))
			{
				throw new Main\ObjectNotFoundException('type not found');
			}

			$result = new $className;

			static::$typeCache[$type] = $result;
		}

		return $result;
	}

	public static function release()
	{
		static::$typeCache = [];
	}
}
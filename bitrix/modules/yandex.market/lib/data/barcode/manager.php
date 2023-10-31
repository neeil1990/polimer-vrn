<?php

namespace Yandex\Market\Data\Barcode;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Manager
{
	use Market\Reference\Concerns\HasLang;

	const FORMAT_CODE128 = 'code128';

	/**
	 * @param $template string
	 * @return AbstractFormat
	 * @throws Main\SystemException
	 */
	public static function createFormat($template)
	{
		$className = static::getFormatClassName($template);

		static::validateFormatClassName($className);

		return new $className;
	}

	protected static function getFormatClassName($template)
	{
		return __NAMESPACE__ . '\\' . ucfirst($template) . 'Format';
	}

	protected static function validateFormatClassName($className)
	{
		if (!class_exists($className))
		{
			throw new Main\SystemException(static::getLang('DATA_BARCODE_FORMAT_CLASS_NOT_FOUND'));
		}

		if (!is_subclass_of($className, AbstractFormat::class))
		{
			throw new Main\SystemException(static::getLang('DATA_BARCODE_FORMAT_CLASS_INVALID'));
		}
	}
}
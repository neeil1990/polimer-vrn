<?php

namespace Yandex\Market\Ui\UserField\Concerns;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Ui\UserField\Helper;

trait HasMultipleRow
{
	protected static function makeFieldHtmlId($userField, $block = '')
	{
		$result = ($block !== '' ? $block . '_' : '');
		$result .= Helper\Attributes::convertNameToId($userField['FIELD_NAME']);

		return $result;
	}

	protected static function getMultipleAddButton($userField)
	{
		Market\Ui\Assets::loadPlugin('Ui.StringType');

		return sprintf(
			'<input type="button" value="%s" onClick="ymAddNewRow(\'%s\', \'%s\', this)">',
			Main\Localization\Loc::getMessage('USER_TYPE_PROP_ADD'),
			static::makeFieldHtmlId($userField, 'table'),
			static::makeMultipleHtmlRegexp($userField)
		);
	}

	protected static function getMultipleAutoSaveScript($userField)
	{
		return sprintf('<script type="text/javascript">'
			. 'BX.addCustomEvent("onAutoSaveRestore", function(ob, data) {'
				. 'var name = "%s";'
				. 'for (var i in data){'
					. 'if (i.substring(0, name.length + 1) == name + "[") {'
						. 'addNewRow("%s", "%s")'
				. '}}})'
			. '</script>',
			$userField['FIELD_NAME'],
			static::makeFieldHtmlId($userField, 'table'),
			static::makeMultipleHtmlRegexp($userField)
		);
	}

	protected static function makeMultipleHtmlRegexp($userField)
	{
		$htmlId = static::makeFieldHtmlId($userField);
		$parts = array_unique([
			$htmlId,
			$userField['FIELD_NAME'],
		]);
		$parts = array_map([static::class, 'escapeHtmlRegexp'], $parts);

		return implode('|', $parts);
	}

	protected static function escapeHtmlRegexp($regexp)
	{
		return preg_replace('/([[(])/', '\\\\\\\\$1', $regexp);
	}
}
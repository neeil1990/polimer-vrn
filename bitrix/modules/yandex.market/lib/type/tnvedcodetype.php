<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

class TnVedCodeType extends AbstractType
{
	use Market\Reference\Concerns\HasLang;

	protected $lastSanitizedValue;
	protected $lastSanitizedResult;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$value = $this->sanitizeValue($value);
		$errorCode = null;

		if ($value === '')
		{
			$errorCode = 'NOT_NUMERIC';
		}
		else if (Market\Data\TextString::getLength($value) !== 10)
		{
			$errorCode = 'LENGTH_NOT_MATCH';
		}

		if ($errorCode !== null && $nodeResult)
		{
			$nodeResult->registerError(static::getLang('TYPE_TN_VED_CODE_ERROR_' . $errorCode));
		}

		return ($errorCode === null);
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		return $this->sanitizeValue($value);
	}

	protected function sanitizeValue($value)
	{
		if ($this->lastSanitizedValue === $value)
		{
			$result = $this->lastSanitizedResult;
		}
		else
		{
			$result = preg_replace('/\D/', '', $value);

			$this->lastSanitizedValue = $value;
			$this->lastSanitizedResult = $result;
		}

		return $result;
	}
}
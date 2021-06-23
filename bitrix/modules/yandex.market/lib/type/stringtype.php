<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class StringType extends AbstractType
{
	protected $lastSanitizedValue;
	protected $lastSanitizedResult;

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$result = true;
		$sanitizedValue = $this->getSanitizedValue($value);

		if ($sanitizedValue === '')
		{
			$result = false;

			if ($nodeResult)
			{
				if ($node === null || $node->isRequired())
				{
					$nodeResult->registerError(
						Market\Config::getLang('XML_NODE_VALIDATE_EMPTY'),
						Market\Error\XmlNode::XML_NODE_VALIDATE_EMPTY
					);
				}
				else
				{
					$nodeResult->invalidate();
				}
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$result = $this->getSanitizedValue($value);
		$result = $this->replaceXmlEntity($result);

		$maxLength = $node ? $node->getMaxLength() : null;

		if ($maxLength !== null)
		{
			$result = $this->truncateText($result, $maxLength);
		}

		return $result;
	}

	protected function getSanitizedValue($value)
	{
		if ($value === null)
		{
			$result = '';
		}
		else if ($value === $this->lastSanitizedValue)
		{
			$result = $this->lastSanitizedResult;
		}
		else
		{
			$result = $this->sanitizeValue($value);

			$this->lastSanitizedValue = $value;
			$this->lastSanitizedResult = $result;
		}

		return $result;
	}

	protected function sanitizeValue($value)
	{
		return trim(strip_tags($value));
	}

	protected function replaceXmlEntity($value)
	{
		return Market\Utils\XmlValue::escape($value);
	}

	protected function truncateText($text, $maxLength)
	{
		$result = $text;

		if ($this->getStringLength($result) > $maxLength)
		{
			$suffix = Market\Config::getLang('TYPE_STRING_TRUNCATE_SUFFIX');
			$suffixLength = 1;

			$result = $this->getSubstring($result, 0, $maxLength - $suffixLength);
			$result = rtrim($result, '.') . $suffix;
		}

		return $result;
	}

	protected function getStringLength($text)
	{
		return Market\Data\TextString::getLength($text);
	}

	protected function getSubstring($text, $from, $length = null)
	{
		return Market\Data\TextString::getSubstring($text, $from, $length);
	}
}
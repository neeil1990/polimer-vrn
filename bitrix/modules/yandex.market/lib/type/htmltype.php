<?php

namespace Yandex\Market\Type;

use Yandex\Market;

class HtmlType extends StringType
{
	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$result = $this->getSanitizedValue($value);
		$maxLength = $node ? $node->getMaxLength() : null;

		if (Market\Data\TextString::getPosition($result, '<') !== false) // has tags
		{
			$result = $this->stripTagAttributes($result);

			if ($maxLength !== null)
			{
				$textParser = new \CTextParser();
				$suffixLength = 3;

				$result = $textParser->html_cut($result, $maxLength - $suffixLength);
			}

			if ($nodeResult !== null)
			{
				$cdata = $this->makeCData($result);

				$result = $nodeResult->addReplace($cdata);
			}
			else
			{
				$result = $this->replaceXmlEntity($result);
			}
		}
		else
		{
			$result = $this->replaceXmlEntity($result);

			if ($maxLength !== null)
			{
				$result = $this->truncateText($result, $maxLength);
			}
		}

		return $result;
	}

	protected function sanitizeValue($value)
	{
		return trim(strip_tags($value, '<h3><br><ul><ol><li><p>'));
	}

	protected function makeCData($contents)
	{
		$contents = str_replace(
			['<![CDATA[', ']]>'],
			['&lt;![CDATA[', ']]&gt;'],
			$contents
		);
		$contents = preg_replace("/[\x1-\x8\xB-\xD\xE-\x1F]/", '', $contents); // remove special chars

		return '<![CDATA[' . PHP_EOL .  $contents . PHP_EOL . ']]>';
	}

	protected function stripTagAttributes($contents)
	{
		return preg_replace('/<([a-z][a-z0-9]*) [^>]+?(\/?>)/i', '<$1$2', $contents);
	}
}
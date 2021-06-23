<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class RecordingLengthType extends StringType
{
	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$result = true;
		$errorMessage = null;

		if (preg_match('/(\d+)[.:](\d{2})/', $value, $matches))
		{
			$seconds = (int)$matches[2];

			if ($seconds < 0 || $seconds >= 60)
			{
				$errorMessage = 'SECONDS_OUT_OF_BOUND';
			}
		}
		else
		{
			$errorMessage = 'INVALID';
		}

		if ($errorMessage !== null)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Market\Config::getLang('TYPE_RECORDING_LENGTH_ERROR_' . $errorMessage));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$valueFormatted = str_replace(':', '.', $value);
		$fullLength = 5;

		if ((int)$this->getStringLength($valueFormatted) === $fullLength - 1)
		{
			$valueFormatted = '0' . $valueFormatted;
		}

		return $valueFormatted;
	}
}
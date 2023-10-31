<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Bitrix\Main;

class BarcodeType extends StringType
{
	use Market\Reference\Concerns\HasMessage;
	
    protected static $availableLengthMap = [
        8 => true,
        12 => true,
        13 => true,
	    14 => true,
    ];

    public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
    {
        $valueDigits = $this->sanitizeValue($value);
	    $result = true;

        if ($valueDigits === '')
        {
            $result = false;

            if ($nodeResult)
            {
                $nodeResult->registerError(self::getMessage('ERROR_NOT_NUMERIC'));
            }
        }
        else
        {
            $length = $this->getStringLength($valueDigits);

            if (!isset(static::$availableLengthMap[$length]))
            {
                $result = false;

                if ($nodeResult)
                {
                    $nodeResult->registerError(self::getMessage('ERROR_NOT_FOUND_LENGTH_FORMAT'));
                }
            }
        }

        return $result;
    }

	/** @deprecated */
    protected function validateCheckSum($valueDigits, $length)
    {
        $evenSum = 0;
        $oddSum = 0;
        $isEven = true;

        for ($i = $length - 2; $i >= 0; $i--)
        {
            $digit = Market\Data\TextString::getSubstring($valueDigits, $i, 1);

            if ($isEven)
            {
                $evenSum += $digit;
            }
            else
            {
                $oddSum += $digit;
            }

            $isEven = !$isEven;
        }

        $sumLastDigit = (($evenSum * 3 + $oddSum) % 10);

        $calculatedCheckSum = ($sumLastDigit > 0 ? 10 - $sumLastDigit : 0);
        $valueCheckSum = (int)Market\Data\TextString::getSubstring($valueDigits, $length - 1, 1);

        return ($valueCheckSum === $calculatedCheckSum);
    }

    public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
    {
        return $this->sanitizeValue($value);
    }

    protected function sanitizeValue($value)
    {
        return preg_replace('/\D/', '', $value);
    }
}
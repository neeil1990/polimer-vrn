<?php

namespace Darneo\Ozon\Fields\Value\Type;

use Bitrix\Main\Context\Culture;
use Bitrix\Main\Type\DateTime;
use Darneo\Ozon\Fields\Value\Base;
use Darneo\Ozon\Main\Helper\Encoding as HelpersEncoding;

class Date extends Base
{
    private string $dateType = 'short';
    private string $dateFull = 'DD.MM.YYYY HH:MI:SS';
    private string $dateShort = 'j F Y, HH:MI';

    public function get(): string
    {
        $rawValue = $this->getRaw();
        $value = '';
        if ($rawValue instanceof DateTime) {
            switch ($this->dateType) {
                case 'short':
                    $value = FormatDateFromDB(
                        $rawValue->toString(),
                        $this->dateShort
                    );
                    $value = mb_strtolower($value);
                    break;
                default:
                    $value = $rawValue->toString(new Culture(['FORMAT_DATETIME' => $this->dateFull]));
            }
        }

        $value = HelpersEncoding::toUtf($value);

        return $value;
    }

    public function setDateType($dateType): void
    {
        if (in_array($dateType, ['short', 'full'])) {
            $this->dateType = $dateType;
        }
    }
}

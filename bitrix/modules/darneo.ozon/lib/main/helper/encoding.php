<?php

namespace Darneo\Ozon\Main\Helper;

class Encoding
{
    public static function toUtf($string = ''): string
    {
        $string = trim($string);
        if (ToUpper(SITE_CHARSET) !== 'UTF-8') {
            $string = \Bitrix\Main\Text\Encoding::convertEncoding($string, SITE_CHARSET, 'UTF-8');
        }

        return $string ?: '';
    }

    public static function fromUtf($string)
    {
        if (ToUpper(SITE_CHARSET) !== 'UTF-8') {
            $string = \Bitrix\Main\Text\Encoding::convertEncoding($string, 'UTF-8', SITE_CHARSET);
        }

        return $string;
    }
}

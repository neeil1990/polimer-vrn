<?php

namespace Sotbit\Seometa\Helper;

use Bitrix\Main\Text\Converter;

class SitemapRuntime
{
    const PROGRESS_WIDTH = 500;

    public static function showProgress($text, $title, $v)
    {
        $v = $v >= 0 ? $v : 0;

        if ($v < 100)
        {
            $msg = new \CAdminMessage(array(
                "TYPE" => "PROGRESS",
                "HTML" => true,
                "MESSAGE" => $title,
                "DETAILS" => "#PROGRESS_BAR#<div style=\"width: " . self::PROGRESS_WIDTH . "px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-top: 20px;\">" . Converter::getHtmlConverter()->encode($text) . "</div>",
                "PROGRESS_TOTAL" => 100,
                "PROGRESS_VALUE" => $v,
                "PROGRESS_TEMPLATE" => '#PROGRESS_PERCENT#',
                "PROGRESS_WIDTH" => self::PROGRESS_WIDTH,
            ));
        }
        else
        {
            $msg = new \CAdminMessage(array(
                "TYPE" => "OK",
                "MESSAGE" => $title,
                "DETAILS" => $text,
            ));
        }

        return $msg->show();
    }
}
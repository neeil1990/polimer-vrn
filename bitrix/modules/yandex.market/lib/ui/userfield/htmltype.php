<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class HtmlType extends StringType
{
	public static function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        $html = '';

        if (Main\Loader::includeModule('fileman'))
        {
            $html = '<input type="hidden" name="' . $arHtmlControl['NAME'] . '" value="" />';

            ob_start();

            \CFileMan::AddHTMLEditorFrame($arHtmlControl['NAME'], $arHtmlControl['VALUE'], 'html', 'html', [
                'height' => 100,
                'width' => 400
            ]);

            $html .= ob_get_clean();
        }

        return $html;
    }
}
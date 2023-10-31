<?php
/** @noinspection PhpUnused */
namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;

class HtmlType extends StringType
{
	public static function SanitizeFields($userField, $value)
	{
		if (!isset($userField['FIELD_NAME'])) { return $value; }

		$name = static::rawInputName($userField['FIELD_NAME']);

		if ($name === $userField['FIELD_NAME']) { return $value; }

		return isset($_POST[$name]) ? (string)$_POST[$name] : null;
	}

	public static function GetEditFormHTML($userField, $htmlControl)
    {
        $html = '';

        if (Main\Loader::includeModule('fileman'))
        {
			$name = static::rawInputName($htmlControl['NAME']);
            $html = '<input type="hidden" name="' . $name . '" value="" />';

            ob_start();

            \CFileMan::AddHTMLEditorFrame($name, $htmlControl['VALUE'], 'html', 'html', [
                'height' => 100,
                'width' => 400
            ]);

            $html .= ob_get_clean();
        }

        return $html;
    }

	protected static function rawInputName($name)
	{
		return preg_replace('/[^a-zA-Z0-9_:.]/', '_', $name);
	}
}
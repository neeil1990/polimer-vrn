<?php
namespace Wbs24\Sbermmexport;

class StringTemplate
{
    public function getInputWithTemplate(string $inputName, array $marks, string $defaultValue): string
    {
        $inputId = str_replace(['[', ']'], ['-', ''], $inputName);
        $buttonId = $inputId.'_BUTTON';
        $code =
            '<input class="input_template" name="'.$inputName.'" id="'.$inputId.'" value="'.$defaultValue.'" />'
            .'<button id="'.$buttonId.'">...</button>'
            .'<script>'
                .'if (typeof window.StringTemplate == "undefined") window.StringTemplate = new Wbs24SbermmexportStringTemplate();'
                .'StringTemplate.setInputHandlers("'.$buttonId.'", "'.$inputId.'", '.\CUtil::PhpToJSObject($marks).');'
            .'</script>'
        ;

        return $code;
    }

    public function getStringByTemplate(string $template, array $markValues, string $defaultValue): string
    {
        $result = $template;
        foreach ($markValues as $mark => $value) {
            $result = str_replace('{'.$mark.'}', $value, $result);
        }
        if (!$result) $result = $defaultValue;

        return $result;
    }
}

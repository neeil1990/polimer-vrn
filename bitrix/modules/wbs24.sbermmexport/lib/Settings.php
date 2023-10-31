<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Localization\Loc;

class Settings
{
    public function getUrlSelect($name, $options, $currentValue, $param = [])
    {
        $disabled = $param['disabled'] ?: false;
        $defaultOption = $param['default'] ?: Loc::getMessage("WBS24_SBERMMEXPORT_DEFAULT");

        $code =
            '<select'
                .' class="'.$this->getClassName($name).'"'
                .($disabled ? ' disabled' : '')
                .' style="min-width:370px;"'
                .' name="'.$name.'"'
            .'>'
        ;
        $code .= '<option value="">'.$defaultOption.'</option>';
        foreach ($options as $key => $option) {
            $code .=
                '<option value="'.$option['ID'].'"'
                    .($option['ID'] == $currentValue ? ' selected': '')
                .'>'
                    .'['.htmlspecialcharsbx($key).'] '.htmlspecialcharsbx($option['NAME'])
                .'</option>'
            ;
        }
        $code .= '</select>';

        return $code;
    }

    protected function getClassName($name)
    {
        $class = strtolower($name);
        return 'select_'.strtr($class, [
            '[' => '_',
            ']' => '',
        ]);
    }
}

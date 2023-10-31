<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Localization\Loc;

class DetailSettings
{
    protected $StringTemplate;

    public function __construct($objects = [])
    {
        $this->StringTemplate = $objects['StringTemplate'] ?? new StringTemplate();
    }

    public function getSelect($name, $options, $currentValue, $param = [])
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

    public function getNameInput($inputName, $propertiesGroups, $currentValue)
    {
        $marks = [];
        $value = $currentValue ?? '{NAME}';

        // базовые поля
        $marks[] = [
            'TEXT' => Loc::getMessage("WBS24_SBERMMEXPORT_ELEMENT_NAME"),
            'MARK' => 'NAME',
        ];

        // свойства
        foreach ($propertiesGroups as $group => $properties) {
            $propertyMarks = [];
            foreach ($properties as $key => $property) {
                if (!$this->isValidProperty($property)) continue;
                $propertyMarks[] = [
                    'TEXT' => $property['NAME'],
                    'MARK' => 'PROPERTY_'.$property['ID'],
                ];
            }
            $marks[] = [
                'TEXT' => Loc::getMessage("WBS24_SBERMMEXPORT_ELEMENT_".$group."_PROPERTIES_LABEL"),
                'MENU' => $propertyMarks,
            ];
        }

        return $this->StringTemplate->getInputWithTemplate($inputName, $marks, $value);
    }

    protected function isValidProperty(array $property): bool
    {
        $valid = false;
        if (
            in_array($property['PROPERTY_TYPE'], ['S', 'N', 'L', 'E'])
            && $property['USER_TYPE'] == ''
            && $property['MULTIPLE'] == 'N'
        ) $valid = true;

        return $valid;
    }
}

<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;
use Bitrix\Main;

$arResult['DISPLAY_VALUE'] = [];

$context = (array)$arParams['CONTEXT'];
$fetchFieldValues = [];

foreach ($arParams['VALUE'] as $itemValue)
{
    if (
        !empty($itemValue['VALUE'])
		&& empty($arParams['VALUE_ENUM'][$itemValue['FIELD']]) // hasn't enum
        && !empty($arParams['FIELD_ENUM'][$itemValue['FIELD']]['AUTOCOMPLETE'])
    )
    {
        $valueList = (array)$itemValue['VALUE'];

        if (!isset($fetchFieldValues[$itemValue['FIELD']]))
        {
            $fetchFieldValues[$itemValue['FIELD']] = $valueList;
        }
        else
        {
            foreach ($valueList as $value)
            {
                if (!in_array($value, $fetchFieldValues[$itemValue['FIELD']]))
                {
                    $fetchFieldValues[$itemValue['FIELD']][] = $value;
                }
            }
        }
    }
}

foreach ($fetchFieldValues as $fieldPath => $valueList)
{
    $field = $arParams['FIELD_ENUM'][$fieldPath];
    $field['ID'] = str_replace($field['SOURCE'] . '.', '', $fieldPath);

    try
    {
        $source = Market\Export\Entity\Manager::getSource($field['SOURCE']);

        $displayValueList = $source->getFieldDisplayValue($field, $valueList, $context);

        if ($displayValueList !== null)
        {
            $arResult['DISPLAY_VALUE'][$fieldPath] = $displayValueList;
        }
    }
    catch (Main\SystemException $exception)
    {
        // nothing
    }
}
<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

$summaryTextParts = [];

foreach ($arParams['VALUE'] as $itemValue)
{
	$hasField = isset($itemValue['FIELD']) && (string)$itemValue['FIELD'] !== '';
	$hasCompare = isset($itemValue['COMPARE']) && (string)$itemValue['COMPARE'] !== '';
	$hasValue = isset($itemValue['VALUE']) ? !Market\Utils\Value::isEmpty($itemValue['VALUE']) : false;

	if ($hasField && $hasCompare && $hasValue)
	{
		$fieldText = isset($arParams['FIELD_ENUM'][$itemValue['FIELD']]) ? $arParams['FIELD_ENUM'][$itemValue['FIELD']]['VALUE'] : $itemValue['FIELD'];
		$compareText = $itemValue['COMPARE'];
		$compareEnum = null;
		$isCompareDefined = false;
		$valueText = null;
		$isItemValueMultiple = is_array($itemValue['VALUE']);
		$isFoundItemValue = false;

		if (!empty($arParams['COMPARE_ENUM']))
		{
			foreach ($arParams['COMPARE_ENUM'] as $compareOption)
			{
				if ($itemValue['COMPARE'] === $compareOption['ID'])
				{
					$compareText = $compareOption['VALUE'];
					$compareEnum = isset($compareOption['ENUM']) ? $compareOption['ENUM'] : null;
					$isCompareDefined = isset($compareOption['DEFINED']);
					break;
				}
			}
		}

		if (!$isCompareDefined)
		{
			$valueEnum = null;
			$foundValues = [];
			$valueList = (array)$itemValue['VALUE'];

			if ($compareEnum !== null)
			{
				$valueEnum = $compareEnum;
			}
			else if (!empty($arResult['DISPLAY_VALUE'][$itemValue['FIELD']]))
            {
                $valueEnum = $arResult['DISPLAY_VALUE'][$itemValue['FIELD']];
            }
			else if (isset($arParams['VALUE_ENUM'][$itemValue['FIELD']]))
			{
				$valueEnum = $arParams['VALUE_ENUM'][$itemValue['FIELD']];
			}

			if ($valueEnum !== null)
			{
				foreach ($valueEnum as $valueOption)
				{
					$isSelected = $isItemValueMultiple
						? in_array($valueOption['ID'], $itemValue['VALUE'])
						: $valueOption['ID'] == $itemValue['VALUE']; // maybe int conversion

					if ($isSelected)
					{
                        $foundValues[$valueOption['ID']] = true;
						$valueText = ($valueText ? $valueText . ', ' : '') . $valueOption['VALUE'];
					}
				}
			}

            foreach ($valueList as $value)
            {
                $value = trim($value);

                if ($value !== '' && !isset($foundValues[$value]))
                {
                    $valueText = ($valueText ? $valueText . ', ' : '') . $value;
                }
            }
		}

		$summaryTextParts[] = trim($fieldText . ' ' . $compareText . ' ' . $valueText);
	}
}

?>
<a class="b-link action--heading target--inside js-condition-summary__text" href="#"><?
	echo count($summaryTextParts) > 0
		? Market\Utils::htmlEscape(implode($lang['JUNCTION'], $summaryTextParts))
		: $lang['PLACEHOLDER'];
?></a>
<div class="b-grid spacing--1x1">
	<div class="b-grid__item vertical--middle">
		<button class="adm-btn js-condition-summary__edit-button" type="button"><?= $langStatic['EDIT_BUTTON']; ?></button>
	</div>
	<?
	$countParentClassName = 'js-condition-summary';

	include __DIR__ . '/count.php';
	?>
</div>

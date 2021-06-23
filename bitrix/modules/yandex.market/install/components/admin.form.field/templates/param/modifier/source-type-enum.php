<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

$context = $arParams['CONTEXT'];
$sourceTypeList = Market\Export\Entity\Manager::getSourceTypeList();
$arResult['SOURCE_TYPE_ENUM'] = [];

// recommendation

$arResult['RECOMMENDATION_TYPE'] = Market\Export\ParamValue\Table::SOURCE_TYPE_RECOMMENDATION;

$arResult['SOURCE_TYPE_ENUM'][$arResult['RECOMMENDATION_TYPE']] = [
	'ID' => $arResult['RECOMMENDATION_TYPE'],
	'VALUE' => Market\Export\ParamValue\Table::getFieldEnumTitle('SOURCE_TYPE', $arResult['RECOMMENDATION_TYPE']),
	'CONTROL' => Market\Export\Entity\Manager::CONTROL_SELECT,
	'VARIABLE' => false,
	'TEMPLATE' => false,
];

// sources

foreach ($sourceTypeList as $sourceType)
{
	$source = Market\Export\Entity\Manager::getSource($sourceType);

	if ($source->isSelectable() && !$source->isInternal())
	{
		$sourceOption = [
			'ID' => $sourceType,
			'VALUE' => $source->getTitle(),
			'CONTROL' => $source->getControl(),
			'VARIABLE' => $source->isVariable(),
			'TEMPLATE' => $source->isTemplate(),
		];

		if ($source instanceof Market\Export\Entity\Reference\HasFunctions)
		{
			$sourceOption['FUNCTIONS'] = $source->getFunctions($context);
		}

		$arResult['SOURCE_TYPE_ENUM'][$sourceType] = $sourceOption;
	}
}
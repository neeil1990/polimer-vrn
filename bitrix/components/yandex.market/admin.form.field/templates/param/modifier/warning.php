<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

$arResult['SUPPORT_WARNINGS'] = [
	'param' => true,
];

/** @var \Yandex\Market\Export\Xml\Tag\Base $tag */
foreach ($arResult['TAGS'] as $tagId => $tag)
{
	if (!isset($arResult['SUPPORT_WARNINGS'][$tagId])) { continue; }

	foreach ($arParams['VALUE'] as &$rowValue)
	{
		if ($tagId !== $rowValue['XML_TAG']) { continue; }
		if (empty($rowValue['PARAM_VALUE']) || !is_array($rowValue['PARAM_VALUE'])) { continue; }

		// collect values

		$attributes = [];
		$value = null;

		foreach ($rowValue['PARAM_VALUE'] as $paramValue)
		{
			$paramValueSource = [
				'TYPE' => $paramValue['SOURCE_TYPE'],
				'FIELD' => $paramValue['SOURCE_FIELD'],
			];

			if ($paramValue['XML_TYPE'] === Market\Export\ParamValue\Table::XML_TYPE_ATTRIBUTE)
			{
				$attributes[$paramValue['XML_ATTRIBUTE_NAME']] = $paramValueSource;
			}
			else if ($paramValue['XML_TYPE'] === Market\Export\ParamValue\Table::XML_TYPE_VALUE)
			{
				$value = $paramValueSource;
			}
		}

		// test values

		switch ($tagId)
		{
			case 'param':
				$sizeName = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_PARAM_SIZE_NAME');
				$isSizeTag = (
					isset($attributes['name'])
					&& $attributes['name']['TYPE'] === Market\Export\Entity\Manager::TYPE_TEXT
					&& Market\Data\TextString::getPositionCaseInsensitive($attributes['name']['FIELD'], $sizeName) !== false
				);
				$hasUnit = (isset($attributes['unit']) && (string)$attributes['unit']['FIELD'] !== '');

				if ($isSizeTag && !$hasUnit)
				{
					$rowValue['WARNING'] = Loc::getMessage('YANDEX_MARKET_T_ADMIN_FIELD_PARAM_PARAM_SIZE_WARNINIG_REQUIRE_UNIT');
				}
			break;
		}
	}
	unset($rowValue);
}
<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main;

$arResult['RECOMMENDATION'] = [];
$context = $arParams['CONTEXT'];
$tagRecommendationList = [];

/** @var Market\Export\Xml\Tag\Base $tag */
foreach ($arResult['TAGS'] as $tagId => $tag)
{
	$nodeList = [ $tag ];

	if ($tag->hasAttributes())
	{
		array_splice($nodeList, 1, 0, $tag->getAttributes());
	}

	foreach ($nodeList as $node)
	{
		$nodeFullType = ($node === $tag ? $tagId : $tagId . '.' . $node->getId());
		$nodeRecommendation = $node->getSourceRecommendation($context);

		if (!empty($nodeRecommendation))
		{
			$tagRecommendationList[$nodeFullType] = $nodeRecommendation;

			$arResult['NODE_AVAILABLE_SOURCES'][$nodeFullType][$arResult['RECOMMENDATION_TYPE']] = true;
		}
	}
}

foreach ($tagRecommendationList as $nodeName => $recommendationList)
{
	$newRecommendationList = [];

	foreach ($recommendationList as $recommendation)
	{
		if (isset($arResult['SOURCE_TYPE_ENUM'][$recommendation['TYPE']]))
		{
			$typeEnum = $arResult['SOURCE_TYPE_ENUM'][$recommendation['TYPE']];

			if ($typeEnum['CONTROL'] === Market\Export\Entity\Manager::CONTROL_TEXT)
			{
				$newRecommendationList[] = [
					'ID' => htmlspecialcharsbx($recommendation['TYPE'] . '|' . $recommendation['VALUE']),
					'VALUE' => $recommendation['VALUE']
				];
			}
			else if ($typeEnum['CONTROL'] === Market\Export\Entity\Manager::CONTROL_FORMULA)
			{
				$formulaSource = Market\Export\Entity\Manager::getSource($recommendation['TYPE']);
				$formulaTitleValues = [];

				if (!($formulaSource instanceof Market\Export\Entity\Reference\HasFieldCompilation)) { continue; }
				if (!isset($recommendation['FIELD']['FUNCTION'], $recommendation['FIELD']['PARTS'])) { continue; }
				if (empty($typeEnum['FUNCTIONS'])) { continue; }

				$isFormulaFunctionFound = false;

				foreach ($typeEnum['FUNCTIONS'] as $functionOption)
				{
					if ($functionOption['ID'] === $recommendation['FIELD']['FUNCTION'])
					{
						$formulaTitleValues[] = $functionOption['VALUE'];
						$isFormulaFunctionFound = true;
						break;
					}
				}

				if (!$isFormulaFunctionFound) { continue; }

				$isFormulaPartsFound = true;

				foreach ((array)$recommendation['FIELD']['PARTS'] as $part)
				{
					$partType = strtok($part, '.');

					if (!isset($arResult['SOURCE_TYPE_ENUM'][$partType], $arResult['SOURCE_FIELD_ENUM'][$part]))
					{
						$isFormulaPartsFound = false;
						break;
					}

					$formulaTitleValues[] = sprintf(
						'%s: %s',
						$arResult['SOURCE_TYPE_ENUM'][$partType]['VALUE'],
						$arResult['SOURCE_FIELD_ENUM'][$part]['VALUE']
					);
				}

				if (!$isFormulaPartsFound) { continue; }

				$newRecommendationList[] = [
					'ID' => $typeEnum['ID'] . '|' . $formulaSource->compileField($recommendation['FIELD']),
					'VALUE' => implode(' ', $formulaTitleValues),
				];
			}
			else
			{
				$fieldKey = $recommendation['TYPE'] . '.' . $recommendation['FIELD'];

				if (isset($arResult['SOURCE_FIELD_ENUM'][$fieldKey]))
				{
					$fieldEnum = $arResult['SOURCE_FIELD_ENUM'][$fieldKey];

					$newRecommendationList[] = [
						'ID' => $typeEnum['ID'] . '|' . $fieldEnum['ID'],
						'VALUE' => $typeEnum['VALUE'] . ': '. $fieldEnum['VALUE']
					];
				}
			}
		}
	}

	if (!empty($newRecommendationList))
	{
		$arResult['RECOMMENDATION'][$nodeName] = $newRecommendationList;
	}
}
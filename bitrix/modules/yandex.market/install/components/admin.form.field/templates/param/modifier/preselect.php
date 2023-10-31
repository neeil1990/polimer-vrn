<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market\Export;

$tagValues = is_array($arParams['VALUE']) ? $arParams['VALUE'] : [];
$tagValues = array_filter($tagValues, static function($tagValue) { return !empty($tagValue) && empty($tagValue['PLACEHOLDER']); });

if (!empty($tagValues)) { return; }

$context = is_array($arParams['CONTEXT']) ? $arParams['CONTEXT'] : [];

/** @var Export\Xml\Tag\Base $tag */
foreach ($arResult['TAGS'] as $tagId => $tag)
{
	$nodes = array_merge([ $tag ], $tag->getAttributes());
	$valueGroups = [];

	foreach ($nodes as $node)
	{
		$needPreselect = (
			$node->getParameter('preselect') === true
			|| ($tag->isRequired() && $node->isRequired())
		);
		
		if (!$needPreselect) { continue; }

		$values = $node->preselect($context);

		if ($values === null) { continue; }

		if (isset($values['TYPE'])) { $values = [ $values ]; } // convert single format to multiple

		foreach ($values as $index => $value)
		{
			if (!isset($value['TYPE'], $arResult['SOURCE_TYPE_ENUM'][$value['TYPE']])) { continue; }

			$type = $arResult['SOURCE_TYPE_ENUM'][$value['TYPE']];
			$recommendationId = $tagId;
			$fields = [
				'XML_TYPE' => Export\ParamValue\Table::XML_TYPE_VALUE,
			];

			if ($node !== $tag) // is attribute
			{
				$fields = [
					'XML_TYPE' => Export\ParamValue\Table::XML_TYPE_ATTRIBUTE,
					'XML_ATTRIBUTE_NAME' => $node->getId(),
				];

				$recommendationId .= '.' . $node->getId();
			}

			if ($type['CONTROL'] === Export\Entity\Manager::CONTROL_TEXT)
			{
				$fields += [
					'SOURCE_TYPE' => $value['TYPE'],
					'SOURCE_FIELD' => $value['VALUE'],
				];

				$recommendationValue = $value['TYPE'] . '|' . $value['VALUE'];
			}
			else if ($type['CONTROL'] === Export\Entity\Manager::CONTROL_FORMULA)
			{
				$formulaSource = Export\Entity\Manager::getSource($value['TYPE']);

				if (!($formulaSource instanceof Export\Entity\Reference\HasFieldCompilation)) { continue; }

				$fields += [
					'SOURCE_TYPE' => $value['TYPE'],
					'SOURCE_FIELD' => $value['FIELD'],
				];

				$recommendationValue = $value['TYPE'] . '|' . $formulaSource->compileField($value['FIELD']);
			}
			else
			{
				$fields += [
					'SOURCE_TYPE' => $value['TYPE'],
					'SOURCE_FIELD' => $value['FIELD'],
				];

				$recommendationValue = $value['TYPE'] . '|' . $value['FIELD'];
			}

			if (isset($arResult['RECOMMENDATION'][$recommendationId]))
			{
				foreach ($arResult['RECOMMENDATION'][$recommendationId] as $recommendation)
				{
					if ($recommendation['ID'] === $recommendationValue)
					{
						$fields['SOURCE_TYPE'] = Export\ParamValue\Table::SOURCE_TYPE_RECOMMENDATION;
						$fields['SOURCE_FIELD'] = $recommendationValue;
						break;
					}
				}
			}

			if (!isset($valueGroups[$index]))
			{
				$valueGroups[$index] = [];
			}

			$valueGroups[$index][] = $fields;
		}
	}

	foreach ($valueGroups as $valueGroup)
	{
		$tagValues[] = [
			'XML_TAG' => $tagId,
			'PARAM_VALUE' => $valueGroup,
		];
	}
}

$arParams['VALUE'] = $tagValues;

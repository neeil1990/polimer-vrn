<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

Main\Localization\Loc::loadMessages(__FILE__);

class Condition extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'condition',
			'reason_name' => 'reason'
		];
	}

	protected function exportTagValue($tagValue, $tagValuesList, $context, \SimpleXMLElement $parent)
	{
		$result = parent::exportTagValue($tagValue, $tagValuesList, $context, $parent);

		if (!empty($tagValue['ATTRIBUTES']['type']) && !$result->isSuccess())
		{
			$typeAttribute = $this->getAttribute('type');
			$typeValue = $tagValue['ATTRIBUTES']['type'];

			if ($typeAttribute !== null && $typeAttribute->validate($typeValue, $context))
			{
				$errorMessage = Market\Config::getLang('TAG_CONDITION_ISSET_TYPE_WITHOUT_REASON', [ '#TYPE#' => $typeValue ]);

				$error = new Market\Error\XmlNode($errorMessage);
				$error->markCritical();
				$error->setTagName($this->name);

				$result->addError($error);
			}
		}

		return $result;
	}

	public function exportNode($value, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$valueExport = $this->formatValue($value, $context, $nodeResult, $settings);

		$result = $parent->addChild($this->name);
		$result->addChild($this->getParameter('reason_name'), $valueExport);

		return $result;
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];
		$iblockList = [];

		if (!empty($context['IBLOCK_ID']))
		{
			$iblockList[Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY] = $context['IBLOCK_ID'];
		}

		if (!empty($context['OFFER_IBLOCK_ID']))
		{
			$iblockList[Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY] = $context['OFFER_IBLOCK_ID'];
		}

		if (!empty($iblockList) && Main\Loader::includeModule('iblock'))
		{
			foreach ($iblockList as $sourceType => $iblockId)
			{
				$query = Iblock\PropertyTable::getList([
					'filter' => [
						'=IBLOCK_ID' => (int)$iblockId,
						'=USER_TYPE' => Market\Ui\UserField\ConditionType\Property::USER_TYPE,
						'=WITH_DESCRIPTION' => 'Y'
					],
					'select' => [
						'ID'
					]
				]);

				while ($property = $query->fetch())
				{
					$result[] = [
						'TYPE' => $sourceType,
						'FIELD' => $property['ID'] . '.DESCRIPTION'
					];
				}
			}
		}

		return $result;
	}
}
<?php

namespace Yandex\Market\Export\Xml\Tag;

use Bitrix\Main;
use Yandex\Market;
use Bitrix\Catalog;

class CargoTypes extends Base
{
	use Market\Reference\Concerns\HasMessage;

	public function getDefaultParameters()
	{
		return [
			'name' => 'cargo-types',
			'value_type' => Market\Type\Manager::TYPE_BOOLEAN,
			'overrides' => [
				'true' => 'CIS_REQUIRED',
			],
		];
	}

	public function validate($value, array $context, $siblingsValues = null, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$result = parent::validate($value, $context, $siblingsValues, $nodeResult, $settings);

		if ($result && $this->formatValue($value, $context) === 'false')
		{
			$result = false;

			if ($nodeResult)
			{
				if ($this->isRequired)
				{
					$nodeResult->registerError(
						self::getMessage('VALIDATE_FALSE'),
						Market\Error\XmlNode::XML_NODE_VALIDATE_EMPTY
					);
				}
				else
				{
					$nodeResult->invalidate();
				}
			}
		}

		return $result;
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if (
			$context['HAS_CATALOG']
			&& Main\Loader::includeModule('catalog')
			&& class_exists(Catalog\Product\SystemField::class)
		)
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => Catalog\Product\SystemField::CODE_MARKING_CODE_GROUP,
			];
		}

		return $result;
	}
}
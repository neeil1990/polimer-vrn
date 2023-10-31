<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;
use Bitrix\Main;

class Offer extends Base
{
	use Market\Reference\Concerns\HasLang;

	const ADV_PREFIX = 'adv';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function extendTagDescription($tagDescription, array $context)
	{
		$tagDescription = parent::extendTagDescription($tagDescription, $context);

		if (
			isset($tagDescription['SETTINGS']['ADV_PREFIX'])
			&& (string)$tagDescription['SETTINGS']['ADV_PREFIX'] === Market\Ui\UserField\BooleanType::VALUE_Y
		)
		{
			$tagDescription = $this->applyTagDescriptionAdvPrefix($tagDescription);
		}

		return $tagDescription;
	}

	protected function applyTagDescriptionAdvPrefix($tagDescription)
	{
		foreach ($this->getAttributes() as $attribute)
		{
			$attributeId = $attribute->getId();

			if (!$attribute->isPrimary()) { continue; }
			if (!isset($tagDescription['ATTRIBUTES'][$attributeId])) { continue; }

			$attributeDescription = $tagDescription['ATTRIBUTES'][$attributeId];

			if (!isset($attributeDescription['TYPE'], $attributeDescription['FIELD'])) { continue; }

			$tagDescription['ATTRIBUTES'][$attributeId] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_TEMPLATE,
				'FIELD' => sprintf(
					'%s{=%s.%s}',
					static::ADV_PREFIX,
					$attributeDescription['TYPE'],
					$attributeDescription['FIELD']
				)
			];
		}

		return $tagDescription;
	}

	public function getSettingsDescription(array $context = [])
	{
		$langKey = $this->getLangKey();

		$result = [
			'ADV_PREFIX' => [
				'TITLE' => static::getLang($langKey . '_SETTINGS_ADV_PREFIX_TITLE'),
				'DESCRIPTION' => static::getLang($langKey . '_SETTINGS_ADV_PREFIX_DESCRIPTION', [
					'#PREFIX#' => static::ADV_PREFIX,
				]),
				'TYPE' => 'boolean',
				'DEPRECATED' => 'Y',
			],
		];

		return array_diff_key($result, $this->getDisabledSettings($context));
	}

	protected function getDisabledSettings(array $context)
	{
		$result = [];

		if ($context['EXPORT_SERVICE'] !== Market\Export\Xml\Format\Manager::EXPORT_SERVICE_YANDEX_MARKET)
		{
			$result['ADV_PREFIX'] = true;
		}

		return $result;
	}
}
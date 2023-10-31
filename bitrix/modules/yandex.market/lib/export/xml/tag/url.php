<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Url extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'url',
			'value_type' => Market\Type\Manager::TYPE_URL
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'DETAIL_PAGE_URL',
			],
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'CANONICAL_PAGE_URL',
			]
		];

		if (isset($context['OFFER_IBLOCK_ID']))
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'DETAIL_PAGE_URL',
			];

			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'CANONICAL_PAGE_URL',
			];
		}

		return $result;
	}

	protected function formatValue($value, array $context = [], Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$hasValue = ($value !== null && $value !== '');

		if ($hasValue && $settings !== null)
		{
			$queryParams = [];
			$hasQueryParams = false;
			$utmFields = [
				'utm_source' => 'UTM_SOURCE',
				'utm_medium' => 'UTM_MEDIUM',
				'utm_campaign' => 'UTM_CAMPAIGN',
				'utm_content' => 'UTM_CONTENT',
				'utm_term' => 'UTM_TERM',
			];

			foreach ($utmFields as $utmRequest => $utmField)
			{
				if (isset($settings[$utmField]) && is_string($settings[$utmField]))
				{
					$utmValue = trim($settings[$utmField]);

					if ($utmValue !== '')
					{
						$hasQueryParams = true;
						$queryParams[$utmRequest] = $utmValue;
					}
				}
			}

			if ($hasQueryParams)
			{
				$anchorPosition = Market\Data\TextString::getPosition($value, '#');
				$slicedPart = '';

				if ($anchorPosition !== false)
				{
					$slicedPart = Market\Data\TextString::getSubstring($value, $anchorPosition);
					$value = Market\Data\TextString::getSubstring($value, 0, $anchorPosition);
				}

				$value .=
					(Market\Data\TextString::getPosition($value, '?') === false ? '?' : '&')
					. $this->buildQueryParams($queryParams);

				if ($slicedPart !== '')
				{
					$value .= $slicedPart;
				}
			}
		}

		return parent::formatValue($value, $context, $nodeResult, $settings);
	}

	public function getSettingsDescription(array $context = [])
	{
		$langKey = $this->getLangKey();

		$result = [
			'UTM_SOURCE' => [
				'TITLE' => Market\Config::getLang($langKey . '_SETTINGS_UTM_SOURCE'),
				'TYPE' => 'param'
			],
			'UTM_MEDIUM' => [
				'TITLE' => Market\Config::getLang($langKey . '_SETTINGS_UTM_MEDIUM'),
				'TYPE' => 'param'
			],
			'UTM_CAMPAIGN' => [
				'TITLE' => Market\Config::getLang($langKey . '_SETTINGS_UTM_CAMPAIGN'),
				'TYPE' => 'param'
			],
			'UTM_CONTENT' => [
				'TITLE' => Market\Config::getLang($langKey . '_SETTINGS_UTM_CONTENT'),
				'TYPE' => 'param'
			],
			'UTM_TERM' => [
				'TITLE' => Market\Config::getLang($langKey . '_SETTINGS_UTM_TERM'),
				'TYPE' => 'param'
			]
		];

		return $result;
	}

	protected function buildQueryParams($queryParams)
	{
		if (!Main\Application::isUtfMode())
		{
			$queryParams = Main\Text\Encoding::convertEncodingArray($queryParams, LANG_CHARSET, 'UTF-8');
		}

		return http_build_query($queryParams, null, '&', PHP_QUERY_RFC3986);
	}
}

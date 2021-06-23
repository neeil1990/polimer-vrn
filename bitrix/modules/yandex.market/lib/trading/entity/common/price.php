<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;

class Price extends Market\Trading\Entity\Reference\Price
{
	use Market\Reference\Concerns\HasLang;

	const SOURCE_CATALOG = 'catalog';
	const SOURCE_OPTIMAL = 'optimal';

	/** @var Environment */
	protected $environment;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function getSourceEnum()
	{
		$result = [];

		foreach ($this->getSources() as $source)
		{
			$result[] = [
				'ID' => $source,
				'VALUE' => $this->getSourceTitle($source),
			];
		}

		return $result;
	}

	protected function getSources()
	{
		return [
			static::SOURCE_CATALOG,
			static::SOURCE_OPTIMAL,
		];
	}

	protected function getSourceTitle($source)
	{
		$sourceUpper = Market\Data\TextString::toUpper($source);

		return static::getLang('TRADING_ENTITY_COMMON_PRICE_SOURCE_' . $sourceUpper, null, $source);
	}

	public function getTypeEnum()
	{
		$result = [];
		$priceTypes = \CCatalogGroup::GetListArray();

		foreach ($priceTypes as $priceType)
		{
			$result[] = [
				'ID' => $priceType['ID'],
				'VALUE' => '[' . $priceType['ID'] . '] ' . $this->extractPriceTypeName($priceType),
			];
		}

		return $result;
	}

	public function getTypeDefaults(array $userGroups = null)
	{
		$anonymousGroups = $this->getAnonymousGroups();
		$userGroupsWithAnonymous = array_unique(array_merge((array)$userGroups, $anonymousGroups));
		$priceTypeRules = \CCatalogGroup::GetGroupsPerms($userGroupsWithAnonymous);

		if (!empty($priceTypeRules['buy']))
		{
			$result = $priceTypeRules['buy'];
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function extractPriceTypeName($priceType)
	{
		$result = '';

		if (isset($priceType['NAME_LANG']) && (string)$priceType['NAME_LANG'] !== '')
		{
			$result = $priceType['NAME_LANG'];
		}
		else if (isset($priceType['NAME']))
		{
			$result = (string)$priceType['NAME'];
		}

		return $result;
	}

	protected function getAnonymousGroups()
	{
		if (method_exists(Main\UserTable::class, 'getUserGroupIds'))
		{
			$result = Main\UserTable::getUserGroupIds(0);
		}
		else
		{
			$result = [2];
		}

		return $result;
	}

	public function getBasketData($productIds, $quantities = null, array $context = [])
	{
		if (!empty($context['SOURCE']))
		{
			$selectMap = $this->buildSelectMap($context);
			$exportContext = $this->buildExportContext($context);
			$exportContext += $this->appendExportContextQuantities($quantities);
			$sourceSelect = $this->getExportSelect($selectMap);

			$exportValues = Market\Export\Entity\Facade::loadValues($productIds, $sourceSelect, $exportContext);

			$result = $this->fillExportValues($productIds, $selectMap, $exportValues);
			$result = $this->extendExportValues($result);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function fillExportValues($elementIds, $selectMap, $values)
	{
		$result = [];

		foreach ($elementIds as $elementId)
		{
			foreach ($selectMap as $to => list($source, $field))
			{
				if (!isset($values[$elementId][$source][$field])) { continue; }

				$result[$elementId][$to] = $values[$elementId][$source][$field];
			}
		}

		return $result;
	}

	protected function extendExportValues($allBasketFields)
	{
		foreach ($allBasketFields as $productId => &$basketFields)
		{
			$basketFields += [
				'DISCOUNT_NAME' => null,
				'DISCOUNT_COUPON' => null,
				'DISCOUNT_VALUE' => null,
			];

			if (isset($basketFields['PRICE']))
			{
				$basketFields['CUSTOM_PRICE'] = 'Y';

				if (
					isset($basketFields['BASE_PRICE'])
					&& $basketFields['BASE_PRICE'] > $basketFields['PRICE']
					&& $basketFields['BASE_PRICE'] > 0
				)
				{
					$priceDiff = $basketFields['BASE_PRICE'] - $basketFields['PRICE'];
					$percent = round($priceDiff / $basketFields['BASE_PRICE'] * 100, 0);

					if ($percent > 0)
					{
						$basketFields['DISCOUNT_VALUE'] = $percent . '%';
					}
				}
			}

			if (!empty($basketFields['DISCOUNT_LIST']) && is_array($basketFields['DISCOUNT_LIST']))
			{
				$firstDiscount = reset($basketFields['DISCOUNT_LIST']);
				$firstDiscountPercent = isset($firstDiscount['PERCENT']) ? (float)$firstDiscount['PERCENT'] : 0;

				$basketFields['DISCOUNT_NAME'] = '[' . $firstDiscount['ID'] . '] ' . $firstDiscount['NAME'];

				if (isset($firstDiscount['COUPON']))
				{
					$basketFields['DISCOUNT_COUPON'] = $firstDiscount['COUPON'];
				}

				if ($firstDiscountPercent > 0)
				{
					$basketFields['DISCOUNT_VALUE'] = $firstDiscountPercent . '%';
				}
			}
		}
		unset($basketFields);

		return $allBasketFields;
	}

	protected function buildExportContext($context)
	{
		$result = [
			'SITE_ID' => $context['SITE_ID'],
			'USER_GROUPS' => Main\UserTable::getUserGroupIds($context['USER_ID']),
			'PRICE_TYPE_ID' => $context['PRICE_TYPE'],
		];

		if (isset($context['CURRENCY']))
		{
			$result['CONVERT_CURRENCY'] = $context['CURRENCY'];
		}

		return $result;
	}

	protected function appendExportContextQuantities($quantities)
	{
		if (!empty($quantities))
		{
			$result = [
				'QUANTITY_LIST' => $quantities,
			];
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function buildSelectMap($context)
	{
		$behaviorMap = [
			static::SOURCE_CATALOG => 'MINIMAL',
			static::SOURCE_OPTIMAL => 'OPTIMAL',
		];

		if (isset($behaviorMap[$context['SOURCE']]))
		{
			$behavior = $behaviorMap[$context['SOURCE']];
			$priceSource = Market\Export\Entity\Manager::TYPE_CATALOG_PRICE;
			$result = $this->compileSourceSelect($priceSource, [
				'PRODUCT_PRICE_ID' => 'PRICE_ID',
				'DISCOUNT_LIST',
				'NOTES' => 'PRICE_TYPE_NAME',
				'PRICE_TYPE_ID',
				'CURRENCY',
				'VAT_RATE',
				'VAT_INCLUDED'
			], $behavior . '.');

			if ($context['USE_DISCOUNT'])
			{
				$result['PRICE'] = [ $priceSource, $behavior . '.DISCOUNT_VALUE' ];
				$result['BASE_PRICE'] = [ $priceSource, $behavior . '.VALUE' ];
			}
			else
			{
				$result['PRICE'] = [ $priceSource, $behavior . '.VALUE' ];
			}
		}
		else
		{
			throw new Market\Exceptions\NotImplemented($context['SOURCE'] . 'not implemented for ' . static::class);
		}

		return $result;
	}

	protected function getExportSelect($selectMap)
	{
		$result = [];

		foreach ($selectMap as list($source, $field))
		{
			if (!isset($result[$source]))
			{
				$result[$source] = [];
			}

			$result[$source][] = $field;
		}

		return $result;
	}

	protected function compileSourceSelect($source, array $fields, $prefix = '')
	{
		$result = [];

		foreach ($fields as $to => $from)
		{
			if (is_numeric($to))
			{
				$to = $from;
			}

			$result[$to] = [ $source, $prefix . $from ];
		}

		return $result;
	}
}
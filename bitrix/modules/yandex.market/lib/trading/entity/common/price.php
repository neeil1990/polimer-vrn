<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Catalog;
use Bitrix\Sale;
use Bitrix\Currency;

class Price extends Market\Trading\Entity\Reference\Price
{
	use Market\Reference\Concerns\HasLang;

	const SALE_ACTION_GIFT = 'GiftCondGroup';

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
		return Market\Data\UserGroup::getDefaults();
	}

	public function needRefresh(Main\Type\DateTime $date, array $context = [])
	{
		return (
			$this->hasDiscountChanges($date, $context)
			|| $this->hasCurrencyChanges($date, $context)
		);
	}

	protected function hasDiscountChanges(Main\Type\DateTime $date, array $context)
	{
		if (empty($context['USE_DISCOUNT'])) { return false; }

		if (Main\Config\Option::get('sale', 'use_sale_discount_only') === 'Y' && Main\Loader::includeModule('sale'))
		{
			$now = new Main\Type\DateTime();
			$discounts = [];

			$queryDiscounts = Sale\Internals\DiscountTable::getList([
				'filter' => [
					'=LID' => $context['SITE_ID'],
					'=USE_COUPONS' => 'N',
					'=EXECUTE_MODULE' => [ 'all', 'catalog' ],
					[
						'LOGIC' => 'OR',
						[ '>=TIMESTAMP_X' => $date ], // is changed
						[ '>=ACTIVE_FROM' => $date, '<=ACTIVE_FROM'	=> $now ], // is started
						[ '>=ACTIVE_TO' => $date, '<=ACTIVE_TO' => $now ], // is finished
					]

				],
				'select' => [
					'ID',
					'ACTIONS',
				],
			]);

			while ($discount = $queryDiscounts->fetch())
			{
				if (is_string($discount['ACTIONS']) && Market\Data\TextString::getPosition($discount['ACTIONS'], static::SALE_ACTION_GIFT) !== false) // is gift
				{
					continue;
				}

				$discounts[] = $discount['ID'];
			}

			if (empty($discounts)) { return false; }

			$userGroups = $this->getContextUserGroups($context);

			$queryAccess = Sale\Internals\DiscountGroupTable::getList([
				'filter' => [
					'=DISCOUNT_ID' => $discounts,
					'=GROUP_ID' => $userGroups,
					'=ACTIVE' => 'Y',
				],
				'select' => [ 'DISCOUNT_ID' ],
				'limit' => 1,
			]);

			$result = (bool)$queryAccess->fetchAll();
		}
		else
		{
			$result = false;
			$nowFormatted = ConvertTimeStamp(time(), 'FULL');
			$dateFormatted = ConvertTimeStamp($date->getTimestamp(), 'FULL');
			$commonFilter = [
				'SITE_ID' => $context['SITE_ID'],
				'TYPE' => \CCatalogDiscount::ENTITY_ID,
				'RENEWAL' => 'N',
				'+USER_GROUP_ID' => $this->getContextUserGroups($context),
				'+COUPON' => [],
			];
			$timeFilters = [
				[ '>=TIMESTAMP_X' => $dateFormatted ],
				[ '>=ACTIVE_FROM' => $dateFormatted, '<=ACTIVE_FROM' => $nowFormatted ], // is started
				[ '>=ACTIVE_TO' => $dateFormatted, '<=ACTIVE_TO' => $nowFormatted ], // is finished
			];

			foreach ($timeFilters as $timeFilter)
			{
				$filter = $commonFilter + $timeFilter;

				$queryDiscounts = \CCatalogDiscount::GetList(
					[],
					$filter,
					false,
					[ 'nTopCount' => 1 ],
					[ 'ID' ]
				);

				$result = (bool)$queryDiscounts->Fetch();

				if ($result) { break; }
			}
		}

		return $result;
	}

	protected function hasCurrencyChanges(Main\Type\DateTime $date, array $context)
	{
		if (empty($context['CURRENCY']) || !Main\Loader::includeModule('currency')) { return false; }

		// from rates

		$queryRates = Currency\CurrencyRateTable::getList([
			'filter' => [
				'<=DATE_RATE' => new Main\Type\Date(),
				[
					'LOGIC' => 'OR',
					[ '>=TIMESTAMP_X' => $date ],
					[ '>=DATE_RATE' => $date ],
				]
			],
			'select' => [ 'CURRENCY' ],
			'limit' => 1,
		]);

		if ($queryRates->fetch()) { return true; }

		// from currency

		$queryCurrencies = Currency\CurrencyTable::getList([
			'filter' => [ '>=DATE_UPDATE' => $date ],
			'select' => [ 'CURRENCY' ],
			'limit' => 1,
		]);

		return (bool)$queryCurrencies->fetch();
	}

	public function getChanged(array $context = [], Main\Type\DateTime $date = null, $offset = null, $limit = 500)
	{
		$result = [];
		$context = $this->enrichPricesContext($context);
		$parameters = [
			'select' => [ 'PRODUCT_ID' ],
			'filter' => [],
			'offset' => (int)$offset,
			'limit' => (int)$limit,
			'runtime' => [],
		];

		$parameters = $this->applyChangedExportParameters($parameters, $context);
		$parameters = $this->applyChangedQuantityParameters($parameters, $context);
		$parameters = $this->applyChangedIblockParameters($parameters, $context);
		$parameters = $this->applyChangedDateParameters($parameters, $date);

		$query = Catalog\PriceTable::getList($parameters);

		while ($row = $query->fetch())
		{
			$result[] = $row['PRODUCT_ID'];
		}

		return $result;
	}

	protected function applyChangedExportParameters(array $parameters, array $context)
	{
		$exportSource = $this->getExportSource();
		$behavior = $this->getExportPriceBehavior($context['SOURCE']);

		if ($behavior === null || !method_exists($exportSource, 'getPriceFilter')) { return $parameters; }

		$exportContext = $this->buildExportContext($context);

		$filter = $exportSource->getPriceFilter($behavior, $exportContext);

		if (!empty($filter))
		{
			$parameters['filter'][] = $filter;
		}

		return $parameters;
	}

	protected function applyChangedQuantityParameters(array $parameters, array $context)
	{
		if (empty($context['PACK_RATIO']))
		{
			$parameters['filter'][] = [
				'LOGIC' => 'OR',
				'<=QUANTITY_FROM' => 1,
				'=QUANTITY_FROM' => null,
			];
			$parameters['filter'][] = [
				'LOGIC' => 'OR',
				'>=QUANTITY_TO' => 1,
				'=QUANTITY_TO' => null,
			];
		}
		else
		{
			$parameters['group'] = [ 'PRODUCT_ID' ];
		}

		return $parameters;
	}

	protected function applyChangedIblockParameters(array $parameters, array $context)
	{
		if (empty($context['SKU_MAP'])) { return $parameters; }

		$iblockIds = array_column($context['SKU_MAP'], 'IBLOCK');

		Main\Type\Collection::normalizeArrayValuesByInt($iblockIds);

		if (empty($iblockIds) || !$this->hasOtherCatalog($iblockIds)) { return $parameters; }

		$parameters['filter'][] = [
			'=YM_IBLOCK_ELEMENT.IBLOCK_ID' => $iblockIds,
			'=YM_IBLOCK_ELEMENT.ACTIVE' => 'Y',
		];
		$parameters['runtime'][] = new Main\Entity\ReferenceField('YM_IBLOCK_ELEMENT', Iblock\ElementTable::class, [
			'=this.PRODUCT_ID' => 'ref.ID',
		]);

		return $parameters;
	}

	protected function hasOtherCatalog(array $iblockIds)
	{
		$queryOtherCatalogs = Catalog\CatalogIblockTable::getList([
			'filter' => [ '!=IBLOCK_ID' => $iblockIds ],
			'select' => [ 'IBLOCK_ID' ]
		]);
		$otherCatalogs = $queryOtherCatalogs->fetchAll();
		$otherIblockIds = array_column($otherCatalogs, 'IBLOCK_ID');

		if (empty($otherIblockIds)) { return false; }

		$otherCount = $this->countIblockPrices($otherIblockIds);

		if ($otherCount >= 1000) { return true; }
		if ($otherCount === 0) { return false; }

		$selfCount = $this->countIblockPrices($iblockIds);

		return ($selfCount / $otherCount) <= 1;
	}

	protected function countIblockPrices(array $iblockIds)
	{
		if (empty($iblockIds)) { return 0; }

		$queryPricesCount = Catalog\PriceTable::getList([
			'filter' => [
				'=YM_IBLOCK_ELEMENT.IBLOCK_ID' => $iblockIds,
			],
			'select' => [ 'CNT' ],
			'runtime' => [
				new Main\Entity\ReferenceField('YM_IBLOCK_ELEMENT', Iblock\ElementTable::class, [
					'=this.PRODUCT_ID' => 'ref.ID',
				]),
				new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
			],
		]);
		$pricesCount = $queryPricesCount->fetch();

		return (int)$pricesCount['CNT'];
	}

	protected function applyChangedDateParameters(array $parameters, Main\Type\DateTime $date = null)
	{
		if ($date === null) { return $parameters; }

		$parameters['order'] = [ 'TIMESTAMP_X' => 'ASC' ];
		$parameters['filter'][] = [ '>=TIMESTAMP_X' => $date ];

		return $parameters;
	}

	public function getPrices($productIds, $quantities = null, array $context = [])
	{
		$select = $this->getPricesSelect();
		$context = $this->enrichPricesContext($context);
		$export = $this->loadExportValues($select, $productIds, $quantities, $context);

		return $this->extendPriceValues($export);
	}

	protected function enrichPricesContext(array $context)
	{
		if (empty($context['SOURCE']))
		{
			$context['SOURCE'] = static::SOURCE_CATALOG; // used by default in export
		}

		if (!isset($context['USE_DISCOUNT']))
		{
			$context['USE_DISCOUNT'] = true;
		}

		return $context;
	}

	protected function getPricesSelect()
	{
		return [
			'CURRENCY',
			'VAT_RATE',
			'VAT_INCLUDED',
		];
	}

	protected function extendPriceValues($prices)
	{
		$result = [];

		foreach ($prices as $productId => $price)
		{
			if (isset($price['VAT_INCLUDED'], $price['VAT_RATE']) && $price['VAT_INCLUDED'] === 'N')
			{
				foreach (['PRICE', 'BASE_PRICE'] as $field)
				{
					if (!isset($price[$field])) { continue; }

					$price[$field] *= (1 + $price['VAT_RATE']);
				}

				$price['VAT_INCLUDED'] = 'Y';
			}

			$result[] = [ 'ID' => $productId ] + $price;
		}

		return $result;
	}

	public function getBasketData($productIds, $quantities = null, array $context = [])
	{
		$select = $this->getBasketSelect();
		$export = $this->loadExportValues($select, $productIds, $quantities, $context);

		return $this->extendExportValues($export);
	}

	protected function getBasketSelect()
	{
		return [
			'PRODUCT_PRICE_ID' => 'PRICE_ID',
			'DISCOUNT_LIST',
			'NOTES' => 'PRICE_TYPE_NAME',
			'PRICE_TYPE_ID',
			'CURRENCY',
			'VAT_RATE',
			'VAT_INCLUDED'
		];
	}

	protected function loadExportValues($select, $productIds, $quantities = null, array $context = [])
	{
		if (empty($context['SOURCE'])) { return []; }

		$selectMap = $this->buildSelectMap($context, $select);
		$exportContext = $this->buildExportContext($context);
		$exportContext += $this->appendExportContextQuantities($quantities);
		$sourceSelect = $this->getExportSelect($selectMap);

		$exportValues = Market\Export\Entity\Facade::loadValues($productIds, $sourceSelect, $exportContext);

		return $this->fillExportValues($productIds, $selectMap, $exportValues);
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
		foreach ($allBasketFields as &$basketFields)
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

	protected function getContextUserGroups($context)
	{
		if (isset($context['USER_GROUPS'])) { return $context['USER_GROUPS']; }

		$userId = isset($context['USER_ID']) ? (int)$context['USER_ID'] : null;

		return Market\Data\UserGroup::getUserGroups($userId);
	}

	protected function buildExportContext($context)
	{
		$result = [
			'SITE_ID' => $context['SITE_ID'],
			'USER_GROUPS' => $this->getContextUserGroups($context),
		];

		if (!empty($context['PRICE_TYPE']))
		{
			$result['PRICE_TYPE_ID'] = $context['PRICE_TYPE'];
		}

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

	protected function buildSelectMap($context, $select)
	{
		$behavior = $this->getExportPriceBehavior($context['SOURCE']);

		if ($behavior === null)
		{
			throw new Market\Exceptions\NotImplemented($context['SOURCE'] . 'not implemented for ' . static::class);
		}

		$priceSource = $this->getExportSourceType();
		$result = $this->compileSourceSelect($priceSource, $select, $behavior . '.');

		if ($context['USE_DISCOUNT'])
		{
			$result['PRICE'] = [ $priceSource, $behavior . '.DISCOUNT_VALUE' ];
			$result['BASE_PRICE'] = [ $priceSource, $behavior . '.VALUE' ];
		}
		else
		{
			$result['PRICE'] = [ $priceSource, $behavior . '.VALUE' ];
		}

		return $result;
	}

	protected function getExportPriceBehavior($source)
	{
		$behaviorMap = [
			static::SOURCE_CATALOG => 'MINIMAL',
			static::SOURCE_OPTIMAL => 'OPTIMAL',
		];

		return isset($behaviorMap[$source]) ? $behaviorMap[$source] : null;
	}

	protected function getExportSource()
	{
		$type = $this->getExportSourceType();

		return Market\Export\Entity\Manager::getSource($type);
	}

	protected function getExportSourceType()
	{
		return Market\Export\Entity\Manager::TYPE_CATALOG_PRICE;
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
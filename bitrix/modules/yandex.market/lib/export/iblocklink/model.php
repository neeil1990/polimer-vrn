<?php

namespace Yandex\Market\Export\IblockLink;

use Bitrix\Main\Localization\Loc;
use Yandex\Market;
use Yandex\Market\Export\Glossary;
use Yandex\Market\Watcher;

Loc::loadMessages(__FILE__);

class Model extends Market\Reference\Storage\Model
{
	protected $iblockContext;
	protected $tagDescriptionList;

	public function getTagDescription($tagName)
	{
		$result = null;
		$tagDescriptionList = $this->getTagDescriptionList();

		foreach ($tagDescriptionList as $tagDescription)
		{
			if ($tagDescription['TAG'] === $tagName)
			{
				$result = $tagDescription;
				break;
			}
		}

		return $result;
	}

	public function getTagDescriptionList()
	{
		if ($this->tagDescriptionList === null)
		{
			$this->tagDescriptionList = $this->createTagDescriptionList();
		}

		return $this->tagDescriptionList;
	}

	protected function createTagDescriptionList()
	{
		$paramCollection = $this->getParamCollection();

		return $this->describeTagDescriptionList($paramCollection);
	}

	protected function describeTagDescriptionList(Market\Export\Param\Collection $paramCollection)
	{
		$result = [];
		$textType = Market\Export\Entity\Manager::TYPE_TEXT;

		/** @var \Yandex\Market\Export\Param\Model $param */
		foreach ($paramCollection as $param)
		{
			$paramValueCollection = $param->getValueCollection();
			$tagResult = [
				'TAG' => $param->getField('XML_TAG'),
				'VALUE' => null,
				'CHILDREN' => $this->describeTagDescriptionList($param->getChildren()),
				'ATTRIBUTES' => [],
				'SETTINGS' => $param->getSettings()
			];

			/** @var \Yandex\Market\Export\ParamValue\Model $paramValue */
			foreach ($paramValueCollection as $paramValue)
			{
				$sourceType = $paramValue->getSourceType();
				$sourceField = $paramValue->getSourceField();
				$sourceMap = (
					$sourceType === $textType
						? [ 'VALUE' => $sourceField ]
						: [ 'TYPE' => $sourceType, 'FIELD' => $sourceField ]
				);

				if ($paramValue->isAttribute())
				{
					$attributeName = $paramValue->getAttributeName();

					$tagResult['ATTRIBUTES'][$attributeName] = $sourceMap;
				}
				else
				{
					$tagResult['VALUE'] = $sourceMap;
				}
			}

			$result[] = $tagResult;
		}

		return $result;
	}

	public function getSourceSelect()
	{
		$result = [];
		$paramCollection = $this->getParamCollection();

		/** @var \Yandex\Market\Export\Param\Model $param */
		foreach ($paramCollection as $param)
		{
			$paramValueCollection = $param->getValueCollection();

			/** @var \Yandex\Market\Export\ParamValue\Model $paramValue */
			foreach ($paramValueCollection as $paramValue)
			{
				$sourceType = $paramValue->getSourceType();
				$sourceField = $paramValue->getSourceField();

				if (!isset($result[$sourceType]))
				{
					$result[$sourceType] = [];
				}

				if (!in_array($sourceField, $result[$sourceType]))
				{
					$result[$sourceType][] = $sourceField;
				}
			}
		}

		return $this->extendSourceSelect($result);
	}

	protected function extendSourceSelect($sourceSelect)
	{
		$context = $this->getContext();

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = Market\Export\Entity\Manager::getSource($sourceType);

			$source->initializeQueryContext($sourceFields, $context, $sourceSelect);
			$source->releaseQueryContext($sourceFields, $context, $sourceSelect);
		}

		return $sourceSelect;
	}

	public function getUsedSources()
	{
		$result = $this->getSourceSelect();

		foreach ($this->getFilterCollection() as $filterModel)
		{
			$filterUserSources = $filterModel->getUsedSources();

			foreach ($filterUserSources as $sourceType)
			{
				if (!isset($result[$sourceType]))
				{
					$result[$sourceType] = true;
				}
			}
		}

		return array_keys($result);
	}

	public function getTrackSourceList()
	{
		$sourceList = $this->getUsedSources();
		$context = $this->getContext();
		$result = [];

		foreach ($sourceList as $sourceType)
		{
			$eventHandler = Market\Export\Entity\Manager::getEvent($sourceType);

            $result[] = [
                'SOURCE_TYPE' => $sourceType,
                'SOURCE_PARAMS' => $eventHandler->getSourceParams($context)
            ];
		}

		return $result;
	}

	public function getSetupBindEntities()
	{
		$context = $this->getIblockContext();
		$result = [
			new Watcher\Track\BindEntity(Glossary::ENTITY_OFFER, $context['IBLOCK_ID']),
		];

		if ($context['HAS_OFFER'])
		{
			$result[] = new Watcher\Track\BindEntity(Glossary::ENTITY_OFFER, $context['OFFER_IBLOCK_ID']);
		}

		if ($this->hasCurrencyConversion())
		{
			$result[] = new Market\Watcher\Track\BindEntity(Glossary::ENTITY_CURRENCY);
		}

		return $result;
	}

	protected function hasCurrencyConversion()
	{
		$result = false;
		$tags = [
			'price',
			'oldprice',
			'currencyId',
		];

		foreach ($tags as $tagName)
		{
			$tagDescription = $this->getTagDescription($tagName);

			if (!isset($tagDescription['VALUE']['TYPE'], $tagDescription['VALUE']['FIELD'])) { continue; }

			$source = Market\Export\Entity\Manager::getSource($tagDescription['VALUE']['TYPE']);

			if (
				method_exists($source, 'hasCurrencyConversion')
				&& $source->hasCurrencyConversion($tagDescription['VALUE']['FIELD'], $tagDescription['SETTINGS'])
			)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getContext()
	{
		$result = [
			'IBLOCK_LINK_ID' => $this->getId(),
		];

		$result += $this->getIblockContext();
		$result += $this->getTagContext();

		// sales notes

		$salesNotes = $this->getSalesNotes();

		if ($salesNotes !== '')
		{
			$result['SALES_NOTES'] = $salesNotes;
		}

		// delivery options

		$deliveryOptions = $this->getDeliveryOptions();

		if (!empty($deliveryOptions))
		{
			$result['DELIVERY_OPTIONS'] = $deliveryOptions;
		}

		$result = $this->mergeParentContext($result);

		return $result;
	}

	protected function mergeParentContext($selfContext)
	{
		$collection = $this->getCollection();
		$setup = $collection ? $collection->getParent() : null;
		$setupContext = $setup ? $setup->getContext() : null;
		$result = $selfContext;

		if (isset($setupContext))
		{
			$result += $setupContext;

			if (isset($setupContext['DELIVERY_OPTIONS']) && !isset($selfContext['DELIVERY_OPTIONS']))
			{
				unset($result['DELIVERY_OPTIONS']);
			}
		}

		return $result;
	}

	protected function getIblockContext()
	{
		if ($this->iblockContext === null)
		{
			$iblockId = $this->getIblockId();
			$iblockContext = Market\Export\Entity\Iblock\Provider::getContext($iblockId);

			if (count($iblockContext['SITE_LIST']) > 1)
			{
				$setup = $this->getParent();

				if ($setup instanceof Market\Export\Setup\Model)
				{
					$domain = $setup->getDomain();
					$path = $setup->getDomainPath();
					$domainSiteId = Market\Data\SiteDomain::getSite($domain, $path);

					if ($domainSiteId !== null && in_array($domainSiteId, $iblockContext['SITE_LIST'], true))
					{
						$iblockContext['SITE_ID'] = $domainSiteId;
					}
				}
			}

			$this->iblockContext = $iblockContext;
		}

		return $this->iblockContext;
	}

	protected function getTagContext()
	{
		$result = [];
		$priceTag = $this->getTagDescription('price');

		if (isset($priceTag['SETTINGS']['USER_GROUP']))
		{
			$selectedGroup = (int)$priceTag['SETTINGS']['USER_GROUP'];
			$groups = Market\Data\UserGroup::getDefaults();

			if (!in_array($selectedGroup, $groups, true))
			{
				$groups[] = $selectedGroup;
			}

			$result['USER_GROUPS'] = $groups;
		}

		return $result;
	}

	public function getDeliveryOptions()
	{
		$deliveryCollection = $this->getDeliveryCollection();

		return $deliveryCollection->getDeliveryOptions();
	}

	public function getSalesNotes()
	{
		return trim($this->getField('SALES_NOTES'));
	}

	public function getIblockId()
	{
		return (int)$this->getField('IBLOCK_ID');
	}

	public function getOfferIblockId()
	{
		$iblockContext = $this->getIblockContext();

		return (isset($iblockContext['OFFER_IBLOCK_ID']) ? $iblockContext['OFFER_IBLOCK_ID'] : null);
	}

	public function getOfferPropertyId()
	{
		$iblockContext = $this->getIblockContext();

		return (isset($iblockContext['OFFER_PROPERTY_ID']) ? $iblockContext['OFFER_PROPERTY_ID'] : null);
	}

	public function isExportAll()
	{
		return $this->getField('EXPORT_ALL') === '1';
	}

	public function getSiteId()
	{
		$iblockContext = $this->getIblockContext();

		return $iblockContext['SITE_ID'];
	}

	public function hasIblockCatalog()
	{
		$iblockContext = $this->getIblockContext();

		return $iblockContext['HAS_CATALOG'];
	}

	public function isIblockCatalogOnlyOffers()
	{
		$iblockContext = $this->getIblockContext();

		return !empty($iblockContext['OFFER_ONLY']);
	}

	public function hasIblockOffers()
	{
		$iblockContext = $this->getIblockContext();

		return $iblockContext['HAS_OFFER'];
	}

	/**
	 * Название класса таблицы
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	/**
	 * @return \Yandex\Market\Export\Filter\Collection
	 */
	public function getFilterCollection()
	{
		return $this->getChildCollection('FILTER');
	}

	/**
	 * @return \Yandex\Market\Export\Param\Collection
	 */
	public function getParamCollection()
	{
		return $this->getChildCollection('PARAM');
	}

	/**
	 * @return \Yandex\Market\Export\Param\Collection
	 */
	public function getDeliveryCollection()
	{
		return $this->getChildCollection('DELIVERY');
	}

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'FILTER':
				$result = Market\Export\Filter\Collection::getClassName();
			break;

			case 'PARAM':
				$result = Market\Export\Param\Collection::getClassName();
			break;

			case 'DELIVERY':
				$result = Market\Export\Delivery\Collection::getClassName();
			break;
		}

		return $result;
	}

	protected function queryChildCollection($collectionClassName, $fieldKey)
	{
		if ($fieldKey === 'PARAM')
		{
			return $this->queryParamCollection($collectionClassName, $fieldKey);
		}

		return parent::queryChildCollection($collectionClassName, $fieldKey);
	}

	/**
	 * @param class-string<Market\Export\Param\Collection> $collectionClassName
	 * @param string $fieldKey
	 *
	 * @return Market\Export\Param\Collection
	 */
	protected function queryParamCollection($collectionClassName, $fieldKey)
	{
		$queryParams = $this->getChildCollectionQueryParameters($fieldKey);
		$queryParams['order'] = [ 'PARENT_ID' => 'asc' ];
		$queryParams['filter'] = array_diff_key($queryParams['filter'], [
			'=PARENT_ID' => true,
		]);

		/** @var $flatCollection Market\Export\Param\Collection */
		$flatCollection = $collectionClassName::load($this, $queryParams);
		$flatCollection->initChildren();
		$flatCollection->preloadReference();

		return $this->makeParamCollectionTree($collectionClassName, $flatCollection);
	}

	/**
	 * @param class-string<Market\Export\Param\Collection> $collectionClassName
	 * @param Market\Export\Param\Collection $flatCollection
	 *
	 * @return Market\Export\Param\Collection
	 */
	protected function makeParamCollectionTree($collectionClassName, $flatCollection)
	{
		$valueTreeMap = [];
		$result = new $collectionClassName;

		/** @var Market\Export\Param\Model $param */
		foreach ($flatCollection as $param)
		{
			$valueId = (int)$param->getId();
			$parentId = (int)$param->getField('PARENT_ID');
			$parentLevel = $result;

			if (empty($parentId)) // is root
			{
				$parentTree = [];
			}
			else
			{
				if (!isset($valueTreeMap[$parentId])) { continue; }

				$parentTree = $valueTreeMap[$parentId];
			}

			foreach ($parentTree as $parentId)
			{
				/** @var Market\Export\Param\Model $child */
				$child = $parentLevel->getItemById($parentId);

				if ($child === null)
				{
					$parentLevel = null;
					break;
				}

				$parentLevel = $child->initChildren();
			}

			if ($parentLevel === null) { continue; }

			$param->setCollection($parentLevel);
			$parentLevel->addItem($param);

			$selfTree = $parentTree;
			$selfTree[] = $valueId;

			$valueTreeMap[$valueId] = $selfTree;
		}

		return $result;
	}
}
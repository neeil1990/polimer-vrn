<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * @method Market\Export\Xml\Tag\Base getTag($type = null)
*/
class Offer extends Base
{
	const ELEMENT_TYPE_PRODUCT = 1;
	/** @noinspection PhpUnused */
	const ELEMENT_TYPE_SET = 2;
	const ELEMENT_TYPE_SKU = 3;
	const ELEMENT_TYPE_OFFER = 4;
	/** @noinspection PhpUnused */
	const ELEMENT_TYPE_FREE_OFFER = 5;
	const ELEMENT_TYPE_EMPTY_SKU = 6;

	public function getName()
	{
		return Market\Export\Run\Manager::STEP_OFFER;
	}

	public function getReadyCount()
	{
		$dataClass = $this->getStorageDataClass();
		$context = $this->getContext();
		$readyFilter = $this->getStorageReadyFilter($context);
		$result = 0;

		$query = $dataClass::getList([
			'filter' => $readyFilter,
			'select' => [ 'CNT' ],
			'runtime' => [
				new Main\Entity\ExpressionField('CNT', 'COUNT(1)')
			]
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['CNT'];
		}

		return $result;
	}

	public function getSuccessCount($context = null)
	{
		if ($context === null) { $context = $this->getContext(); }

		$dataClass = $this->getStorageDataClass();
		$readyFilter = $this->getStorageReadyFilter($context, true);
		$readyFilter['=STATUS'] = static::STORAGE_STATUS_SUCCESS;
		$result = 0;

		$query = $dataClass::getList([
			'filter' => $readyFilter,
			'select' => [ 'CNT' ],
			'runtime' => [
				new Main\Entity\ExpressionField('CNT', 'COUNT(1)')
			]
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['CNT'];
		}

		return $result;
	}

	public function getCount($offset = null, $isNeedAll = null)
	{
		$result = new Market\Result\StepCount();
		$offsetObject = new Market\Data\Run\Offset($offset, [
			'iblock',
			'filter',
		]);

		foreach ($this->getIblockConfigList($isNeedAll) as $iblockConfig)
		{
			if (!$offsetObject->tick('iblock')) { continue; }

			$counterManager = new Market\Export\Run\Counter\Manager();

			do
			{
				try
				{
					$isNeedRepeatCount = false;
					$counter = null;
					$previousFilterSum = 0;

					foreach ($iblockConfig['FILTER_LIST'] as $sourceFilter)
					{
						if (!$offsetObject->tick('filter')) { continue; }

						$filterContext = $sourceFilter['CONTEXT'] + $iblockConfig['CONTEXT'];
						$filterBuilder = new Market\Export\Routine\QueryBuilder\Filter();
						$exportFilter = $filterBuilder->boot($sourceFilter['FILTER'], $filterContext);
						$queryFilters = $this->compileQueryFilters($filterBuilder, $exportFilter, [], $filterContext);

						$filterCount = 0;
						$isIblockConfigFilter = ($sourceFilter['ID'] === null);

						if ($isIblockConfigFilter)
						{
							$totalCount = 0;

							foreach ($queryFilters as $queryFilter)
							{
								$totalCount += $this->queryTotalCount($queryFilter, $filterContext);
							}

							$filterCount = $totalCount - $previousFilterSum;

							if ($this->isCatalogTypeCompatibility($filterContext))
							{
								$result->addCountWarning($iblockConfig['ID'], new Market\Error\Base(
									Market\Config::getLang('EXPORT_RUN_STEP_OFFER_COUNT_CATALOG_TYPE_COMPATIBILITY')
								));
							}
						}
						else if (!empty($sourceFilter['FILTER']))
						{
							if ($counter === null)
							{
								$counter = $counterManager->getCounter();
								$counter->start();
							}

							foreach ($queryFilters as $queryFilter)
							{
								$filterCount += $this->queryCount($queryFilter, $filterContext, $counter);
							}

							$previousFilterSum += $filterCount;
						}

						if ($isIblockConfigFilter) // is iblock link
						{
							$result->setCount($iblockConfig['ID'], $filterCount);
						}
						else
						{
							$result->setCount($iblockConfig['ID'] . ':' . $sourceFilter['ID'], $filterCount);
						}

						$filterBuilder->release($sourceFilter['FILTER'], $filterContext);
					}

					if ($counter !== null)
					{
						$counter->finish();
					}
				}
				catch (Main\SystemException $exception)
				{
					$counterManager->invalidateCounter();

					if ($counterManager->hasCounter())
					{
						$isNeedRepeatCount = true;
					}
					else
					{
						$result->addCountWarning($iblockConfig['ID'], new Market\Error\Base(
							Market\Config::getLang('EXPORT_RUN_STEP_OFFER_COUNT_FAILED')
						));
					}
				}
			}
			while ($isNeedRepeatCount);
		}

		return $result;
	}

	public function run($action, $offset = null)
	{
		$result = new Market\Result\Step();

		$this->setRunAction($action);

		$formatTag = $this->getTag();
		$isTimeExpired = false;
		$offsetObject = new Market\Data\Run\Offset($offset, [
			'iblock',
			'filter',
			'query',
			'element',
		]);

		foreach ($this->getIblockConfigList() as $iblockConfig)
		{
			if (!$offsetObject->tick('iblock')) { continue; }

			$tagDescriptionList = $iblockConfig['TAG_DESCRIPTION_LIST'];
			$iblockContext = $iblockConfig['CONTEXT'];
			$iblockLimit = (isset($iblockConfig['LIMIT']) ? (int)$iblockConfig['LIMIT'] : null);
			$iblockReadyCount = 0;
			$hasIblockLimit = ($iblockLimit > 0);
			$isExceededIblockLimit = false;
			$changesFilter = null;

			if ($action === 'change')
			{
				$changes = $this->getChanges();
				$changesFilter = $this->getQueryChangesFilter($changes, $iblockContext);

				if ($changesFilter === null) { continue; } // changed other entity
			}

			$formatTag->extendTagDescriptionList($tagDescriptionList, $iblockContext);

			$selectBuilder = new Market\Export\Routine\QueryBuilder\Select();
			$sourceSelect = $this->getSourceSelect($tagDescriptionList);
			$sourceSelect = $selectBuilder->boot($sourceSelect, $iblockContext);
			$querySelect = $selectBuilder->compile($sourceSelect, $iblockContext);

			$this->applySelectMap($tagDescriptionList, $iblockContext);

			if ($hasIblockLimit && $offsetObject->get('filter') !== null)
			{
				$iblockReadyCount = $this->getSuccessCount($iblockContext);
				$isExceededIblockLimit = ($iblockReadyCount >= $iblockLimit);

				if ($isExceededIblockLimit) { continue; }
			}

			foreach ($iblockConfig['FILTER_LIST'] as $filterConfig)
			{
				if (!$offsetObject->tick('filter')) { continue; }

				$filterContext = $filterConfig['CONTEXT'] + $iblockContext;
				$filterBuilder = new Market\Export\Routine\QueryBuilder\Filter();

				$exportFilter = $filterBuilder->boot($filterConfig['FILTER'], $filterContext);
				$queryFilters = $this->compileQueryFilters($filterBuilder, $exportFilter, $sourceSelect, $filterContext, $changesFilter);

				foreach ($queryFilters as $queryFilter)
				{
					if (!$offsetObject->tick('query')) { continue; }

					$filterResult = $this->exportIblockFilter(
						$queryFilter,
						$sourceSelect,
						$querySelect,
						$tagDescriptionList,
						$filterContext,
						$offsetObject->get('element'),
						$iblockLimit,
						$iblockReadyCount
					);

					$iblockReadyCount += $filterResult['SUCCESS_COUNT'];

					if ($filterResult['OFFSET'] !== null)
					{
						$isTimeExpired = true;

						$offsetObject->set('element', $filterResult['OFFSET']);
					}
					else if ($hasIblockLimit && $iblockReadyCount >= $iblockLimit)
					{
						$isExceededIblockLimit = true;
						$isTimeExpired = $this->getProcessor()->isTimeExpired();

						$offsetObject->next('iblock');
						break;
					}
					else
					{
						$isTimeExpired = $this->getProcessor()->isTimeExpired();

						$offsetObject->next('query');
					}

					if ($isTimeExpired) { break; }
				}

				$filterBuilder->release($exportFilter, $filterContext);

				if ($isExceededIblockLimit || $isTimeExpired) { break; }
			}

			$selectBuilder->release($sourceSelect, $iblockContext);

			if ($isTimeExpired) { break; }
		}

		if ($isTimeExpired)
		{
			$result->setOffset((string)$offsetObject);
			$result->setTotal(1);

			if ($this->getParameter('progressCount') === true)
			{
				$result->setReadyCount($this->getReadyCount());
			}
		}

		return $result;
	}

	public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return $format->getOffer();
	}

	public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
	{
		return $format->getOfferParentName();
	}

	protected function getOfferTag()
	{
		$step = $this->getProcessor()->getStep(Market\Export\Run\Manager::STEP_OFFER);

		return $step->getTag();
	}

	protected function getOfferPrimarySource(Market\Export\IblockLink\Model $iblockLink, array $context)
	{
		/** @var Market\Export\Xml\Tag\Base $offerTag */
		$offerTag = $this->getOfferTag();
		$offerName = $offerTag->getName();
		$primaryName = $this->getTagPrimaryName($offerTag);
		$tagDescription = $iblockLink->getTagDescription($offerName);
		$tagDescription = $offerTag->extendTagDescription($tagDescription, $context);
		$result = null;

		if ($tagDescription !== null && isset($tagDescription['ATTRIBUTES'][$primaryName]))
		{
			$result = $tagDescription['ATTRIBUTES'][$primaryName];
		}

		return $result;
	}

	protected function useHashCollision()
	{
		return $this->getFormat()->useOfferHashCollision();
	}

	protected function usePrimaryCollision($context)
	{
		return !empty($context['USE_PRIMARY_COLLISION']) && $this->useTagPrimary();
	}

	/**
	 * Возможно ли совпадение идентификатора для тега
	 *
	 * @param $tagDescriptionList array[]
	 * @param $context array
	 *
	 * @return bool
	 */
	protected function resolvePrimaryCollision($tagDescriptionList, $context)
	{
		$tagType = isset($context['ELEMENT_TYPE']) ? $context['ELEMENT_TYPE'] : null;
		$tag = $this->getTag($tagType);
		$result = false;

		if ($tag !== null)
		{
			$tagName = $tag->getName();
			$primaryName = $this->getTagPrimaryName($tag);

			foreach ($tagDescriptionList as $tagDescription)
			{
				if (
					$tagDescription['TAG'] === $tagName
					&& isset(
						$tagDescription['ATTRIBUTES'][$primaryName]['TYPE'],
						$tagDescription['ATTRIBUTES'][$primaryName]['FIELD']
					)
				)
				{
					$primarySource = $tagDescription['ATTRIBUTES'][$primaryName]['TYPE'];
					$primaryField = $tagDescription['ATTRIBUTES'][$primaryName]['FIELD'];

					if ($primaryField === 'ID')
					{
						$sourcesWithoutCollision = [
							Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD => true,
						];

						if (!$context['HAS_OFFER'])
						{
							$sourcesWithoutCollision[Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD] = true;
						}

						$result = !isset($sourcesWithoutCollision[$primarySource]);
					}
					else
					{
						$result = true;
					}

					break;
				}
			}
		}

		return $result;
	}

	protected function getTagResultHash($tagResult, $tagValues)
	{
		$xmlContents = $tagResult->getXmlContents();

		if ($xmlContents === null) { return ''; }

		return $this->calculateXmlContentHash($xmlContents, $tagValues->getType());
	}

	public function calculateXmlContentHash($xmlContent, $tagType = null)
	{
		if ($this->useHashCollision())
		{
			$tag = $this->getTag($tagType);
			$primaryName = $this->getTagPrimaryName($tag);

			$xmlContent = preg_replace('/^(<[^ ]+) ' . $primaryName . '="[^"]*?"/', '$1', $xmlContent); // remove id attr for check tag contents
			$xmlContent = preg_replace_callback('/(<url>.*?\?)(.*?)(#.*)?(<\/url>)/', static function($matches) {
				$utmMarker = 'utm_';
				$queryString = $matches[2];

				if (Market\Data\TextString::getPosition($queryString, $utmMarker) !== false)
				{
					$glue = '&amp;';
					$isChanged = false;
					$queryParameters = explode($glue, $queryString);

					foreach ($queryParameters as $queryParameterIndex => $queryParameter)
					{
						if (Market\Data\TextString::getPosition($queryParameter, $utmMarker) === 0)
						{
							$isChanged = true;
							unset($queryParameters[$queryParameterIndex]);
						}
					}

					if ($isChanged)
					{
						$queryString = implode($glue, $queryParameters);
					}
				}

				return $matches[1] . $queryString . $matches[3] . $matches[4];
			}, $xmlContent); // remove utm from url
		}

		return md5($xmlContent);
	}

	protected function getStorageDataClass()
	{
		return Market\Export\Run\Storage\OfferTable::getClassName();
	}

	protected function getStorageChangesFilter($changes, $context)
	{
		$isNeedFull = false;
		$result = [];

		if (!empty($changes))
		{
			foreach ($changes as $changeType => $entityIds)
			{
				switch ($changeType)
				{
					case Market\Export\Run\Manager::ENTITY_TYPE_OFFER:

						$dataClass = $this->getStorageDataClass();
						$elementFilter = [];
						$parentFilter = [];

						$query = $dataClass::getList([
							'filter' => [
								'=SETUP_ID' => $context['SETUP_ID'],
								[
									'LOGIC' => 'OR',
									[ '=ELEMENT_ID' => $entityIds ],
									[ '=PARENT_ID' => $entityIds ]
								]

							],
							'select' => [
								'ELEMENT_ID',
								'PARENT_ID'
							]
						]);

						while ($row = $query->fetch())
						{
							$parentId = (int)$row['PARENT_ID'];

							if ($parentId > 0)
							{
								$parentFilter[$parentId] = true;
							}
							else
							{
								$elementFilter[] = (int)$row['ELEMENT_ID'];
							}
						}

						$hasParentFilter = !empty($parentFilter);
						$hasElementFilter = !empty($elementFilter);

						if ($hasParentFilter || $hasElementFilter)
						{
							if ($hasParentFilter)
							{
								$result[] = [
									'=PARENT_ID' => array_keys($parentFilter)
								];
							}

							if ($hasElementFilter)
							{
								$result[] = [
									'=ELEMENT_ID' => $elementFilter
								];
							}
						}
					break;

					case Market\Export\Run\Manager::ENTITY_TYPE_CATEGORY:
						$result[] = [
							'=CATEGORY_ID' => $entityIds
						];
					break;

					case Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY:
						$result[] = [
							'=CURRENCY_ID' => $entityIds
						];
					break;

					default:
						$isNeedFull = true;
					break;
				}

				if ($isNeedFull)
				{
					break;
				}
			}
		}

		if ($isNeedFull)
		{
			$result = [];
		}
		else if (empty($result))
		{
			$result = null;
		}
		else if (count($result) > 1)
		{
			$result['LOGIC'] = 'OR';
		}

		return $result;
	}

	protected function getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data)
	{
		$categoryId = $tagValues->getTagValue('categoryId') ?: '';
		$currencyId = $tagValues->getTagValue('currencyId') ?: '';

		return [
			'PARENT_ID' => isset($element['PARENT_ID']) ? $element['PARENT_ID'] : '',
			'IBLOCK_LINK_ID' => isset($context['IBLOCK_LINK_ID']) ? $context['IBLOCK_LINK_ID'] : '',
			'FILTER_ID' => isset($context['FILTER_ID']) ? $context['FILTER_ID'] : '',
			'CATEGORY_ID' => $categoryId,
			'CURRENCY_ID' => $currencyId
		];
	}

	protected function getDataLogEntityType()
	{
		return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_OFFER;
	}

	protected function isAllowPublicDelete()
	{
		return true;
	}

	protected function getIgnoredTypeChanges()
	{
		return [
			Market\Export\Run\Manager::ENTITY_TYPE_PROMO => true,
			Market\Export\Run\Manager::ENTITY_TYPE_GIFT => true,
			Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION => true,
		];
	}

	/**
	 * Описание фильтров по инфоблокам
	 *
	 * @param bool|null $isNeedAll
	 *
	 * @return array
	 */
	protected function getIblockConfigList($isNeedAll = null)
	{
		$setup = $this->getSetup();
		$result = [];

		/** @var Market\Export\IblockLink\Model $iblockLink */
		foreach ($setup->getIblockLinkCollection() as $iblockLink)
		{
			$iblockContext = $iblockLink->getContext();

			$result[] = [
				'ID' => $iblockLink->getInternalId(),
				'EXPORT_ALL' => $iblockLink->isExportAll(),
				'TAG_DESCRIPTION_LIST' => $iblockLink->getTagDescriptionList(),
				'FILTER_LIST' => $this->getSourceFilterList($iblockLink, $isNeedAll),
				'CONTEXT' => $iblockContext,
			];
		}

		return $result;
	}

	/**
	 * Выгрузка элементов по фильтру
	 *
	 * @param $queryFilter
	 * @param $sourceSelect
	 * @param $querySelect
	 * @param $tagDescriptionList
	 * @param $context
	 * @param int|null $queryOffset
	 * @param int|null $limit
	 * @param int $successCount
	 *
	 * @return array 'OFFSET' => отстут для пошагового запроса, 'SUCCESS_COUNT' => количество успешно выгруженных тегов
	 */
	protected function exportIblockFilter($queryFilter, $sourceSelect, $querySelect, $tagDescriptionList, $context, $queryOffset = null, $limit = null, $successCount = 0)
	{
		$elementFetcher = $this->createElementFetcher($context);
		$collectionLink = new Offer\CollectionLink($this->getSetup(), $this->getFormat(), $this->getRunAction());
		$queryDistinct = !empty($queryFilter['DISTINCT']) ? $queryFilter['DISTINCT'] : null;
		$useDistinct = ($queryDistinct !== null);
		$hasLimit = ($limit > 0);
		$processChunkSize = ($hasLimit ? $limit : 500);
		$result = [
			'OFFSET' => null,
			'SUCCESS_COUNT' => 0
		];

		$context['USE_DISTINCT'] = $useDistinct;
		$context['USE_PRIMARY_COLLISION'] = $this->resolvePrimaryCollision($tagDescriptionList, $context);

		do
		{
			$queryResult = $elementFetcher->load($queryFilter, $querySelect, $context, $queryOffset);
			$queryOffset = (int)$queryResult['OFFSET'];

			$this->processExportElementList($queryResult['ELEMENT'], $queryResult['PARENT'], $context);

			foreach ($this->chunkElementList($queryResult['ELEMENT'], $processChunkSize, $useDistinct) as $elementChunk)
			{
				$parentChunk = array_intersect_key($queryResult['PARENT'], array_column($elementChunk, 'PARENT_ID', 'PARENT_ID'));
				$sourceValueList = $this->extractElementListValues($sourceSelect, $elementChunk, $parentChunk, $context);
				$sourceValueList = $collectionLink->extend(array_column($elementChunk, 'ID'), $sourceValueList);

				// write

				$writeLimit = ($hasLimit ? $limit - $successCount : null);
				$tagValuesList = $this->buildTagValuesList($tagDescriptionList, $sourceValueList, $context);
				$writeData = [
					'PARENT_LIST' => $parentChunk,
					'SOURCE_VALUE' => $sourceValueList,
				];

				$this->extendData($tagValuesList, $elementChunk, $context, $writeData);

				if ($useDistinct)
				{
					$tagValuesList = $this->resolveDistinctGroups($queryDistinct, $elementChunk, $sourceValueList, $tagValuesList, $context);
				}

				$writeResultList = $this->writeData($tagValuesList, $elementChunk, $context, $writeData, $writeLimit);
				$written = array_filter($writeResultList, static function(array $writeResult) { return $writeResult['STATUS'] === static::STORAGE_STATUS_SUCCESS; });

				$collectionLink->commit(array_column($written, 'ID', 'ID'));

				$successCount += count($written);
				$result['SUCCESS_COUNT'] += count($written);

				if ($hasLimit && $successCount >= $limit) { break; }
			}

			if ($hasLimit && $successCount >= $limit)
			{
				$result['OFFSET'] = null;
				break;
			}

			if ($queryResult['HAS_NEXT'] && $this->getProcessor()->isTimeExpired())
			{
				$result['OFFSET'] = $queryOffset;
				break;
			}
		}
		while ($queryResult['HAS_NEXT']);

		return $result;
	}

	protected function createElementFetcher(array $context)
	{
		$fetcher = new Market\Export\Routine\QueryBuilder\ElementFetcher();

		if (empty($context['IGNORE_EXCLUDE']))
		{
			$fetcher->exclude(
				$this->getStorageDataClass(),
				$this->getStorageReadyFilter($context),
				'ELEMENT_ID',
				$context['HAS_OFFER'] && !empty($context['USE_DISTINCT']) ? 'PARENT_ID' : null
			);
		}

		return $fetcher;
	}

	protected function processExportElementList(&$elementList, &$parentList, $context)
	{
		// nothing by default
	}

	protected function applySelectMap(&$tagDescriptionList, $iblockContext)
	{
		if (!empty($iblockContext['SELECT_MAP']))
		{
			$selectMap = $iblockContext['SELECT_MAP'];
			$innerTypes = [ 'ATTRIBUTES', 'SETTINGS' ];

			foreach ($tagDescriptionList as &$tagDescription)
			{
				if (isset($tagDescription['VALUE']))
				{
					$valueSourceMap = $tagDescription['VALUE'];

					if (isset($selectMap[$valueSourceMap['TYPE']][$valueSourceMap['FIELD']]))
					{
						$tagDescription['VALUE']['FIELD'] = $selectMap[$valueSourceMap['TYPE']][$valueSourceMap['FIELD']];
					}
				}

				foreach ($innerTypes as $innerType)
				{
					if (isset($tagDescription[$innerType]))
					{
						foreach ($tagDescription[$innerType] as &$innerSourceMap)
						{
							if (is_array($innerSourceMap) && isset($selectMap[$innerSourceMap['TYPE']][$innerSourceMap['FIELD']]))
							{
								$innerSourceMap['FIELD'] = $selectMap[$innerSourceMap['TYPE']][$innerSourceMap['FIELD']];
							}
						}
						unset($innerSourceMap);
					}
				}
			}
			unset($tagDescription);
		}
	}

	/** @noinspection DuplicatedCode */
	protected function getQueryChangesFilter($changes, $context)
	{
		$changesFilter = [];
		$isNeedFull = false;

		foreach ($changes as $changeType => $entityIds)
		{
			$entityType = null;
			$entityFilter = null;

			switch ($changeType)
			{
				case Market\Export\Run\Manager::ENTITY_TYPE_OFFER:
				case Market\Export\Run\Manager::ENTITY_TYPE_GIFT:

					if (!isset($context['OFFER_IBLOCK_ID']))
					{
						$entityType = 'ELEMENT';
						$entityFilter = [
							'ID' => $entityIds
						];
					}
					else
					{
						// no support for only one offer change

						$elementIdsMap = array_flip($entityIds);

						$queryOffers = \CIBlockElement::GetList(
							array(),
							array(
								'IBLOCK_ID' => $context['OFFER_IBLOCK_ID'],
								'ID' => $entityIds
							),
							false,
							false,
							array(
								'IBLOCK_ID',
								'ID',
								'PROPERTY_' . $context['OFFER_PROPERTY_ID']
							)
						);

						while ($offer = $queryOffers->Fetch())
						{
							$offerId = (int)$offer['ID'];
							$offerElementId = (int)$offer['PROPERTY_' . $context['OFFER_PROPERTY_ID'] . '_VALUE'];

							if ($offerElementId > 0 && !isset($elementIdsMap[$offerElementId]))
							{
								$elementIdsMap[$offerElementId] = true;
							}

							if (isset($elementIdsMap[$offerId]))
							{
								unset($elementIdsMap[$offerId]);
							}
						}

						$entityType = 'ELEMENT';
						$entityFilter = [
							'ID' => !empty($elementIdsMap) ? array_keys($elementIdsMap) : -1
						];
					}

				break;

				case Market\Export\Run\Manager::ENTITY_TYPE_CATEGORY:
					$entityType = 'ELEMENT';
					$entityFilter = [
						'SECTION_ID' => $entityIds,
						'INCLUDE_SUBSECTIONS' => 'Y'
					];
				break;

				case Market\Export\Run\Manager::ENTITY_TYPE_PROMO:
					/** @noinspection TypeUnsafeArraySearchInspection */
					if (isset($context['PROMO_ID']) && in_array($context['PROMO_ID'], (array)$entityIds))
					{
						$isNeedFull = true;
					}
				break;

				default: // unsupported change, need full refresh
					$isNeedFull = true;
				break;
			}

			if ($isNeedFull)
			{
				$changesFilter = [];
				break;
			}

			if (isset($entityType, $entityFilter))
			{
				if (!isset($changesFilter[$entityType]))
				{
					$changesFilter[$entityType] = [];
				}
				else if (count($changesFilter[$entityType]) === 1)
				{
					$changesFilter[$entityType]['LOGIC'] = 'OR';
				}

				$changesFilter[$entityType][] = $entityFilter;
			}
		}

		if (!$isNeedFull && empty($changesFilter))
		{
			$changesFilter = null;
		}

		return $changesFilter;
	}

	protected function queryCount($queryFilter, $queryContext, Market\Export\Run\Counter\Base $counter)
	{
		$countContext = $queryContext;
		$countContext['PAGE_SIZE'] = (int)(Market\Config::getOption('export_count_offer_page_size') ?: 100);
		$countContext['CATALOG_TYPE_COMPATIBILITY'] = $queryContext['HAS_OFFER'] && $this->isCatalogTypeCompatibility($queryContext);
		$countContext['USE_DISTINCT'] = !empty($queryFilter['DISTINCT']);

		return $counter->count($queryFilter, $countContext);
	}

	protected function queryTotalCount($queryFilter, $queryContext)
	{
		$hasOffers = isset($queryContext['OFFER_PROPERTY_ID']);
		$isOnlyOffers = !empty($queryContext['OFFER_ONLY']);
		$result = 0;

		// element count

		if (!$isOnlyOffers)
		{
			$elementFilter = $queryFilter['ELEMENT'];

			if ($hasOffers)
			{
				$catalogTypeFieldName = Market\Export\Entity\Catalog\Provider::useCatalogShortFields()
					? 'TYPE'
					: 'CATALOG_TYPE';

				$elementFilter['!' . $catalogTypeFieldName] = static::ELEMENT_TYPE_SKU;
			}

			$result += (int)\CIBlockElement::GetList([], $elementFilter, []);
		}

		// offers count

		if ($hasOffers)
		{
			$result += (int)\CIBlockElement::GetList([], $queryFilter['OFFERS'], []);
		}

		return $result;
	}

	protected function compileQueryFilters(
		Market\Export\Routine\QueryBuilder\Filter $filterBuilder,
		array $sourceFilter,
		array $sourceSelect,
		array $context,
		array $changesFilter = null
	)
	{
		$queryFilters = $filterBuilder->compile($sourceFilter, $sourceSelect, $context, $changesFilter);
		$queryFilters = $this->applyFewQueryFiltersModifications($queryFilters, $context);

		return $queryFilters;
	}

	protected function applyFewQueryFiltersModifications(array $queryFilters, array $filterContext)
	{
		foreach ($queryFilters as &$queryFilter)
		{
			$queryFilter = $this->applyQueryFilterModifications($queryFilter, $filterContext);
		}
		unset($queryFilter);

		return $queryFilters;
	}

	/**
	 * Добавляем фильтры по-умолчанию для бизнес-логики Битрикс
	 *
	 * @param $queryFilter
	 * @param $queryContext
	 *
	 * @return array
	 */
	protected function applyQueryFilterModifications($queryFilter, $queryContext)
	{
		$result = $queryFilter;
		$result = $this->modifyQueryFilterBySectionActive($result, $queryContext);

		return $result;
	}

	/**
	 * Добавляем фильтр по активности раздела
	 *
	 * @param $queryFilter
	 * @param $queryContext
	 *
	 * @return array
	 */
	protected function modifyQueryFilterBySectionActive($queryFilter, $queryContext)
	{
		if (
			isset($queryFilter['ELEMENT'])
			&& empty($queryContext['FILTER_MANUAL']['ELEMENT']['SECTION_ACTIVE'])
			&& !$this->hasQuerySectionFilter($queryFilter['ELEMENT'])
		)
		{
			if ($this->isIblockElementSectionRequired($queryContext))
			{
				$queryFilter['ELEMENT'][] = [
					'SECTION_GLOBAL_ACTIVE' => 'Y',
				];
			}
			else
			{

				$queryFilter['ELEMENT'][] = [
					'LOGIC' => 'OR',
					[ 'SECTION_ID' => 0 ],
					[ 'SECTION_GLOBAL_ACTIVE' => 'Y' ],
				];
			}
		}

		return $queryFilter;
	}

	/**
	 * Ищём фильтр по разделу
	 *
	 * @param $elementFilter
	 *
	 * @return bool
	 */
	protected function hasQuerySectionFilter($elementFilter)
	{
		$result = false;

		if (is_array($elementFilter))
		{
			foreach ($elementFilter as $fieldName => $filter)
			{
				if ($fieldName === 'SUBSECTION' || Market\Data\TextString::getPosition($fieldName, 'SECTION_') === 0)
				{
					$result = true;
				}
				else if (is_numeric($fieldName) && (!isset($filter['LOGIC']) || $filter['LOGIC'] !== 'OR'))
				{
					$result = $this->hasQuerySectionFilter($filter);
				}

				if ($result === true) { break; }
			}
		}

		return $result;
	}

	/**
	 * @param array $context
	 *
	 * @return bool
	 */
	protected function isIblockElementSectionRequired($context)
	{
		$iblockLink = $this->getContextIblockLink($context);
		$result = false;

		if ($iblockLink !== null)
		{
			$tagDescription = $iblockLink->getTagDescription('categoryId');

			$result = (
				isset($tagDescription['VALUE']['TYPE'], $tagDescription['VALUE']['FIELD'])
				&& $tagDescription['VALUE']['TYPE'] === Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD
				&& $tagDescription['VALUE']['FIELD'] === 'IBLOCK_SECTION_ID'
			);
		}

		return $result;
	}

	/**
	 * @param array $context
	 *
	 * @return Market\Export\IblockLink\Model|null
	 */
	protected function getContextIblockLink($context)
	{
		$result = null;

		if (isset($context['IBLOCK_LINK']))
		{
			$result = $context['IBLOCK_LINK'];
		}
		else if (isset($context['IBLOCK_LINK_ID']))
		{
			$result = $this->getSetup()->getIblockLinkCollection()->getItemById($context['IBLOCK_LINK_ID']);
		}

		return $result;
	}

	/**
	 * Фильтр по готовым элементам
	 *
	 * @param $queryContext array
	 * @param $isNeedFull bool
	 *
	 * @return array
	 */
	protected function getStorageReadyFilter($queryContext, $isNeedFull = false)
	{
		$filter = [
			'=SETUP_ID' => $queryContext['SETUP_ID']
		];

		if (isset($queryContext['IBLOCK_LINK_ID']))
		{
			$filter['=IBLOCK_LINK_ID'] = $queryContext['IBLOCK_LINK_ID'];
		}

		if (!$isNeedFull)
		{
			switch ($this->getRunAction())
			{
				case 'change':
				case 'refresh':
					$filter['>=TIMESTAMP_X'] = $this->getParameter('initTimeUTC');
				break;
			}
		}

		return $filter;
	}

	/**
	 * Генерируем список "Select по источникам" на основании описании тега
	 *
	 * @param $tagDescriptionList
	 *
	 * @return array
	 */
	protected function getSourceSelect($tagDescriptionList)
	{
		$result = [];
		$childKeys = [
			'ATTRIBUTES',
			'SETTINGS'
		];

		foreach ($tagDescriptionList as $tagSourceValue)
		{
			if (isset($tagSourceValue['VALUE']['TYPE'], $tagSourceValue['VALUE']['FIELD']))
			{
				$sourceType = $tagSourceValue['VALUE']['TYPE'];
				$sourceField = $tagSourceValue['VALUE']['FIELD'];

				if ($this->isSourceVirtual($sourceType)) { continue; }

				if (!isset($result[$sourceType]))
				{
					$result[$sourceType] = [];
				}

				if (!in_array($sourceField, $result[$sourceType], true))
				{
					$result[$sourceType][] = $sourceField;
				}
			}

			foreach ($childKeys as $childKey)
			{
				if (isset($tagSourceValue[$childKey]) && is_array($tagSourceValue[$childKey]))
				{
					foreach ($tagSourceValue[$childKey] as $attributeValueSource)
					{
						if (
							isset($attributeValueSource['TYPE'])
							&& $attributeValueSource['TYPE'] !== Market\Export\Entity\Manager::TYPE_TEXT
							&& !empty($attributeValueSource['FIELD'])
						)
						{
							$sourceType = $attributeValueSource['TYPE'];
							$sourceField = $attributeValueSource['FIELD'];

							if ($this->isSourceVirtual($sourceType)) { continue; }

							if (!isset($result[$sourceType]))
							{
								$result[$sourceType] = [];
							}

							if (!in_array($sourceField, $result[$sourceType], true))
							{
								$result[$sourceType][] = $sourceField;
							}
						}
					}
				}
			}

			if (!empty($tagSourceValue['CHILDREN']))
			{
				$childrenSelect = $this->getSourceSelect($tagSourceValue['CHILDREN']);

				foreach ($childrenSelect as $sourceType => $sourceFields)
				{
					if (!isset($result[$sourceType]))
					{
						$result[$sourceType] = $sourceFields;
						continue;
					}

					foreach ($sourceFields as $sourceField)
					{
						if (in_array($sourceField, $result[$sourceType], true)) { continue; }

						$result[$sourceType][] = $sourceField;
					}
				}
			}
		}

		return $result;
	}

	protected function isSourceVirtual($type)
	{
		return mb_strpos($type, 'VIRTUAL_') === 0;
	}

	/**
	 * Генерируем список "Фильтров по источникам" на основании настроек
	 *
	 * @param \Yandex\Market\Export\IblockLink\Model $iblockLink
	 * @param $isNeedAll bool|null
	 *
	 * @return array
	 */
	protected function getSourceFilterList(Market\Export\IblockLink\Model $iblockLink, $isNeedAll = null)
	{
		$result = [];
		$filterCollection = $iblockLink->getFilterCollection();
		$isFirstFilter = true;
		$commonContext = [
			'CAN_IGNORE_OFFER_SUBQUERY' => ($this->getName() === Market\Export\Run\Manager::STEP_OFFER),
		];

		/** @var \Yandex\Market\Export\Filter\Model $filterModel */
		foreach ($filterCollection as $filterModel)
		{
			$sourceFilter = $filterModel->getSourceFilter();
			$result[] = [
				'ID' => $filterModel->getInternalId(),
				'FILTER' => $sourceFilter,
				'CONTEXT' =>
					$filterModel->getContext(true)
					+ [ 'IGNORE_EXCLUDE' => $isFirstFilter ]
					+ $commonContext,
			];

			$isFirstFilter = false;
		}

		if ($isNeedAll === null)
		{
			$isNeedAll = $iblockLink->isExportAll();
		}

		if ($isNeedAll)
		{
			$result[] = [
				'ID' => null,
				'FILTER' => [],
				'CONTEXT' =>
					[ 'IGNORE_EXCLUDE' => $isFirstFilter ]
					+ $commonContext
			];
		}

		return $result;
	}

	/**
	 * Разбиваем результаты выборки по частям с учетом группировки предложений
	 *
	 * @param $elementList
	 * @param $limit
	 * @param $useDistinct
	 *
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	protected function chunkElementList($elementList, $limit, $useDistinct)
	{
		if ($useDistinct)
		{
			$result = [];
			$chunk = [];
			$chunkSize = 0;
			$parentElements = [];
			$parentElementsCount = 0;
			$currentParentId = null;

			// sort by PARENT_ID

			uasort($elementList, static function($aElement, $bElement) {
				$aParentId = isset($aElement['PARENT_ID']) ? $aElement['PARENT_ID'] : null;
				$bParentId = isset($bElement['PARENT_ID']) ? $bElement['PARENT_ID'] : null;

				if ($aParentId === $bParentId)
				{
					return $aElement['ID'] < $bElement['ID'] ? -1 : 1;
				}

				return $aParentId < $bParentId ? -1 : 1;
			});

			// build chunks

			foreach ($elementList as $elementId => $element)
			{
				$parentId = isset($element['PARENT_ID']) ? $element['PARENT_ID'] : null;

				if ($parentId === null)
				{
					$chunk[$elementId] = $element;
					++$chunkSize;

					if ($chunkSize >= $limit)
					{
						$result[] = $chunk;

						$chunk = [];
						$chunkSize = 0;
					}
				}
				else
				{
					if ($parentId !== $currentParentId)
					{
						if ($chunkSize + $parentElementsCount < $limit)
						{
							$chunk += $parentElements;
							$chunkSize += $parentElementsCount;
						}
						else if ($parentElementsCount > $chunkSize)
						{
							$result[] = $parentElements;
						}
						else
						{
							$result[] = $chunk;

							$chunk = $parentElements;
							$chunkSize = $parentElementsCount;
						}

						$parentElements = [];
						$parentElementsCount = 0;
						$currentParentId = $parentId;
					}

					$parentElements[$elementId] = $element;
					++$parentElementsCount;
				}
			}

			if ($parentElementsCount > 0)
			{
				if ($chunkSize + $parentElementsCount < $limit)
				{
					$chunk += $parentElements;
					$chunkSize += $parentElementsCount;
				}
				else if ($parentElementsCount > $chunkSize)
				{
					$result[] = $parentElements;
				}
				else
				{
					$result[] = $chunk;

					$chunk = $parentElements;
					$chunkSize = $parentElementsCount;
				}
			}

			if ($chunkSize > 0)
			{
				$result[] = $chunk;
			}
		}
		else
		{
			$result = array_chunk($elementList, $limit, true);
		}

		return $result;
	}

	/**
	 * Получаем значения из источников на основе результатов запроса к базе данных
	 *
	 * @param $sourceSelect
	 * @param $elementList
	 * @param $parentList
	 * @param $queryContext
	 *
	 * @return array
	 */
	protected function extractElementListValues($sourceSelect, $elementList, $parentList, $queryContext)
	{
		$sourceFetcher = new Market\Export\Routine\QueryBuilder\SourceFetcher();
		$result = $sourceFetcher->load($sourceSelect, $elementList, $parentList, $queryContext);

		$conflictList = $this->getProcessor()->getConflicts();

		foreach ($result as &$elementValues)
		{
			foreach ($conflictList as $sourceConflicts)
			{
				foreach ($sourceConflicts as $fieldName => $conflictAction)
				{
					if (isset($elementValues[$fieldName]))
					{
						$elementValues[$fieldName] = $this->applyValueConflict($elementValues[$fieldName], $conflictAction);
					}
				}
			}
		}
		unset($elementValues);

		return $result;
	}

	/**
	 * Получить источник данных для выгрузки
	 *
	 * @param $type
	 *
	 * @return \Yandex\Market\Export\Entity\Reference\Source
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	protected function getSource($type)
	{
		return Market\Export\Entity\Manager::getSource($type);
	}

	/**
	 * Поле CATALOG_TYPE содержит неверную информацию "Имеет ли товар торговые предложения"
	 */
	protected function isCatalogTypeCompatibility($context)
	{
		return Market\Export\Entity\Catalog\Provider::isCatalogTypeCompatibility($context);
	}

	/**
	 * Сортировка групп тегов по значениям
	 *
	 * @param array $rules
	 * @param array $elementList
	 * @param array $sourceValuesList
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array $context
	 *
	 * @return Market\Result\XmlValue[]
	 */
	protected function resolveDistinctGroups($rules, $elementList, $sourceValuesList, $tagValuesList, $context)
	{
		$elementGroups = $this->getElementGroups($elementList);
		$elementsWithGroup = $this->flattenElementGroups($elementGroups, 1);

		if (!empty($elementsWithGroup))
		{
			$elementsDistinctValues = $this->getDistinctValues($elementsWithGroup, $rules, $sourceValuesList, $tagValuesList, $context);
			$sortedGroups = $this->sortDistinctGroups($elementGroups, $elementsDistinctValues, $rules);

			$result = $this->applyDistinctGroups($tagValuesList, $sortedGroups);
		}
		else
		{
			$result = $tagValuesList;
		}

		return $result;
	}

	/**
	 * Группировка предложений по родительскому элементу
	 *
	 * @param array $elementList
	 *
	 * @return int[][]
	 */
	protected function getElementGroups($elementList)
	{
		$result = [];

		foreach ($elementList as $elementId => $element)
		{
			if (isset($element['PARENT_ID']))
			{
				$groupId = $element['PARENT_ID'];

				if (!isset($result[$groupId])) { $result[$groupId] = []; }

				$result[$groupId][] = $elementId;
			}
		}

		return $result;
	}

	/**
	 * Список элементов в группах, количество элементов в которых больше минимального количества
	 *
	 * @param int[][] $groups
	 * @param int $minimalGroupCount
	 *
	 * @return int[]
	 */
	protected function flattenElementGroups($groups, $minimalGroupCount = 0)
	{
		$result = [];

		foreach ($groups as $group)
		{
			if ($minimalGroupCount === 0 || count($group) > $minimalGroupCount)
			{
				foreach ($group as $elementId)
				{
					$result[] = $elementId;
				}
			}
		}

		return $result;
	}

	/**
	 * Получение значений тегов для сортировки
	 *
	 * @param int[] $elementIds
	 * @param array $rules
	 * @param array $sourceValuesList
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array $context
	 *
	 * @return array[]
	 */
	protected function getDistinctValues($elementIds, $rules, $sourceValuesList, $tagValuesList, $context)
	{
		$result = [];
		$exportTag = $this->getTag();

		foreach ($rules as $ruleIndex => $rule)
		{
			$ruleValues = array_fill_keys($elementIds, null);

			if (isset($rule['TAG']))
			{
				$useAttribute = false;
				$ruleNode = (
					$exportTag->getName() === $rule['TAG']
						? $exportTag
						: $exportTag->getChild($rule['TAG'])
				);

				if (isset($rule['ATTRIBUTE']))
				{
					$useAttribute = true;
					$ruleNode = $ruleNode !== null ? $ruleNode->getAttribute($rule['ATTRIBUTE']) : null;
				}

				foreach ($elementIds as $elementId)
				{
					$tagValues = $tagValuesList[$elementId];

					if ($useAttribute)
					{
						$ruleValue = $tagValues->getTagAttribute($rule['TAG'], $rule['ATTRIBUTE']);
					}
					else
					{
						$ruleValue = $tagValues->getTagValue($rule['TAG']);
					}

					if (!$this->isEmptyXmlValue($ruleValue))
					{
						if ($ruleNode === null)
						{
							$ruleValues[$elementId] = $ruleValue;
						}
						else if ($ruleNode->validate($ruleValue, $context))
						{
							$ruleValues[$elementId] = $ruleNode->compareValue($ruleValue, $context, $tagValues);
						}
					}
				}
			}
			else
			{
				foreach ($elementIds as $elementId)
				{
					if (!isset($sourceValuesList[$elementId][$rule['SOURCE']][$rule['FIELD']])) { continue; }

					$ruleValue = $sourceValuesList[$elementId][$rule['SOURCE']][$rule['FIELD']];

					if (!$this->isEmptyXmlValue($ruleValue))
					{
						$ruleValues[$elementId] = $ruleValue;
					}
				}
			}

			$result[$ruleIndex] = $ruleValues;
		}

		return $result;
	}

	/**
	 * Сортиворка групп
	 *
	 * @param int[][] $groups
	 * @param array[] $elementValues
	 * @param array $rules
	 *
	 * @return int[][]
	 */
	protected function sortDistinctGroups($groups, $elementValues, $rules)
	{
		$sortSigns = $this->getDistinctRulesSign($rules);

		foreach ($groups as &$group)
		{
			usort($group, static function($aElementId, $bElementId) use ($elementValues, $sortSigns)
			{
				foreach ($sortSigns as $ruleIndex => $sortSign)
				{
					$aValue = $elementValues[$ruleIndex][$aElementId];
					$bValue = $elementValues[$ruleIndex][$bElementId];

					if ($aValue !== $bValue)
					{
						if ($aValue === null)
						{
							$result = 1;
						}
						else if ($bValue === null)
						{
							$result = -1;
						}
						else
						{
							$result = ($aValue < $bValue ? -1 : 1) * $sortSign;
						}

						return $result;
					}
				}

				return $aElementId < $bElementId ? -1 : 1; // all equal
			});
		}
		unset($group);

		return $groups;
	}

	/**
	 * Знак сравнения для группировки тегов
	 *
	 * @param array $rules
	 *
	 * @return int[]
	 */
	protected function getDistinctRulesSign($rules)
	{
		$result = [];

		foreach ($rules as $ruleIndex => $rule)
		{
			if (Market\Data\TextString::toLower($rule['ORDER']) === 'desc')
			{
				$result[$ruleIndex] = -1;
			}
			else
			{
				$result[$ruleIndex] = 1;
			}
		}

		return $result;
	}

	/**
	 * Применение результатов группировки к тегам
	 *
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array $groups
	 *
	 * @return Market\Result\XmlValue[]
	 */
	protected function applyDistinctGroups($tagValuesList, $groups)
	{
		$priority = [];

		// apply distinct and fill priority

		foreach ($groups as $groupId => $group)
		{
			foreach ($group as $index => $elementId)
			{
				$tagValues = $tagValuesList[$elementId];

				$tagValues->setDistinct($groupId);
				$priority[$elementId] = $index;
			}
		}

		// sort tagValuesList

		uksort($tagValuesList, static function($aElementId, $bElementId) use ($priority)
		{
			$aPriority = isset($priority[$aElementId]) ? $priority[$aElementId] : 0;
			$bPriority = isset($priority[$bElementId]) ? $priority[$bElementId] : 0;

			if ($aPriority === $bPriority)
			{
				return $aElementId < $bElementId ? -1 : 1;
			}

			return $aPriority < $bPriority ? -1 : 1;
		});

		return $tagValuesList;
	}
}

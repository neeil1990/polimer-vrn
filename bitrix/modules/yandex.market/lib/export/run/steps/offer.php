<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Offer extends Base
{
	const ELEMENT_TYPE_PRODUCT = 1;
	const ELEMENT_TYPE_SET = 2;
	const ELEMENT_TYPE_SKU = 3;
	const ELEMENT_TYPE_OFFER = 4;
	const ELEMENT_TYPE_FREE_OFFER = 5;
	const ELEMENT_TYPE_EMPTY_SKU = 6;

	protected $queryExcludeFilterList = [];
	protected $isCatalogTypeCompatibility;
	protected $sourceCurrencyConversion = null;

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

	public function getTotalCount($isDisableCalculation = false)
	{
		if ($this->totalCount === null && !$isDisableCalculation)
		{
			$this->totalCount = 0;

			$iblockConfigList = $this->getIblockConfigList();
			$iblockConfigIndex = 0;

			foreach ($iblockConfigList as $iblockConfig)
			{
				if ($iblockConfig['EXPORT_ALL'])
				{
					$queryFilters = $this->makeQueryFilters([], [], $iblockConfig['CONTEXT']);

					foreach ($queryFilters as $queryFilter)
					{
						$this->totalCount += $this->queryTotalCount($queryFilter, $iblockConfig['CONTEXT']);
					}
				}
				else
				{
					$filterCountList = $this->getCount($iblockConfigIndex, false);

					$this->totalCount += $filterCountList->getSum();
				}

				$iblockConfigIndex++;
			}
		}

		return $this->totalCount;
	}

	public function getCount($offset = null, $isNeedAll = null)
	{
		$result = new Market\Result\StepCount();
		$offsetIblockConfigIndex = null;
		$offsetFilterIndex = null;

		if (isset($offset))
		{
			$offsetParts = explode(':', $offset);
			$offsetIblockConfigIndex = (int)$offsetParts[0];
			$offsetFilterIndex = isset($offsetParts[1]) ? (int)$offsetParts[1] : null;
		}

		$iblockConfigList = $this->getIblockConfigList($isNeedAll);
		$iblockConfigIndex = 0;

		foreach ($iblockConfigList as $iblockConfig)
		{
			if ($offsetIblockConfigIndex !== null && $offsetIblockConfigIndex !== $iblockConfigIndex) // is iblock out of offset
			{
				$iblockConfigIndex++;
				continue;
			}

			$counterManager = new Market\Export\Run\Counter\Manager();

			do
			{
				$isNeedRepeatCount = false;
				$counter = null;
				$sourceFilterIndex = 0;
				$previousFilterSum = 0;

				try
				{
					foreach ($iblockConfig['FILTER_LIST'] as $sourceFilter)
					{
						if ($offsetFilterIndex === null || $offsetFilterIndex >= $sourceFilterIndex) // is filter in offset or no offset
						{
							$filterContext = $sourceFilter['CONTEXT'] + $iblockConfig['CONTEXT'];

							$this->initializeFilterContext($filterContext, $sourceFilter['FILTER']);

							$queryFilters = $this->makeQueryFilters($sourceFilter['FILTER'], [], $filterContext);
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
						}

						$sourceFilterIndex++;
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

			$iblockConfigIndex++;
		}

		return $result;
	}

	/**
	 * Запускаем выгрузку
	 *
	 * @param string $action
	 * @param string|null $offset
	 *
	 * @return Market\Result\Step
	 *
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function run($action, $offset = null)
	{
		$result = new Market\Result\Step();

		$this->setRunAction($action);

		$iblockConfigList = $this->getIblockConfigList();
		$formatTag = $this->getTag();

		// calculate offset and total

		$offsetIblockConfigIndex = null;
		$offsetFilterIndex = null;
		$offsetQueryIndex = null;
		$offsetFilterShift = null;
		$totalFilterCount = 0;
		$iblockConfigWeightList = [];

		if ($offset !== null)
		{
			$offsetParts = explode(':', $offset);
			$offsetIblockConfigIndex = (int)$offsetParts[0];
			$offsetFilterIndex = isset($offsetParts[1]) ? (int)$offsetParts[1] : null;
			$offsetQueryIndex = isset($offsetParts[2]) ? (int)$offsetParts[2] : null;
			$offsetFilterShift = isset($offsetParts[3]) ? (int)$offsetParts[3] : null;

			if ($offsetFilterShift === null && $offsetQueryIndex > 2) // compatibility offset without multiple queries
			{
				$offsetFilterShift = $offsetQueryIndex;
				$offsetQueryIndex = 0;
			}
		}

		foreach ($iblockConfigList as $iblockConfig)
		{
			$iblockConfigWeight = count($iblockConfig['FILTER_LIST']);

			$iblockConfigWeightList[] = $iblockConfigWeight;
			$totalFilterCount += $iblockConfigWeight;
		}

		$result->setTotal($totalFilterCount);

		// run export

		$iblockConfigIndex = 0;
		$isTimeExpired = false;

		foreach ($iblockConfigList as $iblockConfig)
		{
			if ($offsetIblockConfigIndex !== null && $offsetIblockConfigIndex > $iblockConfigIndex) // is iblock out of offset
			{
				$result->increaseProgress($iblockConfigWeightList[$iblockConfigIndex]);
				$iblockConfigIndex++;
				continue;
			}

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

				if ($changesFilter === null) // changed other entity
				{
					$result->increaseProgress($iblockConfigWeightList[$iblockConfigIndex]);
					$iblockConfigIndex++;
					continue;
				}
			}

			$formatTag->extendTagDescriptionList($tagDescriptionList, $iblockContext);

			$sourceSelect = $this->getSourceSelect($tagDescriptionList);

			$this->sortSourceSelect($sourceSelect);
			$this->initializeQueryContext($iblockContext, $sourceSelect);
			$this->sortSourceSelect($sourceSelect);
			$this->applySelectMap($tagDescriptionList, $iblockContext);

			$querySelect = $this->makeQuerySelect($sourceSelect, $iblockContext);
			$sourceFilterIndex = 0;

			if ($hasIblockLimit && ($offsetFilterIndex !== null || $offsetFilterShift !== null))
			{
				$iblockReadyCount = $this->getSuccessCount($iblockContext);
				$isExceededIblockLimit = ($iblockReadyCount >= $iblockLimit);
			}

			foreach ($iblockConfig['FILTER_LIST'] as $filterConfig)
			{
				if ($isExceededIblockLimit)
				{
					$result->increaseProgress(1);
					$result->setOffset($iblockConfigIndex + 1);
					$sourceFilterIndex++;
					continue;
				}

				if ($offsetFilterIndex !== null && $offsetFilterIndex > $sourceFilterIndex)
				{
					$result->increaseProgress(1);
					$sourceFilterIndex++;
					continue;
				}

				$filterContext = $filterConfig['CONTEXT'] + $iblockContext;

				$this->initializeFilterContext($filterContext, $filterConfig['FILTER']);

				$queryFilters = $this->makeQueryFilters($filterConfig['FILTER'], $sourceSelect, $filterContext, $changesFilter);
				$queryFilterIndex = 0;
				$queryFilterCount = count($queryFilters);

				foreach ($queryFilters as $queryFilter)
				{
					if ($offsetQueryIndex !== null && $offsetQueryIndex > $queryFilterIndex)
					{
						$result->increaseProgress(1 / $queryFilterCount);
						$queryFilterIndex++;
						continue;
					}

					$filterResult = $this->exportIblockFilter(
						$queryFilter,
						$sourceSelect,
						$querySelect,
						$tagDescriptionList,
						$filterContext,
						$offsetFilterShift,
						$iblockLimit,
						$iblockReadyCount
					);

					$iblockReadyCount += $filterResult['SUCCESS_COUNT'];

					if ($filterResult['OFFSET'] !== null)
					{
						$isTimeExpired = true;

						$result->setOffset($iblockConfigIndex . ':' . $sourceFilterIndex . ':' . $queryFilterIndex . ':' . $filterResult['OFFSET']);
					}
					else if ($hasIblockLimit && $iblockReadyCount >= $iblockLimit)
					{
						$isExceededIblockLimit = true;

						$result->increaseProgress(1 / $queryFilterCount);
						$result->setOffset($iblockConfigIndex + 1);
					}
					else
					{
						$isTimeExpired = $this->getProcessor()->isTimeExpired();

						$result->increaseProgress(1 / $queryFilterCount);

						if ($queryFilterIndex + 1 < $queryFilterCount)
						{
							$result->setOffset($iblockConfigIndex . ':' . $sourceFilterIndex . ':' . ($queryFilterIndex + 1));
						}
						else
						{
							$result->setOffset($iblockConfigIndex . ':' . ($sourceFilterIndex + 1));
						}
					}

					if ($isTimeExpired) { break; }

					$offsetFilterShift = null;

					$queryFilterIndex++;
				}

				$this->releaseFilterContext($filterContext, $filterConfig['FILTER']);

				if ($isTimeExpired) { break; }

				$offsetQueryIndex = null;
				$offsetFilterShift = null;

				$sourceFilterIndex++;
			}

			$this->releaseQueryContext($iblockContext, $sourceSelect);

			if ($isExceededIblockLimit)
			{
				$isTimeExpired = $this->getProcessor()->isTimeExpired();
			}

			if ($isTimeExpired) { break; }

			$offsetFilterIndex = null;
			$offsetQueryIndex = null;
			$offsetFilterShift = null;

			$iblockConfigIndex++;
		}

		if ($this->getParameter('progressCount') === true)
		{
			$readyCount = $this->getReadyCount();

			$result->setReadyCount($readyCount);
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
		return true;
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
		$result = '';
		$xmlContents = $tagResult->getXmlContents();

		if ($xmlContents !== null)
		{
			if ($this->useHashCollision())
			{
				$tagType = $tagValues->getType();
				$tag = $this->getTag($tagType);
				$primaryName = $this->getTagPrimaryName($tag);

				$xmlContents = preg_replace('/^(<[^ ]+) ' . $primaryName . '="[^"]*?"/', '$1', $xmlContents); // remove id attr for check tag contents
				$xmlContents = preg_replace_callback('/(<url>.*?\?)(.*?)(#.*)?(<\/url>)/', static function($matches) {
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
				}, $xmlContents); // remove utm from url
			}

			$result = md5($xmlContents);
		}

		return $result;
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
		$result = [
			Market\Export\Run\Manager::ENTITY_TYPE_PROMO => true,
			Market\Export\Run\Manager::ENTITY_TYPE_GIFT => true,
		];

		if (!$this->hasSourceCurrencyConversion())
		{
			$result[Market\Export\Run\Manager::ENTITY_TYPE_CURRENCY] = true;
		}

		return $result;
	}

	/**
	 * Используется ли конвератция валюты
	 *
	 * @return bool
	 */
	protected function hasSourceCurrencyConversion()
	{
		if ($this->sourceCurrencyConversion === null)
		{
			$this->sourceCurrencyConversion = $this->findSourceCurrencyConversion();
		}

		return $this->sourceCurrencyConversion;
	}

	/**
	 * Проверяем источники для профиля на наличие конвертации валюты
	 *
	 * @return bool
	 */
	protected function findSourceCurrencyConversion()
	{
		$setup = $this->getSetup();
		$iblockLinkCollection = $setup->getIblockLinkCollection();
		$tags = [
			'price',
			'oldprice',
			'currencyId'
		];
		$result = false;

		/** @var Market\Export\IblockLink\Model $iblockLink */
		foreach ($iblockLinkCollection as $iblockLink)
		{
			foreach ($tags as $tagName)
			{
				$tagDescription = $iblockLink->getTagDescription($tagName);

				if (isset($tagDescription['VALUE']['TYPE'], $tagDescription['VALUE']['FIELD']))
				{
					$source = $this->getSource($tagDescription['VALUE']['TYPE']);

					if (
						method_exists($source, 'hasCurrencyConversion')
						&& $source->hasCurrencyConversion($tagDescription['VALUE']['FIELD'], $tagDescription['SETTINGS'])
					)
					{
						$result = true;
						break 2;
					}
				}
			}
		}

		return $result;
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
				'FILTER_LIST' => $this->getSourceFilterList($iblockLink, $iblockContext, $isNeedAll),
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
	 *
	 * @throws Main\ObjectNotFoundException
	 */
	protected function exportIblockFilter($queryFilter, $sourceSelect, $querySelect, $tagDescriptionList, $context, $queryOffset = null, $limit = null, $successCount = 0)
	{
		$queryDistinct = !empty($queryFilter['DISTINCT']) ? $queryFilter['DISTINCT'] : null;
		$useDistinct = ($queryDistinct !== null);
		$hasLimit = ($limit > 0);
		$sourceChunkSize = 500;
		$processChunkSize = ($hasLimit ? $limit : $sourceChunkSize);
		$result = [
			'OFFSET' => null,
			'SUCCESS_COUNT' => 0
		];

		$context['USE_DISTINCT'] = $useDistinct;
		$context['USE_PRIMARY_COLLISION'] = $this->resolvePrimaryCollision($tagDescriptionList, $context);

		do
		{
			$queryResult = $this->loadElements($queryFilter, $querySelect, $context, $queryOffset);
			$queryOffset = (int)$queryResult['OFFSET'];

			$this->processExportElementList($queryResult['ELEMENT'], $queryResult['PARENT'], $context);

			foreach ($this->chunkElementList($queryResult['ELEMENT'], $processChunkSize, $useDistinct) as $elementChunk)
			{
				$sourceValueList = null;

				// load source value

				foreach (array_chunk($elementChunk, $sourceChunkSize, true) as $elementsPart)
				{
					$partSourceValueList = $this->extractElementListValues($sourceSelect, $elementsPart, $queryResult['PARENT'], $context);

					if ($sourceValueList === null)
					{
						$sourceValueList = $partSourceValueList;
					}
					else
					{
						$sourceValueList += $partSourceValueList;
					}
				}

				// write

				$writeLimit = ($hasLimit ? $limit - $successCount : null);
				$tagValuesList = $this->buildTagValuesList($tagDescriptionList, $sourceValueList, $context);
				$writeData = [
					'PARENT_LIST' => $queryResult['PARENT'],
					'SOURCE_VALUE' => $sourceValueList,
				];

				$this->extendData($tagValuesList, $elementChunk, $context, $writeData);

				if ($useDistinct)
				{
					$tagValuesList = $this->resolveDistinctGroups($queryDistinct, $elementChunk, $sourceValueList, $tagValuesList, $context);
				}

				$writeResultList = $this->writeData($tagValuesList, $elementChunk, $context, $writeData, $writeLimit);

				foreach ($writeResultList as $writeResult)
				{
					if ($writeResult['STATUS'] === static::STORAGE_STATUS_SUCCESS)
					{
						$successCount++;
						$result['SUCCESS_COUNT']++;
					}
				}

				if ($hasLimit && $successCount >= $limit) { break; }
			}

			if ($hasLimit && $successCount >= $limit)
			{
				$result['OFFSET'] = null;
				break;
			}
			else if ($queryResult['HAS_NEXT'] && $this->getProcessor()->isTimeExpired())
			{
				$result['OFFSET'] = $queryOffset;
				break;
			}
		}
		while ($queryResult['HAS_NEXT']);

		return $result;
	}

	protected function processExportElementList(&$elementList, &$parentList, $context)
	{
		// nothing by default
	}

	protected function initializeQueryContext(&$iblockContext, &$sourceSelect)
	{
		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);

			$source->initializeQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}
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

	protected function releaseQueryContext($iblockContext, $sourceSelect)
	{
		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);

			$source->releaseQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}
	}

	protected function initializeFilterContext(&$filterContext, &$sourceFilter)
	{
		foreach ($sourceFilter as $sourceType => $sourceFields)
		{
			if (is_numeric($sourceType)) { continue; } // no support for logic filters

			$source = $this->getSource($sourceType);
			$source->initializeFilterContext($sourceFields, $filterContext, $sourceFilter);
		}
	}

	protected function releaseFilterContext($filterContext, $sourceFilter)
	{
		foreach ($sourceFilter as $sourceType => $sourceFields)
		{
			if (is_numeric($sourceType)) { continue; } // no support for logic filters

			$source = $this->getSource($sourceType);
			$source->releaseFilterContext($sourceFields, $filterContext, $sourceFilter);
		}
	}

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
			else if (isset($entityType) && isset($entityFilter))
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
		$countContext['PAGE_SIZE'] = (int)($this->getParameter('offerPageSize') ?: Market\Config::getOption('export_count_offer_page_size') ?: 100);
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

	/**
	 * Запрашиваем элементы из базы данных
	 *
	 * @param $queryFilter
	 * @param $querySelect
	 * @param $queryContext
	 * @param $offset
	 * @param $limit
	 *
	 * @return array
	 */
	protected function loadElements($queryFilter, $querySelect, $queryContext, $offset = 0, $limit = null)
	{
		if ($queryFilter['DIRECTION'] === 'OFFER')
		{
			$result = $this->loadElementsByOffer($queryFilter, $querySelect, $queryContext, $offset, $limit);
		}
		else
		{
			$result = $this->loadElementsBySelf($queryFilter, $querySelect, $queryContext, $offset, $limit);
		}

		return $result;
	}

	protected function loadElementsBySelf($queryFilter, $querySelect, $queryContext, $offset = 0, $limit = null)
	{
		$parentList = [];
		$hasOffers = isset($queryContext['OFFER_PROPERTY_ID']);

		// elements

		$pageSize = $this->getQueryElementListPageSize($queryContext, $limit);

		$elementFilter = $queryFilter['ELEMENT'];
		$elementFilter = $this->extendQueryElementListFilter($elementFilter, $queryContext, 'ELEMENT');

		$elementQueryResult = $this->queryElementList($elementFilter, $querySelect['ELEMENT'], $queryContext, $offset, $pageSize);
		$elementList = $elementQueryResult['ELEMENT'];

		// offers

		if ($hasOffers)
		{
			$isCatalogTypeCompatibility = $this->isCatalogTypeCompatibility($queryContext);
			$foundParents = [];

			if ($isCatalogTypeCompatibility)
			{
				$parentList = $elementList;
			}
			else
			{
				foreach ($elementList as $elementId => $element)
				{
					if ($this->getElementCatalogType($element, $queryContext) === static::ELEMENT_TYPE_SKU)
					{
						$parentList[$elementId] = $element;
					}
				}

				$elementList = array_diff_key($elementList, $parentList);
			}

			if (!empty($parentList))
			{
				$offerFilter = $queryFilter['OFFERS'];
				$offerFilter['=PROPERTY_' . $queryContext['OFFER_PROPERTY_ID']] = array_keys($parentList);
				$offerFilter = $this->extendQueryOfferListFilter($offerFilter, $queryContext, 'ELEMENT');

				$offerQueryResult = $this->queryOfferList($offerFilter, $querySelect['OFFERS'], $queryContext);

				$foundParents = array_column($offerQueryResult['ELEMENT'], 'ID', 'PARENT_ID');

				$offerList = $offerQueryResult['ELEMENT'];
				$offerList = $this->processQueryResultOfferList($offerList, $queryContext, 'ELEMENT');

				$elementList += $offerList;
			}

			foreach ($parentList as $parentId => $parent)
			{
				if (!isset($foundParents[$parentId]))
				{
					unset($parentList[$parentId]);
				}
				else if ($isCatalogTypeCompatibility)
				{
					if (isset($elementList[$parentId]))
					{
						unset($elementList[$parentId]);
					}
				}
			}
		}

		return [
			'ELEMENT' => $elementList,
			'PARENT' => $parentList,
			'OFFSET' => $elementQueryResult['OFFSET'],
			'HAS_NEXT' => $elementQueryResult['HAS_NEXT'],
		];
	}

	protected function loadElementsByOffer($queryFilter, $querySelect, $queryContext, $offset = 0, $limit = null)
	{
		$hasOffers = isset($queryContext['OFFER_PROPERTY_ID']);
		$elementList = [];
		$parentList = [];
		$hasNext = false;

		if ($hasOffers)
		{
			// offers

			$pageSize = $this->getQueryElementListPageSize($queryContext, $limit, 'OFFER');

			$offerFilter = $queryFilter['OFFERS'];
			$offerFilter = $this->extendQueryOfferListFilter($offerFilter, $queryContext, 'OFFER');

			$offerQueryResult = $this->queryOfferList($offerFilter, $querySelect['OFFERS'], $queryContext, $offset, $pageSize);

			$elementList = $offerQueryResult['ELEMENT'];
			$elementList = $this->processQueryResultOfferList($elementList, $queryContext, 'OFFER');

			$offset = $offerQueryResult['OFFSET'];
			$hasNext = $offerQueryResult['HAS_NEXT'];

			// parents

			$parentMap = array_column($elementList, 'ID', 'PARENT_ID');

			if (!empty($parentMap))
			{
				$elementFilter = $queryFilter['ELEMENT'];
				$elementFilter['=ID'] = array_keys($parentMap);
				$elementFilter = $this->extendQueryElementListFilter($elementFilter, $queryContext, 'OFFER');

				$elementQueryResult = $this->queryElementList($elementFilter, $querySelect['ELEMENT'], $queryContext);
				$parentList = $elementQueryResult['ELEMENT'];
			}

			// unset elements without parent

			$elementList = array_filter($elementList, static function($element) use ($parentList) {
				return isset($parentList[$element['PARENT_ID']]);
			});
		}

		return [
			'ELEMENT' => $elementList,
			'PARENT' => $parentList,
			'OFFSET' => $offset,
			'HAS_NEXT' => $hasNext,
		];
	}

	protected function queryElementList($filter, $select, $context, $offset = 0, $pageSize = null)
	{
		$elementList = [];
		$count = 0;

		if ($offset > 0)
		{
			$filter[] = [ '>ID' => $offset ];
		}

		if ($pageSize > 0)
		{
			$paging = [ 'nTopCount' => $pageSize ];
		}
		else
		{
			$paging = false;
		}

		$select[] = 'ID';

		$queryElementList = \CIBlockElement::GetList(
			[ 'ID' => 'ASC' ],
			$filter,
			false,
			$paging,
			$select
		);

		while ($element = $queryElementList->Fetch())
		{
			$elementList[$element['ID']] = $element;

			$offset = (int)$element['ID'];
			++$count;
		}

		return [
			'ELEMENT' => $elementList,
			'OFFSET' => $offset,
			'HAS_NEXT' => ($pageSize > 0 && $count >= $pageSize),
		];
	}

	protected function queryOfferList($filter, $select, $context, $offset = 0, $pageSize = null)
	{
		$offerList = [];
		$count = 0;

		$skuPropertyKey = 'PROPERTY_' . $context['OFFER_PROPERTY_ID'];
		$skuPropertyValueKey = $skuPropertyKey . '_VALUE';

		if ($offset > 0)
		{
			$filter[] = [ '>ID' => $offset ];
		}

		if ($pageSize > 0)
		{
			$paging = [ 'nTopCount' => $pageSize ];
		}
		else
		{
			$paging = false;
		}

		$select[] = $skuPropertyKey;
		$select[] = 'ID';

		$queryOfferList = \CIBlockElement::GetList(
			[ 'ID' => 'ASC' ],
			$filter,
			false,
			$paging,
			$select
		);

		while ($offer = $queryOfferList->Fetch())
		{
			$offerElementId = (int)$offer[$skuPropertyValueKey];

			if ($offerElementId > 0)
			{
				$offer['PARENT_ID'] = $offerElementId;

				$offerList[$offer['ID']] = $offer;
			}

			$offset = (int)$offer['ID'];
			++$count;
		}

		return [
			'ELEMENT' => $offerList,
			'OFFSET' => $offset,
			'HAS_NEXT' => ($pageSize > 0 && $count >= $pageSize),
		];
	}

	/**
	 * Количество элементов обрабатываемых за один шаг
	 *
	 * @param $context array
	 * @param $limit int|null
	 * @param $direction string
	 *
	 * @return int
	 */
	protected function getQueryElementListPageSize($context, $limit = null, $direction = 'ELEMENT')
	{
		if ($limit > 0)
		{
			$result = (int)$limit;
		}
		else
		{
			$parameter = (int)$this->getParameter('offerPageSize');
			$option = (int)Market\Config::getOption('export_run_offer_page_size');

			if ($parameter > 0)
			{
				$result = $parameter;
			}
			else if ($option > 0)
			{
				$result = $option;
			}
			else if ($direction === 'OFFER' || !$context['HAS_OFFER'])
			{
				$result = 100;
			}
			else
			{
				$result = 50;
			}
		}

		return $result;
	}

	/**
	 * @param array $filter
	 * @param array $context
	 * @param string $direction
	 *
	 * @return array
	 */
	protected function extendQueryElementListFilter($filter, $context, $direction = 'ELEMENT')
	{
		if (empty($context['IGNORE_EXCLUDE']))
		{
			if ($direction !== 'OFFER')
			{
				$filter[] = [
					'!ID' => $this->getQueryExcludeFilter($context)
				];
			}

			if ($context['HAS_OFFER'] && !empty($context['USE_DISTINCT']))
			{
				$filter[] = [
					'!ID' => $this->getQueryExcludeFilter($context, 'PARENT_ID')
				];
			}
		}

		return $filter;
	}

	/**
	 * @param array $filter
	 * @param array $context
	 * @param string $direction
	 *
	 * @return array
	 */
	protected function extendQueryOfferListFilter($filter, $context, $direction = 'ELEMENT')
	{
		if (
			empty($context['IGNORE_EXCLUDE'])
			&& !$this->isDelayedExcludeQueryOffers($context, $direction)
		)
		{
			$filter[] = [
				'!ID' => $this->getQueryExcludeFilter($context)
			];
		}

		return $filter;
	}

	/**
	 * @param array<int, array> $offerList
	 * @param array $context
	 * @param string $direction
	 *
	 * @return array<int, array>
	 */
	protected function processQueryResultOfferList($offerList, $context, $direction = 'ELEMENT')
	{
		if (
			!empty($offerList)
			&& empty($context['IGNORE_EXCLUDE'])
			&& $this->isDelayedExcludeQueryOffers($context, $direction)
		)
		{
			$storageDataClass = $this->getStorageDataClass();
			$storageReadyFilter = $this->getStorageReadyFilter($context);
			$offerIds = array_keys($offerList);

			foreach (array_chunk($offerIds, 500) as $offerIdsChunk)
			{
				$storageReadyFilter['@ELEMENT_ID'] = $offerIdsChunk;

				$queryReadyOffers = $storageDataClass::getList([
					'filter' => $storageReadyFilter,
					'select' => [ 'ELEMENT_ID' ]
				]);

				while ($readyOffer = $queryReadyOffers->fetch())
				{
					if (isset($offerList[$readyOffer['ELEMENT_ID']]))
					{
						unset($offerList[$readyOffer['ELEMENT_ID']]);
					}
				}
			}
		}

		return $offerList;
	}

	/**
	 * Отложенное исключение торговых предложений
	 *
	 * @param $context
	 * @param $direction
	 *
	 * @return bool
	 */
	protected function isDelayedExcludeQueryOffers($context, $direction)
	{
		return $direction !== 'OFFER' && $this->isCatalogTypeCompatibility($context);
	}

	/**
	 * Фильтры запросов по фильтру источников
	 *
	 * @param $sourceFilterList array
	 * @param $sourceSelectList array
	 * @param $queryContext array
	 * @param $changesFilter array|null
	 *
	 * @return array[]
	 */
	protected function makeQueryFilters($sourceFilterList, $sourceSelectList, $queryContext, $changesFilter = null)
	{
		$queryFilter = $this->convertSourceFilterToQuery($sourceFilterList, $sourceSelectList);

		if ($this->isNeedSplitQueryFilter($queryFilter, $queryContext))
		{
			$result = [
				$this->buildQueryFilter($queryFilter, $queryContext, $changesFilter, 'ELEMENT'),
				$this->buildQueryFilter($queryFilter, $queryContext, $changesFilter, 'OFFERS'),
			];
		}
		else
		{
			$result = [
				$this->buildQueryFilter($queryFilter, $queryContext, $changesFilter),
			];
		}

		return $result;
	}

	/**
	 * Нужно ли разбить запрос по товарам и предложениям отдельно
	 *
	 * @param $queryFilter array
	 * @param $context array
	 *
	 * @return bool
	 */
	protected function isNeedSplitQueryFilter($queryFilter, $context)
	{
		return (
			!empty($queryFilter['CATALOG'])
			&& empty($queryFilter['OFFERS'])
			&& $context['HAS_OFFER']
			&& !$context['OFFER_ONLY']
			&& !$this->canQueryCatalogFilterMerge($queryFilter['CATALOG'])
			&& !$this->isCatalogTypeCompatibility($context)
		);
	}

	/**
	 * Формируем фильтры для запросов
	 *
	 * @param $queryFilter array
	 * @param $queryContext array
	 * @param $changesFilter array|null
	 * @param $catalogBehavior string|null
	 *
	 * @return array
	 */
	protected function buildQueryFilter($queryFilter, $queryContext, $changesFilter = null, $catalogBehavior = null)
	{
		$isOfferSubQueryInitialized = false;
		$result = $queryFilter + [
			'ELEMENT' => [],
			'OFFERS' => [],
		];

		// catalog filter

		if (!empty($result['CATALOG']))
		{
			if (!$queryContext['HAS_OFFER']) // hasn't offers
			{
				$result['ELEMENT'][] = $result['CATALOG'];
			}
			else if (!empty($result['OFFERS'])) // has required offers
			{
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else if (!empty($queryContext['OFFER_ONLY']))
			{
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else if ($this->canQueryCatalogFilterMerge($result['CATALOG']))
			{
				$isOfferSubQueryInitialized = true;
				$result['ELEMENT'][] = $result['CATALOG'];
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else if ($catalogBehavior === 'ELEMENT')
			{
				$typeField = Market\Export\Entity\Catalog\Provider::useCatalogShortFields() ? 'TYPE' : 'CATALOG_TYPE';

				$result['ELEMENT']['!=' . $typeField] = static::ELEMENT_TYPE_SKU;
				$result['ELEMENT'][] = $result['CATALOG'];
			}
			else if ($catalogBehavior === 'OFFERS')
			{
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else
			{
				// element match catalog condition or has offers match condition

				$isOfferSubQueryInitialized = true;
				$catalogOfferFilter = $this->getFilterDefaults('OFFERS', $queryContext['OFFER_IBLOCK_ID'], $queryContext);
				$catalogOfferFilter[] = $result['CATALOG'];

				$result['ELEMENT'][] = [
					'LOGIC' => 'OR',
					$result['CATALOG'],
					[
						'ID' => \CIBlockElement::SubQuery(
							'PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'],
							$catalogOfferFilter
						),
					]
				];

				// filter offers by catalog rules

				$result['OFFERS'][] = $result['CATALOG'];
			}
		}

		// offer subquery for elements

		if ($queryContext['HAS_OFFER'] && !empty($result['OFFERS']) && empty($result['DISTINCT']) && empty($result['ELEMENT']))
		{
			$result['DIRECTION'] = 'OFFER';
		}
		else
		{
			$result['DIRECTION'] = 'ELEMENT';
		}

		// extend by changes filter

		if (!empty($changesFilter))
		{
			$result = $this->appendQueryFilterChanges($result, $changesFilter, $queryContext);
		}

		// init defaults

		$result['ELEMENT'] = array_merge(
			$this->getFilterDefaults('ELEMENT', $queryContext['IBLOCK_ID'], $queryContext),
			$result['ELEMENT']
		);

		if ($queryContext['HAS_OFFER'])
		{
			$hasOfferUserFilter = !empty($result['OFFERS']);

			$result['OFFERS'] = array_merge(
				$this->getFilterDefaults('OFFERS', $queryContext['OFFER_IBLOCK_ID'], $queryContext),
				$result['OFFERS']
			);

			if (
				$hasOfferUserFilter
				&& !$isOfferSubQueryInitialized
				&& $result['DIRECTION'] !== 'OFFER'
				&& !$this->isIgnoreOfferSubQuery($queryContext)
			)
			{
				$result['ELEMENT'][] = [
					'ID' => \CIBlockElement::SubQuery(
						'PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'],
						$result['OFFERS']
					),
				];
			}
		}

		// distinct

		if (!empty($result['DISTINCT']))
		{
			$result['DISTINCT'] = call_user_func_array('array_merge', $result['DISTINCT']);
		}

		$result = $this->applyQueryFilterModifications($result, $queryContext);

		return $result;
	}

	protected function convertSourceFilterToQuery($sourceFilterList, $sourceSelectList, $isChild = false)
	{
		$result = [];
		$logic = null;

		foreach ($sourceFilterList as $sourceName => $sourceFilter)
		{
			$queryFilter = null;

			if ($sourceName === 'LOGIC')
			{
				$logic = (string)$sourceFilter;
			}
			else if (is_numeric($sourceName))
			{
				$queryFilter = $this->convertSourceFilterToQuery($sourceFilter, $sourceSelectList, true);
			}
			else
			{
				$source = $this->getSource($sourceName);

				if ($source->isFilterable())
				{
					$sourceSelect = isset($sourceSelectList[$sourceName]) ? $sourceSelectList[$sourceName] : [];

					$queryFilter = $source->getQueryFilter($sourceFilter, $sourceSelect);
				}
			}

			if ($queryFilter !== null)
			{
				foreach ($queryFilter as $chainType => $filter)
				{
					if (!empty($filter))
					{
						if (isset($result[$chainType]))
						{
							// nothing
						}
						else if ($isChild)
						{
							$result[$chainType] = ($logic !== null ? [ 'LOGIC' => $logic ] : []);
						}
						else
						{
							$result[$chainType] = [];
						}

						$result[$chainType][] = $filter;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Добавляем фильтр по изменениям
	 *
	 * @param $resultFilter array<string, array>
	 * @param $changesFilter array<string, array>
	 * @param $queryContext array
	 *
	 * @return array<string, array>
	 */
	protected function appendQueryFilterChanges($resultFilter, $changesFilter, $queryContext)
	{
		foreach ($changesFilter as $entityType => $entityFilter)
		{
			// modify by direction

			if ($entityType === 'ELEMENT' && $resultFilter['DIRECTION'] === 'OFFER')
			{
				$entityOperations = $this->extractFilterOperations($entityFilter);

				if (count($entityOperations) === 1 && $entityOperations[0]['FIELD'] === 'ID')
				{
					$skuPropertyCompare = ($entityOperations[0]['PREFIX'] !== '' ? $entityOperations[0]['PREFIX'] : '=');
					$skuPropertyKey = 'PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'];

					$entityType = 'OFFERS';
					$entityFilter = [
						$skuPropertyCompare . $skuPropertyKey => $entityOperations[0]['VALUE'],
					];
				}
				else
				{
					$resultFilter['DIRECTION'] = 'ELEMENT';
				}
			}

			// push filter

			if (!isset($resultFilter[$entityType]))
			{
				$resultFilter[$entityType] = [];
			}

			$resultFilter[$entityType][] = $entityFilter;
		}

		return $resultFilter;
	}

	/**
	 * Список операций, которые используются в фильтре
	 *
	 * @param $filter array
	 *
	 * @return array{FIELD: string, PREFIX: string, OPERATION: string, VALUE: mixed}[]
	 */
	protected function extractFilterOperations($filter)
	{
		$result = [];

		if (is_array($filter))
		{
			foreach ($filter as $fieldName => $filterValue)
			{
				if (is_numeric($fieldName))
				{
					$operations = $this->extractFilterOperations($filterValue);

					foreach ($operations as $operation)
					{
						$result[] = $operation;
					}
				}
				else
				{
					$operation = \CIBlock::MkOperationFilter($fieldName);
					$operation['VALUE'] = $filterValue;

					if (!isset($operation['PREFIX']))
					{
						$fieldNameLength = Market\Data\TextString::getLength($fieldName);
						$operationFieldLength = Market\Data\TextString::getLength($operation['FIELD']);
						$operationPrefixLength = $fieldNameLength - $operationFieldLength;

						$operation['PREFIX'] = Market\Data\TextString::getSubstring($fieldName, 0, $operationPrefixLength);
					}

					$result[] = $operation;
				}
			}
		}

		return $result;
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
		$result = $this->prioritizeQueryFilters($result, $queryContext);

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
	 * Приоритизация для группы запросов
	 *
	 * @param $filter
	 * @param $context
	 *
	 * @return array
	 */
	protected function prioritizeQueryFilters($filter, $context)
	{
		$keys = [
			'ELEMENT',
			'OFFERS',
		];

		foreach ($keys as $key)
		{
			if (!isset($filter[$key])) { continue; }

			$filter[$key] = $this->prioritizeFilter($filter[$key], $context);
		}

		return $filter;
	}

	/**
	 * Переносим базовые параметры запроса в корень для упрощения запроса
	 *
	 * @param $filter
	 * @param $context
	 *
	 * @return array
	 */
	protected function prioritizeFilter($filter, $context)
	{
		$priorityMap = [
			'IBLOCK_ID' => 0,
			'ACTIVE' => 1,
			'ACTIVE_DATE' => 1,
			'SUBSECTION' => 2,
			'SECTION_ID' => 2,
			'SECTION_ACTIVE' => 2,
			'SECTION_GLOBAL_ACTIVE' => 2,
			'SECTION_SCOPE' => 2,
			'IBLOCK_SECTION_ID' => 2,
			'INCLUDE_SUBSECTIONS' => 2,
		];
		$priorityChain = [];
		$hasConflicts = false;
		$result = $filter;

		foreach ($filter as $firstKey => $firstLevel)
		{
			$isLevelVirtual = false;

			if (!is_numeric($firstKey))
			{
				$isLevelVirtual = true;
				$firstLevel = [
					$firstKey => $firstLevel,
				];
			}

			if (!is_array($firstLevel) || (isset($firstLevel['LOGIC']) && $firstLevel['LOGIC'] !== 'AND')) { continue; }

			foreach ($firstLevel as $secondKey => $secondLevel)
			{
				$operation = \CIBlock::MkOperationFilter($secondKey);

				if (!isset($priorityMap[$operation['FIELD']])) { continue; }

				$priority = $priorityMap[$operation['FIELD']];

				if (isset($priorityChain[$priority][$secondKey]))
				{
					$hasConflicts = true;
					break;
				}

				if (!isset($priorityChain[$priority])) { $priorityChain[$priority] = []; }

				$priorityChain[$priority][$secondKey] = $secondLevel;

				if (!$isLevelVirtual)
				{
					unset($result[$firstKey][$secondKey]);

					if (empty($result[$firstKey]))
					{
						unset($result[$firstKey]);
					}
				}
			}

			if ($hasConflicts) { break; }
		}

		if ($hasConflicts)
		{
			$result = $filter;
		}
		else if (!empty($priorityChain))
		{
			ksort($priorityChain);

			$priorityFilter = array_merge(...$priorityChain);

			$result = array_merge($priorityFilter, $result);
		}

		return $result;
	}

	/**
	 * Можно ли фильтровать по данным каталога без subfilter (пример, =AVAILABLE => Y).
	 *
	 * @param $catalogFilter
	 *
	 * @return bool
	 */
	protected function canQueryCatalogFilterMerge($catalogFilter)
	{
		$result = false;

		if (is_array($catalogFilter) && Market\Export\Entity\Catalog\Provider::useSkuAvailableCalculation())
		{
			$result = true;
			$availableFieldName = Market\Export\Entity\Catalog\Provider::useCatalogShortFields()
				? '=AVAILABLE'
				: '=CATALOG_AVAILABLE';

			foreach ($catalogFilter as $partName => $part)
			{
				$canMerge = false;

				if (!is_array($part))
				{
					$canMerge = ($partName === $availableFieldName && $part === 'Y');
				}
				else if (count($part) === 1)
				{
					$canMerge = (isset($part[$availableFieldName]) && $part[$availableFieldName] === 'Y');
				}

				if (!$canMerge)
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Исключаем уже выгруженные элементы
	 *
	 * @param $queryContext
	 * @param $field
	 *
	 * @return Market\Export\Run\Helper\ExcludeFilter
	 */
	protected function getQueryExcludeFilter($queryContext, $field = 'ELEMENT_ID')
	{
		$primary = $this->getQueryExcludeFilterPrimary($queryContext);
		$cacheKey = $primary . ':' . $field;

		if (!isset($this->queryExcludeFilterList[$cacheKey]))
		{
			$this->queryExcludeFilterList[$cacheKey] = new Market\Export\Run\Helper\ExcludeFilter(
				$this->getStorageDataClass(),
				$field,
				$this->getStorageReadyFilter($queryContext)
			);
		}

		return $this->queryExcludeFilterList[$cacheKey];
	}

	/**
	 * Ключ для фильтра исключения
	 *
	 * @param $queryContext
	 *
	 * @return int
	 */
	protected function getQueryExcludeFilterPrimary($queryContext)
	{
		return (int)$queryContext['IBLOCK_LINK_ID'];
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
					$filter['>=TIMESTAMP_X'] = $this->getParameter('initTime');
				break;
			}
		}

		return $filter;
	}

	/**
	 * Формируем select для запросов
	 *
	 * @param $sourceSelect
	 * @param $context
	 *
	 * @return array
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	protected function makeQuerySelect($sourceSelect, $context)
	{
		$result = [
			'ELEMENT' => $this->getSelectDefaults('ELEMENT', $context),
			'OFFERS' => $this->getSelectDefaults('OFFERS', $context)
		];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);
			$querySelect = $source->getQuerySelect($sourceFields);

			foreach ($querySelect as $chainType => $fields)
			{
				if (!empty($fields))
				{
					if (!isset($result[$chainType]))
					{
						$result[$chainType] = [];
					}

					foreach ($fields as $field)
					{
						if (!in_array($field, $result[$chainType]))
						{
							$result[$chainType][] = $field;
						}
					}
				}
			}
		}

		if (empty($result['CATALOG']))
		{
			// nothing
		}
		else if (!empty($context['OFFER_ONLY']))
		{
			$result['OFFERS'] = array_merge($result['OFFERS'], $result['CATALOG']);
		}
		else
		{
			$result['ELEMENT'] = array_merge($result['ELEMENT'], $result['CATALOG']);
			$result['OFFERS'] = array_merge($result['OFFERS'], $result['CATALOG']);
		}

		return $result;
	}

	/**
	 * Определяем тип элемента инфоблока для инфоблока
	 *
	 * @param $element
	 * @param $context
	 *
	 * @return int
	 */
	protected function getElementCatalogType($element, $context)
	{
		$result = static::ELEMENT_TYPE_PRODUCT;

		if (!empty($context['OFFER_ONLY']))
		{
			$result = static::ELEMENT_TYPE_SKU;
		}
		else if (isset($element['TYPE']))
		{
			$result = (int)$element['TYPE'];
		}
		else if (isset($element['CATALOG_TYPE']))
		{
			$result = (int)$element['CATALOG_TYPE'];
		}
		else if (
			(array_key_exists('CATALOG_TYPE', $element) || array_key_exists('TYPE', $element))
			&& !empty($context['OFFER_IBLOCK_ID'])
		)
		{
			$result = static::ELEMENT_TYPE_SKU;
		}

		return $result;
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

		foreach ($tagDescriptionList as $tagName => $tagSourceValue)
		{
			if (isset($tagSourceValue['VALUE']['TYPE']) && isset($tagSourceValue['VALUE']['FIELD']))
			{
				$sourceType = $tagSourceValue['VALUE']['TYPE'];
				$sourceField = $tagSourceValue['VALUE']['FIELD'];

				if (!isset($result[$sourceType]))
				{
					$result[$sourceType] = [];
				}

				if (!in_array($sourceField, $result[$sourceType]))
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
				}
			}
		}

		return $result;
	}

	protected function sortSourceSelect(&$sourceSelect)
	{
		$order = [];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);
			$order[$sourceType] = $source->getOrder();
		}

		uksort($sourceSelect, function($aType, $bType) use ($order) {
			$aOrder = $order[$aType];
			$bOrder = $order[$bType];

			if ($aOrder === $bOrder) { return 0; }

			return ($aOrder < $bOrder ? -1 : 1);
		});
	}

	/**
	 * Генерируем список "Фильтров по источникам" на основании настроек
	 *
	 * @param \Yandex\Market\Export\IblockLink\Model $iblockLink
	 * @param $iblockContext array
	 * @param $isNeedAll bool|null
	 *
	 * @return array
	 */
	protected function getSourceFilterList(Market\Export\IblockLink\Model $iblockLink, $iblockContext, $isNeedAll = null)
	{
		$result = [];
		$filterCollection = $iblockLink->getFilterCollection();
		$isFirstFilter = true;

		/** @var \Yandex\Market\Export\Filter\Model $filterModel */
		foreach ($filterCollection as $filterModel)
		{
			$sourceFilter = $filterModel->getSourceFilter();
			$result[] = [
				'ID' => $filterModel->getInternalId(),
				'FILTER' => $sourceFilter,
				'CONTEXT' => $filterModel->getContext(true) + [ 'IGNORE_EXCLUDE' => $isFirstFilter ]
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
				'CONTEXT' => [ 'IGNORE_EXCLUDE' => $isFirstFilter ]
			];
		}

		return $result;
	}

	/**
	 * Поля для запроса по умолчанию
	 *
	 * @param $entityType
	 * @param $context
	 *
	 * @return array
	 */
	protected function getSelectDefaults($entityType, $context)
	{
		switch ($entityType)
		{
			case 'ELEMENT':
				$result = [ 'IBLOCK_ID',  'ID' ];

				if (
					isset($context['OFFER_IBLOCK_ID']) // has offers
					&& empty($context['OFFER_ONLY']) // has not only offers
					&& !$this->isCatalogTypeCompatibility($context) // is valid catalog_type
				)
				{
					$result[] = Market\Export\Entity\Catalog\Provider::useCatalogShortFields()
						? 'TYPE'
						: 'CATALOG_TYPE';
				}
			break;

			case 'OFFERS':
				$result = [ 'IBLOCK_ID', 'ID' ];
			break;

			default:
				$result = [];
			break;
		}

		return $result;
	}

	/**
	 * Фильтр для запроса по умолчанию
	 *
	 * @param $entityType string
	 * @param $iblockId int
	 * @param $context array|null
	 *
	 * @return array
	 */
	protected function getFilterDefaults($entityType, $iblockId, array $context = null)
	{
		switch ($entityType)
		{
			case 'ELEMENT':
			case 'OFFERS':
				$result = [
					'IBLOCK_ID' => $iblockId,
				];

				if (empty($context['FILTER_MANUAL'][$entityType]['ACTIVE']))
				{
					$result += [
						'ACTIVE' => 'Y',
						'ACTIVE_DATE' => 'Y',
					];
				}
			break;

			default:
				$result = [];
			break;
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
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	protected function extractElementListValues($sourceSelect, $elementList, $parentList, $queryContext)
	{
		$result = [];
		$conflictList = $this->getProcessor()->getConflicts();

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);
			$sourceValues = $source->getElementListValues($elementList, $parentList, $sourceFields, $queryContext, $result);
			$sourceConflicts = (isset($conflictList[$sourceType]) ? $conflictList[$sourceType] : null);

			foreach ($sourceValues as $elementId => $elementValues)
			{
				if (!isset($result[$elementId]))
				{
					$result[$elementId] = [];
				}

				if ($sourceConflicts !== null)
				{
					foreach ($sourceConflicts as $fieldName => $conflictAction)
					{
						if (isset($elementValues[$fieldName]))
						{
							$elementValues[$fieldName] = $this->applyValueConflict($elementValues[$fieldName], $conflictAction);
						}
					}
				}

				$result[$elementId][$sourceType] = $elementValues;
			}
		}

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
	 *
	 * @param $context
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	protected function isCatalogTypeCompatibility($context)
	{
		$result = false;

		if (!isset($context['OFFER_IBLOCK_ID'])) // hasn't offers
		{
			$result = false;
		}
		else if (!empty($context['OFFER_ONLY'])) // has only offers
		{
			$result = false;
		}
		else if ($this->isCatalogTypeCompatibility !== null) // already fetched
		{
			$result = $this->isCatalogTypeCompatibility;
		}
		else
		{
			$this->isCatalogTypeCompatibility = Market\Export\Entity\Catalog\Provider::useCatalogTypeCompatibility();
		}

		return $result;
	}

	/**
	 * Обрабатывать все элементы, вне зависимости от фильтра по торговым предложениям
	 *
	 * @param $context
	 *
	 * @return bool
	 */
	protected function isIgnoreOfferSubQuery($context)
	{
		return (
			$this->getName() === Market\Export\Run\Manager::STEP_OFFER
			&& Market\Config::getOption('export_offer_process_all_elements', 'N') === 'Y'
			&& !$this->isCatalogTypeCompatibility($context)
		);
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
	 * @return mixed[][]
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
	 * @param mixed[][] $elementValues
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

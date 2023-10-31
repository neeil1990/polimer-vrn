<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class Base implements Market\Data\Run\Step
{
	const STORAGE_STATUS_FAIL = 1;
	const STORAGE_STATUS_SUCCESS = 2;
	const STORAGE_STATUS_DUPLICATE = 4;
	const STORAGE_STATUS_DELETE = 5;

	/** @var Market\Export\Run\Processor */
	protected $processor;
	/** @var Market\Export\Xml\Tag\Base */
	protected $tag;
	/** @var Market\Export\Xml\Tag\Base[] */
	protected $typedTagList = [];
	/** @var string|null */
	protected $tagParentName;
	/** @var array */
	protected $tagPath;
	/** @var string */
	protected $runAction;
	/** @var bool */
	protected $useTagPrimary;

	public static function getStorageStatusTitle($status)
	{
		return Market\Config::getLang('EXPORT_RUN_STEP_STORAGE_STATUS_' . $status);
	}

	public function __construct(Market\Export\Run\Processor $processor)
	{
		$this->processor = $processor;
	}

	/**
	 * Название шага для событий
	 *
	 * @return mixed
	 */
	abstract public function getName();

	/**
	 * Не работает с файлом выгрузки
	 *
	 * @return bool
	 */
	public function isVirtual()
	{
		return false;
	}

	/**
	 * Очищаем лог и хранилище шага полностью
	 *
	 * @param $isStrict bool
	 */
	public function clear($isStrict = false)
	{
		$context = $this->getContext();

		$this->clearDataLog($context);
		$this->clearDataStorage($context);
	}

	public function getReadyCount()
	{
		return null;
	}

	/**
	 * Установить текущий режим выгрузки
	 *
	 * @param $action
	 */
	protected function setRunAction($action)
	{
		$this->runAction = $action;
	}

	/**
	 * Текущий режим выгрузки
	 *
	 * @return string
	 */
	public function getRunAction()
	{
		return $this->runAction;
	}

	/**
	 * Необходимо ли запускать процесс
	 *
	 * @param $action
	 *
	 * @return bool
	 */
	public function validateAction($action)
	{
		$result = true;

		if (!$this->isSupported())
		{
			$result = false;
		}
		else
		{
			$initTime = $this->getParameter('initTime');
			$isValidInitTime = ($initTime instanceof Main\Type\Date);

			switch ($action)
			{
				case 'change':
					$changes = $this->getChanges();
					$result = ($isValidInitTime && !empty($changes));
				break;

				case 'refresh':
					$result = $isValidInitTime;
				break;
			}
		}

		return $result;
	}

	/**
	 * Поддерживается ли шаг
	 *
	 * @return bool
	 */
	protected function isSupported()
	{
		return ($this->getTag() !== null);
	}

	/**
	 * Запускаем шаг
	 *
	 * @param $action
	 * @param $offset
	 *
	 * @return Market\Result\Step
	 */
	abstract public function run($action, $offset = null);

	/**
	 * Записываем данные шага
	 *
	 * @param $tagValuesList Market\Result\XmlValue[]
	 * @param $elementList array
	 * @param $context array
	 * @param $data array|null
	 * @param $limit int|null
	 *
	 * @return array
	 */
	protected function writeData($tagValuesList, $elementList, array $context = [], array $data = null, $limit = null)
	{
		$tagResultList = $this->buildTagList($tagValuesList, $context);

		$this->writeDataUserEvent($tagResultList, $elementList, $context, $data);

		$storageResultList = $this->writeDataStorage($tagResultList, $tagValuesList, $elementList, $context, $data, $limit);

		if (!$this->isVirtual())
		{
			$this->writeDataFile($storageResultList, $context);
		}

		$this->writeDataLog($tagResultList, $context);

		return $storageResultList;
	}

	/**
	 * Расширяем данные шага через $tagValuesList
	 *
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array                    $elementList
	 * @param array                    $context
	 * @param array|null               $data
	 */
	protected function extendData($tagValuesList, $elementList, array $context = [], array $data = null)
	{
		$this->extendDataUserEvent($tagValuesList, $elementList, $context, $data);
	}

	/**
	 * Пользовательское событие для расширения через $tagValuesList
	 *
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array                    $elementList
	 * @param array                    $context
	 * @param array|null               $data
	 */
	protected function extendDataUserEvent($tagValuesList, $elementList, array $context = [], array $data = null)
	{
		$stepName = $this->getName();
		$moduleName = Market\Config::getModuleName();
		$eventName = 'onExport' . ucfirst($stepName) . 'ExtendData';
		$eventData = [
			'TAG_VALUE_LIST' => $tagValuesList,
			'ELEMENT_LIST' => $elementList,
			'CONTEXT' => $context
		];

		if (isset($data))
		{
			$eventData += $data;
		}

		$event = new Main\Event($moduleName, $eventName, $eventData);
		$event->send();
	}

	/**
	 * Генерируем теги
	 *
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array                    $context
	 *
	 * @return Market\Result\XmlNode[]
	 */
	protected function buildTagList($tagValuesList, array $context = [])
	{
		$document = null;
		$isTypedTag = $this->isTypedTag();
		$readyDistinctValues = [];
		$result = [];

		foreach ($tagValuesList as $elementId => $tagValue)
		{
			$tagDistinct = $tagValue->getDistinct();
			$tagType = ($isTypedTag ? $tagValue->getType() : null);
			$tagData = $tagValue->getTagData();
			$tag = $this->getTag($tagType);

			if ($tagDistinct !== null && isset($readyDistinctValues[$tagDistinct]))
			{
				$tagResult = new Market\Result\XmlNode();
				$tagResult->invalidate();
			}
			else
			{
				if ($document === null)
				{
					$document = $tag->exportDocument();
				}

				$tagResult = $tag->exportTag($tagData, $context, $document);

				if ($tagDistinct !== null && $tagResult->isSuccess())
				{
					$readyDistinctValues[$tagDistinct] = true;
				}
			}

			$result[$elementId] = $tagResult;
		}

		return $result;
	}

	/**
	 * Список изменений
	 *
	 * @return array|null
	 */
	protected function getChanges()
	{
		$result = $this->getParameter('changes');

		return $this->filterChanges($result);
	}

	/**
	 * Фильтруем список изменений по правилам шага
	 *
	 * @param $changes
	 *
	 * @return array|null
	 */
	protected function filterChanges($changes)
	{
		$result = $changes;
		$ignoredTypeList = $this->getIgnoredTypeChanges();

		if ($result !== null && $ignoredTypeList !== null)
		{
			foreach ($result as $changeType => $entityIds)
			{
				if (isset($ignoredTypeList[$changeType]))
				{
					unset($result[$changeType]);
				}
			}
		}

		return $result;
	}

	/**
	 * Список типов изменений, которые игнорируются внутри шага
	 *
	 * @return array|null
	 */
	protected function getIgnoredTypeChanges()
	{
		return null;
	}

	protected function writeDataUserEvent($tagResultList, $elementList, array $context = [], array $data = null)
	{
		$stepName = $this->getName();
		$moduleName = Market\Config::getModuleName();
		$eventName = 'onExport' . ucfirst($stepName) . 'WriteData';
		$eventData = [
			'TAG_RESULT_LIST' => $tagResultList,
			'ELEMENT_LIST' => $elementList,
			'CONTEXT' => $context
		];

		if (isset($data))
		{
			$eventData += $data;
		}

		$event = new Main\Event($moduleName, $eventName, $eventData);
		$event->send();
	}

	/**
	 * Класс хранилища результовов шага
	 *
	 * @return Market\Reference\Storage\Table
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	protected function getStorageDataClass()
	{
		return null;
	}

	/**
	 * Основной ключ строки в хранилище результатов выгрузки
	 *
	 * @return array
	 */
	protected function getStoragePrimaryList()
	{
		return [
			'SETUP_ID',
			'ELEMENT_ID'
		];
	}

	/**
	 * Секция runtime для запросов к хранилищю результатов
	 *
	 * @return array
	 */
	protected function getStorageRuntime()
	{
		return [];
	}

	/**
	 * Очищаем хранилище результатов выгрузки полностью
	 *
	 * @param $context
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function clearDataStorage($context)
	{
		$dataClass = $this->getStorageDataClass();

		if ($dataClass)
		{
			$dataClass::deleteBatch([
				'filter' => [ '=SETUP_ID' => $context['SETUP_ID'] ]
			]);
		}
	}

	/**
	 * Фильтр по изменениям для хранилища результатов
	 *
	 * @param $changes
	 * @param $context
	 *
	 * @return array|null
	 */
	protected function getStorageChangesFilter($changes, $context)
	{
		return []; // invalidate all by default
	}

	/**
	 * Записываем результат выгрузки в постоянное хранилище
	 *
	 * @param Market\Result\XmlNode[] $tagResultList
	 * @param Market\Result\XmlValue[] $tagValuesList
	 * @param array $elementList
	 * @param array $context
	 * @param array|null $data
	 * @param int|null $limit
	 *
	 * @return array
	 */
	protected function writeDataStorage($tagResultList, $tagValuesList, $elementList, array $context = [], array $data = null, $limit = null)
	{
		// make fields

		$fieldsList = $this->makeStorageDataList($tagResultList, $tagValuesList, $elementList, $context, $data);
		$fieldsList = $this->resolveChunkCollision($tagResultList, $fieldsList, $context);
		list($fieldsList, $deleteElements) = $this->resolveStoredCollision($tagResultList, $fieldsList, $context);
		$fieldsList = $this->applyLimitStorageDataList($tagResultList, $fieldsList, $limit);

		// load exists

		if ($this->isVirtual())
		{
			$existsRows = null;
		}
		else
		{
			$existsFilter = $this->getExistDataStorageFilter($context);
			$existsFilter['=ELEMENT_ID'] = array_merge(array_keys($fieldsList), $deleteElements);

			$existsRows = $this->loadExistDataStorage($existsFilter);
		}

		// unset error fields without additional info

		$fieldsList = $this->unsetEmptyStorageDataList($tagResultList, $fieldsList, $existsRows);

		// write db

		if ($this->getRunAction() !== 'full')
		{
			list($insertFieldsList, $updateElements) = $this->splitUpdateStorageElements($fieldsList, $existsRows);
		}
		else
		{
			$insertFieldsList = $fieldsList;
			$updateElements = [];
		}

		if (!empty($insertFieldsList))
		{
			$this->insertDataStorage($insertFieldsList);
		}

		if (!empty($updateElements))
		{
			$this->markUpdatedStorageElements($updateElements, $context);
		}

		if (!empty($deleteElements))
		{
			$fieldsList += $this->markDeletedStorageElements($deleteElements, $context);
		}

		return $this->collectWriteDataStorageResult($fieldsList, $existsRows);
	}

	/**
	 * Загрузка сохраненных результатов выгрузки
	 *
	 * @param  $filter array
	 *
	 * @return array<int, array{ELEMENT_ID: mixed, HASH: string, PRIMARY: string|null}>
	 */
	public function loadExistDataStorage($filter)
	{
		$dataClass = $this->getStorageDataClass();
		$result = [];

		if ($dataClass)
		{
			$select = [
				'ELEMENT_ID',
				'STATUS',
				'HASH',
			];

			if ($this->useTagPrimary())
			{
				$select[] = 'PRIMARY';
			}

			$queryExists = $dataClass::getList([
				'filter' => $filter,
				'select' => $select,
				'runtime' => $this->getStorageRuntime(),
			]);

			while ($row = $queryExists->fetch())
			{
				$result[$row['ELEMENT_ID']] = $row;
			}
		}

		return $result;
	}

	/**
	 * Выделение элементов, которым требуется только обновление TIMESTAMP_X
	 *
	 * @param $fieldsList array[]
	 * @param $existsRows array[]
	 *
	 * @return array{array[], string[]}
	 */
	protected function splitUpdateStorageElements($fieldsList, $existsRows)
	{
		$updateElements = [];

		$compareKeys = [
			'STATUS',
			'HASH',
		];

		if ($this->useTagPrimary())
		{
			$compareKeys[] = 'PRIMARY';
		}

		foreach ($fieldsList as $elementId => $fields)
		{
			if (!isset($existsRows[$elementId])) { continue; }

			$existsRow = $existsRows[$elementId];
			$isChanged = false;

			foreach ($compareKeys as $compareKey)
			{
				if ((string)$fields[$compareKey] !== (string)$existsRow[$compareKey])
				{
					$isChanged = true;
					break;
				}
			}

			if (!$isChanged)
			{
				$updateElements[] = $elementId;
				unset($fieldsList[$elementId]);
			}
		}

		return [$fieldsList, $updateElements];
	}

	/**
	 * Обновление TIMESTAMP_X для элементов
	 *
	 * @param $elementIds string[]
	 * @param $context array
	 *
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function markUpdatedStorageElements($elementIds, $context)
	{
		$filter = $this->getExistDataStorageFilter($context);
		$filter['=ELEMENT_ID'] = $elementIds;

		$this->updateDataStorage($filter, [
			'TIMESTAMP_X' => new Market\Data\Type\CanonicalDateTime(),
		]);
	}

	/**
	 * Отметка об удалении результатов выгрузки
	 *
	 * @param $elementIds int[]
	 * @param $context array
	 *
	 * @return array[]
	 */
	protected function markDeletedStorageElements($elementIds, $context)
	{
		$result = [];

		$filter = $this->getExistDataStorageFilter($context);
		$filter['=ELEMENT_ID'] = $elementIds;
		$fields = [
			'STATUS' => static::STORAGE_STATUS_DELETE,
			'HASH' => '',
			'PRIMARY' => '',
			'CONTENTS' => '',
			'TIMESTAMP_X' => new Market\Data\Type\CanonicalDateTime(),
		];

		$this->updateDataStorage($filter, $fields);

		foreach ($elementIds as $elementId)
		{
			$result[$elementId] = $fields + [
				'SETUP_ID' => $context['SETUP_ID'],
				'ELEMENT_ID' => $elementId,
			];
		}

		return $result;
	}

	/**
	 * Запись результатов выгрузки в базу
	 *
	 * @param $rows array[]
	 *
	 * @throws Main\SystemException
	 */
	protected function insertDataStorage($rows)
	{
		$dataClass = $this->getStorageDataClass();

		if ($dataClass !== null && !empty($rows))
		{
			$chunkSize = $this->getWriteStorageChunkSize();
			$disabledFields = $this->getDataStorageDisabledFields();

			foreach (array_chunk($rows, $chunkSize) as $rowsChunk)
			{
				$dbList = $this->sanitizeDataStorageRows($rowsChunk, $disabledFields);

				$writeResult = $dataClass::addBatch($dbList, true);

				// process write result

				if (!$writeResult->isSuccess())
				{
					$errorMessage = implode(PHP_EOL, $writeResult->getErrorMessages());
					throw new Main\SystemException($errorMessage);
				}
			}
		}
	}

	/**
	 * Обновление результатов выгрузки в базе
	 *
	 * @param $filter array
	 * @param $fields array
	 *
	 * @return bool
	 * @throws Main\SystemException
	 */
	protected function updateDataStorage($filter, $fields)
	{
		$dataClass = $this->getStorageDataClass();
		$disabledFields = $this->getDataStorageDisabledFields();
		$fields = $this->sanitizeDataStorageRow($fields, $disabledFields);
		$result = false;

		if ($dataClass !== null && !empty($filter) && !empty($fields))
		{
			$updateParameters = [
				'filter' => $filter,
				'runtime' => $this->getStorageRuntime(),
			];
			$updateResult = $dataClass::updateBatch($updateParameters, $fields);

			if (!$updateResult->isSuccess())
			{
				$errorMessage = implode(PHP_EOL, $updateResult->getErrorMessages());
				throw new Main\SystemException($errorMessage);
			}

			$result = ($updateResult->getAffectedRowsCount() > 0);
		}

		return $result;
	}

	/**
	 * Удаление неподдерживаемых полей результатов выгрузки для списка строк
	 *
	 * @param $rows array[]
	 * @param $disabledFields array<string, bool>
	 *
	 * @return array[]
	 */
	protected function sanitizeDataStorageRows($rows, $disabledFields)
	{
		if (empty($disabledFields))
		{
			$result = $rows;
		}
		else
		{
			$result = array_map(
				static function($row) use ($disabledFields) { return array_diff_key($row, $disabledFields); },
				$rows
			);
		}

		return $result;
	}

	/**
	 * Удаление неподдерживаемых полей результатов выгрузки для строки
	 *
	 * @param $row array
	 * @param $disabledFields array<string, bool>
	 *
	 * @return array
	 */
	protected function sanitizeDataStorageRow($row, $disabledFields)
	{
		if (empty($disabledFields))
		{
			$result = $row;
		}
		else
		{
			$result = array_diff_key($row, $disabledFields);
		}

		return $result;
	}

	/**
	 * Список неподдерживаемых полей результатов выгрузки
	 *
	 * @return array<string, bool>
	 */
	protected function getDataStorageDisabledFields()
	{
		return array_filter([
			'CONTENTS' => !$this->isVirtual(),
			'PRIMARY' => !$this->useTagPrimary(),
		]);
	}

	/**
	 * Сборка результатов записи
	 *
	 * @param $rows array[]
	 * @param $existsRows array[]|null
	 *
	 * @return array[]
	 */
	protected function collectWriteDataStorageResult($rows, $existsRows)
	{
		$useTagPrimary = $this->useTagPrimary();
		$result = [];

		foreach ($rows as $fields)
		{
			$elementId = $fields['ELEMENT_ID'];
			$prevHash = '';
			$prevPrimary = '';

			if (isset($existsRows[$elementId]))
			{
				$prevHash = (string)$existsRows[$elementId]['HASH'];

				if ($prevHash !== '')
				{
					if ($useTagPrimary)
					{
						$prevPrimary = (string)$existsRows[$elementId]['PRIMARY'];

						if ($prevPrimary === '')
						{
							$prevPrimary = (string)$elementId;
						}
					}
					else
					{
						$prevPrimary = (string)$elementId;
					}
				}
			}

			$result[$elementId] = [
				'ID' => $elementId,
				'STATUS' => $fields['STATUS'],
				'HASH' => $fields['HASH'],
				'PRIMARY' => $fields['PRIMARY'],
				'CONTENTS' => $fields['CONTENTS'],
				'STORED_HASH' => $prevHash,
				'STORED_PRIMARY' => $prevPrimary,
			];
		}

		return $result;
	}

	/**
	 * Размер пакета для записи в результаты выгрузки
	 *
	 * @return int
	 */
	protected function getWriteStorageChunkSize()
	{
		return (int)Market\Config::getOption('export_write_storage_chunk_size') ?: 50;
	}

	/**
	 * Фильтр по уже выгруженным результатам для выбранных элементов
	 *
	 * @param $context array
	 *
	 * @return array
	 */
	protected function getExistDataStorageFilter(array $context)
	{
		return [
			'=SETUP_ID' => $context['SETUP_ID'],
		];
	}

	/**
	 * Есть ли успешно выгруженные элементы данного типа
	 *
	 * @param array|null $context
	 *
	 * @return bool
	 */
	protected function hasDataStorageSuccess($context = null)
	{
		$dataClass = $this->getStorageDataClass();
		$result = false;

		if ($dataClass)
		{
			if ($context === null) { $context = $this->getContext(); }

			$readyFilter = $this->getStorageReadyFilter($context, true);
			$readyFilter['=STATUS'] = static::STORAGE_STATUS_SUCCESS;

			$query = $dataClass::getList([
				'filter' => $readyFilter,
				'limit' => 1
			]);

			if ($query->fetch())
			{
				$result = true;
			}
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
	 * Информация для сохранения в таблице результатов выгрузки
	 *
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $tagValuesList Market\Result\XmlValue[]
	 * @param $elementList array[]
	 * @param $context array
	 * @param $data array
	 *
	 * @return array[]
	 */
	protected function makeStorageDataList($tagResultList, $tagValuesList, $elementList, $context, $data)
	{
		$timestamp = new Market\Data\Type\CanonicalDateTime();
		$result = [];

		foreach ($tagResultList as $elementId => $tagResult)
		{
			$element = isset($elementList[$elementId]) ? $elementList[$elementId] : null;
			$tagValues = isset($tagValuesList[$elementId]) ? $tagValuesList[$elementId] : null;

			$fields = [
				'SETUP_ID' => $context['SETUP_ID'],
				'ELEMENT_ID' => $elementId, // not int, maybe currency
				'STATUS' => static::STORAGE_STATUS_FAIL,
				'PRIMARY' => '',
				'HASH' => '',
				'CONTENTS' => '',
				'TIMESTAMP_X' => $timestamp,
			];

			$additionalData = $this->getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data);

			if (!empty($additionalData))
			{
				$fields += $additionalData;
			}

			if ($tagResult->isSuccess())
			{
				$fields['STATUS'] = static::STORAGE_STATUS_SUCCESS;
				$fields['PRIMARY'] = $this->getTagResultPrimary($tagResult, $tagValues);
				$fields['HASH'] = $this->getTagResultHash($tagResult, $tagValues);
				$fields['CONTENTS'] = $tagResult->getXmlContents();
			}

			$result[$elementId] = $fields;
		}

		return $result;
	}

	/**
	 * Проверка дубликатов среди обрабатываемых элементов
	 *
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $fieldsList array[]
	 * @param $context array
	 *
	 * @return array[]
	 */
	protected function resolveChunkCollision($tagResultList, $fieldsList, $context)
	{
		$collisionFieldsMap = [
			'PRIMARY' => $this->usePrimaryCollision($context),
			'HASH' => $this->useHashCollision(),
		];

		// check chunk

		foreach ($collisionFieldsMap as $collisionField => $useCollision)
		{
			if (!$useCollision) { continue; }

			$duplicateMap = $this->checkChunkCollision($fieldsList, $collisionField);

			$fieldsList = $this->applyDuplicateMap($tagResultList, $fieldsList, $duplicateMap, $collisionField);
		}

		return $fieldsList;
	}

	/**
	 * Проверка дубликатов по записанным в файл элементам
	 *
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $fieldsList array[]
	 * @param $context array
	 *
	 * @return array{array[], string[]}
	 */
	protected function resolveStoredCollision($tagResultList, $fieldsList, $context)
	{
		$deleteElements = [];
		$collisionFieldsMap = [
			'PRIMARY' => $this->usePrimaryCollision($context),
			'HASH' => $this->useHashCollision(),
		];

		foreach ($collisionFieldsMap as $collisionField => $useCollision)
		{
			if (!$useCollision) { continue; }

			$valueMap = $this->collectChunkCollisionMap($fieldsList, $collisionField);
			$duplicatesByStatus = $this->checkStoredCollision($valueMap, $collisionField, $context, array_column($fieldsList, 'ELEMENT_ID', 'ELEMENT_ID'));

			foreach ($duplicatesByStatus as $status => $duplicateMap)
			{
				if ($status === static::STORAGE_STATUS_DUPLICATE)
				{
					foreach ($duplicateMap as $originId)
					{
						if (!isset($tagResultList[$originId]))
						{
							$deleteElements[] = $originId;
						}
					}
				}
				else
				{
					$fieldsList = $this->applyDuplicateMap($tagResultList, $fieldsList, $duplicateMap, $collisionField);
				}
			}
		}

		return [ $fieldsList, $deleteElements ];
	}

	protected function applyDuplicateMap($tagResultList, $fieldsList, $duplicateMap, $collisionField)
	{
		if (empty($duplicateMap)) { return $fieldsList; }

		$this->registerCollisionError($tagResultList, $duplicateMap, $collisionField);

		$fieldsList = $this->applyStatusStorageDataList($fieldsList, $duplicateMap, static::STORAGE_STATUS_DUPLICATE);

		return $fieldsList;
	}

	/**
	 * Изменение статуса строк информации перед сохранением
	 *
	 * @param $fieldsList array[]
	 * @param $elementMap array<string, string>
	 * @param $status int
	 *
	 * @return array[]
	 */
	protected function applyStatusStorageDataList($fieldsList, $elementMap, $status)
	{
		foreach ($elementMap as $elementId => $originId)
		{
			if (isset($fieldsList[$elementId]))
			{
				$fields = &$fieldsList[$elementId];

				$fields['STATUS'] = $status;

				if ($status !== static::STORAGE_STATUS_SUCCESS)
				{
					$fields['PRIMARY'] = '';
					$fields['HASH'] = '';
					$fields['CONTENTS'] = '';
				}

				unset($fields);
			}
		}

		return $fieldsList;
	}

	/**
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $map array<string, string>
	 */
	protected function invalidateTagResultList($tagResultList, $map)
	{
		foreach ($map as $elementId => $originId)
		{
			if (isset($tagResultList[$elementId]))
			{
				$tagResult = $tagResultList[$elementId];
				$tagResult->invalidate();
			}
		}
	}

	/**
	 * Добавление ошибки дубликата для результата тега
	 *
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $duplicateMap array<string, string>
	 * @param $type string
	 */
	protected function registerCollisionError($tagResultList, $duplicateMap, $type)
	{
		foreach ($duplicateMap as $elementId => $originId)
		{
			if (isset($tagResultList[$elementId]))
			{
				$tagResult = $tagResultList[$elementId];

				$tagResult->addError(new Market\Error\XmlNode(
					Market\Config::getLang('EXPORT_RUN_STEP_BASE_' . $type . '_COLLISION'),
					Market\Error\XmlNode::XML_NODE_HASH_COLLISION
				));
			}
		}
	}

	/**
	 * Применение ограничения на количество успешно выгружаемых элементов
	 *
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $fieldsList array[]
	 * @param $limit int|null
	 *
	 * @return array[]
	 */
	protected function applyLimitStorageDataList($tagResultList, $fieldsList, $limit)
	{
		if ($limit !== null)
		{
			$successCount = 0;

			foreach ($fieldsList as &$fields)
			{
				if ($fields['STATUS'] !== static::STORAGE_STATUS_SUCCESS) { continue; }

				if ($successCount < $limit)
				{
					++$successCount;
				}
				else
				{
					$tagResult = $tagResultList[$fields['ELEMENT_ID']];

					$tagResult->invalidate();

					$fields['STATUS'] = static::STORAGE_STATUS_FAIL;
					$fields['PRIMARY'] = '';
					$fields['HASH'] = '';
					$fields['CONTENTS'] = '';
				}
			}
			unset($fields);
		}

		return $fieldsList;
	}

	/**
	 * Удаление строк информации, которые содержат ошибку и не будут использованы в пользовательском интерфейсе
	 *
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $fieldsList array[]
	 * @param $existRows array[]|null
	 *
	 * @return array[]
	 */
	protected function unsetEmptyStorageDataList($tagResultList, $fieldsList, $existRows)
	{
		foreach ($fieldsList as $fieldsKey => $fields)
		{
			if ($fields['STATUS'] === static::STORAGE_STATUS_FAIL && !isset($existRows[$fields['ELEMENT_ID']]))
			{
				$tagResult = $tagResultList[$fields['ELEMENT_ID']];

				if (!$tagResult->hasErrors() && !$tagResult->hasWarnings())
				{
					unset($fieldsList[$fieldsKey]);
				}
			}
		}

		return $fieldsList;
	}

	/**
	 * Дополнительная информация для сохранения в таблице результатов выгрузки
	 *
	 * @param $tagResult Market\Result\XmlNode
	 * @param $tagValues Market\Result\XmlValue
	 * @param $element array|null
	 * @param $context array
	 * @param $data array
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data)
	{
		return null;
	}

	/**
	 * Проверять совпадение хешей при экспорте
	 *
	 * @return bool
	 */
	protected function useHashCollision()
	{
		return false;
	}

	/**
	 * Проверять совпадение идентификатора при экспорте
	 *
	 * @param $context array
	 *
	 * @return bool
	 */
	protected function usePrimaryCollision($context)
	{
		return false;
	}

	/**
	 * @param $fieldsList array[]
	 * @param $fieldName string
	 *
	 * @return array<string, int|string>
	 */
	protected function collectChunkCollisionMap($fieldsList, $fieldName)
	{
		$result = [];

		foreach ($fieldsList as $fields)
		{
			if ($fields['STATUS'] === static::STORAGE_STATUS_SUCCESS)
			{
				$elementId = $fields['ELEMENT_ID'];
				$fieldValue = (string)$fields[$fieldName];

				if ($fieldValue !== '' && !isset($result[$fieldValue]))
				{
					$result[$fieldValue] = $elementId;
				}
			}
		}

		return $result;
	}

	/**
	 * Проверяем совпадение значения поля среди обрабатываемой пачки элементов, возвращаем список дубликатов
	 *
	 * @param $fieldsList array[]
	 * @param $fieldName string
	 *
	 * @return array<string, string>
	 */
	protected function checkChunkCollision($fieldsList, $fieldName)
	{
		$existsMap = [];
		$result = [];

		foreach ($fieldsList as $fields)
		{
			if ($fields['STATUS'] === static::STORAGE_STATUS_SUCCESS)
			{
				$elementId = $fields['ELEMENT_ID'];
				$fieldValue = (string)$fields[$fieldName];

				if ($fieldValue === '')
				{
					// nothing
				}
				else if (!isset($existsMap[$fieldValue]))
				{
					$existsMap[$fieldValue] = $elementId;
				}
				else
				{
					$result[$elementId] = $existsMap[$fieldValue];
				}
			}
		}

		return $result;
	}

	/**
	 * Проверяем наличие выгруженных элементов
	 *
	 * @param $valueMap array<string, int>
	 * @param $fieldName string
	 * @param $context array
	 * @param $used array
	 *
	 * @return array<int, array<string, string>>
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function checkStoredCollision($valueMap, $fieldName, $context, $used)
	{
		$result = [];
		$dataClass = $this->getStorageDataClass();

		if ($dataClass && !empty($valueMap))
		{
			$refreshDateString = null;
			$filter = $this->getExistDataStorageFilter($context);
			$select = [
				'ELEMENT_ID',
				'STATUS',
				$fieldName,
			];

			if ($this->getRunAction() === Market\Export\Run\Processor::ACTION_REFRESH)
			{
				$refreshDate = $this->getParameter('initTimeUTC');
				$refreshDateString = $refreshDate->format('Y-m-d H:i:s');

				$select[] = 'TIMESTAMP_X';
			}

			foreach (array_chunk($valueMap, 500, true) as $valueChunk)
			{
				$query = $dataClass::getList([
					'filter' => $filter + [
						'=STATUS' => static::STORAGE_STATUS_SUCCESS,
						'=' . $fieldName => array_keys($valueChunk),
					],
					'select' => $select,
				]);

				while ($row = $query->fetchRaw()) // avoid build DateTime object for TIMESTAMP_X
				{
					$fieldValue = (string)$row[$fieldName];

					if (isset($valueMap[$fieldValue]) && (string)$valueMap[$fieldValue] !== (string)$row['ELEMENT_ID'])
					{
						$elementId = $valueMap[$fieldValue];

						if (
							isset($used[$row['ELEMENT_ID']])
							|| ($refreshDateString !== null && $refreshDateString > $row['TIMESTAMP_X'])
						)
						{
							$status = static::STORAGE_STATUS_DUPLICATE;
						}
						else
						{
							$status = (int)$row['STATUS'];
						}

						if (!isset($result[$status]))
						{
							$result[$status] = [];
						}

						$result[$status][$elementId] = $row['ELEMENT_ID'];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Хэш результата
	 *
	 * @param $tagResult Market\Result\XmlNode
	 * @param $tagValues Market\Result\XmlValue
	 *
	 * @return string
	 */
	protected function getTagResultPrimary($tagResult, $tagValues)
	{
		$tagType = $tagValues->getType();
		$tag = $this->getTag($tagType);
		$primaryName = $this->getTagPrimaryName($tag);

		return $tagResult->getTagAttribute($tag->getName(), $primaryName);
	}

	/**
	 * Хэш результата
	 *
	 * @param $tagResult Market\Result\XmlNode
	 * @param $tagValues Market\Result\XmlValue
	 *
	 * @return string
	 */
	protected function getTagResultHash($tagResult, $tagValues)
	{
		$result = '';
		$xmlContents = $tagResult->getXmlContents();

		if ($xmlContents !== null)
		{
			if ($this->useHashCollision()) // remove id attr for check tag contents
			{
				$tagType = $tagValues->getType();
				$tag = $this->getTag($tagType);
				$primaryName = $this->getTagPrimaryName($tag);

				$xmlContents = preg_replace('/^(<[^ ]+) ' . $primaryName . '="[^"]*?"/', '$1', $xmlContents);
			}

			$result = md5($xmlContents);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function useTagPrimary()
	{
		if ($this->useTagPrimary === null)
		{
			$storageDataClass = $this->getStorageDataClass();

			if ($storageDataClass !== null)
			{
				$entity = $storageDataClass::getEntity();
				$this->useTagPrimary = $entity->hasField('PRIMARY');
			}
			else
			{
				$this->useTagPrimary = false;
			}
		}

		return $this->useTagPrimary;
	}

	protected function makeFileActionList($storageResultList)
	{
		$result = [];

		foreach ($storageResultList as $storageResult)
		{
			$isChangedHash = ((string)$storageResult['HASH'] !== (string)$storageResult['STORED_HASH']);
			$isChangedPrimary = ((string)$storageResult['PRIMARY'] !== (string)$storageResult['STORED_PRIMARY']);

			if ($isChangedHash || $isChangedPrimary)
			{
				$prevFileAction = ($storageResult['STORED_HASH'] !== '' ? 'add' : 'delete');
				$newFileAction = ($storageResult['HASH'] !== '' ? 'add' : 'delete');

				if ($prevFileAction !== $newFileAction)
				{
					if ($newFileAction === 'add')
					{
						$result[] = [
							'ACTION' => $newFileAction,
							'PRIMARY' => $storageResult['PRIMARY'],
							'CONTENTS' => $storageResult['CONTENTS'],
							'HASH' => $storageResult['HASH'],
						];
					}
					else
					{
						$result[] = [
							'ACTION' => $newFileAction,
							'PRIMARY' => $storageResult['STORED_PRIMARY'],
							'CONTENTS' => '',
							'HASH' => $storageResult['STORED_HASH'],
						];
					}
				}
				else if ($newFileAction === 'add')
				{
					if ($isChangedPrimary)
					{
						$result[] = [
							'ACTION' => 'delete',
							'PRIMARY' => $storageResult['STORED_PRIMARY'],
							'CONTENTS' => '',
							'HASH' => $storageResult['STORED_HASH'],
						];

						$result[] = [
							'ACTION' => 'add',
							'PRIMARY' => $storageResult['PRIMARY'],
							'CONTENTS' => $storageResult['CONTENTS'],
							'HASH' => $storageResult['HASH'],
						];
					}
					else
					{
						$result[] = [
							'ACTION' => 'update',
							'PRIMARY' => $storageResult['PRIMARY'],
							'CONTENTS' => $storageResult['CONTENTS'],
							'HASH' => $storageResult['HASH'],
						];
					}
				}
			}
		}

		return $result;
	}

	protected function mergeFileActionList($actionDataList)
	{
		$toWrite = [];
		$toDelete = [];

		foreach ($actionDataList as $actionIndex => $actionData)
		{
			if ($actionData['ACTION'] === 'delete')
			{
				if (!isset($toDelete[$actionData['PRIMARY']]))
				{
					$toDelete[$actionData['PRIMARY']] = [];
				}

				$toDelete[$actionData['PRIMARY']][] = $actionIndex;
			}
			else
			{
				if (!isset($toWrite[$actionData['PRIMARY']]))
				{
					$toWrite[$actionData['PRIMARY']] = [];
				}

				$toWrite[$actionData['PRIMARY']][] = $actionIndex;
			}
		}

		$intersectPrimaries = array_intersect_key($toDelete, $toWrite);

		foreach ($intersectPrimaries as $primary => $dummy)
		{
			foreach ($toWrite[$primary] as $writeIndex)
			{
				$writeAction = $actionDataList[$writeIndex];
				$isFoundMatchedHash = false;

				foreach ($toDelete[$primary] as $deleteIndex)
				{
					$deleteAction = $actionDataList[$deleteIndex];

					if ($writeAction['HASH'] === $deleteAction['HASH'])
					{
						$isFoundMatchedHash = true;
						break;
					}
				}

				if ($isFoundMatchedHash)
				{
					unset($actionDataList[$writeIndex]);
				}
				else
				{
					$actionDataList[$writeIndex]['ACTION'] = 'update';
				}
			}

			foreach ($toDelete[$primary] as $deleteIndex)
			{
				unset($actionDataList[$deleteIndex]);
			}
		}

		return $actionDataList;
	}

	/**
	 * Записываем изменения в файл экспорта
	 *
	 * @param $storageResultList array[]
	 * @param $context array
	 */
	protected function writeDataFile($storageResultList, $context)
	{
		$writer = $this->getWriter();
		$isOnlyDelete = true;
		$fileActionList = $this->makeFileActionList($storageResultList);
		$fileActionList = $this->mergeFileActionList($fileActionList);
		$actionDataList = [];

		foreach ($fileActionList as $fileAction)
		{
			$actionType = null;
			$actionContents = null;

			switch ($fileAction['ACTION'])
			{
				case 'add':
				case 'update':
					$isOnlyDelete = false;

					$actionType = $fileAction['ACTION'];
					$actionContents = $fileAction['CONTENTS'];
				break;

				case 'delete':
					$actionType = 'update';
					$actionContents = '';
				break;
			}

			if ($actionType !== null)
			{
				if (!isset($actionDataList[$actionType]))
				{
					$actionDataList[$actionType] = [];
				}

				$actionDataList[$actionType][$fileAction['PRIMARY']] = $actionContents;
			}
		}

		foreach ($actionDataList as $action => $actionData)
		{
			switch ($action)
			{
				case 'add':
					$tagParentName = $this->getTagParentName();

					$writeResultList = $writer->writeTagList($actionData, $tagParentName);

					if (empty($writeResultList) && $this->isAllowDeleteParent()) // failed write to file, then parent tag is missing
					{
						$parentPath = $this->getTagPath();
						$needRepeat = [];
						$pathName = $tagParentName;

						foreach ($parentPath as $parentName => $parentPosition)
						{
							$parentWriteResult = $writer->writeParent($pathName, $parentName, $parentPosition);

							if ($parentWriteResult)
							{
								foreach (array_reverse($needRepeat) as list($missingName, $repeatParent, $repeatPosition))
								{
									$writer->writeParent($missingName, $repeatParent, $repeatPosition);
								}

								break;
							}

							if (
								$parentPosition === Market\Export\Run\Writer\Base::POSITION_APPEND
								|| $parentPosition === Market\Export\Run\Writer\Base::POSITION_PREPEND
							)
							{
								$needRepeat[] = [ $pathName, $parentName, $parentPosition ];

								$pathName = $parentName;
							}
						}

						$writer->writeTagList($actionData, $tagParentName);
					}
				break;

				case 'update':
					$tag = $this->getTag();
					$tagName = $tag->getName();
					$primaryName = $this->getTagPrimaryName($tag);
					$tagParentName = $this->getTagParentName();
					$isTagSelfClosed = $tag->isSelfClosed();
					$runAction = $this->getRunAction();

					$writer->updateTagList($tagName, $actionData, $primaryName, $isTagSelfClosed);

					if ($isOnlyDelete && ($runAction === 'change' || $runAction === 'refresh'))
					{
						$isNeedDeleteParent = ($tagParentName !== null && $this->isAllowDeleteParent() && !$this->hasDataStorageSuccess($context));

						if ($isNeedDeleteParent)
						{
							$writer->updateTag($tagParentName, null, '');
						}
					}
				break;
			}
		}
	}

	/**
	 * Разрешно ли удалять родительский тег
	 *
	 * @return bool
	 */
	protected function isAllowDeleteParent()
	{
		return false;
	}

	/**
	 * Разрешено ли удалять элементы из публичного файла (используется при внесении изменений)
	 *
	 * @return bool
	 */
	protected function isAllowPublicDelete()
	{
		return false;
	}

	/**
	 * Очищаем лог
	 *
	 * @param $context
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function clearDataLog($context)
	{
		$entityType = $this->getDataLogEntityType();

		if ($entityType)
		{
			Market\Logger\Table::deleteBatch([
				'filter' => [
					'=ENTITY_TYPE' => $entityType,
					'=ENTITY_PARENT' => $context['SETUP_ID'],
				]
			]);
		}
	}

	/**
	 * Записываем ошибки и warning в таблицу логов
	 *
	 * @param $tagResultList Market\Result\XmlNode[]
	 * @param $context array
	 */
	protected function writeDataLog($tagResultList, $context)
	{
		$entityType = $this->getDataLogEntityType();

		if ($entityType && !empty($tagResultList))
		{
			$runAction = $this->getRunAction();

			$logger = new Market\Logger\Logger();
			$logger->allowBatch();

			if ($runAction === 'change' || $runAction === 'refresh')
			{
				$logger->allowCheckExists();
				$logger->allowRelease();
			}

			foreach ($tagResultList as $elementId => $tagResult)
			{
				$logContext = [
					'ENTITY_TYPE' => $entityType,
					'ENTITY_PARENT' => $context['SETUP_ID'],
					'ENTITY_ID' => $elementId
				];
				$errorGroupList = [
					Market\Psr\Log\LogLevel::CRITICAL => $tagResult->getErrors(),
					Market\Psr\Log\LogLevel::WARNING => $tagResult->getWarnings()
				];

				foreach ($errorGroupList as $logLevel => $errorGroup)
				{
					/** @var \Yandex\Market\Error\Base $error */
					foreach ($errorGroup as $error)
					{
						$errorContext = $logContext;
						$message = $error->getMessage();

						if ($messageCode = $error->getCode())
						{
							$errorContext['ERROR_CODE'] = $messageCode;
						}

						$logger->log($logLevel, $message, $errorContext);
					}
				}

				$logger->registerElement($logContext['ENTITY_TYPE'], $logContext['ENTITY_PARENT'], $logContext['ENTITY_ID']);
			}

			$logger->flush();
		}
	}

	/**
	 * Тип сущности для логов
	 *
	 * @return string|null
	 */
	protected function getDataLogEntityType()
	{
		return null;
	}

	/**
	 * Связь таблицы логов с таблицей результатов выгрузки
	 *
	 * @return array
	 */
	protected function getDataLogEntityReference()
	{
		return [
			'=this.ENTITY_PARENT' => 'ref.SETUP_ID',
			'=this.ENTITY_ID' => 'ref.ELEMENT_ID',
		];
	}

	public function after($action)
	{
		if ($action === Market\Export\Run\Processor::ACTION_CHANGE)
		{
			$this->removeInvalid();
		}
		else if ($action === Market\Export\Run\Processor::ACTION_REFRESH)
		{
			$this->removeOld();
		}
	}

	public function finalize($action)
	{
		// nothing by default
	}

	/**
	 * Удаляем инвалидированные элементы, которые не попали в выгрузку по изменениям
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function removeInvalid()
	{
		$context = $this->getContext();
		$changes = $this->getChanges();
		$changesFilter = $this->getStorageChangesFilter($changes, $context);

		if ($changesFilter !== null)
		{
			$filter = [
				'=SETUP_ID' => $context['SETUP_ID'],
				'!=STATUS' => static::STORAGE_STATUS_DELETE,
				'<TIMESTAMP_X' => $this->getParameter('initTimeUTC')
			];

			if (!empty($changesFilter))
			{
				$filter[] = $changesFilter;
			}

			$this->removeByFilter($filter, $context);
		}
	}

	/**
	 * Удаляем необработанные элементы
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function removeOld()
	{
		$context = $this->getContext();
		$filter = [
			'=SETUP_ID' => $context['SETUP_ID'],
			'!=STATUS' => static::STORAGE_STATUS_DELETE,
			'<TIMESTAMP_X' => $this->getParameter('initTimeUTC')
		];

		$this->removeByFilter($filter, $context);
	}

	/**
	 * Удаляем элементы по фильтру
	 *
	 * @param $filter
	 * @param $context
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function removeByFilter($filter, $context)
	{
		// get ids exist in file

		$exitsRows = null;

		if (!$this->isVirtual())
		{
			$exitsRows = $this->loadExistDataStorage($filter + [ '!=HASH' => false ]);
		}

		// update db

		$updateFields = [
			'STATUS' => static::STORAGE_STATUS_DELETE,
			'HASH' => '',
			'PRIMARY' => '',
			'CONTENTS' => '',
			'TIMESTAMP_X' => new Market\Data\Type\CanonicalDateTime(),
		];

		$hasUpdateStorage = $this->updateDataStorage($filter, $updateFields);

		if ($hasUpdateStorage)
		{
			$this->removeDeletedLog($context);
		}

		// write to file

		if (!empty($exitsRows))
		{
			$updateRows = [];

			foreach ($exitsRows as $elementId => $exitsRow)
			{
				$updateRows[] = $updateFields + [
					'SETUP_ID' => $context['SETUP_ID'],
					'ELEMENT_ID' => $elementId,
				];
			}

			$writeList = $this->collectWriteDataStorageResult($updateRows, $exitsRows);

			$this->writeDataFile($writeList, $context);
		}
	}

	/**
	 * Удаляем лог для удаленных элементов
	 *
	 * @param $context array
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function removeDeletedLog($context)
	{
		$dataClass = $this->getStorageDataClass();
		$logEntityType = $this->getDataLogEntityType();

		if ($dataClass !== null && $logEntityType !== null)
		{
			/** @noinspection PhpParamsInspection */
			Market\Logger\Table::deleteBatch([
				'filter' => [
					'=ENTITY_TYPE' => $logEntityType,
					'=ENTITY_PARENT' => $context['SETUP_ID'],
					'=RUN_STORAGE.STATUS' => static::STORAGE_STATUS_DELETE
				],
				'runtime' => [
					new Main\Entity\ReferenceField(
						'RUN_STORAGE',
						$dataClass,
						$this->getDataLogEntityReference()
					)
				]
			]);
		}
	}

	/**
	 * Контроллер выгрузки
	 *
	 * @return \Yandex\Market\Export\Run\Processor
	 */
	protected function getProcessor()
	{
		return $this->processor;
	}

	/**
	 * Модель настройки выгрузки
	 *
	 * @return \Yandex\Market\Export\Setup\Model
	 */
	protected function getSetup()
	{
		return $this->getProcessor()->getSetup();
	}

	/**
	 * Писатель файла экспорта
	 *
	 * @return \Yandex\Market\Export\Run\Writer\Base
	 */
	protected function getWriter()
	{
		return $this->getProcessor()->getWriter();
	}

	/**
	 * Параметр выполнения
	 *
	 * @param $name
	 *
	 * @return mixed|null
	 */
	protected function getParameter($name)
	{
		return $this->getProcessor()->getParameter($name);
	}

	/**
	 * Контекст выполнения
	 *
	 * @return array
	 */
	protected function getContext()
	{
		return $this->getSetup()->getContext();
	}

	protected function getFormat()
	{
		return $this->getSetup()->getFormat();
	}

	/**
	 * Зависит формат тега от типа данных
	 *
	 * @return bool
	 */
	public function isTypedTag()
	{
		return false;
	}

	/**
	 * Выгружаемый тег
	 *
	 * @param $type string|null
	 *
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 */
	public function getTag($type = null)
	{
		if ($type !== null)
		{
			if (isset($this->typedTagList[$type]))
			{
				$result = $this->typedTagList[$type];
			}
			else
			{
				$format = $this->getFormat();
				$result = $this->getFormatTag($format, $type);

				$this->typedTagList[$type] = $result;
			}
		}
		else if ($this->tag !== null)
		{
			$result = $this->tag;
		}
		else
		{
			$format = $this->getFormat();
			$result = $this->getFormatTag($format);

			$this->tag = $result;
		}

		return $result;
	}

	/**
	 * Уникальный атрибут тега с поддержкой обратной совместимости
	 *
	 * @param Market\Export\Xml\Tag\Base $tag
	 *
	 * @return string
	 */
	protected function getTagPrimaryName(Market\Export\Xml\Tag\Base $tag)
	{
		$primary = $tag->getPrimary();

		return $primary !== null ? $primary->getName() : 'id';
	}

	/**
	 * Название родительского тега
	 *
	 * @return null|string
	 */
	public function getTagParentName()
	{
		if (!isset($this->tagParentName))
		{
			$format = $this->getFormat();

			$this->tagParentName = $this->getFormatTagParentName($format);
		}

		return $this->tagParentName;
	}

	/**
	 * Путь к родительскому тегу
	 *
	 * @return array
	 *
	 * @throws Main\SystemException
	 */
	public function getTagPath()
	{
		if ($this->tagPath === null)
		{
			$format = $this->getFormat();
			$parentName = $this->getTagParentName();
			$rootTag = $format->getRoot();
			$path = $this->findTagPath($rootTag, $parentName, $this->useTagPathReverse());

			if ($path === null)
			{
				throw new Main\SystemException('not found tag path for ' . $parentName);
			}

			$this->tagPath = $path;
		}

		return $this->tagPath;
	}

	protected function useTagPathReverse()
	{
		return true;
	}

	/**
	 * Поиск пути к тегу
	 *
	 * @param Market\Export\Xml\Tag\Base $tag
	 * @param string $findName
	 * @param bool $useReverse
	 *
	 * @return array|null
	 */
	protected function findTagPath(Market\Export\Xml\Tag\Base $tag, $findName, $useReverse = false)
	{
		$result = null;
		$afterTagNameList = [];
		$children = $tag->getChildren();

		if ($useReverse) { $children = array_reverse($children); }

		/** @var Market\Export\Xml\Tag\Base $child */
		foreach ($children as $child) // because gifts require promos, categories and currencies requires offers
		{
			$childName = $child->getName();
			$childResult = null;
			$isFoundSelf = false;

			if ($childName === $findName)
			{
				$isFoundSelf = true;
			}
			else
			{
				$childResult = $this->findTagPath($child, $findName);
			}

			if ($isFoundSelf || $childResult !== null)
			{
				if ($isFoundSelf)
				{
					foreach (array_reverse($afterTagNameList) as $afterTagName)
					{
						$result[$afterTagName] = (
							$useReverse
								? Market\Export\Run\Writer\Base::POSITION_BEFORE
								: Market\Export\Run\Writer\Base::POSITION_AFTER
						);
					}
				}
				else
				{
					foreach ($childResult as $childName => $childPosition)
					{
						$result[$childName] = $childPosition;
					}
				}

				$result[$tag->getName()] = Market\Export\Run\Writer\Base::POSITION_APPEND;

				break;
			}

			$afterTagNameList[] = $childName;
		}

		return $result;
	}

	/**
	 * Выгружаемый тег из формата настройки
	 *
	 * @param Market\Export\Xml\Format\Reference\Base $format
	 * @param $type string|null
	 *
	 * @return \Yandex\Market\Export\Xml\Tag\Base|null
	 */
	public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return null;
	}

	/**
	 * Название родительского тега из формата настройки
	 *
	 * @return string|null
	 * */
	public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
	{
		return null;
	}

	/**
	 * @param $tagDescriptionList
	 * @param $sourceValuesList
	 * @param $context
	 *
	 * @return Market\Result\XmlValue[]
	 */
	protected function buildTagValuesList($tagDescriptionList, $sourceValuesList, $context)
	{
		$result = [];

		foreach ($sourceValuesList as $elementId => $sourceValues)
		{
			$result[$elementId] = $this->buildTagValues($elementId, $tagDescriptionList, $sourceValues, $context);
		}

		return $result;
	}

	/**
	 * @param $elementId
	 * @param $tagDescriptionList
	 * @param $sourceValues
	 * @param $context
	 * @param Market\Export\Xml\Tag\Base $root
	 *
	 * @return Market\Result\XmlValue
	 */
	protected function buildTagValues($elementId, $tagDescriptionList, $sourceValues, $context, Market\Export\Xml\Tag\Base $root = null)
	{
		$result = new Market\Result\XmlValue();

		if (isset($sourceValues['TYPE']) && $this->isTypedTag())
		{
			$result->setType($sourceValues['TYPE']);
		}

		if ($root === null)
		{
			$root = $this->getTag();
		}

		foreach ($tagDescriptionList as $tagDescription)
		{
			$tagName = $tagDescription['TAG'];
			$tag = $root->getId() === $tagName ? $root : $root->getChild($tagName);

			// get values list

			$tagValues = [];

			if (isset($tagDescription['VALUE']))
			{
				$tagValue = $this->getSourceValue($tagDescription['VALUE'], $sourceValues);

				if (is_array($tagValue))
				{
					$tagValues = $tagValue;
				}
				else
				{
					$tagValues[] = $tagValue;
				}
			}
			else
			{
				$tagValues[] = null;
			}

			// settings

			$tagSettings = isset($tagDescription['SETTINGS']) ? $tagDescription['SETTINGS'] : null;

			if (is_array($tagSettings))
			{
				foreach ($tagSettings as $settingName => $setting)
				{
					if (isset($setting['TYPE'], $setting['FIELD']))
					{
						if ($setting['TYPE'] === Market\Export\Entity\Manager::TYPE_TEXT)
						{
							$tagSettings[$settingName] = $setting['FIELD'];
						}
						else
						{
							$tagSettings[$settingName] = $this->getSourceValue($setting, $sourceValues);
						}
					}
				}
			}

			// fill available keys and load attributes

			$valueKeys = array_flip(array_keys($tagValues));
			$attributeValues = [];

			if (!empty($tagDescription['ATTRIBUTES']))
			{
				foreach ($tagDescription['ATTRIBUTES'] as $attributeName => $attributeSourceMap)
				{
					$attributeValue = $this->getSourceValue($attributeSourceMap, $sourceValues);

					if (is_array($attributeValue))
					{
						foreach ($attributeValue as $valueKey => $value)
						{
							if (!isset($valueKeys[$valueKey]))
							{
								$valueKeys[$valueKey] = true;
							}
						}
					}

					$attributeValues[$attributeName] = $attributeValue;
				}
			}

			// children

			$childrenValues = [];

			if (!empty($tagDescription['CHILDREN']))
			{
				$childrenTag = $this->buildTagValues($elementId, $tagDescription['CHILDREN'], $sourceValues, $context);

				if ($tag !== null && $childrenTag->hasMultipleTags() && ($tag->isMultiple() || $tag->isUnion()))
				{
					$childrenValueKeys = $childrenTag->getMultipleKeys();

					foreach ($childrenValueKeys as $childrenValueKey)
					{
						$childrenValues[$childrenValueKey] = $childrenTag->getMultipleData($childrenValueKey);
					}

					$valueKeys += array_flip(array_keys($childrenValues));
				}
				else if (!empty($valueKeys))
				{
					/** @noinspection PhpArrayIndexResetIsUnnecessaryInspection */
					reset($valueKeys);
					$childrenValues[key($valueKeys)] = $childrenTag->getTagData();
				}
			}

			// export values

			foreach ($valueKeys as $valueKey => $dummy)
			{
				$tagValue = isset($tagValues[$valueKey]) ? $tagValues[$valueKey] : null;
				$childrenValue = isset($childrenValues[$valueKey]) ? $childrenValues[$valueKey] : null;
				$isEmptyTagValue = empty($childrenValue) && $this->isEmptyXmlValue($tagValue); // is empty
				$tagAttributeList = [];

				foreach ($attributeValues as $attributeName => $attributeValue)
				{
					if (is_array($attributeValue))
					{
						$attributeValue = isset($attributeValue[$valueKey]) ? $attributeValue[$valueKey] : null;
					}

					$tagAttributeList[$attributeName] = $attributeValue;

					if (!$this->isEmptyXmlValue($attributeValue)) // is not empty
					{
						$isEmptyTagValue = false;
					}
				}

				if (!$isEmptyTagValue && !$result->hasTag($tagName, $tagValue, $tagAttributeList, $childrenValue))
				{
					$result->addTag($tagName, $tagValue, $tagAttributeList, $tagSettings, $childrenValue);
				}
			}
		}

		return $result;
	}

	protected function getSourceValue($sourceMap, $sourceValues)
	{
		$result = null;

		if (isset($sourceMap['VALUE']))
		{
			$result = $sourceMap['VALUE'];
		}
		else if (isset($sourceValues[$sourceMap['TYPE']][$sourceMap['FIELD']]))
		{
			$result = $sourceValues[$sourceMap['TYPE']][$sourceMap['FIELD']];
		}

		return $result;
	}

	protected function isEmptyXmlValue($value)
	{
		if ($value === null)
		{
			$result = true;
		}
		else if (is_scalar($value))
		{
			$result = (trim($value) === '');
		}
		else
		{
			$result = empty($value);
		}

		return $result;
	}

	protected function applyValueConflict($elementValue, $conflictAction)
	{
		if ($conflictAction['TYPE'] === 'INCREMENT')
		{
			$result = $elementValue + $conflictAction['VALUE'];
		}
		else
		{
			$result = $elementValue;
		}

		return $result;
	}
}
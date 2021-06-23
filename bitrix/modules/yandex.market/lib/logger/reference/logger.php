<?php

namespace Yandex\Market\Logger\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Logger extends Market\Psr\Log\AbstractLogger
{
	const FLUSH_QUEUE_ADD = 'add';
	const FLUSH_QUEUE_UPDATE = 'update';
	const FLUSH_QUEUE_DELETE = 'delete';

	protected $level = Market\Logger\Level::WARNING;
	protected $allowBatch = false;
	protected $allowCheckExists = false;
	protected $allowRelease = false;
	protected $elementList = [];
	protected $flushQueue = [];
	protected $context = [];

	/**
	 * @return Market\Reference\Storage\Table
	 */
	abstract public function getDataClass();

	public function setLevel($level)
	{
		$this->level = (string)$level;
	}

	protected function isMatchLevel($level)
	{
		return (
			$this->level !== ''
			&& Market\Logger\Level::isMatch($this->level, $level)
		);
	}

	public function log($level, $message, array $context = [])
	{
		if (isset($context['LOG_ID']))
		{
			$this->addFlushQueue(static::FLUSH_QUEUE_UPDATE, $context['LOG_ID']);
		}
		else if ($this->isMatchLevel($level))
		{
			if (array_key_exists('LOG_ID', $context))
			{
				unset($context['LOG_ID']);
			}

			$parsedMessage = $this->parseMessage($message);
			$context = $this->extendContext($message, $context);
			$context += $this->context;

			$row = $this->createRow($level, $parsedMessage, $context);

			$this->addFlushQueue(static::FLUSH_QUEUE_ADD, $row);
		}
	}

	protected function parseMessage($message)
	{
		if ($message instanceof \Exception || $message instanceof \Throwable || $message instanceof Main\Error)
		{
			$errorCode = $message->getCode();
			$result = $message->getMessage() . (!empty($errorCode) ? ' #' . $errorCode : '');
		}
		else if ($message instanceof Main\Result || $message instanceof Market\Result\Base)
		{
			$result = implode(PHP_EOL, $message->getErrorMessages());
		}
		else if (!is_scalar($message))
		{
			$result = print_r($message, true);
		}
		else
		{
			$result = (string)$message;
		}

		return $result;
	}

	public function resetContext(array $context)
	{
		$this->context = $context;
	}

	public function getFullContext()
	{
		return $this->context;
	}

	public function getContext($name)
	{
		return isset($this->context[$name]) ? $this->context[$name] : null;
	}

	public function setContext($name, $key)
	{
		$this->context[$name] = $key;
	}

	public function releaseContext()
	{
		$this->context = [];
	}

	protected function extendContext($message, $context)
	{
		if (
			($message instanceof \Exception || $message instanceof \Throwable)
			&& $this->isTracingOn()
		)
		{
			$context['TRACE'] = Main\Diag\ExceptionHandlerFormatter::format($message);
		}

		return $context;
	}

	protected function isTracingOn()
	{
		return false;
	}

	protected function createRow($level, $message, array $context = [])
	{
		$contextFieldNames = $this->getContextFields();
		list($contextFields, $rowContext) = $this->splitContextFields($context, $contextFieldNames);

		return $contextFields + [
			'TIMESTAMP_X' => new Main\Type\DateTime(),
			'LEVEL' => $level,
			'MESSAGE' => $message,
			'CONTEXT' => $rowContext,
		];
	}

	protected function getContextFields()
	{
		return [];
	}

	protected function splitContextFields($context, $fieldNames)
	{
		$data = [];

		foreach ($fieldNames as $fieldName)
		{
			if (array_key_exists($fieldName, $context))
			{
				$value = (string)$context[$fieldName];
				unset($context[$fieldName]);
			}
			else
			{
				$value = '';
			}

			$data[$fieldName] = $value;
		}

		return [ $data, $context ];
	}

	public function getExists($entityType, $entityParent, $entityId)
	{
		$result = [];

		$filter = [
			'=ENTITY_TYPE' => $entityType,
			'=ENTITY_PARENT' => $entityParent
		];

		if ($entityId !== null)
		{
			$filter['=ENTITY_ID'] = $entityId;
		}

		$dataClass = $this->getDataClass();
		$query = $dataClass::getList([
			'filter' => $filter,
		]);

		while ($row = $query->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	public function releaseExists($logId)
	{
		return $this->addFlushQueue(static::FLUSH_QUEUE_DELETE, $logId);
	}

	public function allowBatch()
	{
		$this->allowBatch = true;
	}

	public function disallowBatch()
	{
		$this->allowBatch = false;
	}

	public function allowCheckExists()
	{
		$this->allowCheckExists = true;
	}

	public function disallowCheckExists()
	{
		$this->allowCheckExists = false;
	}

	public function allowRelease()
	{
		$this->allowRelease = true;
	}

	public function disallowRelease()
	{
		$this->allowRelease = false;
	}

	public function registerElement($entityType, $entityParent, $entityId)
	{
		$parentKey = $entityType . ':' . $entityParent;

		if (!isset($this->elementList[$parentKey]))
		{
			$this->elementList[$parentKey] = [];
		}

		$this->elementList[$parentKey][] = $entityId;
	}

	public function flush()
	{
		$chunkSize = $this->getFlushChunkSize();

		foreach ($this->flushQueue as $chain => $dataList)
		{
			foreach (array_chunk($dataList, $chunkSize) as $dataChunk)
			{
				$this->processFlushQueue($chain, $dataChunk);
			}
		}

		if ($this->allowCheckExists && $this->allowRelease && !empty($this->elementList))
		{
			$this->processFlushQueue(static::FLUSH_QUEUE_ADD, []);
		}

		$this->flushQueue = [];
	}

	protected function addFlushQueue($chain, $data)
	{
		if (!$this->allowBatch)
		{
			$result = $this->processFlushQueue($chain, [ $data ]);
		}
		else
		{
			$result = true;

			if (!isset($this->flushQueue[$chain]))
			{
				$this->flushQueue[$chain] = [];
			}

			$this->flushQueue[$chain][] = $data;
		}

		return $result;
	}

	protected function processFlushQueue($chain, $dataList)
	{
		$result = false;

		switch ($chain)
		{
			case static::FLUSH_QUEUE_ADD:
				$addList = $dataList;
				$updateList = null;
				$deleteList = null;
				$result = true;

				if ($this->allowCheckExists)
				{
					list($addList, $updateList, $deleteList) = $this->splitExists($dataList);
				}

				if (!empty($addList))
				{
					$addResult = $this->flushAdd($addList);

					if (!$addResult)
					{
						$result = false;
					}
				}

				if (!empty($updateList))
				{
					$updateResult = $this->flushUpdate($updateList);

					if (!$updateResult)
					{
						$result = false;
					}
				}

				if (!empty($deleteList))
				{
					$deleteResult = $this->flushDelete($deleteList);

					if (!$deleteResult)
					{
						$result = false;
					}
				}
			break;

			case static::FLUSH_QUEUE_UPDATE:
				$result = $this->flushUpdate($dataList);
			break;

			case static::FLUSH_QUEUE_DELETE:
				$result = $this->flushDelete($dataList);
			break;
		}

		return $result;
	}

	protected function splitExists($dataList)
	{
		$checkEntityList = [];
		$addList = $dataList;
		$updateList = [];
		$deleteList = [];

		foreach ($dataList as $dataKey => $data)
		{
			$entityKey = $data['ENTITY_TYPE'] . ':' . $data['ENTITY_PARENT'];

			if (!isset($checkEntityList[$entityKey]))
			{
				$checkEntityList[$entityKey] = [
					'ENTITY_TYPE' => $data['ENTITY_TYPE'],
					'ENTITY_PARENT' => $data['ENTITY_PARENT'],
					'ENTITY_ID' => []
				];
			}

			if (!isset($checkEntityList[$entityKey]['ENTITY_ID'][$data['ENTITY_ID']]))
			{
				$checkEntityList[$entityKey]['ENTITY_ID'][$data['ENTITY_ID']] = [];
			}

			$checkEntityList[$entityKey]['ENTITY_ID'][$data['ENTITY_ID']][$data['MESSAGE']] = $dataKey;
		}

		if ($this->allowRelease)
		{
			foreach ($this->elementList as $entityKey => $entityIdList)
			{
				if (!isset($checkEntityList[$entityKey]))
				{
					$entityType = strtok($entityKey, ':');
					$entityParent = strtok(null);

					$checkEntityList[$entityKey] = [
						'ENTITY_TYPE' => $entityType,
						'ENTITY_PARENT' => $entityParent,
						'ENTITY_ID' => []
					];
				}
			}
		}

		foreach ($checkEntityList as $entityKey => $checkEntity)
		{
			$entityIdList = isset($this->elementList[$entityKey]) ? $this->elementList[$entityKey] : array_keys($checkEntity['ENTITY_ID']);

			$existsList = $this->getExists(
				$checkEntity['ENTITY_TYPE'],
				$checkEntity['ENTITY_PARENT'],
				$entityIdList
			);

			foreach ($existsList as $existRow)
			{
				if (isset($checkEntity['ENTITY_ID'][$existRow['ENTITY_ID']][$existRow['MESSAGE']]))
				{
					$dataKey = $checkEntity['ENTITY_ID'][$existRow['ENTITY_ID']][$existRow['MESSAGE']];

					$updateList[] = $existRow['ID'];
					unset($addList[$dataKey]);
				}
				else if ($this->allowRelease)
				{
					$deleteList[] = $existRow['ID'];
				}
			}
		}

		$this->elementList = []; // reset element list

		return [ $addList, $updateList, $deleteList ];
	}

	protected function flushAdd($dataList)
	{
		$dataClass = $this->getDataClass();
		$query = $dataClass::addBatch($dataList);

		return $query->isSuccess();
	}

	protected function flushUpdate($dataList)
	{
		$dataClass = $this->getDataClass();
		$query = $dataClass::updateBatch(
			[ 'filter' => [ '=ID' => $dataList ] ],
			[ 'TIMESTAMP_X' => new Main\Type\DateTime() ]
		);

		return $query->isSuccess();
	}

	protected function flushDelete($dataList)
	{
		$dataClass = $this->getDataClass();
		$query = $dataClass::deleteBatch([
			'filter' => [ '=ID' => $dataList ]
		]);

		return $query->isSuccess();
	}

	protected function getFlushChunkSize()
	{
		return max(1, (int)Market\Config::getOption('log_flush_chunk_size', 50));
	}
}
<?php

namespace Yandex\Market\Export\Run;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Data;
use Yandex\Market\Reference\Concerns;

class Processor implements Data\Run\Processor
{
	use Concerns\HasMessage;

	/** @var \Yandex\Market\Export\Setup\Model */
	protected $setup;
	/** @var Writer\Base */
	protected $writer;
	/** @var bool */
	protected $isWriterLocked;
	/** @var string */
	protected $publicFilePath;
	/** @var array */
	protected $parameters;
	/** @var Data\Run\ResourceLimit */
	protected $resourceLimit;
	/** @var array */
	protected $conflictList;
	/** @var Market\Export\Run\Steps\Base[] */
	protected $steps = [];

	public function __construct(Market\Export\Setup\Model $setup, $parameters = [])
	{
		$this->setup = $setup;
		$this->parameters = $this->extendParameters($parameters);
		$this->resourceLimit = new Data\Run\ResourceLimit([
			'startTime' => $this->getParameter('startTime'),
			'timeLimit' => $this->getParameter('timeLimit')
		]);
	}

	protected function extendParameters($parameters)
	{
		if (isset($parameters['initTime']) && $parameters['initTime'] instanceof Main\Type\DateTime)
		{
			$canonicalTime = Market\Data\DateTime::toCanonical($parameters['initTime']);
			$canonicalTime->setDefaultTimeZone();

			$parameters['initTimeUTC'] = $canonicalTime;
		}

		return $parameters;
	}

	public function clear($isStrict = false)
	{
		$steps = Manager::getSteps();

		$this->loadModules();

		foreach ($steps as $stepName)
		{
			$step = $this->getStep($stepName);

			$step->clear($isStrict);
		}
	}

	public function run($action = self::ACTION_FULL)
	{
		$requestStep = $this->getParameter('step');
		$requestOffset = $this->getParameter('stepOffset');

		if (!$this->lockWriter())
		{
			$result = new Market\Result\StepProcessor();
			$result->setStep($requestStep);
			$result->setStepOffset($requestOffset);
			$result->setTotal(1);

			return $result;
		}

		$this->loadModules();

		if ($requestStep === null && $action === static::ACTION_FULL) // is start full export
		{
			$this->clear();
			$this->resolveWriterIndex();
		}

		if ($action !== static::ACTION_FULL) // check is file index valid
		{
			$this->testWriter();
		}

		if (preg_match('/^(.*?)\|(\d+)$/', (string)$requestOffset, $matches))
		{
			$requestOffset = $matches[1];
			$pointerOffset = (int)$matches[2];

			$this->getWriter()->setPointer($pointerOffset);
		}

		$stepper = new Data\Run\Stepper($this->getSteps(), $this->resourceLimit);
		$result = $stepper->process($action, $requestStep, $requestOffset);

		if ($result->isFinished())
		{
			$this->finalize($action);
		}
		else
		{
			$result->setStepOffset($result->getStepOffset() . '|' . $this->getWriter()->getPointer());
		}

		$this->releaseWriter();

		return $result;
	}

	public function finalize($action)
	{
		/** @var Steps\Root $rootStep */
		$rootStep = $this->getStep(Manager::STEP_ROOT);

		if ($action !== 'change')
		{
			$this->publishFile();
		}

		$rootStep->updateDate();
	}

	protected function getSteps()
	{
		$result = [];

		foreach (Manager::getSteps() as $name)
		{
			$result[$name] = $this->getStep($name);
		}

		return $result;
	}

	public function getStep($name)
	{
		if (!isset($this->steps[$name]))
		{
			$this->steps[$name] = Manager::getStepProvider($name, $this);
		}

		return $this->steps[$name];
	}

	/**
	 * Модель настройки
	 *
	 * @return Market\Export\Setup\Model
	 */
	public function getSetup()
	{
		return $this->setup;
	}

	protected function resolveWriterIndex()
	{
        $setup = $this->getSetup();

		Writer\IndexFacade::resolve($setup->getId(), $setup->getFileName());
	}

	public function publishFile()
	{
		if ($this->publicFilePath !== null)
		{
			$writer = $this->getWriter();

			$writer->move($this->publicFilePath);

			$this->publicFilePath = null;
		}
	}

	public function getPublicFilePath()
	{
		return $this->publicFilePath;
	}

	/**
	 * Получаем класс писателя
	 *
	 * @return Market\Export\Run\Writer\Base
	 */
	public function getWriter()
	{
		if ($this->writer === null)
		{
			$this->writer = $this->loadWriter();
		}

		return $this->writer;
	}

	/**
	 * Создаем класс писателя
	 *
	 * @param $isIgnoreTemp bool
	 *
	 * @return Market\Export\Run\Writer\File
	 */
	protected function loadWriter($isIgnoreTemp = false)
	{
		$setup = $this->getSetup();
		$filePath = $setup->getFileAbsolutePath();
		$useIndex = false;

		if (!$isIgnoreTemp)
		{
			$tmpFilePath = $filePath . '.tmp';
			$useIndex = (
				Writer\IndexFacade::isAllowed()
				&& Writer\IndexFacade::search($setup->getId(), $setup->getFileName())
			);

			if ($this->getParameter('usePublic') === false || file_exists($tmpFilePath))
			{
				$this->publicFilePath = $filePath;

				$filePath = $tmpFilePath;
			}
		}

		$parameters = [
			'filePath' => $filePath,
		];

		if ($useIndex)
		{
			return new Writer\FileIndexed($parameters + [
				'setupId' => $setup->getId(),
			]);
		}

		return new Writer\File($parameters);
	}

	/**
	 * Блокировка файла
	 *
	 * @return bool
	 */
	protected function lockWriter()
	{
		if (!$this->isWriterLocked)
		{
			$writer = $this->getWriter();
			$this->isWriterLocked = $writer->lock();
		}

		return $this->isWriterLocked;
	}

	protected function testWriter()
	{
		$writer = $this->getWriter();

		if (
			!($writer instanceof Writer\FileIndexed)
			|| $writer->test()
		)
		{
			return;
		}

		$logger = new Market\Logger\Logger();
		$logger->warning(self::getMessage('FILE_INDEXED_CHANGED'), [
			'ENTITY_TYPE' => Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_ROOT,
			'ENTITY_PARENT' => $this->getSetup()->getId(),
		]);

		Writer\IndexFacade::reset($this->getSetup()->getId());

		$this->writer = $this->loadWriter();
	}

	/**
	 * Выгружаем из памяти класс писателя
	 */
	protected function releaseWriter()
	{
		if ($this->writer !== null)
		{
			if ($this->isWriterLocked)
			{
				$this->isWriterLocked = false;
				$this->writer->unlock();
				$this->writer->commit();
			}

			$this->writer->destroy();
			$this->writer = null;
		}
	}

	/**
	 * Параметр выполнения
	 *
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function getParameter($name)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
	}

	/**
	 * Загружаем необходимые для работы модули (модули sale и catalog не является необходимыми, должны быть загружены
	 * при запросе данных)
	 *
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function loadModules()
	{
		$modules = [ 'iblock' ];

		foreach ($modules as $module)
		{
			if (!Main\Loader::includeModule($module))
			{
				throw new Main\SystemException('require module ' . $module);
			}
		}
	}

	public function getTimeLimit()
	{
		return $this->resourceLimit->getTimeLimit();
	}

	public function isTimeExpired()
	{
		$this->resourceLimit->tick();

		return $this->resourceLimit->isExpired();
	}

	public function isResourcesExpired()
	{
		return $this->resourceLimit->isExpired();
	}

	public function getConflicts()
	{
		if ($this->conflictList === null)
		{
			$this->conflictList = $this->findConflicts();
		}

		return $this->conflictList;
	}

	protected function findConflicts()
	{
		$conflictTags = [
			'categoryId' => true
		];
		$conflictSources = [];
		$iblockLinkCollection = $this->setup->getIblockLinkCollection();
		$iblockLinkMap = [];
		$iblockContextList = [];
		$result = [];

		/** @var \Yandex\Market\Export\IblockLink\Model $iblockLink */
		foreach ($iblockLinkCollection as $iblockLink)
		{
			$iblockLinkId = $iblockLink->getId();
			$iblockLinkMap[$iblockLinkId] = $iblockLink;
			$tagDescriptionList = $iblockLink->getTagDescriptionList();

			foreach ($tagDescriptionList as $tagDescription)
			{
				$tagName = $tagDescription['TAG'];

				if (isset($conflictTags[$tagName]))
				{
					if (!isset($conflictSources[$tagName]))
					{
						$conflictSources[$tagName] = [];
					}

					$conflictSources[$tagName][$iblockLinkId] = $tagDescription['VALUE'];
				}
			}
		}

		foreach ($conflictSources as $tagName => $sourceList)
		{
			$fieldTypeList = [];

			if (count($sourceList) > 1)
			{
				foreach ($sourceList as $iblockLinkId => $sourceMap)
				{
					$iblockContext = null;

					if (isset($iblockContextList[$iblockLinkId]))
					{
						$iblockContext = $iblockContextList[$iblockLinkId];
					}
					else
					{
						$iblockLink = $iblockLinkMap[$iblockLinkId];
						$iblockContext = $iblockLink->getContext();

						$iblockContextList[$iblockLinkId] = $iblockContext;
					}

					$source = Market\Export\Entity\Manager::getSource($sourceMap['TYPE']);
					$sourceFields = $source->getFields($iblockContext);
					$fieldType = null;

					foreach ($sourceFields as $sourceField)
					{
						if ($sourceField['ID'] === $sourceMap['FIELD'])
						{
							$fieldType = $sourceField['TYPE'];
							break;
						}
					}

					if ($fieldType !== null)
					{
						if (!isset($fieldTypeList[$fieldType]))
						{
							$fieldTypeList[$fieldType] = [];
						}

						$fieldTypeList[$fieldType][] = $sourceMap;
					}
				}
			}

			if ($tagName === 'categoryId' && count($fieldTypeList) > 1)
			{
				$this->resolveConflictForCategoryId($result, $fieldTypeList);
			}
		}

		return $result;
	}

	protected function resolveConflictForCategoryId(&$result, $fieldTypeList)
	{
		$iblockSectionType = Market\Export\Entity\Data::TYPE_IBLOCK_SECTION;

		if (isset($fieldTypeList[$iblockSectionType]))
		{
			$maxIblockSectionId = $this->getMaxIblockSectionId();
			$gap = 1000000;
			$incrementForOtherTypes = $gap * (round($maxIblockSectionId / $gap) + 1);

			foreach ($fieldTypeList as $fieldType => $sourceMapList)
			{
				if ($fieldType !== $iblockSectionType)
				{
					foreach ($sourceMapList as $sourceMap)
					{
						if (!isset($result[$sourceMap['TYPE']]))
						{
							$result[$sourceMap['TYPE']] = [];
						}

						$result[$sourceMap['TYPE']][$sourceMap['FIELD']] = [
							'TYPE' => 'INCREMENT',
							'VALUE' => $incrementForOtherTypes
						];
					}
				}
			}
		}
	}

	protected function getMaxIblockSectionId()
	{
		$result = 0;

		if (Main\Loader::includeModule('iblock'))
		{
			$queryLastSection = \CIBlockSection::GetList(
				[ 'ID' => 'DESC' ],
				[],
				false,
				[ 'ID' ],
				[ 'nTopCount' => 1 ]
			);

			if ($lastSection = $queryLastSection->Fetch())
			{
				$result = (int)$lastSection['ID'];
			}
		}

		return $result;
	}
}
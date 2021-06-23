<?php

namespace Yandex\Market\Ui\Checker\Export;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Export\Setup as ExportSetup;
use Yandex\Market\Export\Run as ExportRun;
use Yandex\Market\Ui\Service as UiService;

class SetupStatus extends Checker\Reference\AbstractTest
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();
		$setupList = ExportSetup\Model::loadList();

		foreach ($setupList as $setup)
		{
			$status = ExportRun\Data\SetupStatus::getExportState($setup);

			if ($status === ExportRun\Data\SetupStatus::EXPORT_PROGRESS)
			{
				$resolver = $this->makeExportResolve($setup);
				$statusMessage = $this->getStateMessage($status);
				$error = $this->makeError($setup, $statusMessage, 'EXPORT_PROGRESS', $resolver);

				$result->addWarning($error);
			}
			else if ($status !== ExportRun\Data\SetupStatus::EXPORT_READY)
			{
				$resolver = $this->makeExportResolve($setup);
				$statusMessage = $this->getStateMessage($status);
				$error = $this->makeError($setup, $statusMessage, 'EXPORT_FAIL', $resolver);

				$result->addError($error);
			}
			else
			{
				Market\Result\Facade::merge([
					$result,
					$this->testRefresh($setup),
					$this->testChange($setup)
				]);
			}
		}

		return $result;
	}

	protected function testRefresh(ExportSetup\Model $setup)
	{
		$result = new Market\Result\Base();

		if ($setup->hasFullRefresh())
		{
			$modificationTime = $this->getFileModificationDate($setup);
			$expectedRefresh = $this->getExpectedRefreshDate($setup);
			$warningDate = $this->getUnprocessedWarningDate($expectedRefresh);
			$errorDate = $this->getUnprocessedErrorDate($expectedRefresh);

			if (Market\Data\DateTime::compare($errorDate, $modificationTime) === 1)
			{
				$error = $this->makeRefreshHaltError($setup, $modificationTime);
				$result->addError($error);
			}
			else if (Market\Data\DateTime::compare($warningDate, $modificationTime) === 1)
			{
				$error = $this->makeRefreshHaltError($setup, $modificationTime);
				$result->addWarning($error);
			}
		}

		return $result;
	}

	protected function makeRefreshHaltError(ExportSetup\Model $setup, Main\Type\DateTime $modificationTime)
	{
		$message = $this->getMessage('STATE_REFRESH_HALT', [
			'#MODIFICATION_DATE#' => $modificationTime,
		]);
		$description = $this->makeAgentHaltDescription();

		$result = $this->makeError($setup, $message, 'REFRESH_HALT');
		$result->setDescription($description);

		return $result;
	}

	protected function getExpectedRefreshDate(ExportSetup\Model $setup)
	{
		$nextExec = $setup->getRefreshNextExec();
		$interval = $setup->getRefreshPeriod();

		if ($interval !== null)
		{
			$intervalDescription = '-PT' . $interval . 'S';

			$nextExec->add($intervalDescription); // to current
			$nextExec->add($intervalDescription); // to previous
		}

		return $nextExec;
	}

	protected function getFileModificationDate(ExportSetup\Model $setup)
	{
		$path = $setup->getFileAbsolutePath();
		$file = new Main\IO\File($path);
		$timestamp = $file->getModificationTime();

		return Main\Type\DateTime::createFromTimestamp($timestamp);
	}

	protected function testChange(ExportSetup\Model $setup)
	{
		$result = new Market\Result\Base();

		if ($setup->isAutoUpdate())
		{
			$errorDate = $this->getUnprocessedErrorDate();
			$errorCount = $this->getUnprocessedChangesCount($setup, $errorDate);

			if ($errorCount > 0)
			{
				$error = $this->makeUnprocessedChangesError($setup, $errorCount);
				$result->addError($error);
			}
			else
			{
				$warningDate = $this->getUnprocessedWarningDate();
				$warningCount = $this->getUnprocessedChangesCount($setup, $warningDate);
				$warningLimit = 10;

				if ($warningCount > $warningLimit)
				{
					$error = $this->makeUnprocessedChangesError($setup, $warningCount);
					$result->addWarning($error);
				}
			}
		}

		return $result;
	}

	protected function makeUnprocessedChangesError(ExportSetup\Model $setup, $count)
	{
		$message = $this->getMessage('STATE_UNPROCESSED_CHANGES', [
			'#COUNT#' => $count,
			'#LABEL#' => Market\Utils::sklon($count, [
				$this->getMessage('CHANGE_1'),
				$this->getMessage('CHANGE_2'),
				$this->getMessage('CHANGE_5'),
			])
		]);
		$description = $this->makeAgentHaltDescription();

		$result = $this->makeError($setup, $message, 'UNPROCESSED_CHANGES');
		$result->setDescription($description);

		return $result;
	}

	protected function getUnprocessedChangesCount(ExportSetup\Model $setup, Main\Type\DateTime $date)
	{
		$result = 0;
		$filter = [
			'=SETUP_ID' => $setup->getId(),
			'<=TIMESTAMP_X' => $date,
		];

		$query = ExportRun\Storage\ChangesTable::getList([
			'filter' => $filter,
			'select' => [ 'CNT' ],
			'runtime' => [
				new Main\Entity\ExpressionField('CNT', 'COUNT(*)')
			],
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['CNT'];
		}

		return $result;
	}

	protected function getUnprocessedWarningDate(Main\Type\DateTime $limitDate = null)
	{
		return $this->makeUnprocessedDate('-PT1H', $limitDate);
	}

	protected function getUnprocessedErrorDate(Main\Type\DateTime $limitDate = null)
	{
		return $this->makeUnprocessedDate('-PT3H', $limitDate);
	}

	protected function makeUnprocessedDate($interval, Main\Type\DateTime $limitDate = null)
	{
		$result = new Main\Type\DateTime();
		$result->add($interval);

		if ($limitDate !== null && Market\Data\DateTime::compare($limitDate, $result) === -1)
		{
			$result = $limitDate;
		}

		return $result;
	}

	protected function makeExportResolve(ExportSetup\Model $setup)
	{
		$url = Market\Ui\Admin\Path::getModuleUrl('setup_run', array_filter([
			'lang' => LANGUAGE_ID,
			'service' => $this->getSetupUiServiceCode($setup),
			'id' => $setup->getId(),
		]));

		return sprintf(
			'<a href="%s">%s</a>',
			$url,
			$this->getMessage('RESOLVE_EXPORT')
		);
	}

	protected function getSetupUiServiceCode(ExportSetup\Model $setup)
	{
		$result = null;
		$setupService = $setup->getField('EXPORT_SERVICE');

		foreach (UiService\Manager::getTypes() as $type)
		{
			$uiService = UiService\Manager::getInstance($type);
			$uiServiceSupports = $uiService->getExportServices();

			if (in_array($setupService, $uiServiceSupports, true))
			{
				$result = !$uiService->isInverted() ? $type : null;
				break;
			}
		}

		return $result;
	}

	protected function makeError(ExportSetup\Model $setup, $statusMessage, $code = 0, $resolver = '')
	{
		$message = sprintf(
			'[%s] %s: %s',
			$setup->getId(),
			$setup->getField('NAME'),
			Market\Data\TextString::lcfirst($statusMessage)
		);

		if ((string)$resolver !== '')
		{
			$message .= ', ' . $resolver;
		}

		if ((string)$code !== '0')
		{
			$code .= '_' . $setup->getId();
		}

		return new Checker\Reference\Error($message, $code);
	}

	protected function makeAgentHaltDescription()
	{
		return Market\Utils::isAgentUseCron()
			? $this->getMessage('HALT_DESCRIPTION_INCREASE_FREQUENCY')
			: $this->getMessage('HALT_DESCRIPTION_USE_CRONTAB');
	}

	protected function getStateMessage($status)
	{
		return $this->getMessage('STATE_' . $status);
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_EXPORT_SETUP_STATUS';
	}
}
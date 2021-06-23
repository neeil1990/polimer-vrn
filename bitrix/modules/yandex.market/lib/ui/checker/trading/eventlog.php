<?php

namespace Yandex\Market\Ui\Checker\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Logger\Trading as LoggerTrading;
use Yandex\Market\Ui\Service as UiService;

class EventLog extends Checker\Reference\AbstractTest
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();
		$errorLevel = Market\Logger\Level::ERROR;
		$warningLevel = Market\Logger\Level::WARNING;

		foreach ($this->getLogSetups([$errorLevel, $warningLevel]) as $setup)
		{
			$logErrors = $this->getLogRecords($setup, $errorLevel);
			$logWarnings = $this->getLogRecords($setup, $warningLevel);

			if (!empty($logErrors))
			{
				$errors = $this->makeErrors($setup, $logErrors, $errorLevel);
				$result->addWarnings($errors);
			}

			if (!empty($logWarnings))
			{
				$errors = $this->makeErrors($setup, $logWarnings, $warningLevel);
				$result->addWarnings($errors);
			}
		}

		return $result;
	}

	/**
	 * @param string[] $levels
	 *
	 * @return TradingSetup\Model[]
	 */
	protected function getLogSetups($levels)
	{
		$ids = $this->getLogSetupIds($levels);

		return !empty($ids)
			? TradingSetup\Model::loadList([ 'filter' => [ '=ID' => $ids ] ])
			: [];
	}

	protected function getLogSetupIds($levels)
	{
		$query = LoggerTrading\Table::getList([
			'filter' => [
				'=LEVEL' => $levels,
				'>=TIMESTAMP_X' => $this->getLimitDate(),
				'=SETUP.ACTIVE' => TradingSetup\Table::BOOLEAN_Y,
			],
			'group' => [ 'SETUP_ID' ],
			'select' => [ 'SETUP_ID' ],
		]);
		$rows = $query->fetchAll();

		return array_column($rows, 'SETUP_ID');
	}

	protected function getLogRecords(TradingSetup\Model $setup, $level)
	{
		$query = LoggerTrading\Table::getList([
			'filter' => [
				'=SETUP_ID' => $setup->getId(),
				'=LEVEL' => $level,
				'>=TIMESTAMP_X' => $this->getLimitDate(),
			],
			'group' => [ 'MESSAGE' ],
			'select' => [ 'MESSAGE', 'CNT' ],
			'limit' => 5,
			'runtime' => [
				new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
			],
		]);

		return $query->fetchAll();
	}

	protected function getLimitDate()
	{
		return (new Main\Type\DateTime())->add('-P1W');
	}

	protected function makeErrors(TradingSetup\Model $setup, $logRows, $level)
	{
		$result = [];
		$logUrl = $this->getLogUrl($setup, $level);
		$setupName = $setup->getField('NAME');
		$description = $this->getMessage('ERROR_DESCRIPTION');

		foreach ($logRows as $logRow)
		{
			$error = new Checker\Reference\Error($logRow['MESSAGE']);
			$error->setDescription($description);
			$error->setGroup($setupName, $logUrl);
			$error->setCount($logRow['CNT']);

			$result[] = $error;
		}

		return $result;
	}

	protected function getLogUrl(TradingSetup\Model $setup, $level)
	{
		return Market\Ui\Admin\Path::getModuleUrl('trading_log', [
			'lang' => LANGUAGE_ID,
			'service' => $this->getUiServiceCode($setup),
			'find_setup' => $setup->getId(),
			'find_level' => $level,
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);
	}

	protected function getUiServiceCode(TradingSetup\Model $setup)
	{
		$serviceCode = $setup->getServiceCode();
		$result = null;

		foreach (UiService\Manager::getTypes() as $uiType)
		{
			$uiService = UiService\Manager::getInstance($uiType);

			if (in_array($serviceCode, $uiService->getTradingServices(), true))
			{
				$result = $uiType;
				break;
			}
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_TRADING_EVENT_LOG';
	}
}
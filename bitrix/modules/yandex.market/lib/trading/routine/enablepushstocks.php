<?php

namespace Yandex\Market\Trading\Routine;

use Bitrix\Main;
use Yandex\Market\Ui;
use Yandex\Market\Utils;
use Yandex\Market\Config;
use Yandex\Market\Reference\Storage;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Service as TradingService;

class EnablePushStocks
{
	use Concerns\HasMessage;

	protected $filter = [];
	protected $changed = [];
	protected $warehouses = [];

	public function __construct(array $filter)
	{
		$this->filter = $filter;
	}

	public function run()
	{
		$this->prepareEnvironment();

		$tradingSetups = TradingSetup\Model::loadList($this->filter);

		foreach ($tradingSetups as $tradingSetup)
		{
			$this->process($tradingSetup);
		}

		$this->resetEnvironment();
	}

	/** @noinspection DuplicatedCode */
	public function notify()
	{
		if (empty($this->changed)) { return; }

		$url = Ui\Admin\Path::getModuleUrl('trading_list', [
			'lang' => LANGUAGE_ID,
			'service' => 'marketplace',
			'find_id_numsel' => 'range',
			'find_id_from' => min($this->changed),
			'find_id_to' => max($this->changed),
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);

		\CAdminNotify::Add([
			'MODULE_ID' => Config::getModuleName(),
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_NORMAL,
			'MESSAGE' => str_replace('#URL#', $url, self::getMessage('NOTIFY')),
			'TAG' => 'YAMARKET_PUSH_STOCKS',
		]);
	}

	protected function prepareEnvironment()
	{
		Utils\HttpConfiguration::stamp();
		Utils\HttpConfiguration::setGlobalTimeout(5);
	}

	protected function resetEnvironment()
	{
		Utils\HttpConfiguration::restore();
	}

	protected function process(TradingSetup\Model $setup)
	{
		try
		{
			if (!$setup->isActive()) { return; }

			$options = $setup->wakeupService()->getOptions();

			if (
				!($options instanceof TradingService\Marketplace\Options)
				|| $options->usePushStocks()
				|| $options->useWarehouses()
			)
			{
				return;
			}

			$this->overwriteOptions($setup, $options, [
				'USE_PUSH_STOCKS' => Storage\Table::BOOLEAN_Y,
			]);

			$this->saveOptions($setup, $options);

			$this->changed[] = $setup->getId();
		}
		/** @noinspection PhpRedundantCatchClauseInspection */
		catch (Main\SystemException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}
	}

	protected function overwriteOptions(TradingSetup\Model $setup, TradingService\Marketplace\Options $options, array $values)
	{
		$options->setValues(array_merge($options->getValues(), $values));

		$setup->wakeupService();
		$setup->tweak();
	}

	protected function saveOptions(TradingSetup\Model $setup, TradingService\Marketplace\Options $options)
	{
		$valuesRows = [];

		foreach ($options->getValues() as $key => $value)
		{
			$valuesRows[] = [
				'NAME' => $key,
				'VALUE' => $value,
			];
		}

		TradingSetup\Table::update($setup->getId(), [ 'SETTINGS' => $valuesRows ]);
	}
}
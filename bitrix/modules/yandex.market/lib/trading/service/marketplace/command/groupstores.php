<?php

namespace Yandex\Market\Trading\Service\Marketplace\Command;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Reference\Storage as ReferenceStorage;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Settings as TradingSettings;

class GroupStores
{
	use Concerns\HasOnce;

	/** @var TradingService\Marketplace\Provider */
	protected $provider;
	/** @var array */
	protected $settings;
	/** @var array */
	protected $preload = [
		'PRODUCT_STORE',
		'USE_ORDER_RESERVE',
		'STOCKS_BEHAVIOR',
	];

	public function __construct(TradingService\Marketplace\Provider $provider)
	{
		$this->provider = $provider;
	}

	public function useOrderReserve()
	{
		$withReserve = $this->linkedWithReserve();

		return !empty($withReserve);
	}

	public function linkedWithReserve()
	{
		$compatibleCheckboxes = $this->setting('USE_ORDER_RESERVE');
		$stocksBehaviors = $this->setting('STOCKS_BEHAVIOR');

		$enabled = $this->valuePositive($compatibleCheckboxes);
		$enabled += $this->valueFilter($stocksBehaviors, [
			TradingService\Marketplace\Options::STOCKS_WITH_RESERVE,
			TradingService\Marketplace\Options::STOCKS_ONLY_AVAILABLE,
		]);

		return array_keys($enabled);
	}

	public function stores()
	{
		$stores = $this->setting('PRODUCT_STORE');

		return $this->valueMerge($stores);
	}

	public function primarySetup()
	{
		$grouped = $this->grouped();

		return reset($grouped) ?: null;
	}

	public function linked()
	{
		return array_values(array_diff($this->grouped(), [
			$this->provider->getOptions()->getSetupId(),
		]));
	}

	protected function grouped()
	{
		return $this->once('grouped', null, function() {
			$campaignIds = Api\Business\Warehouses\Facade::storeGroup($this->provider->getOptions(), $this->provider->getLogger());
			list(, $primaryCampaign) = Api\Business\Warehouses\Facade::primaryWarehouse($this->provider->getOptions(), $this->provider->getLogger());

			if (empty($campaignIds)) { return []; }

			$configured = TradingSettings\Table::getList([
				'filter' => [
					'=NAME' => 'CAMPAIGN_ID',
					'=VALUE' => $campaignIds,
					'=SETUP.ACTIVE' => true,
				],
				'select' => [ 'SETUP_ID', 'VALUE' ],
			]);

			$campaignMap = array_column($configured->fetchAll(), 'SETUP_ID', 'VALUE');

			if (isset($primaryCampaign, $campaignMap[$primaryCampaign]))
			{
				$campaignMap =
					[ $primaryCampaign => $campaignMap[$primaryCampaign] ]
					+ $campaignMap;
			}

			return $campaignMap;
		});
	}

	protected function valueMerge(array $values)
	{
		$partials = [];

		foreach ($values as $value)
		{
			if (!is_array($value)) { continue; }

			$partials[] = $value;
		}

		return !empty($partials) ? array_merge(...$partials) : [];
	}

	protected function valuePositive(array $values)
	{
		return $this->valueFilter($values, ReferenceStorage\Table::BOOLEAN_Y);
	}

	protected function valueFilter(array $values, $filter)
	{
		$result = [];

		foreach ($values as $setupId => $value)
		{
			if (is_array($filter))
			{
				$matched = in_array($value, $filter, true);
			}
			else
			{
				$matched = ((string)$value === $filter);
			}

			if ($matched)
			{
				$result[$setupId] = $value;
				break;
			}
		}

		return $result;
	}

	protected function setting($name)
	{
		$settings = $this->settings();

		if (!isset($settings[$name]))
		{
			throw new Main\ArgumentException(sprintf('register setting %s preload', $name));
		}

		return $settings[$name];
	}

	protected function settings()
	{
		if ($this->settings === null)
		{
			$this->settings = $this->loadSettings();
		}

		return $this->settings;
	}

	protected function loadSettings()
	{
		$setupIds = $this->linked();
		$result = array_fill_keys($this->preload, []);

		if (empty($setupIds)) { return $result; }

		$query = TradingSettings\Table::getList([
			'filter' => [
				'=SETUP_ID' => $setupIds,
				'=NAME' => $this->preload,
			],
			'select' => [ 'SETUP_ID', 'NAME', 'VALUE' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['NAME']][$row['SETUP_ID']] = $row['VALUE'];
		}

		return $result;
	}
}
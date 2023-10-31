<?php

namespace Yandex\Market\Trading\Service\Marketplace\Command;

use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Settings as TradingSettings;
use Yandex\Market\Trading\Setup as TradingSetup;

/** @deprecated */
class GroupStoresTweak
{
	protected $provider;
	protected $setupId;
	protected $linked;
	protected $waitTweak = [];

	public function __construct(TradingService\Marketplace\Provider $provider, $setupId, array $linked)
	{
		$this->provider = $provider;
		$this->setupId = (int)$setupId;
		$this->linked = array_map('intval', $linked);
	}

	public function execute()
	{
		$stored = $this->stored();

		$this->link($stored);
		$this->unlink($stored);

		$this->flushTweak();
	}

	protected function stored()
	{
		$result = [];

		$query = TradingSettings\Table::getList([
			'filter' => [ '=NAME' => 'STORE_GROUP' ],
			'select' => [ 'SETUP_ID', 'VALUE' ],
		]);

		while ($row = $query->fetch())
		{
			if ((int)$row['SETUP_ID'] === $this->setupId) { continue; }

			$option = $row['VALUE'];

			if (!is_array($option)) { $option = []; }

			Main\Type\Collection::normalizeArrayValuesByInt($option);

			$result[$row['SETUP_ID']] = $option;
		}

		return $result;
	}

	protected function link(array $stored)
	{
		foreach ($this->linked as $setupId)
		{
			$setupId = (int)$setupId;

			if ($setupId === $this->setupId) { continue; }

			$optionExists = isset($stored[$setupId]);
			$option = $optionExists ? $stored[$setupId] : [];
			$newOption = array_diff(
				array_merge($this->linked, [ $this->setupId ]),
				[ $setupId ]
			);
			$diffOption = array_merge(
				array_diff($option, $newOption),
				array_diff($newOption, $option)
			);

			if (empty($diffOption)) { continue; }

			$this->save($setupId, $newOption, $optionExists);
			$this->waitTweak($setupId);
		}
	}

	protected function unlink(array $stored)
	{
		foreach ($stored as $setupId => $option)
		{
			$setupId = (int)$setupId;

			if ($setupId === $this->setupId) { continue; }
			if (in_array($setupId, $this->linked, true)) { continue; }

			$newOption = array_diff(
				$option,
				array_merge($this->linked, [ $this->setupId ])
			);
			$diffOption = array_merge(
				array_diff($option, $newOption),
				array_diff($newOption, $option)
			);

			if (empty($diffOption)) { continue; }

			$this->save($setupId, $newOption, true);
			$this->waitTweak($setupId);
		}
	}

	protected function save($setupId, $linked, $optionExists)
	{
		$primary = [
			'SETUP_ID' => $setupId,
			'NAME' => 'STORE_GROUP',
		];
		$fields = [
			'VALUE' => $linked,
		];

		if (empty($linked) && !$optionExists) { return; }

		if ($optionExists)
		{
			TradingSettings\Table::update($primary, $fields);
		}
		else
		{
			TradingSettings\Table::add($primary + $fields);
		}
	}

	protected function waitTweak($setupId)
	{
		$this->waitTweak[] = $setupId;
	}

	protected function flushTweak()
	{
		foreach ($this->waitTweak as $setupId)
		{
			$this->tweak($setupId);
		}

		$this->waitTweak = [];
	}

	protected function tweak($setupId)
	{
		try
		{
			$setup = TradingSetup\Model::loadById($setupId);

			$setup->wakeupService();
			$setup->tweak();
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			// setup not found, then skip
		}
	}
}
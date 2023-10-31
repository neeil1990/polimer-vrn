<?php

namespace Yandex\Market\Trading\Service\Marketplace\Command;

class BasketReserves extends SkeletonReserves
{
	protected $debug = [];

	public function execute(array $storeData)
	{
		$this->resetDebug();

		$quantities = $this->mapQuantities($storeData);
		$quantities = array_filter($quantities, static function($value) { return $value > 0; });

		if (empty($quantities)) { return $storeData; }

		$this->configureEnvironment();

		list($processingStates, $otherStates) = $this->loadOrders();
		$allUsedStates = $processingStates + $otherStates;
		$productIds = array_keys($quantities);

		$waiting = $this->loadWaiting($processingStates, $productIds);
		$reserves = $this->loadReserves($processingStates, $productIds);
		$siblingReserves = $this->loadSiblingReserves($allUsedStates, $productIds);
		$limits = $this->loadLimits($productIds);

		$this->storeDebug('MARKET', $reserves);
		$this->storeDebug('SIBLING', $siblingReserves);
		$this->storeDebug('WAITING', $waiting);

		$storeData = $this->applyReserves($storeData, $reserves);
		$storeData = $this->applyReserves($storeData, $siblingReserves);
		$storeData = $this->applyLimits($storeData, $limits);
		$storeData = $this->applyReserves($storeData, $waiting);

		return $storeData;
	}

	protected function mapQuantities(array $storeData)
	{
		$result = [];

		foreach ($storeData as $productId => $productValues)
		{
			if (!isset($productValues['AVAILABLE_QUANTITY'])) { continue; }

			$result[$productId] = $productValues['AVAILABLE_QUANTITY'];
		}

		return $result;
	}

	protected function applyReserves(array $storeData, array $reserves)
	{
		foreach ($storeData as $productId => &$productValues)
		{
			if (!isset($reserves[$productId])) { continue; }

			$productValues['AVAILABLE_QUANTITY'] -= max(0, $reserves[$productId]['QUANTITY']);
		}
		unset($productValues);

		return $storeData;
	}

	protected function applyLimits(array $storeData, array $limits)
	{
		foreach ($storeData as $productId => &$productValues)
		{
			if (isset($limits[$productId]) && $productValues['AVAILABLE_QUANTITY'] > $limits[$productId])
			{
				$productValues['AVAILABLE_QUANTITY'] = $limits[$productId];
			}
		}
		unset($productValues);

		return $storeData;
	}

	protected function resetDebug()
	{
		$this->debug = [];
	}

	protected function storeDebug($key, array $reserves)
	{
		$this->debug[$key] = $reserves;
	}

	public function findDebugProduct($productId)
	{
		$result = [];

		foreach ($this->debug as $key => $reserves)
		{
			if (!isset($reserves[$productId])) { continue; }

			$result[$key] = $reserves[$productId];
		}

		return $result;
	}
}
<?php

namespace Yandex\Market\Trading\Service\Marketplace\Command;

use Yandex\Market;
use Yandex\Market\Trading\Service\Marketplace;

class ProductReserves extends SkeletonReserves
{
	public function execute(array $amounts)
	{
		if (empty($amounts)) { return []; }

		$behavior = $this->provider->getOptions()->getStocksBehavior();

		if ($behavior === Marketplace\Options::STOCKS_PLAIN) { return $amounts; }

		$this->configureEnvironment();

		$productIds = array_column($amounts, 'ID');
		list($processingStates, $otherStates) = $this->loadOrders();
		$allUsedStates = $processingStates + $otherStates;

		if ($behavior === Marketplace\Options::STOCKS_WITH_RESERVE)
		{
			$reserves = $this->loadAmounts($processingStates, $productIds);
			$siblingReserved = $this->loadSiblingReserves($allUsedStates, $productIds);

			$amounts = $this->applyReserves($amounts, $reserves);
			$amounts = $this->applyReserves($amounts, $siblingReserved, true);
		}
		else
		{
			$waiting = $this->loadWaiting($processingStates, $productIds);
			$reserves = $this->loadReserves($processingStates, $productIds);
			$siblingReserved = $this->loadSiblingReserves($allUsedStates, $productIds);
			$limits = $this->loadLimits($productIds);

			$amounts = $this->applyReserves($amounts, $reserves, true);
			$amounts = $this->applyReserves($amounts, $siblingReserved, true);
			$amounts = $this->applyLimits($amounts, $limits);
			$amounts = $this->applyReserves($amounts, $waiting, true);
		}

		return $amounts;
	}

	protected function loadAmounts(array $orderStates, array $productIds)
	{
		$orderIds = array_column($orderStates, 'INTERNAL_ID');

		return $this->environment->getReserve()->getAmounts($orderIds, $productIds);
	}

	protected function applyReserves(array $amounts, array $reserves, $invert = false)
	{
		$sign = ($invert ? -1 : 1);

		foreach ($amounts as &$amount)
		{
			if (!isset($reserves[$amount['ID']])) { continue; }

			$reserve = $reserves[$amount['ID']];

			if (isset($amount['QUANTITY_LIST'][Market\Data\Trading\Stocks::TYPE_FIT]))
			{
				$amount['QUANTITY_LIST'][Market\Data\Trading\Stocks::TYPE_FIT] += $sign * $reserve['QUANTITY'];
			}

			if (isset($amount['QUANTITY']))
			{
				$amount['QUANTITY'] += $sign * $reserve['QUANTITY'];
			}

			if (
				isset($reserve['TIMESTAMP_X'])
				&& Market\Data\DateTime::compare($reserve['TIMESTAMP_X'], $amount['TIMESTAMP_X']) === 1
			)
			{
				$amount['TIMESTAMP_X'] = $reserve['TIMESTAMP_X'];
			}
		}
		unset($amount);

		return $amounts;
	}

	protected function applyLimits(array $amounts, array $limits)
	{
		foreach ($amounts as &$amount)
		{
			if (!isset($limits[$amount['ID']])) { continue; }

			$limit = $limits[$amount['ID']];

			if (
				isset($amount['QUANTITY_LIST'][Market\Data\Trading\Stocks::TYPE_FIT])
				&& $amount['QUANTITY_LIST'][Market\Data\Trading\Stocks::TYPE_FIT] > $limit
			)
			{
				$amount['QUANTITY_LIST'][Market\Data\Trading\Stocks::TYPE_FIT] = $limit;
			}

			if (isset($amount['QUANTITY']) && $amount['QUANTITY'] > $limit)
			{
				$amount['QUANTITY'] = $limit;
			}
		}
		unset($amount);

		return $amounts;
	}
}
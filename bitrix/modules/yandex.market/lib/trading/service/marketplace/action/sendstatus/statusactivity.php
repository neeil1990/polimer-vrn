<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendStatus;

use Yandex\Market\Data;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Service as TradingService;

class StatusActivity extends TradingService\Reference\Action\ComplexActivity
{
	use Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function useGroup()
	{
		return true;
	}

	public function getSort()
	{
		return 100;
	}

	public function getFilter()
	{
		return [
			'STATUS_READY' => true,
		];
	}

	public function getActivities()
	{
		$result = [];

		foreach ($this->getVariants() as $status)
		{
			$result[$status] = $this->makeStatusActivity($status);
		}

		return $result;
	}

	protected function getVariants()
	{
		/** @var TradingService\Marketplace\Status $statusService */
		$statusService = $this->provider->getStatus();
		$meaningfulMap = $statusService->getOutgoingMeaningfulMap();
		$ignore = array_intersect_key($meaningfulMap, [
			Data\Trading\MeaningfulStatus::CANCELED => true,
		]);

		return array_diff(
			$statusService->getOutgoingVariants(),
			$ignore
		);
	}

	protected function makeStatusActivity($status, array $parameters = [])
	{
		return $this->makeCommand(
			$this->getStatusTitle($status),
			$this->getStatusPayload($status),
			$this->getStatusParameters($status, $parameters)
		);
	}

	protected function getStatusTitle($status)
	{
		return $this->provider->getStatus()->getTitle($status, 'SHORT');
	}

	protected function getStatusPayload($status)
	{
		return [
			'externalStatus' => $status,
		];
	}

	protected function getStatusParameters($status, array $parameters = [])
	{
		return array_merge_recursive($parameters, [
			'FILTER' => [
				'STATUS_ALLOW' => $status,
			],
		]);
	}
}
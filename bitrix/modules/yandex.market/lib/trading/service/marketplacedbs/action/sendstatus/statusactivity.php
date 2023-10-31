<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendStatus;

use Yandex\Market\Trading\Service as TradingService;

class StatusActivity extends TradingService\Marketplace\Action\SendStatus\StatusActivity
{
	public function getActivities()
	{
		$result = [];

		foreach ($this->getVariants() as $status)
		{
			if ($status === TradingService\MarketplaceDbs\Status::STATUS_PICKUP)
			{
				$result[$status] = $this->makeCompleteActivity($status);
			}
			else if ($status === TradingService\MarketplaceDbs\Status::STATUS_DELIVERED)
			{
				$result[$status . '_DEFAULT'] = $this->makeCompleteActivity($status, [
					'FILTER' => [
						'!STATUS' => TradingService\MarketplaceDbs\Status::STATUS_PICKUP,
					],
				]);
				$result[$status . '_PICKUP'] = $this->makeStatusActivity($status, [
					'USE_GROUP' => false,
					'FILTER' => [
						'STATUS' => TradingService\MarketplaceDbs\Status::STATUS_PICKUP,
					],
				]);
			}
			else
			{
				$result[$status] = $this->makeStatusActivity($status);
			}
		}

		return $result;
	}

	protected function makeCompleteActivity($status, array $parameters = [])
	{
		return new CompleteActivity(
			$this->provider,
			$this->environment,
			$this->getStatusTitle($status),
			$this->getStatusPayload($status),
			$this->getStatusParameters($status, $parameters)
		);
	}
}
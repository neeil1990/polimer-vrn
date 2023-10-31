<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendStatus;

use Yandex\Market\Data;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Service as TradingService;

class CancelActivity extends TradingService\Reference\Action\ComplexActivity
{
	use Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getSort()
	{
		return 1000;
	}

	public function getFilter()
	{
		return [
			'CANCEL_ALLOW' => true,
		];
	}

	public function getActivities()
	{
		$status = $this->getStatus();

		if ($status === null) { return []; }

		$result = [];

		if ($this->provider instanceof TradingService\Reference\HasCancelReason)
		{
			$reasonService = $this->provider->getCancelReason();

			foreach ($reasonService->getVariants() as $reason)
			{
				$title = $reasonService->getTitle($reason);

				$result[$reason] = $this->makeCommand($title, [
					'externalStatus' => $status,
					'cancelReason' => $reason,
				], [
					'CONFIRM' => true,
					'CONFIRM_MESSAGE' => self::getMessage('CONFIRM', [
						'#REASON#' => $title,
					]),
				]);
			}
		}
		else
		{
			$title = $this->provider->getStatus()->getTitle($status, 'SHORT');

			$result['default'] = $this->makeCommand($title, [
				'externalStatus' => $status,
			], [
				'CONFIRM' => true,
				'CONFIRM_MESSAGE' => self::getMessage('CONFIRM', [
					'#REASON#' => $title,
				]),
			]);
		}

		return $result;
	}

	protected function getStatus()
	{
		/** @var TradingService\Marketplace\Status $statusService */
		$statusService = $this->provider->getStatus();
		$meaningfulMap = $statusService->getOutgoingMeaningfulMap();

		return isset($meaningfulMap[Data\Trading\MeaningfulStatus::CANCELED])
			? $meaningfulMap[Data\Trading\MeaningfulStatus::CANCELED]
			: null;
	}
}
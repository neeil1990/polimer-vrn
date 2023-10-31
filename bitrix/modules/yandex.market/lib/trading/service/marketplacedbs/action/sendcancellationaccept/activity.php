<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendCancellationAccept;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Reference\Action\ComplexActivity
{
	use Market\Reference\Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFilter()
	{
		return [
			'CANCELLATION_ACCEPT' => Market\Data\Trading\CancellationAccept::WAIT,
		];
	}

	public function getActivities()
	{
		if (!($this->provider instanceof TradingService\Reference\HasCancellationAccept)) { return []; }

		$cancellationAcceptProvider = $this->provider->getCancellationAccept();
		$result = [];

		// accept

		$payload = [
			'accepted' => true,
		];

		$result['confirm'] = $this->makeCommand(self::getMessage('CONFIRM'), $payload, [
			'CONFIRM' => true,
			'CONFIRM_MESSAGE' => self::getMessage('CONFIRM_PROMPT'),
		]);

		// reject

		foreach ($cancellationAcceptProvider->getReasonVariants() as $variant)
		{
			$title = $cancellationAcceptProvider->getReasonTitle($variant);
			$payload = [
				'accepted' => false,
				'reason' => $variant,
			];

			$result['reject:' . $variant] = $this->makeCommand($title, $payload, [
				'CONFIRM' => true,
				'CONFIRM_MESSAGE' => self::getMessage('REJECT_PROMPT', [
					'#REASON#' => $title,
				]),
			]);
		}

		return $result;
	}

	public function getPayload(array $values)
	{
		return $values;
	}
}
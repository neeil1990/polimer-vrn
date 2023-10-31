<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\AdminList;

use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Marketplace\Action\AdminList\Request
{
	public function onlyWaitingForCancellationApprove()
	{
		$value = $this->getField('onlyWaitingForCancellationApprove');

		return $value !== null ? (bool)$value : null;
	}

	public function getParameters()
	{
		$result = parent::getParameters();
		$result = array_diff_key($result, [
			'hasCis' => true,
		]);
		$result += array_intersect_key($this->getFields(), [
			'dispatchType' => true,
			'onlyWaitingForCancellationApprove' => true,
		]);

		return $result;
	}
}
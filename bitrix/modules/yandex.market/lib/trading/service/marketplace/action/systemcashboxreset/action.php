<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SystemCashboxReset;

use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
*/
class Action extends TradingService\Reference\Action\DataAction
{
	use TradingService\Common\Concerns\Action\HasOrder;

	public function __construct(
		TradingService\Marketplace\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		array $data
	)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		if (!$this->needReset()) { return; }

		$this->getOrder()->resetCashbox();
	}

	protected function needReset()
	{
		return $this->provider->getOptions()->getCashboxCheck() === TradingService\Marketplace\PaySystem::CASHBOX_CHECK_DISABLED;
	}
}
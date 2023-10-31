<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SystemCashboxReset;

use Yandex\Market\Trading\State as TradingState;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\MarketplaceDbs\Provider $provider
*/
class Action extends TradingService\Marketplace\Action\SystemCashboxReset\Action
{
	public function __construct(
		TradingService\MarketplaceDbs\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		array $data
	)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function needReset()
	{
		$paySystemId = $this->getOrder()->getPaySystemId();
		$paySystemOptions = $this->provider->getOptions()->getPaySystemOptions()->getItemsByPaySystemId($paySystemId);

		if (!empty($paySystemOptions))
		{
			$result = $paySystemOptions[0]->getCashboxCheck() === TradingService\Marketplace\PaySystem::CASHBOX_CHECK_DISABLED;
		}
		else
		{
			$result = $this->getPaymentType() === TradingService\Marketplace\PaySystem::TYPE_PREPAID;
		}

		return $result;
	}

	protected function getPaymentType()
	{
		return $this->getStoredPaymentType() ?: $this->getExternalPaymentType();
	}

	protected function getStoredPaymentType()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrderId();

		return TradingState\OrderData::getValue($serviceKey, $orderId, 'PAYMENT_TYPE');
	}

	protected function getExternalPaymentType()
	{
		return $this->getExternalOrder()->getPaymentType();
	}
}
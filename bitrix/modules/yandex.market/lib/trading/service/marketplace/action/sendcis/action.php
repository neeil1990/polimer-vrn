<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendCis;

use Yandex\Market\Trading\Service as TradingService;

/**
 * @deprecated
 * @property TradingService\Marketplace\Provider $provider
 * @property Request $request
 */
class Action extends TradingService\Marketplace\Action\SendIdentifiers\Action
{
	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	protected function buildRequest()
	{
		$result = new TradingService\Marketplace\Api\SendCis\Request();
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$items = $this->makeItems();

		$result->setLogger($logger);
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setCampaignId($options->getCampaignId());
		$result->setOrderId($this->request->getOrderId());
		$result->setItems($items);

		$this->sentItems = $items;

		return $result;
	}
}
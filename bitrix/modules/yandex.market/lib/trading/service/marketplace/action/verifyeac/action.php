<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\VerifyEac;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/** @property Request $request */
class Action extends TradingService\Reference\Action\DataAction
	implements TradingService\Reference\Action\HasActivity
{
	use Market\Reference\Concerns\HasMessage;
	use TradingService\Common\Concerns\Action\HasOrder;
	use TradingService\Common\Concerns\Action\HasOrderMarker;

	public function __construct(TradingService\Marketplace\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	public function getActivity()
	{
		return new Activity($this->provider, $this->environment);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$request = new TradingService\Marketplace\Api\VerifyEac\Request();

		$request->setLogger($logger);
		$request->setCampaignId($options->getCampaignId());
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setOrderId($this->request->getOrderId());
		$request->setCode($this->request->getCode());

		$sendResult = $request->send();

		Market\Result\Facade::handleException($sendResult);
	}
}
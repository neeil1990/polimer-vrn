<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class AbstractAction
{
	protected $provider;
	protected $environment;
	protected $request;
	protected $response;

	public function __construct(TradingService\Reference\Provider $provider, TradingEntity\Reference\Environment $environment)
	{
		$this->provider = $provider;
		$this->environment = $environment;
		$this->response = $this->createResponse();
	}

	public function getAudit()
	{
		return null;
	}

	abstract public function process();

	/**
	 * @return AbstractRequest
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return Response
	 */
	public function getResponse()
	{
		return $this->response;
	}

	protected function createResponse()
	{
		return new Response();
	}

	protected function getSiteId()
	{
		return $this->provider->getOptions()->getSiteId();
	}

	protected function getPlatform()
	{
		$platform = $this->environment->getPlatformRegistry()->getPlatform(
			$this->provider->getCode(),
			$this->getSiteId()
		);
		$platform->setSetupId($this->provider->getOptions()->getSetupId());

		return $platform;
	}
}
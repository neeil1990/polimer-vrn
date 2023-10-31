<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class DataAction extends AbstractAction
{
	public function __construct(TradingService\Reference\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment);
		$this->request = $this->createRequest($data);
	}

	protected function createRequest(array $data)
	{
		return new DataRequest($data);
	}
}
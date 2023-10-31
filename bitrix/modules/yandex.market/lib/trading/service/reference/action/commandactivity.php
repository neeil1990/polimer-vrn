<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class CommandActivity extends AbstractActivity
{
	protected $title;
	protected $payload;
	protected $parameters;

	public function __construct(
		TradingService\Reference\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		$title,
		array $payload,
		array $parameters = []
	)
	{
		parent::__construct($provider, $environment);
		$this->title = $title;
		$this->payload = $payload;
		$this->parameters = $parameters;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function useGroup()
	{
		return isset($this->parameters['USE_GROUP']) ? $this->parameters['USE_GROUP'] : false;
	}

	public function getFilter()
	{
		return isset($this->parameters['FILTER']) ? $this->parameters['FILTER'] : null;
	}

	public function getPayload()
	{
		return $this->payload;
	}

	public function getParameters()
	{
		return $this->parameters;
	}
}
<?php

namespace Yandex\Market\Trading\Service\Reference;

class Feature
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	public function supportsDeliveryChoose()
	{
		return false;
	}

	public function supportPaySystemChoose()
	{
		return false;
	}
}
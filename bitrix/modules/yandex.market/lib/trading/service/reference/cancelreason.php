<?php

namespace Yandex\Market\Trading\Service\Reference;

abstract class CancelReason
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	abstract public function getTitle($type);

	abstract public function getVariants();
}
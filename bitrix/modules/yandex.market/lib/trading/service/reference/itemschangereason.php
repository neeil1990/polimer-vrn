<?php

namespace Yandex\Market\Trading\Service\Reference;

abstract class ItemsChangeReason
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	abstract public function getDefault();

	abstract public function getTitle($type);

	abstract public function getVariants();
}
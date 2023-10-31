<?php

namespace Yandex\Market\Trading\Service\Reference;

abstract class CancellationAccept
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	abstract public function getReasonTitle($type);

	abstract public function getReasonVariants();
}
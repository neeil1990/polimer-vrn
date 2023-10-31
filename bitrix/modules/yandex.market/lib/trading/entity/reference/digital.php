<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market\Utils;

abstract class Digital
{
	use Concerns\HasModuleDependency;

	protected $environment;
	protected $settings;

	public function __construct(Environment $environment, array $settings = [])
	{
		$this->environment = $environment;
		$this->settings = $settings;
	}

	abstract public function getTitle();
	
	public function getFields($siteId)
	{
		return [];
	}

	protected function setting($name, $default = null)
	{
		$value = isset($this->settings[$name]) ? $this->settings[$name] : null;

		if (Utils\Value::isEmpty($value))
		{
			$value = $default;
		}

		return $value;
	}

	abstract public function exists(Order $order, array $basketQuantities);

	abstract public function reserve(Order $order, array $basketQuantities);

	abstract public function fail(Order $order, array $codes);

	abstract public function ship(Order $order, array $codes);
}
<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class PickupOptions extends DeliveryOptions
{
	public function getDefaultParameters()
	{
		return [ 'name' => 'pickup-options' ] + parent::getDefaultParameters();
	}

	protected function getDefaultOptions($context)
	{
		return !empty($context['DELIVERY_OPTIONS']['pickup']) ? $context['DELIVERY_OPTIONS']['pickup'] : null;
	}
}
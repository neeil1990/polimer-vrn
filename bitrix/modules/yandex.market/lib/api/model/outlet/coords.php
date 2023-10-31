<?php

namespace Yandex\Market\Api\Model\Outlet;

use Yandex\Market;

class Coords extends Market\Api\Reference\Model
{
	public function getLat()
	{
		return $this->getField('lat');
	}

	public function getLon()
	{
		return $this->getField('lon');
	}
}
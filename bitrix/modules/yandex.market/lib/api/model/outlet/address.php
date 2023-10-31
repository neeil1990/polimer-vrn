<?php

namespace Yandex\Market\Api\Model\Outlet;

use Yandex\Market;

class Address extends Market\Api\Reference\Model
{
	public function getCity()
	{
		return $this->getField('city');
	}

	public function getStreet()
	{
		return $this->getField('street');
	}

	public function getNumber()
	{
		return $this->getField('number');
	}

	public function getBuilding()
	{
		return $this->getField('building');
	}

	public function getEstate()
	{
		return $this->getField('estate');
	}

	public function getBlock()
	{
		return $this->getField('block');
	}

	public function getKm()
	{
		return $this->getField('km');
	}

	public function getAdditional()
	{
		return $this->getField('additional');
	}
}
<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

/**
 * @method Delivery getDelivery()
 */
class Environment extends Market\Trading\Entity\Common\Environment
{
	protected $marker;

	public function isSupported()
	{
		return parent::isSupported() && $this->hasSaleObjectClasses() && $this->isSaleConverted();
	}

	protected function hasSaleObjectClasses()
	{
		if (Main\Loader::includeModule('sale'))
		{
			$result = class_exists(Sale\OrderBase::class);
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function isSaleConverted()
	{
		return (Main\Config\Option::get('main', '~sale_converted_15', 'Y') === 'Y');
	}

	protected function createPlatformRegistry()
    {
        return new PlatformRegistry($this);
    }

    protected function createOrderRegistry()
    {
    	return new OrderRegistry($this);
    }

    protected function createUserRegistry()
    {
		return new UserRegistry($this);
    }

	protected function createStatus()
    {
		return new Status($this);
    }

    protected function createListener()
    {
		return new Listener($this);
    }

    protected function createAdminExtension()
    {
		return new AdminExtension($this);
    }

    protected function createDelivery()
    {
    	return new Delivery($this);
    }

	protected function createOutletRegistry()
	{
		return new OutletRegistry($this);
	}

	protected function createCourierRegistry()
	{
		return new CourierRegistry($this);
	}

	protected function createPaySystem()
    {
    	return new PaySystem($this);
    }

    protected function createPersonType()
    {
    	return new PersonType($this);
    }

    protected function createProfile()
    {
    	return new Profile($this);
    }

    protected function createProperty()
    {
	    return new Property($this);
    }

	protected function createLocation()
    {
    	return new Location($this);
    }

	protected function createReserve()
	{
		return class_exists(Sale\ReserveQuantityCollection::class)
			? new Reserve\Basket($this)
			: new Reserve\Shipment($this);
	}

	public function getMarker()
    {
    	if ($this->marker === null)
	    {
	    	$this->marker = $this->loadMarker();
	    }

    	return $this->marker;
    }

    protected function loadMarker()
    {
    	return new Marker($this);
    }

    protected function getRequiredModules()
    {
        return array_merge(
        	parent::getRequiredModules(),
	        [ 'catalog', 'sale' ]
        );
    }
}
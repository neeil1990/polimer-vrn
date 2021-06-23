<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Environment
{
	protected $code;
	protected $orderRegistry;
	protected $userGroupRegistry;
	protected $userRegistry;
	protected $platformRegistry;
	protected $product;
	protected $store;
	protected $price;
	protected $site;
	protected $route;
	protected $status;
	protected $location;
	protected $personType;
	protected $profile;
	protected $property;
	protected $paySystem;
	protected $delivery;
	protected $listener;
	protected $adminExtension;

	public function __construct($code)
	{
		$this->code = $code;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function isSupported()
	{
		return $this->isModulesInstalled();
	}

	protected function isModulesInstalled()
	{
		$result = true;

		foreach ($this->getRequiredModules() as $module)
		{
			if (!Main\ModuleManager::isModuleInstalled($module))
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	public function load()
	{
		$this->loadModules();
	}

	protected function loadModules()
	{
		foreach ($this->getRequiredModules() as $module)
		{
			if (!Main\Loader::includeModule($module))
			{
				throw new Main\SystemException('Cant load required module ' . $module);
			}
		}
	}

	protected function getRequiredModules()
	{
		return [];
	}

	public function getOrderRegistry()
	{
		if ($this->orderRegistry === null)
		{
			$this->orderRegistry = $this->createOrderRegistry();
		}

		return $this->orderRegistry;
	}

	/**
	 * @return OrderRegistry
	 */
	protected function createOrderRegistry()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'OrderRegistry');
	}

	public function getUserGroupRegistry()
	{
		if ($this->userGroupRegistry === null)
		{
			$this->userGroupRegistry = $this->createUserGroupRegistry();
		}

		return $this->userGroupRegistry;
	}

	/**
	 * @return UserGroupRegistry
	 */
	protected function createUserGroupRegistry()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'UserGroupRegistry');
	}

	public function getUserRegistry()
	{
		if ($this->userRegistry === null)
		{
			$this->userRegistry = $this->createUserRegistry();
		}

		return $this->userRegistry;
	}

	/**
	 * @return UserRegistry
	 */
	protected function createUserRegistry()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'UserRegistry');
	}

	public function getPlatformRegistry()
	{
		if ($this->platformRegistry === null)
		{
			$this->platformRegistry = $this->createPlatformRegistry();
		}

		return $this->platformRegistry;
	}

	/**
	 * @return PlatformRegistry
	 */
	protected function createPlatformRegistry()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'PlatformRegistry');
	}

	public function getProduct()
	{
		if ($this->product === null)
		{
			$this->product = $this->createProduct();
		}

		return $this->product;
	}

	/**
	 * @return Product
	 */
	protected function createProduct()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Product');
	}

	public function getStore()
	{
		if ($this->store === null)
		{
			$this->store = $this->createStore();
		}

		return $this->store;
	}

	/**
	 * @return Store
	 */
	protected function createStore()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Store');
	}

	public function getPrice()
	{
		if ($this->price === null)
		{
			$this->price = $this->createPrice();
		}

		return $this->price;
	}

	/**
	 * @return Price
	 */
	protected function createPrice()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Price');
	}

	public function getSite()
	{
		if ($this->site === null)
		{
			$this->site = $this->createSite();
		}

		return $this->site;
	}

	/**
	 * @return Site
	 */
	protected function createSite()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Site');
	}

	public function getListener()
	{
		if ($this->listener === null)
		{
			$this->listener = $this->createListener();
		}

		return $this->listener;
	}

	/**
	 * @return Listener
	 */
	protected function createListener()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Listener');
	}

	public function getAdminExtension()
	{
		if ($this->adminExtension === null)
		{
			$this->adminExtension = $this->createAdminExtension();
		}

		return $this->adminExtension;
	}

	/**
	 * @return AdminExtension
	 */
	protected function createAdminExtension()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'createAdminExtension');
	}

	public function getRoute()
	{
		if ($this->route === null)
		{
			$this->route = $this->createRoute();
		}

		return $this->route;
	}

	/**
	 * @return Route
	 */
	protected function createRoute()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Route');
	}

	public function getStatus()
	{
		if ($this->status === null)
		{
			$this->status = $this->createStatus();
		}

		return $this->status;
	}

	/**
	 * @return Status
	 */
	protected function createStatus()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Status');
	}

	public function getLocation()
	{
		if ($this->location === null)
		{
			$this->location = $this->createLocation();
		}

		return $this->location;
	}

	/**
	 * @return Location
	 */
	protected function createLocation()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Location');
	}

	public function getPersonType()
	{
		if ($this->personType === null)
		{
			$this->personType = $this->createPersonType();
		}

		return $this->personType;
	}

	/**
	 * @return PersonType
	 */
	protected function createPersonType()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'PersonType');
	}

	public function getProfile()
	{
		if ($this->profile === null)
		{
			$this->profile = $this->createProfile();
		}

		return $this->profile;
	}

	/**
	 * @return Profile
	 */
	protected function createProfile()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Profile');
	}

	public function getProperty()
	{
		if ($this->property === null)
		{
			$this->property = $this->createProperty();
		}

		return $this->property;
	}

	/**
	 * @return Property
	 */
	protected function createProperty()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Property');
	}

	public function getPaySystem()
	{
		if ($this->paySystem === null)
		{
			$this->paySystem = $this->createPaySystem();
		}

		return $this->paySystem;
	}

	/**
	 * @return PaySystem
	 */
	protected function createPaySystem()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'PaySystem');
	}

	public function getDelivery()
	{
		if ($this->delivery === null)
		{
			$this->delivery = $this->createDelivery();
		}

		return $this->delivery;
	}

	/**
	 * @return Delivery
	 */
	protected function createDelivery()
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Delivery');
	}
}
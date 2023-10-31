<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;

abstract class Provider
{
	protected $code;
	protected $router;
	protected $options;
	protected $installer;
	protected $info;
	protected $logger;
	protected $status;
	protected $printer;
	protected $modelFactory;
	protected $dictionary;
	protected $feature;
	protected $container;

	public function __construct($code)
	{
		$this->code = $code;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getUniqueKey()
	{
		return $this->getCode();
	}

	public function getServiceCode()
	{
		return $this->code;
	}

	public function getBehaviorCode()
	{
		return Market\Trading\Service\Manager::BEHAVIOR_DEFAULT;
	}

	public function isExperiment()
	{
		return false;
	}

	public function wakeup()
	{
		// nothing by default
	}

	public function getRouter()
	{
		if ($this->router === null)
		{
			$this->router = $this->createRouter();
		}

		return $this->router;
	}

	/**
	 * @return Router
	 */
	abstract protected function createRouter();

	public function getInstaller()
	{
		if ($this->installer === null)
		{
			$this->installer = $this->createInstaller();
		}

		return $this->installer;
	}

	/**
	 * @return Installer
	 */
	abstract protected function createInstaller();

	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->options = $this->createOptions();
		}

		return $this->options;
	}

	/**
	 * @return Options
	 */
	abstract protected function createOptions();

	public function getInfo()
	{
		if ($this->info === null)
		{
			$this->info = $this->createInfo();
		}

		return $this->info;
	}

	/**
	 * @return Info
	 */
	abstract protected function createInfo();

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
	abstract protected function createStatus();

	public function getLogger()
	{
		if ($this->logger === null)
		{
			$this->logger = $this->createLogger();
		}

		return $this->logger;
	}

	/**
	 * @return Market\Psr\Log\LoggerInterface
	 */
	abstract protected function createLogger();

	public function getPrinter()
	{
		if ($this->printer === null)
		{
			$this->printer = $this->createPrinter();
		}

		return $this->printer;
	}

	/**
	 * @return Printer
	 */
	protected function createPrinter()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'createPrinter');
	}

	public function getModelFactory()
	{
		if ($this->modelFactory === null)
		{
			$this->modelFactory = $this->createModelFactory();
		}

		return $this->modelFactory;
	}

	/**
	 * @return ModelFactory
	 */
	protected function createModelFactory()
	{
		return new ModelFactory($this);
	}

	public function getDictionary()
	{
		if ($this->dictionary === null)
		{
			$this->dictionary = $this->createDictionary();
		}

		return $this->dictionary;
	}

	/**
	 * @return Dictionary
	 */
	protected function createDictionary()
	{
		return new Dictionary($this);
	}

	public function getFeature()
	{
		if ($this->feature === null)
		{
			$this->feature = $this->createFeature();
		}

		return $this->feature;
	}

	protected function createFeature()
	{
		return new Feature($this);
	}

	public function getContainer()
	{
		if ($this->container === null)
		{
			$this->container = $this->createContainer();
		}

		return $this->container;
	}

	protected function createContainer()
	{
		return new Container($this);
	}
}
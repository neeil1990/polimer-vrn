<?php

namespace Yandex\Market\Trading\Service\Reference;

use Bitrix\Main;
use Yandex\Market;

class Container
{
	protected $provider;
	protected $instances = [];

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param array $arguments
	 *
	 * @return T
	 */
	public function single($className, array $arguments = [])
	{
		$hash = Market\Utils\Caller::getArgumentsHash($arguments);
		$key = $className . ':' . $hash;

		if (!isset($this->instances[$key]))
		{
			$this->instances[$key] = $this->build($className, $arguments);
		}

		return $this->instances[$key];
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param array $arguments
	 *
	 * @return T
	 */
	public function get($className, array $arguments = [])
	{
		return $this->build($className, $arguments);
	}

	protected function build($className, array $arguments)
	{
		$className = $this->localName($className);
		$reflection = new \ReflectionClass($className);
		$dependencies = $this->dependencies($reflection, $arguments);

		if (empty($dependencies)) { return new $className(); }

		return $reflection->newInstanceArgs($dependencies);
	}

	protected function localName($className)
	{
		$result = $className;
		$namespaces = $this->namespaces();
		$relativeName = null;

		foreach (array_reverse($namespaces) as $namespace)
		{
			if ($relativeName === null && Market\Data\TextString::getPosition($className, $namespace) === 0)
			{
				$relativeName = Market\Data\TextString::getSubstring($className, Market\Data\TextString::getLength($namespace));
				continue;
			}

			if ($relativeName !== null && class_exists($namespace . $relativeName))
			{
				$result = $namespace . $relativeName;
			}
		}

		return $result;
	}

	protected function dependencies(\ReflectionClass $reflection, array $arguments)
	{
		$constructor = $reflection->getConstructor();

		if ($constructor === null) { return []; }

		$arguments += [
			'provider' => $this->provider,
		];
		$result = [];

		foreach ($constructor->getParameters() as $parameter)
		{
			$name = $parameter->getName();

			if (isset($arguments[$name]) || array_key_exists($name, $arguments))
			{
				$value = $arguments[$name];
			}
			else if ($parameter->isDefaultValueAvailable())
			{
				$value = $parameter->getDefaultValue();
			}
			else
			{
				throw new Main\SystemException(sprintf('cant find argument %s for %s', $name, $reflection->getName()));
			}

			$result[] = $value;
		}

		return $result;
	}

	protected function namespaces()
	{
		$reflection = new \ReflectionClass($this->provider);
		$result = [
			$reflection->getNamespaceName(),
		];

		while ($reflection = $reflection->getParentClass())
		{
			$result[] = $reflection->getNamespaceName();
		}

		return $result;
	}
}
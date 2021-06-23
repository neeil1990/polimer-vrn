<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;

abstract class Printer
{
	protected $provider;
	protected $map;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * @param string $type
	 *
	 * @return Document\AbstractDocument
	 * @throws Market\Exceptions\Trading\NotImplementedAction
	 */
	public function getDocument($type)
	{
		$map = $this->getMap();

		if (!isset($map[$type]))
		{
			$message = 'Document not found for ' . $type;
			throw new Market\Exceptions\Trading\NotImplementedAction($message);
		}

		return new $map[$type]($this->provider);
	}

	public function getTypes()
	{
		$map = $this->getMap();

		return array_keys($map);
	}

	/**
	 * Соответствие типов и документов
	 *
	 * @return array<string, Document\AbstractDocument>
	 */
	public function getMap()
	{
		if ($this->map === null)
		{
			$this->map = $this->getUserMap() + $this->getSystemMap(); // allow user override
		}

		return $this->map;
	}

	/**
	 * @return array<string, Document\AbstractDocument>
	 */
	abstract protected function getSystemMap();

	/**
	 * @return array<string, Document\AbstractDocument>
	 */
	protected function getUserMap()
	{
		$result = [];
		$module = Market\Config::getModuleName();
		$name = 'onTradingDocumentBuildList';
		$parameters =[
			'PROVIDER' => $this->provider,
		];

		$event = new Main\Event($module, $name, $parameters);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() !== Main\EventResult::SUCCESS) { continue; }

			$eventResultParameters = $eventResult->getParameters();

			if (!is_array($eventResultParameters))
			{
				throw new Main\SystemException('Event result parameters of ' . $name . ' must be an array');
			}

			foreach ($eventResultParameters as $type => $className)
			{
				if (!is_subclass_of($className, Document\AbstractDocument::class))
				{
					throw new Main\SystemException($className . ' must extends ' . Document\AbstractDocument::class . ' for document ' . $type);
				}

				$result[$type] = $className;
			}
		}

		return $result;
	}
}
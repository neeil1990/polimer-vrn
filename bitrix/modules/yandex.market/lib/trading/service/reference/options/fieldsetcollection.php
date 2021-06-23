<?php

namespace Yandex\Market\Trading\Service\Reference\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

/** @method Fieldset current() */
abstract class FieldsetCollection
	implements \ArrayAccess, \Countable, \IteratorAggregate
{
	use Market\Reference\Concerns\HasCollection;

	protected $provider;
	protected $configurationItem;

	public function __construct(TradingService\Reference\Provider $provider)
	{
		$this->provider = $provider;
	}

	/** @return Fieldset */
	abstract public function getItemReference();

	public function getFieldDescription(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return
			[ 'MULTIPLE' => 'Y' ]
			+ $this->getConfigurationItem()->getFieldDescription($environment, $siteId);
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return $this->getConfigurationItem()->getFields($environment, $siteId);
	}

	public function setValues(array $values)
	{
		$this->collection = [];

		foreach ($values as $fieldsetValues)
		{
			$item = $this->createItem();
			$item->setValues($fieldsetValues);

			$this->collection[] = $item;
		}
	}

	public function getValues()
	{
		$result = [];

		foreach ($this->collection as $fieldset)
		{
			$result[] = $fieldset->getValues();
		}

		return $result;
	}

	protected function createItem()
	{
		$itemReference = $this->getItemReference();

		return new $itemReference($this->provider);
	}

	protected function getConfigurationItem()
	{
		if ($this->configurationItem !== null)
		{
			$result = $this->configurationItem;
		}
		else if (!empty($this->collection))
		{
			$result = reset($this->collection);
		}
		else
		{
			$result = $this->createItem();
			$this->configurationItem = $result;
		}

		return $result;
	}
}
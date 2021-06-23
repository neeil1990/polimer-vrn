<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Action;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasMeaningfulProperties
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingEntity\Reference\Order $order
 */
trait HasMeaningfulProperties
{
	protected function setMeaningfulPropertyValues($values)
	{
		$formattedValues = $this->formatMeaningfulPropertyValues($values);
		$propertyValues = $this->combineMeaningfulPropertyValues($formattedValues);

		if (!empty($propertyValues))
		{
			$fillResult = $this->order->fillProperties($propertyValues);
			$fillData = $fillResult->getData();

			if (isset($fillData['FILLED']))
			{
				$filledMap = array_fill_keys((array)$fillData['FILLED'], true);

				if (isset($this->filledProperties))
				{
					$this->filledProperties += array_intersect_key($propertyValues, $filledMap);
				}

				if (isset($this->relatedProperties))
				{
					$this->relatedProperties += array_diff_key($propertyValues, $filledMap);
				}
			}

			if (!empty($fillData['CHANGES']) && \method_exists($this, 'pushChange'))
			{
				$this->pushChange('PROPERTIES', $fillData['CHANGES']);
			}
		}
	}

	protected function formatMeaningfulPropertyValues($values)
	{
		$options = $this->provider->getOptions();
		$personType = $options->getPersonType();

		return $this->environment->getProperty()->formatMeaningfulValues($personType, $values);
	}

	protected function combineMeaningfulPropertyValues($values)
	{
		$options = $this->provider->getOptions();
		$propertyValues = [];

		foreach ($values as $name => $value)
		{
			$propertyId = (string)$options->getProperty($name);

			if ($propertyId === '') { continue; }

			if (!isset($propertyValues[$propertyId]))
			{
				$propertyValues[$propertyId] = $value;
			}
			else
			{
				if (!is_array($propertyValues[$propertyId]))
				{
					$propertyValues[$propertyId] = [
						$propertyValues[$propertyId],
					];
				}

				if (is_array($value))
				{
					$propertyValues[$propertyId] = array_merge($propertyValues[$propertyId], $value);
				}
				else
				{
					$propertyValues[$propertyId][] = $value;
				}
			}
		}

		return $propertyValues;
	}

	protected function getConfiguredMeaningfulProperties($names)
	{
		$options = $this->provider->getOptions();
		$result = [];

		foreach ($names as $name)
		{
			$propertyId = (string)$options->getProperty($name);

			if ($propertyId !== '')
			{
				$result[$name] = $propertyId;
			}
		}

		return $result;
	}
}
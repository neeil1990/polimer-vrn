<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class DeliveryOptions extends Base
{
	protected $requiredAttributeNames;

	public function getDefaultParameters()
	{
		return [
			'empty_value' => !Market\Config::isExpertMode(),
			'name' => 'delivery-options',
			'value_type' => Market\Type\Manager::TYPE_DELIVERY_OPTIONS
		];
	}

	protected function getDefaultOptions($context)
	{
		return !empty($context['DELIVERY_OPTIONS']['delivery']) ? $context['DELIVERY_OPTIONS']['delivery'] : null;
	}

	public function exportTagMultiple($tagValuesList, $context, \SimpleXMLElement $parent)
	{
		$initialHasEmptyValue = $this->hasEmptyValue;
		$exportResultList = [];
		$hasSuccess = false;

		$this->hasEmptyValue = true;

		$tagValues = $this->getTagValues($tagValuesList, $this->id, true);
		$tagValues = $this->normalizeTagValues($tagValues);

		if ($this->hasFilledTagValuesForDeliveryOptions($tagValues))
		{
			$tagValuesList[$this->id] = $tagValues;
			$exportResultList = parent::exportTagMultiple($tagValuesList, $context, $parent);

			foreach ($exportResultList as $exportResult)
			{
				if ($exportResult->isSuccess())
				{
					$hasSuccess = true;
					break;
				}
			}
		}

		if (!$hasSuccess)
		{
			$defaultOptions = $this->getDefaultOptions($context);
			$tagValues = $this->convertOptionsToTagValues($defaultOptions);

			if (!empty($tagValues))
			{
				$tagValuesList[$this->id] = $tagValues;
				$exportResultList = parent::exportTagMultiple($tagValuesList, $context, $parent);
			}
		}

		$this->hasEmptyValue = $initialHasEmptyValue;

		return $exportResultList;
	}

	public function exportTagSingle($tagValuesList, $context, \SimpleXMLElement $parent)
	{
		$initialHasEmptyValue = $this->hasEmptyValue;
		$exportResult = null;
		$hasSuccess = false;

		$this->hasEmptyValue = true;

		$tagValue = $this->getTagValues($tagValuesList, $this->id);
		list($tagValue) = $this->normalizeTagValues([$tagValue]);

		if ($this->hasFilledTagValuesForDeliveryOptions([$tagValue]))
		{
			$tagValuesList[$this->id] = $tagValue;
			$exportResult = parent::exportTagSingle($tagValuesList, $context, $parent);

			if ($exportResult->isSuccess())
			{
				$hasSuccess = true;
			}
		}

		if (!$hasSuccess)
		{
			$defaultOptions = $this->getDefaultOptions($context);
			$tagValues = $this->convertOptionsToTagValues($defaultOptions);

			if (!empty($tagValues))
			{
				$tagValuesList[$this->id] = $tagValues;
				$exportResult = parent::exportTagSingle($tagValuesList, $context, $parent);
			}
		}

		if ($exportResult === null)
		{
			$exportResult = new Market\Result\XmlNode();
			$exportResult->invalidate();
		}

		$this->hasEmptyValue = $initialHasEmptyValue;

		return $exportResult;
	}

	public function exportNode($value, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$deliveryOptionsTag = isset($parent->{$this->name}) ? $parent->{$this->name} : $parent->addChild($this->name);

		return $deliveryOptionsTag->addChild('option');
	}

	public function detachNode(\SimpleXMLElement $parent, \SimpleXMLElement $node = null)
	{
		if ($node !== null)
		{
			unset($node[0]);

			if (isset($parent->{$this->name}))
			{
				$deliveryOptionsTag = $parent->{$this->name};

				if (count($deliveryOptionsTag->children()) === 0)
				{
					unset($deliveryOptionsTag[0]);
				}
			}
		}
	}

	protected function hasFilledTagValuesForDeliveryOptions($tagValues)
	{
		$result = false;
		$requiredAttributes = $this->getRequiredAttributeNames();

		if (!empty($requiredAttributes) && !empty($tagValues))
		{
			foreach ($tagValues as $tagValue)
			{
				$isValidTagValue = true;

				foreach ($requiredAttributes as $requiredAttribute)
				{
					if (
						!isset($tagValue['ATTRIBUTES'][$requiredAttribute])
						|| $tagValue['ATTRIBUTES'][$requiredAttribute] === ''
					)
					{
						$isValidTagValue = false;
						break;
					}
				}

				if ($isValidTagValue)
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	protected function normalizeTagValues($tagValues)
	{
		$result = [];

		foreach ($tagValues as $tagValue)
		{
			$hasConverted = false;

			if (isset($tagValue['VALUE']) && is_array($tagValue['VALUE']))
			{
				$convertedTagValue = $this->convertOptionToTagValue($tagValue['VALUE']);

				if ($convertedTagValue !== null)
				{
					$hasConverted = true;
					$result[] = $convertedTagValue;
				}
				else
				{
					foreach ($tagValue['VALUE'] as $innerValue)
					{
						$convertedInnerValue = $this->convertOptionToTagValue($innerValue);

						if ($convertedInnerValue !== null)
						{
							$hasConverted = true;
							$result[] = $convertedInnerValue;
						}
					}
				}
			}

			if (!$hasConverted)
			{
				$result[] = $tagValue;
			}
		}

		return $result;
	}

	protected function convertOptionsToTagValues($options)
	{
		$result = [];

		if (is_array($options))
		{
			foreach ($options as $option)
			{
				$tagValue = $this->convertOptionToTagValue($option);

				if ($tagValue !== null)
				{
					$result[] = $tagValue;
				}
			}
		}

		return $result;
	}

	protected function convertOptionToTagValue($option)
	{
		$result = null;

		if (isset($option['COST'], $option['DAYS']))
		{
			$result = [
				'VALUE' => null,
				'ATTRIBUTES' => [
					'cost' => $option['COST'],
					'days' => $option['DAYS'],
					'order-before' => isset($option['ORDER_BEFORE']) ? $option['ORDER_BEFORE'] : null
				]
			];
		}

		return $result;
	}

	protected function getRequiredAttributeNames()
	{
		if ($this->requiredAttributeNames === null)
		{
			$this->requiredAttributeNames = $this->loadRequiredAttributeNames();
		}

		return $this->requiredAttributeNames;
	}

	protected function loadRequiredAttributeNames()
	{
		$result = [];

		foreach ($this->getAttributes() as $attribute)
		{
			if ($attribute->isRequired())
			{
				$result[] = $attribute->getId();
			}
		}

		return $result;
	}
}
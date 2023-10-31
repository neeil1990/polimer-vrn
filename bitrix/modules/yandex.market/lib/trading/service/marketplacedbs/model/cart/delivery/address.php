<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Cart\Delivery;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Address extends Market\Api\Reference\Model
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function fromOutlet(Market\Api\Model\Outlet $outlet)
	{
		$fields = [];
		$address = $outlet->getAddress();
		$coords = $outlet->getCoords();
		$phones = $outlet->getPhones();

		if ($address !== null)
		{
			$fields += [
				'city' => $address->getCity(),
				'street' => $address->getStreet(),
				'house' => implode('/', array_filter([
					$address->getNumber(),
					$address->getBuilding(),
				])),
				'block' => $address->getBlock(),
				'apartment' => $address->getEstate(),
				'recipient' => $address->getAdditional(),
			];
		}

		if ($coords !== null)
		{
			$fields += [
				'lat' => $coords->getLat(),
				'lon' => $coords->getLon(),
			];
		}

		if (!empty($phones))
		{
			$fields['phone'] = reset($phones);
		}

		return new static($fields);
	}

	public static function getZipFields()
	{
		return [
			'POSTCODE',
		];
	}

	public static function getCityFields()
	{
		return [
			'COUNTRY',
			'CITY',
		];
	}

	public static function getAddressFields()
	{
		return [
			'SUBWAY',
			'STREET',
			'HOUSE',
			'BLOCK',
			'ENTRANCE',
			'ENTRYPHONE',
			'FLOOR',
			'APARTMENT',
			'RECIPIENT_PHONE',
			'RECIPIENT',
		];
	}

	public static function getCoordinatesFields()
	{
		return [
			'LAT',
			'LON',
		];
	}

	public static function getFieldTitle($fieldName)
	{
		return static::getLang('API_MODEL_ORDER_DELIVERY_ADDRESS_FIELD_' . $fieldName, null, $fieldName);
	}

	public function getZipValues()
	{
		return [
			'POSTCODE' => $this->getField('postcode'),
		];
	}

	public function getCityValues()
	{
		return [
			'COUNTRY' => $this->getField('country'),
			'CITY' => $this->getField('city'),
		];
	}

	public function getAddressValues()
	{
		return [
			'SUBWAY' => $this->getField('subway'),
			'STREET' => $this->getField('street'),
			'HOUSE' => $this->getField('house'),
			'BLOCK' => $this->getField('block'),
			'ENTRANCE' => $this->getField('entrance'),
			'ENTRYPHONE' => $this->getField('entryphone'),
			'FLOOR' => $this->getField('floor'),
			'APARTMENT' => $this->getField('apartment'),
			'RECIPIENT_PHONE' => $this->getField('phone'),
			'RECIPIENT' => $this->getField('recipient'),
		];
	}

	public function getMeaningfulZip()
	{
		$values = $this->getZipValues();

		return $this->combineValues($values);
	}

	public function getMeaningfulCity()
	{
		$values = $this->getCityValues();

		return $this->combineValues($values);
	}

	public function getMeaningfulAddress(array $skipAdditionalTypes = [])
	{
		$values = $this->getAddressValues();

		return $this->combineAddress($values, $skipAdditionalTypes);
	}

	public function getLat()
	{
		return $this->getField('lat');
	}

	public function getLon()
	{
		return $this->getField('lon');
	}

	protected function combineValues($values)
	{
		$values = array_filter($values, static function($value) { return (string)$value !== ''; });

		return implode(', ', $values);
	}

	protected function combineAddress($values, array $skipAdditionalTypes = [])
	{
		$commonFields = [
			'SUBWAY' => true,
			'STREET' => true,
			'HOUSE' => true,
			'BLOCK' => true,
			'ENTRANCE' => true,
			'APARTMENT' => true,
		];
		$commonValues = [];
		$additionalValues = [];

		foreach ($values as $type => $value)
		{
			if ((string)$value === '') { continue; }

			$displayValue = $this->getAddressTypeValue($type, $value);

			if (isset($commonFields[$type]))
			{
				$commonValues[] = $displayValue;
			}
			else if (!in_array($type, $skipAdditionalTypes, true))
			{
				$additionalValues[] = $displayValue;
			}
		}

		$result = implode(', ', $commonValues);

		if (!empty($additionalValues))
		{
			$result .= ' (' . implode(', ', $additionalValues) . ')';
		}

		return $result;
	}

	protected function getAddressTypeValue($type, $value)
	{
		$prefix = $this->getAddressTypePrefix($type);

		return
			($prefix !== '' ? $prefix . ' ' : '')
			. $value;
	}

	protected function getAddressTypePrefix($type)
	{
		return (string)static::getLang('API_MODEL_ORDER_DELIVERY_ADDRESS_TYPE_' . $type, null, '');
	}
}
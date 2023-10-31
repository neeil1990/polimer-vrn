<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order\Delivery;

use Yandex\Market;

class Courier extends Market\Api\Reference\Model
{
	use Market\Reference\Concerns\HasMessage;

	public static function getMeaningfulFields()
	{
		return [
			'FULL_NAME',
			'PHONE',
			'VEHICLE_NUMBER',
			'VEHICLE_DESCRIPTION',
		];
	}

	public static function getMeaningfulFieldTitle($name)
	{
		return self::getMessage($name);
	}

	public function getMeaningfulValues()
	{
		return [
			'FULL_NAME' => $this->getFullName(),
			'PHONE' => $this->getMeaningfulPhone(),
			'VEHICLE_NUMBER' => $this->getVehicleNumber(),
			'VEHICLE_DESCRIPTION' => $this->getVehicleDescription(),
		];
	}

	public function getMeaningfulPhone()
	{
		$result = $this->getPhone();
		$extension = $this->getPhoneExtension();

		if ((string)$extension !== '')
		{
			$result .= ', ' . self::getMessage('PHONE_EXTENSION', [ '#EXTENSION#' => $extension ], $extension);
		}

		return $result;
	}

	public function getFullName()
	{
		return $this->getField('fullName');
	}

	/** @deprecated */
	public function getFio()
	{
		return $this->getFullName();
	}

	public function getPhone()
	{
		return $this->getField('phone');
	}

	public function getPhoneExtension()
	{
		return $this->getField('phoneExtension');
	}

	public function getVehicleNumber()
	{
		return $this->getField('vehicleNumber');
	}

	public function getVehicleDescription()
	{
		return $this->getField('vehicleDescription');
	}

	/** @deprecated */
	public function getCarNumber()
	{
		return $this->getVehicleNumber();
	}

	/** @deprecated */
	public function getCarDescription()
	{
		return $this->getVehicleDescription();
	}
}
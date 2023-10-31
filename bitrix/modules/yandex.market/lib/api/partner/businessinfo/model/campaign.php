<?php

namespace Yandex\Market\Api\Partner\BusinessInfo\Model;

use Yandex\Market;

/** @deprecated */
class Campaign extends Market\Api\Reference\Model
{
	const PROGRAM_DROPSHIP_BY_SELLER = 'DROPSHIP_BY_SELLER';
	const PROGRAM_DROPSHIP = 'DROPSHIP';

	public function getId()
	{
		return (int)$this->getRequiredField('id');
	}

	public function getPartnerId()
	{
		return (int)$this->getRequiredField('partnerId');
	}

	public function getTradingBehavior()
	{
		switch ($this->getProgram())
		{
			case static::PROGRAM_DROPSHIP_BY_SELLER:
				$result = Market\Trading\Service\Manager::BEHAVIOR_DBS;
			break;

			case static::PROGRAM_DROPSHIP:
				$result = Market\Trading\Service\Manager::BEHAVIOR_DEFAULT;
			break;

			default:
				$result = null;
			break;
		}

		return $result;
	}

	public function getProgram()
	{
		return $this->getField('program');
	}

	public function getEnabled()
	{
		return (bool)$this->getRequiredField('enabled');
	}

	public function getInternalName()
	{
		return (string)$this->getField('internalName');
	}

	/** @return int[]|null */
	public function getWarehouseIds()
	{
		return $this->getField('warehouseIds');
	}
}
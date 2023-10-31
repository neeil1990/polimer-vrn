<?php
/** @noinspection PhpReturnDocTypeMismatchInspection */
/** @noinspection PhpIncompatibleReturnTypeInspection */
namespace Yandex\Market\Api\Campaigns\Model;

use Yandex\Market\Api;
use Yandex\Market\Trading;

class Campaign extends Api\Reference\Model
{
	const PLACEMENT_FBS = 'FBS';
	const PLACEMENT_FBY = 'FBY';
	const PLACEMENT_DBS = 'DBS';

	public function getId()
	{
		return (int)$this->getRequiredField('id');
	}

	public function getDomain()
	{
		return (string)$this->getRequiredField('domain');
	}

	/** @return Business */
	public function getBusiness()
	{
		return $this->getRequiredModel('business');
	}

	public function getPlacementType()
	{
		return (string)$this->getRequiredField('placementType');
	}

	public function getTradingBehavior()
	{
		switch ($this->getPlacementType())
		{
			case static::PLACEMENT_FBS:
			case static::PLACEMENT_FBY:
				$result = Trading\Service\Manager::BEHAVIOR_DEFAULT;
			break;

			case static::PLACEMENT_DBS:
				$result = Trading\Service\Manager::BEHAVIOR_DBS;
			break;

			default:
				$result = null;
			break;
		}

		return $result;
	}

	protected function getChildModelReference()
	{
		return [
			'business' => Business::class,
		];
	}
}
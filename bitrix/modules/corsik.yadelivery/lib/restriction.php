<?php

namespace Corsik\YaDelivery;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Sale\Internals\Entity;

Loc::loadMessages(__FILE__);

class Restriction extends Restrictions\Base
{
	public static function getClassTitle()
	{
		return Loc::getMessage("CORSIK_DELIVERY_BY_DISTANCE");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("CORSIK_DELIVERY_MAX_DISTANCE");
	}

	/*
	 * �������� ���������
	 * 0 - � ����� ������
	 * 1 - MAX_DISTANCE - ������������ ���������� ����������� ��������
	 */
	public static function check($chek, array $params, $deliveryId = 0)
	{
		$length = ceil($_COOKIE['yaRouteLength'] / 1000);
		if ($length == 0 || is_numeric($length) && $params['MAX_DISTANCE'] >= $length)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	 * ������ �������� ������ ��� ��������
	 */

	public static function getParamsStructure($entityId = 0)
	{
		return [
			"MAX_DISTANCE" => [
				'TYPE' => 'NUMBER',
				'DEFAULT' => "0",
				'LABEL' => Loc::getMessage("CORSIK_DELIVERY_MAX_DISTANCE_LABEL"),
			],
		];
	}

	protected static function extractParams(Entity $shipment)
	{
		return true;
	}
}

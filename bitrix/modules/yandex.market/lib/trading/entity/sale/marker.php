<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Marker
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function hasExternalEntity()
	{
		return class_exists(Sale\EntityMarker::class);
	}

	public function hasMarkers($orderId)
	{
		$query = Sale\EntityMarker::getList([
			'filter' => [
				'=ENTITY_TYPE' => 'ORDER',
				'=ENTITY_ID' => $orderId,
			],
			'select' => [ 'ID' ],
			'limit' => 1
		]);

		return (bool)$query->fetch();
	}

	public function getMarkerId($orderId, $code, $codeCondition = null)
	{
		$result = null;

		if ($codeCondition === null)
		{
			$codeCondition = '=';
		}

		$query = Sale\EntityMarker::getList([
			'filter' => [
				'=ENTITY_TYPE' => 'ORDER',
				'=ENTITY_ID' => $orderId,
				$codeCondition . 'CODE' => $code
			],
			'select' => [ 'ID' ],
			'limit' => 1
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ID'];
		}

		return $result;
	}

	public function getActive($orderId, $limit = 5)
	{
		$query = Sale\EntityMarker::getList([
			'filter' => [
				'=ENTITY_TYPE' => 'ORDER',
				'=ENTITY_ID' => $orderId,
			],
			'select' => [ 'CODE', 'MESSAGE' ],
			'limit' => $limit,
		]);

		return $query->fetchAll();
	}

	public function hasSameMarker(Sale\OrderBase $order, $message)
	{
		$exists = Sale\EntityMarker::getMarker($order->getInternalId());

		if (empty($exists) || !is_array($exists)) { return false; }

		$compareMessage = mb_strtolower($message);
		$result = false;

		foreach ($exists as $typeExists)
		{
			foreach ($typeExists as $exist)
			{
				if (!isset($exist['MESSAGE'])) { continue; }

				$existMessage = mb_strtolower($exist['MESSAGE']);

				if (mb_strpos($compareMessage, $existMessage) !== false || mb_strpos($existMessage, $compareMessage) !== false)
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	public function addMarker(Sale\OrderBase $order, Sale\Internals\Entity $entity, $message, $code)
	{
		$markerResult = new Sale\Result();
		$markerResult->addWarning(new Main\Error($message, $code));

		Sale\EntityMarker::addMarker($order, $entity, $markerResult);
	}

	public function delete($id)
	{
		return Sale\EntityMarker::delete($id);
	}
}
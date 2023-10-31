<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendShipmentExcludeOrders;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Reference\Action\FormActivity
{
	use Market\Reference\Concerns\HasMessage;

	protected $orderIds;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getSourceType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT;
	}

	public function getFilter()
	{
		return [
			'PROCESSING' => true,
		];
	}

	public function getFields()
	{
		return [
			'orderIds' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getMessage('ORDER_IDS'),
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'DISPLAY' => 'CHECKBOX',
				],
			],
		];
	}

	public function getEntityValues($entity)
	{
		/** @var TradingService\Marketplace\Model\ShipmentDetails $entity */
		Market\Reference\Assert::typeOf($entity, TradingService\Marketplace\Model\ShipmentDetails::class, 'entity');

		$this->orderIds = $entity->getOrderIds();

		if (empty($this->orderIds))
		{
			throw new Main\SystemException(self::getMessage('SHIPMENT_WITHOUT_ORDERS'));
		}

		return [];
	}

	public function extendFields(array $fields, array $values = null)
	{
		if (isset($fields['orderIds'], $this->orderIds))
		{
			$fields['orderIds']['VALUES'] = array_map(
				static function($orderId) { return [ 'ID' => $orderId, 'VALUE' => $orderId ]; },
				$this->orderIds
			);
		}

		return $fields;
	}

	public function getPayload(array $values)
	{
		return $values;
	}
}
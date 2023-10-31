<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendShipmentConfirm;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Activity extends TradingService\Reference\Action\FormActivity
{
	use Market\Reference\Concerns\HasMessage;

	protected $entityOrderIds;

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
			'externalShipmentId' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('EXTERNAL_SHIPMENT_ID'),
				'MANDATORY' => 'Y',
			],
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

		$orderIds = $entity->getOrderIds();

		if (empty($orderIds))
		{
			throw new Main\SystemException(self::getMessage('SHIPMENT_WITHOUT_ORDERS'));
		}

		$this->entityOrderIds = $orderIds;

		return [
			'externalShipmentId' => $entity->getExternalId() ?: $entity->getId(),
			'orderIds' => $this->filterReady($orderIds),
		];
	}

	protected function filterReady(array $orderIds)
	{
		$orderStatuses = $this->orderStatuses($orderIds);
		$statusService = $this->provider->getStatus();
		$fromStatusOrder = $statusService->getStatusOrder(TradingService\Marketplace\Status::STATUS_PROCESSING);
		$fromSubstatusOrder = $statusService->getSubStatusOrder(TradingService\Marketplace\Status::STATE_READY_TO_SHIP);
		$result = [];

		foreach ($orderStatuses as $orderId => list($status, $substatus))
		{
			if ($statusService->isCanceled($status, $substatus)) { continue; }

			$statusOrder = $statusService->getStatusOrder($status);

			if (
				$statusOrder > $fromStatusOrder
				|| (
					$statusOrder === $fromStatusOrder
					&& $statusService->getSubStatusOrder($substatus) >= $fromSubstatusOrder
				)
			)
			{
				$result[] = $orderId;
			}
		}

		return $result;
	}

	public function extendFields(array $fields, array $values = null)
	{
		if (isset($fields['orderIds'], $this->entityOrderIds))
		{
			$orderStatuses = $this->orderStatuses($this->entityOrderIds);
			$orderStatuses += array_fill_keys($this->entityOrderIds, [ 'OTHER' ]);
			$orderStatuses = $this->sortStatuses($orderStatuses);

			$fields['orderIds']['VALUES'] = array_map(
				function($orderId) use ($orderStatuses) {
					$status = $orderStatuses[$orderId];

					return [
						'ID' => $orderId,
						'VALUE' => $orderId,
						'GROUP' => (
							$status[0] === 'OTHER'
								? self::getMessage('STATUS_OTHER')
								: $this->statusTitle($orderStatuses[$orderId])
						),
					];
				},
				array_keys($orderStatuses)
			);
		}

		return $fields;
	}

	protected function orderStatuses(array $orderIds)
	{
		if (empty($orderIds)) { return []; }

		$result = [];

		$query = Market\Trading\State\Internals\StatusTable::getList([
			'filter' => [
				'=SERVICE' => $this->provider->getUniqueKey(),
				'=ENTITY_ID' => $orderIds,
			],
			'select' => [ 'ENTITY_ID', 'VALUE' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['ENTITY_ID']] = explode(':', $row['VALUE'], 2);
		}

		return $result;
	}

	protected function sortStatuses(array $orderStatuses)
	{
		$statusOrder = $this->provider->getStatus()->getProcessOrder();
		$statusOrder = array_flip(array_keys($statusOrder));
		$substatusOrder = $this->provider->getStatus()->getSubStatusProcessOrder();
		$substatusOrder = array_flip(array_keys($substatusOrder));
		$sort = [];

		foreach ($orderStatuses as $orderId => $orderStatus)
		{
			$primarySort = isset($statusOrder[$orderStatus[0]]) ? $statusOrder[$orderStatus[0]] : 9;
			$substatusSort = isset($orderStatus[1], $substatusOrder[$orderStatuses[1]]) ? $substatusOrder[$orderStatuses[1]] : 9;

			$sort[$orderId] = ($primarySort * 10) + $substatusSort;
		}

		uksort($orderStatuses, static function($aOrderId, $bOrderId) use ($sort) {
			$aSort = $sort[$aOrderId];
			$bSort = $sort[$bOrderId];

			if ($aSort === $bSort) { return 0; }

			return ($aSort < $bSort ? -1 : 1);
		});

		return $orderStatuses;
	}

	protected function statusTitle(array $complexStatus)
	{
		$statusService = $this->provider->getStatus();
		$variants = array_reverse(array_filter($complexStatus));
		$result = null;

		foreach ($variants as $variant)
		{
			$title = $statusService->getTitle($variant, 'SHORT');

			if ($title === $variant)
			{
				$title = $statusService->getTitle($variant);
			}

			if ($title !== $variant)
			{
				$result = $title;
				break;
			}
		}

		if ($result === null)
		{
			$result = implode(':', $complexStatus);
		}

		return $result;
	}

	public function getPayload(array $values)
	{
		return $values;
	}
}
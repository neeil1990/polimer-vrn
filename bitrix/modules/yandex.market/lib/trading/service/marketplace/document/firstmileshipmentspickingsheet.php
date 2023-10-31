<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
 */
class FirstMileShipmentsPickingSheet extends TradingService\Reference\Document\AbstractDocument
	implements TradingService\Reference\Document\HasLoadForm
{
	use Market\Reference\Concerns\HasMessage { getMessage as protected getMessageInternal; }

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . Market\Data\TextString::toUpper($version) : '';

		return self::getMessageInternal('TITLE' . $suffix);
	}

	public function getMessage($type)
	{
		$suffix = Market\Data\TextString::toUpper($type);

		return self::getMessageInternal($suffix, null, '');
	}

	public function getSourceType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT;
	}

	public function getEntityType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER;
	}

	public function loadForm($parameters)
	{
		/** @var TradingService\Marketplace\Model\ShipmentFacade $shipmentFacade */

		Market\Reference\Assert::notNull($parameters['id'], 'parameters["id"]');

		$options = $this->provider->getOptions();
		$shipmentFacade = $this->provider->getModelFactory()->getEntityFacadeClassName(TradingEntity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT);
		$shipmentIds = (array)$parameters['id'];
		$maxSelected = 10;
		$result = [];

		if (count($shipmentIds) > $maxSelected)
		{
			throw new Main\ArgumentException(self::getMessageInternal('FORM_SHIPMENT_SELECTED_LIMIT', [
				'#LIMIT#' => $maxSelected,
			]));
		}

		foreach ($shipmentIds as $shipmentId)
		{
			$ordersInfo = $shipmentFacade::loadOrdersInfo($options, $shipmentId);
			$orderIds = array_merge(
				$ordersInfo->getOrderIdsWithLabels(),
				$ordersInfo->getOrderIdsWithoutLabels()
			);

			foreach ($orderIds as $orderId)
			{
				$result[] = [
					'ID' => $orderId,
					'ENTITY_ID' => implode(':', [ $shipmentId, $orderId ]),
				];
			}
		}

		if (empty($result))
		{
			throw new Main\ArgumentException(self::getMessageInternal('FORM_SHIPMENT_WITHOUT_ORDERS'));
		}

		return $result;
	}

	public function render(array $items, array $settings = [])
	{
		$parameters = [
			'ITEMS' => $items,
		];
		$parameters += $settings;

		return $this->renderComponent('pickingsheet', $parameters);
	}
}
<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
 */
class FirstMileShipmentsBoxLabel extends BoxLabel
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

	public function loadForm($parameters)
	{
		/** @var TradingService\Marketplace\Model\ShipmentFacade $shipmentFacade */

		Market\Reference\Assert::notNull($parameters['id'], 'parameters["id"]');

		$options = $this->provider->getOptions();
		$modelFactory = $this->provider->getModelFactory();
		$shipmentFacade = $modelFactory->getEntityFacadeClassName(TradingEntity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT);
		$shipmentIds = (array)$parameters['id'];
		$maxSelected = 50;
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
					'ORDER_ID' => $orderId,
				];
			}
		}

		if (empty($result))
		{
			throw new Main\ArgumentException(self::getMessageInternal('FORM_SHIPMENT_WITHOUT_ORDERS'));
		}

		return $result;
	}
}
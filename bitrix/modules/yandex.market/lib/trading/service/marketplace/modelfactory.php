<?php
/**
 * @noinspection PhpReturnDocTypeMismatchInspection
 * @noinspection PhpIncompatibleReturnTypeInspection
 */
namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class ModelFactory extends TradingService\Reference\ModelFactory
{
	/** @return Model\Cart */
	public function getCartClassName()
	{
		return Model\Cart::class;
	}

	/** @return Model\OrderFacade */
	public function getOrderFacadeClassName()
	{
		return Model\OrderFacade::class;
	}

	/** @return Model\Order */
	public function getOrderClassName()
	{
		return Model\Order::class;
	}

	/** @return Model\Order\Buyer */
	public function getBuyerClassName()
	{
		return Model\Order\Buyer::class;
	}

	public function getEntityFacadeClassName($entityType)
	{
		if ($entityType === TradingEntity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT)
		{
			return Model\ShipmentFacade::class;
		}

		return parent::getEntityFacadeClassName($entityType);
	}
}
<?php

namespace Yandex\Market\Ui\Trading;

use Yandex\Market;
use Bitrix\Main;

class ShipmentList extends Reference\EntityList
{
	use Market\Reference\Concerns\HasMessage;

	protected function getTargetEntity()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT;
	}

	protected function getUserOptionCategory()
	{
		return 'yamarket_shipment_grid';
	}

	protected function showGrid(Market\Trading\Setup\Model $setup)
	{
		global $APPLICATION;

		$documents = $this->getPrintDocuments($setup);
		$activities = $this->getServiceActivities($setup);

		$this->initializePrintActions($setup, $documents);
		$this->initializeActivityActions($setup, $activities);

		$APPLICATION->IncludeComponent(
			'yandex.market:admin.grid.list',
			'',
			$this->gridActionsParameters($setup, $documents, $activities)
			+ [
				'GRID_ID' => $this->getGridId(),
				'PROVIDER_TYPE' => 'TradingShipment',
				'CONTEXT_MENU_EXCEL' => 'Y',
				'SETUP_ID' => $setup->getId(),
				'BASE_URL' => $this->getComponentBaseUrl($setup),
				'PAGER_FIXED' => Market\Component\TradingShipment\GridList::PAGE_SIZE,
				'DEFAULT_FILTER_FIELDS' => [
					'DATE',
					'STATUS',
					'ORDER_ID',
				],
				'DEFAULT_LIST_FIELDS' => [
					'ID',
					'EXTERNAL_ID',
					'DATE',
					'SHIPMENT_TYPE',
					'STATUS',
					'DELIVERY_SERVICE',
					'DRAFT_COUNT',
					'PLANNED_COUNT',
					'FACT_COUNT',
				],
				'CHECK_ACCESS' => !Market\Ui\Access::isWriteAllowed(),
				'RELOAD_EVENTS' => [
					'yamarketFormSave',
				],
			]
		);
	}

	protected function getGridId()
	{
		return 'YANDEX_MARKET_ADMIN_TRADING_SHIPMENT_LIST';
	}

	protected function getSetupCollection()
	{
		$result = new Market\Trading\Setup\Collection();

		/** @var Market\Trading\Setup\Model $setup */
		foreach (parent::getSetupCollection() as $setup)
		{
			if (!$setup->getService()->getRouter()->hasDataAction('admin/shipments')) { continue; }

			$setup->setCollection($result);
			$result->addItem($setup);
		}

		return $result;
	}
}
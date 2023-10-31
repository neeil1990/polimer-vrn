<?php

namespace Yandex\Market\Ui\Trading;

use Yandex\Market;
use Bitrix\Main;

class OrderList extends Reference\EntityList
{
	use Market\Reference\Concerns\HasMessage;

	protected function getTargetEntity()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER;
	}

	protected function getUserOptionCategory()
	{
		return 'yamarket_order_grid';
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
				'PROVIDER_TYPE' => 'TradingOrder',
				'CONTEXT_MENU_EXCEL' => 'Y',
				'SETUP_ID' => $setup->getId(),
				'BASE_URL' => $this->getComponentBaseUrl($setup),
				'PAGER_LIMIT' => 50,
				'DEFAULT_FILTER_FIELDS' => [
					'STATUS',
					'DATE_CREATE',
					'DATE_SHIPMENT',
					'FAKE',
				],
				'DEFAULT_LIST_FIELDS' => [
					'ID',
					'ACCOUNT_NUMBER',
					'DATE_CREATE',
					'BASKET',
					'TOTAL',
					'SUBSIDY',
					'STATUS_LANG',
				],
				'CHECK_ACCESS' => !Market\Ui\Access::isWriteAllowed(),
				'RELOAD_EVENTS' => [
					'yamarketShipmentSubmitEnd',
					'yamarketFormSave',
				],
			]
		);
	}

	protected function getGridId()
	{
		return 'YANDEX_MARKET_ADMIN_TRADING_ORDER_LIST';
	}

	/**
	 * @param Market\Trading\Setup\Model $setup
	 * @param Market\Trading\Service\Reference\Document\AbstractDocument[] $documents
	 * @param Market\Trading\Service\Reference\Action\AbstractActivity[] $activities
	 *
	 * @return array
	 */
	protected function getOrderListRowActions(Market\Trading\Setup\Model $setup, $documents, $activities)
	{
		return
			$this->getOrderListRowCommonActions($setup)
			+ $this->getOrderListRowActivityActions($setup, $activities)
			+ $this->getOrderListRowPrintActions($setup, $documents);
	}

	protected function getOrderListRowCommonActions(Market\Trading\Setup\Model $setup)
	{
		return [
		    'ACCEPT' => [
		        'ACTION' => 'accept',
                'TEXT' => self::getMessage('ACTION_ORDER_ACCEPT'),
            ],
			'EDIT' => [
				'ICON' => 'view',
				'TEXT' =>
					$setup->getService()->getInfo()->getMessage('ORDER_VIEW_TAB')
					?: self::getMessage('ACTION_ORDER_VIEW'),
				'MODAL' => 'Y',
				'MODAL_TITLE' => self::getMessage('ACTION_ORDER_VIEW_MODAL_TITLE'),
				'MODAL_PARAMETERS' => [
					'width' => 1024,
					'height' => 750,
				],
				'URL' => Market\Ui\Admin\Path::getModuleUrl('trading_order_view', [
					'lang' => LANGUAGE_ID,
					'view' => 'popup',
					'setup' => $setup->getId(),
					'site' => $setup->getSiteId(),
				]) . '&id=#ID#',
				'DEFAULT' => true,
			],
		];
	}

	/**
	 * @param Market\Trading\Setup\Model $setup
	 * @param Market\Trading\Service\Reference\Document\AbstractDocument[] $documents
	 * @param Market\Trading\Service\Reference\Action\AbstractActivity[] $activities
	 *
	 * @return array
	 */
	protected function getOrderListGroupActions(Market\Trading\Setup\Model $setup, $documents, $activities)
	{
		return
			parent::getOrderListGroupActions($setup, $documents, $activities)
			+ $this->getOrderListGroupBoxActions($setup);
	}

	protected function getOrderListGroupActionsParams($activities)
	{
		$result = parent::getOrderListGroupActionsParams($activities);
		$chooses = [
			'boxes',
		];

		foreach ($chooses as $choose)
		{
			$result['select_onchange'] .= sprintf(
				'BX(\'%1$s_chooser\') && (BX(\'%1$s_chooser\').style.display = (this.value == \'%1$s\' ? \'block\' : \'none\'));',
				$choose
			);
		}

		return $result;
	}

	/**
	 * @param Market\Trading\Setup\Model $setup
	 * @param Market\Trading\Service\Reference\Document\AbstractDocument[] $documents
	 * @param Market\Trading\Service\Reference\Action\AbstractActivity[] $activities
	 *
	 * @return array
	 */
	protected function getOrderListUiGroupActions(Market\Trading\Setup\Model $setup, $documents, $activities)
	{
		return
			parent::getOrderListUiGroupActions($setup, $documents, $activities)
			+ $this->getOrderListUiGroupBoxActions($setup);
	}

	protected function getOrderListGroupBoxActions(Market\Trading\Setup\Model $setup)
	{
		if (!$this->isSupportBoxes($setup)) { return []; }

		$variants = $this->getBoxesVariants();

		return [
			'boxes' => self::getMessage('ACTION_SEND_BOXES'),
			'boxes_chooser' => [
				'type' => 'html',
				'value' => $this->makeGroupActionSelectHtml('boxes', $variants),
			],
		];
	}

	protected function getOrderListUiGroupBoxActions(Market\Trading\Setup\Model $setup)
	{
		if (!$this->isSupportBoxes($setup)) { return []; }

		return [
			'boxes' => [
				'type' => 'select',
				'name' => 'boxes',
				'label' => self::getMessage('ACTION_SEND_BOXES'),
				'items' => $this->getBoxesVariants(),
			],
		];
	}

	protected function isSupportBoxes(Market\Trading\Setup\Model $setup)
	{
		return $setup->getService()->getRouter()->hasAction('send/boxes');
	}

	protected function getBoxesVariants()
	{
		$variants = [];
		$plural = [
			self::getMessage('ACTION_SEND_BOXES_COUNT_1'),
			self::getMessage('ACTION_SEND_BOXES_COUNT_2'),
			self::getMessage('ACTION_SEND_BOXES_COUNT_5'),
		];

		for ($count = 1; $count <= 10; ++$count)
		{
			$variants[] = [
				'VALUE' => $count,
				'NAME' => $count . ' ' . Market\Utils::sklon($count, $plural),
			];
		}

		return $variants;
	}

	protected function makeGroupActionSelectHtml($name, $variants)
	{
		$html = sprintf('<div id="%s_chooser" style="display: none;">', $name);
		$html .= sprintf('<select name="%s">', $name);

		foreach ($variants as $outgoingVariant)
		{
			$html .= sprintf(
				'<option value="%s">%s</option>',
				$outgoingVariant['VALUE'],
				$outgoingVariant['NAME']
			);
		}

		$html .= '</select>';
		$html .= '</div>';

		return $html;
	}
}
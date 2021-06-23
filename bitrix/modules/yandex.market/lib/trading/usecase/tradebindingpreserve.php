<?php

namespace Yandex\Market\Trading\UseCase;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

class TradeBindingPreserve extends Market\Reference\Event\Regular
{
	protected static $testRequestResult;
	protected static $deletedBindings = [];
	protected static $isBind = false;

	public static function getHandlers()
	{
		return [
			[
				'module' => 'sale',
				'event' => 'OnBeforeCollectionDeleteItem',
			],
		];
	}

	public static function restore()
	{
		if (!Main\Loader::includeModule('sale')) { return; }
		if (!class_exists(Sale\Internals\OrderTable::class)) { return; }

		$query = Sale\Internals\OrderTable::getList([
			'filter' => [
				'XML_ID' => 'YAMARKET_%',
			],
			'select' => [
				'ID',
				'XML_ID',
				'BINDING_ID' => 'YM_TRADING_PLATFORM.ID',
				'EXTERNAL_ORDER_ID' => 'YM_TRADING_PLATFORM.EXTERNAL_ORDER_ID',
				'PLATFORM_ID' => 'YM_TRADING_PLATFORM.TRADING_PLATFORM_ID',
			],
			'runtime' => [
				new Main\Entity\ReferenceField(
					'YM_TRADING_PLATFORM',
					Sale\TradingPlatform\OrderTable::getEntity(),
					[ '=ref.ORDER_ID' => 'this.ID' ]
				),
			],
		]);

		while ($row = $query->fetch())
		{
			$xmlData = TradingEntity\Sale\Platform::parseOrderXmlId($row['XML_ID']);
			$bindingId = (int)$row['BINDING_ID'];

			if ($xmlData === null) { continue; }
			if ((string)$row['PLATFORM_ID'] !== '' && (int)$xmlData['PLATFORM_ID'] !== (int)$row['PLATFORM_ID']) { continue; } // other platform
			if ((string)$row['EXTERNAL_ORDER_ID'] !== '' && (string)$row['EXTERNAL_ORDER_ID'] !== (string)$row['ID']) { continue; } // EXTERNAL_ORDER_ID filled

			$fields = [
				'EXTERNAL_ORDER_ID' => $xmlData['ORDER_ID'],
				'TRADING_PLATFORM_ID' => $xmlData['PLATFORM_ID'],
				'PARAMS' => [
					'SETUP_ID' => $xmlData['SETUP_ID'],
				],
			];

			if ($bindingId > 0)
			{
				Sale\TradingPlatform\OrderTable::update($bindingId, $fields);
			}
			else
			{
				$additionalFields = [
					'ORDER_ID' => $row['ID'],
				];

				if (class_exists(Sale\TradeBindingEntity::class))
				{
					$additionalFields['XML_ID'] = uniqid('bx_', false);
				}

				Sale\TradingPlatform\OrderTable::add($fields + $additionalFields);
			}
		}
	}

	public static function onBeforeCollectionDeleteItem(Main\Event $event)
	{
		if (!static::testRequest()) { return; }

		$binding = static::sanitizeEntity($event->getParameter('ENTITY'));

		if ($binding === null) { return; }

		$xmlData = static::getTradeBindingOrderXmlData($binding);

		if ($xmlData === null || !static::isTargetBinding($binding, $xmlData)) { return; }

		$orderId = (int)$binding->getField('ORDER_ID');

		static::$deletedBindings[$orderId] = [
			'TRADING_PLATFORM_ID' => (int)$binding->getField('TRADING_PLATFORM_ID'),
			'EXTERNAL_ORDER_ID' => (string)$binding->getField('EXTERNAL_ORDER_ID'),
			'PARAMS' => $binding->getField('PARAMS'),
		];

		static::bind();
	}

	/**
	 * Bitrix 21.0
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onBeforeSaleTradeBindingEntitySetFields(Main\Event $event)
	{
		$binding = static::sanitizeEntity($event->getParameter('ENTITY'));
		$values = $event->getParameter('VALUES');

		if ($binding === null) { return null; }
		if (!isset($values['TRADING_PLATFORM_ID'])) { return null; }

		$xmlData = static::getTradeBindingOrderXmlData($binding);

		if ($xmlData === null || (int)$xmlData['PLATFORM_ID'] !== (int)$values['TRADING_PLATFORM_ID']) { return null; }

		$exists = array_filter($binding->getFields()->getValues());
		$origin = [
			'EXTERNAL_ORDER_ID' => (string)$xmlData['ORDER_ID'],
			'PARAMS' => [ 'SETUP_ID' => $xmlData['SETUP_ID'] ],
		];
		$restore = array_diff_key($origin, $exists);
		$restore = array_diff_key($restore, $values);

		if (empty($restore)) { return null; }

		$orderId = (int)$binding->getField('ORDER_ID');

		if (isset(static::$deletedBindings[$orderId]))
		{
			unset(static::$deletedBindings[$orderId]);
		}

		return new Main\EventResult(Main\EventResult::SUCCESS, [
			'VALUES' => array_merge($values, $restore),
		]);
	}

	/**
	 * Bitrix 20.0
	 *
	 * @param Main\Event $event
	 */
	public static function onCollectionAddItem(Main\Event $event)
	{
		$binding = static::sanitizeEntity($event->getParameter('ENTITY'));

		if ($binding === null) { return; }

		$xmlData = static::getTradeBindingOrderXmlData($binding);

		if ($xmlData === null || !static::isTargetBinding($binding, $xmlData)) { return; }

		$exists = array_filter($binding->getFields()->getValues());
		$origin = [
			'EXTERNAL_ORDER_ID' => (string)$xmlData['ORDER_ID'],
			'PARAMS' => [ 'SETUP_ID' => $xmlData['SETUP_ID'] ],
		];
		$restore = array_diff_key($origin, $exists);

		if (empty($restore)) { return; }

		$binding->setFields($restore);

		$orderId = (int)$binding->getField('ORDER_ID');

		if (isset(static::$deletedBindings[$orderId]))
		{
			unset(static::$deletedBindings[$orderId]);
		}
	}

	/**
	 * Bitrix 18.5
	 *
	 * @param Main\Event $event
	 */
	public static function OnSaleOrderBeforeSaved(Main\Event $event)
	{
		$order = static::sanitizeOrder($event->getParameter('ENTITY'));

		if ($order === null || !method_exists($order, 'getTradeBindingCollection')) { return; }

		$orderId = $order->getId();

		if (!isset(static::$deletedBindings[$orderId])) { return; }

		$storedBinding = static::$deletedBindings[$orderId];
		$tradingCollection = $order->getTradeBindingCollection();
		$isFound = false;

		foreach ($tradingCollection as $binding)
		{
			if ((int)$binding->getField('TRADING_PLATFORM_ID') === $storedBinding['TRADING_PLATFORM_ID'])
			{
				$isFound = true;
				break;
			}
		}

		if (!$isFound)
		{
			$trading = $tradingCollection->createItem();
			$trading->setFieldsNoDemand($storedBinding + [
				'XML_ID' => uniqid('bx_', false),
			]);
		}

		unset(static::$deletedBindings[$orderId]);
	}

	protected static function testRequest()
	{
		if (static::$testRequestResult === null)
		{
			static::$testRequestResult = static::applyTestRequest();
		}

		return static::$testRequestResult;
	}

	protected static function applyTestRequest()
	{
		$requestPage = Main\Context::getCurrent()->getRequest()->getRequestedPage();

		return (
			preg_match('#/crm\.order\..+?/#', $requestPage) // is components namespace crm.order
			&& preg_match('#ajax\.php$#', $requestPage) // ajax page
		);
	}

	protected static function bind()
	{
		if (static::$isBind) { return; }

		static::$isBind = true;

		$eventManager = Main\EventManager::getInstance();

		$eventManager->addEventHandler('sale', 'OnCollectionAddItem', [static::class, 'OnCollectionAddItem']);
		$eventManager->addEventHandler('sale', 'OnBeforeSaleTradeBindingEntitySetFields', [static::class, 'OnBeforeSaleTradeBindingEntitySetFields']);
		$eventManager->addEventHandler('sale', 'OnSaleOrderBeforeSaved', [static::class, 'OnSaleOrderBeforeSaved'], false, 1);
	}

	protected static function getTradeBindingOrderXmlData(Sale\TradeBindingEntity $tradeBindingEntity)
	{
		/** @var Sale\TradeBindingCollection $collection */
		/** @var Sale\Order $order */
		$collection = $tradeBindingEntity->getCollection();

		if ($collection === null) { return null; }

		$order = static::sanitizeOrder($collection->getOrder());

		if ($order === null) { return null; }

		return TradingEntity\Sale\Platform::parseOrderXmlId($order->getField('XML_ID'));
	}

	protected static function isTargetBinding(Sale\TradeBindingEntity $binding, $xmlData)
	{
		return ((int)$binding->getField('TRADING_PLATFORM_ID') === (int)$xmlData['PLATFORM_ID']);
	}

	protected static function sanitizeEntity($entity)
	{
		return $entity instanceof Sale\TradeBindingEntity ? $entity : null;
	}

	protected static function sanitizeOrder(Sale\OrderBase $order = null)
	{
		return $order !== null && $order instanceof Crm\Order\Order && !$order->isNew() ? $order : null;
	}
}
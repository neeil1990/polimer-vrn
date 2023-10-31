<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Ui\Trading as UiTrading;
use Bitrix\Main;

class AdminExtension extends Market\Trading\Entity\Reference\AdminExtension
{
	protected static $isTabInitialized = false;

	public static function OnAdminContextMenuShow(&$items)
	{
		if (static::$isTabInitialized) { return; }

		$request = Main\Context::getCurrent()->getRequest();
		$action = static::getOrderPageAction($request);

		if ($action === null) { return; }

		$orderId = (int)$request->get('ID');
		$contextItem = static::getContextMenuItem([ 'ID' => $orderId ], $action);

		if ($contextItem !== null)
		{
			array_splice($items, 1, 0, [ $contextItem ]);
		}
	}

	public static function OnAdminSaleOrderView($parameters)
	{
		return static::initializeOrderTab($parameters, 'view');
	}

	public static function OnAdminSaleOrderEdit($parameters)
	{
		return static::initializeOrderTab($parameters, 'edit');
	}

	protected static function initializeOrderTab($parameters, $action)
	{
		try
		{
			$orderInfo = static::getOrderInfo($parameters);
			$setup = TradingSetup\Model::loadByTradingInfo($orderInfo);
			$tabSet = new UiTrading\OrderViewTabSet($setup, $orderInfo['EXTERNAL_ORDER_ID']);

			$tabSet->checkReadAccess();
			$tabSet->checkSupport();

			foreach (static::getExtensions($action, $setup) as $extension)
			{
				if (!$extension->isSupported()) { continue; }

				$extension->initialize($orderInfo);
			}

			$result = $tabSet->initialize();
			static::$isTabInitialized = true;
		}
		catch (Main\SystemException $exception)
		{
			$result = null;
		}

		return $result;
	}

	protected static function getContextMenuItem($parameters, $action)
	{
		try
		{
			$orderInfo = static::getOrderInfo($parameters);
			$setup = TradingSetup\Model::loadByTradingInfo($orderInfo);
			$tabSet = new UiTrading\OrderViewTabSet($setup, $orderInfo['EXTERNAL_ORDER_ID']);

			$tabSet->checkReadAccess();
			$tabSet->checkSupport();
			$tabSet->preloadAssets();

			foreach (static::getExtensions($action, $setup) as $extension)
			{
				if (!$extension->isSupported()) { continue; }

				$extension->initialize($orderInfo);
			}

			Market\Ui\Assets::loadPlugin('lib.dialog');

			$actionParams = [
				'content_url' => $tabSet->getContentsUrl(),
				'title' => $tabSet->getTitle(),
				'draggable' => true,
				'resizable' => true,
				'width' => 1024,
				'height' => 750,
			];
			$actionMethod = '(new BX.YandexMarket.Dialog(' . \CUtil::PhpToJSObject($actionParams) . ')).Show();';

			$result = [
				'TEXT' => $tabSet->getNavigationTitle(),
				'LINK' => 'javascript:' . $actionMethod,
			];
		}
		catch (Main\SystemException $exception)
		{
			$result = null;
		}

		return $result;
	}

	/**
	 * @param string $action
	 * @param TradingSetup\Model $setup
	 *
	 * @return UiTrading\OrderView\AbstractExtension[]
	 */
	protected static function getExtensions($action, TradingSetup\Model $setup)
	{
		$result = [];

		if ($action === 'view')
		{
			$result[] = new UiTrading\OrderView\CancelReason($setup);
		}

		return $result;
	}

	/**
	 * @deprecated
	 *
	 * @param array $parameters
	 *
	 * @return UiTrading\OrderViewTabSet
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectNotFoundException
	 * @throws Market\Exceptions\NotImplementedMethod
	 */
	protected static function createTabSet($parameters)
	{
		$orderInfo = static::getOrderInfo($parameters);
		$setup = TradingSetup\Model::loadByTradingInfo($orderInfo);

		return new UiTrading\OrderViewTabSet($setup, $orderInfo['EXTERNAL_ORDER_ID']);
	}

	protected static function getOrderInfo($parameters)
	{
		$orderId = static::extractParametersOrderId($parameters);
		$tradingInfo = static::getTradingInfo($orderId);
		$order = static::getOrder($orderId);

		return $tradingInfo + [
			'ORDER_ID' => $orderId,
			'SITE_ID' => $order->getSiteId(),
		];
	}

	protected static function extractParametersOrderId($parameters)
	{
		if (!isset($parameters['ID']))
		{
			throw new Main\ArgumentException('parameters hasn\'t id');
		}

		return (int)$parameters['ID'];
	}

	protected static function getTradingInfo($orderId)
	{
		$platformRow = OrderRegistry::searchPlatform($orderId);

		if ($platformRow === null)
		{
			throw new Main\ObjectNotFoundException('trading order not registered');
		}

		return $platformRow;
	}

	protected static function getOrder($orderId)
	{
		$environment = Market\Trading\Entity\Manager::createEnvironment();

		return $environment->getOrderRegistry()->loadOrder($orderId);
	}

	protected static function getOrderPageAction(Main\HttpRequest $request)
	{
		$pageUrl = $request->getRequestedPage();
		$actions = [
			BX_ROOT . '/admin/sale_order_view.php' => 'view',
			BX_ROOT . '/admin/sale_order_edit.php' => 'edit',
		];

		return isset($actions[$pageUrl]) ? $actions[$pageUrl] : null;
	}

	protected static function isOrderPage(Main\HttpRequest $request)
	{
		return static::getOrderPageAction($request) !== null;
	}

	protected function getEventHandlers()
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnAdminSaleOrderView',
			],
			[
				'module' => 'main',
				'event' => 'OnAdminSaleOrderEdit',
			],
			[
				'module' => 'main',
				'event' => 'OnAdminContextMenuShow',
			],
		];
	}
}

<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Entity as TradingEntity;

class ShipmentSubmit extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;
	use Market\Reference\Concerns\HasOnce;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	protected function getWriteRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	public function hasRequest()
	{
		return $this->request->isPost();
	}

	public function processRequest()
	{
		$this->checkAccess();
		$submitResults = $this->submit();
		$this->savePreferences();
		$this->flushOrderCache();

		return $this->collectResponse($submitResults);
	}

	/**
	 * @param Market\Result\Base[] $results
	 *
	 * @return array
	 */
	protected function collectResponse(array $results)
	{
		$isAllSuccess = true;
		$actions = [];
		$responseResults = [];

		foreach ($results as $result)
		{
			$data = $result->getData();

			if (!isset($data['PATH'])) { continue; }

			$path = $data['PATH'];
			$title = $this->getActionTitle($path);
			$actions[] = $path;

			if ($result->isSuccess())
			{
				$message = static::getLang('ADMIN_SHIPMENT_SUBMIT_ACTION_SUCCESS');
				$message = Market\Data\TextString::lcfirst($message);

				$responseResults[] = [
					'status' => 'ok',
					'text' => sprintf('%s: %s', $title, $message),
				];
			}
			else
			{
				$isAllSuccess = false;
				$message = implode('<br />', $result->getErrorMessages());
				$message = Market\Data\TextString::lcfirst($message);

				$responseResults[] = [
					'status' => 'error',
					'text' => sprintf('%s: %s', $title, $message),
				];
			}
		}

		if ($isAllSuccess)
		{
			return $this->makeSuccessResponse($actions);
		}

		return [
			'status' => 'error',
			'messages' => $responseResults,
		];
	}

	protected function makeSuccessResponse($actions)
	{
		$replaces = [
			'#ACTIONS#' => $this->combineActionTitles($actions, 'PREPOSITIONAL'),
			'#FOLLOWING#' => $this->makeFollowingInstructions(),
		];

		return [
			'status' => 'ok',
			'message' => $this->getSetup()->getService()->getInfo()->getMessage(
				'SHIPMENT_SUBMIT_SUCCESS',
				$replaces,
				static::getLang('ADMIN_SHIPMENT_SUBMIT_SHIPMENT_SUCCESS', $replaces)
			),
		];
	}

	protected function combineActionTitles($actions, $variant = '')
	{
		$titles = array_map(
			function($action) use ($variant) {
				$title = $this->getActionTitle($action, $variant);
				$title = Market\Data\TextString::lcfirst($title);

				return $title;
			},
			$actions
		);

		return implode(
			static::getLang('ADMIN_SHIPMENT_SUBMIT_ACTION_TITLE_GLUE', null, ', '),
			$titles
		);
	}

	protected function makeFollowingInstructions()
	{
		return $this->getRequestOrder()->useAutoFinish()
			? static::getLang('ADMIN_SHIPMENT_SUBMIT_FOLLOWING_AUTO')
			: static::getLang('ADMIN_SHIPMENT_SUBMIT_FOLLOWING_COMMON');
	}

	protected function getActionTitle($action, $variant = '')
	{
		$key = str_replace('/', '_', $action);
		$key = Market\Data\TextString::toUpper($key);
		$suffix = $variant !== '' ? '_' . $variant : '';

		return static::getLang('ADMIN_SHIPMENT_SUBMIT_ACTION_' . $key . $suffix, null, $action);
	}

	protected function needCheckAccess()
	{
		return !Market\Ui\Access::isWriteAllowed();
	}

	protected function checkAccess()
	{
		global $USER;

		if (!$this->needCheckAccess()) { return; }

		$order = $this->getOrderEntity();
		$userId = $USER->GetID();

		if (!$order->hasAccess($userId, Market\Trading\Entity\Operation\Order::BOX))
		{
			$message = static::getLang('ADMIN_SHIPMENT_SUBMIT_LOCAL_ORDER_DENIED');
			throw new Main\AccessDeniedException($message);
		}
	}

	/** @return TradingEntity\Reference\Order */
	protected function getOrderEntity()
	{
		return $this->once('loadOrderEntity');
	}

	protected function loadOrderEntity()
	{
		$setup = $this->getSetup();
		$externalId = $this->getRequestOrder()->getId();
		$environment = $setup->getEnvironment();
		$platform = $setup->getPlatform();
		$orderRegistry = $environment->getOrderRegistry();
		$internalId = $orderRegistry->search($externalId, $platform, false);

		if ($internalId === null)
		{
			$message = static::getLang('ADMIN_SHIPMENT_SUBMIT_LOCAL_ORDER_NOT_EXISTS');
			throw new Main\ObjectNotFoundException($message);
		}

		return $orderRegistry->loadOrder($internalId);
	}

	protected function savePreferences()
	{
		$this->saveUseDimensionsFlag();
	}

	protected function saveUseDimensionsFlag()
	{
		global $USER;

		$userId = ($USER instanceof \CUser ? (int)$USER->GetID() : 0);
		$stored = (\CUserOptions::GetOption('yamarket_order_view', 'use_dimensions', 'N', $userId) === 'Y');
		$requested = $this->getRequestOrder()->useDimensions();

		if ($stored !== $requested)
		{
			\CUserOptions::SetOption('yamarket_order_view', 'use_dimensions', $requested ? 'Y' : 'N', false, $userId);
		}
	}

	protected function submit()
	{
		return [
			$this->isItemsChanged() ? $this->submitItems() : $this->submitIdentifiers(),
			$this->submitShipments(),
			$this->submitDigitalGoods(),
		];
	}

	protected function isItemsChanged()
	{
		$result = false;

		/** @var ShipmentRequest\BasketItem $basketItem */
		foreach ($this->getRequestOrder()->getBasket() as $basketItem)
		{
			$initialCount = $basketItem->getInitialCount();

			if (
				$basketItem->needDelete()
				|| (
					$initialCount !== null
					&& (int)$initialCount !== (int)$basketItem->getCount()
				)
			)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function submitItems()
	{
		$path = 'send/items';

		try
		{
			$items = $this->makeItems();
			$basketConfirm = $this->getRequestOrder()->getBasketConfirm();

			$result = $this->callProcedure($path, $this->getProcedureTradingData() + [
				'items' => $items,
				'reason' => $basketConfirm !== null ? $basketConfirm->getReason() : null,
			]);
		}
		catch (Market\Exceptions\Api\ObjectPropertyException $exception)
		{
			$result = $this->makeObjectPropertyEmptyResult($path, $exception);
		}

		return $result;
	}

	protected function makeItems()
	{
		$result = [];

		/** @var ShipmentRequest\BasketItem $basketItem */
		foreach ($this->getRequestOrder()->getBasket() as $basketItem)
		{
			if ($basketItem->needDelete()) { continue; }

			$instances = $this->makeItemInstances($basketItem);
			$item = [
				'id' => $basketItem->getId(),
				'count' => $basketItem->getCount(),
			];

			if (!empty($instances))
			{
				$item['instances'] = $instances;
			}

			$result[] = $item;
		}

		return $result;
	}

	protected function submitIdentifiers()
	{
		$path = 'send/identifiers';

		try
		{
			$items = $this->makeIdentifiers();

			if (empty($items)) { return new Market\Result\Base(); }

			$result = $this->callProcedure($path, $this->getProcedureTradingData() + [
				'items' => $items,
			]);
		}
		catch (Market\Exceptions\Api\ObjectPropertyException $exception)
		{
			$result = $this->makeObjectPropertyEmptyResult($path, $exception);
		}

		return $result;
	}

	protected function makeIdentifiers()
	{
		$result = [];

		/** @var ShipmentRequest\BasketItem $basketItem */
		foreach ($this->getRequestOrder()->getBasket() as $basketItem)
		{
			$instances = $this->makeItemInstances($basketItem);

			if (empty($instances)) { continue; }

			$result[] = [
				'id' => $basketItem->getId(),
				'instances' => $instances,
 			];
		}

		return $result;
	}

	protected function makeItemInstances(ShipmentRequest\BasketItem $basketItem)
	{
		$result = [];
		$keysMap = [];

		if ($basketItem->getIdentifierType() !== null)
		{
			$keysMap[Market\Data\Trading\MarkingRegistry::CIS] = mb_strtolower($basketItem->getIdentifierType());
		}

		foreach ($basketItem->getIdentifiers() as $index => $instances)
		{
			if (!is_array($instances)) { continue; }

			foreach ($instances as $type => $value)
			{
				$key = isset($keysMap[$type]) ? $keysMap[$type] : mb_strtolower($type);
				$value = trim($value);

				if ($value === '') { continue; }

				if ($type === Market\Data\Trading\MarkingRegistry::CIS)
				{
					$value = Market\Data\Trading\Cis::formatMarkingCode($value);
				}
				else if ($type === Market\Data\Trading\MarkingRegistry::UIN)
				{
					$value = Market\Data\Trading\Uin::formatMarkingCode($value);
				}

				$result[$index][$key] = $value;
			}
		}

		if (empty($result)) { return null; }

		return $result;
	}

	protected function submitShipments()
	{
		$submitResults = [];
		$requestOrder = $this->getRequestOrder();

		/** @var ShipmentRequest\Shipment $shipment */
		foreach ($requestOrder->getShipments() as $shipment)
		{
			$submitResults[] = $this->submitBoxes($shipment, $requestOrder->useDimensions());
		}

		return !empty($submitResults)
			? Market\Result\Facade::merge($submitResults)
			: new Market\Result\Base();
	}

	protected function submitBoxes(ShipmentRequest\Shipment $shipment, $useDimensions = true)
	{
		$path = 'send/boxes';

		try
		{
			$result = $this->callProcedure($path, $this->getProcedureTradingData() + [
				'shipmentId' => $shipment->getId(),
				'boxes' => $this->makeBoxes($shipment, $useDimensions),
			]);
		}
		catch (Market\Exceptions\Api\ObjectPropertyException $exception)
		{
			$result = $this->makeObjectPropertyEmptyResult($path, $exception);
		}

		return $result;
	}

	protected function makeBoxes(ShipmentRequest\Shipment $requestShipment, $useDimensions = true)
	{
		$result = [];

		/** @var ShipmentRequest\Box $box */
		foreach ($requestShipment->getBoxes() as $box)
		{
			$outgoingBox = [
				'fulfilmentId' => $box->getFulfilmentId(),
			];

			if ($useDimensions)
			{
				$outgoingBox += [
					'weight' => $box->getSize('WEIGHT'),
					'width' => $box->getSize('WIDTH'),
					'height' => $box->getSize('HEIGHT'),
					'depth' => $box->getSize('DEPTH'),
				];
			}

			$result[] = $outgoingBox;
		}

		return $result;
	}

	protected function submitDigitalGoods()
	{
		$path = 'send/digital';

		try
		{
			$digitalGoods = $this->makeDigitalGoods();

			if (empty($digitalGoods)) { return new Market\Result\Base(); }

			$result = $this->callProcedure($path, $this->getProcedureTradingData() + [
				'items' => $digitalGoods,
			]);
		}
		catch (Market\Exceptions\Api\ObjectPropertyException $exception)
		{
			$result = $this->makeObjectPropertyEmptyResult($path, $exception);
		}

		return $result;
	}

	protected function makeDigitalGoods()
	{
		$result = [];

		/** @var ShipmentRequest\BasketItem $basketItem */
		foreach ($this->getRequestOrder()->getBasket() as $basketItem)
		{
			if ($basketItem->needDelete()) { continue; }

			$digital = $basketItem->getDigital();

			if ($digital === null) { continue; }

			$digitalItems = [];

			/** @var ShipmentRequest\DigitalItem $digitalItem */
			foreach ($digital->getItems() as $digitalItem)
			{
				if (count($digitalItems) === (int)$basketItem->getCount()) { break; }

				$digitalItems[] = [
					'id' => $basketItem->getId(),
					'code' => $digitalItem->getCode(),
					'slip' => $digital->getSlip(),
					'activate_till' => $digitalItem->getActivateTill(),
				];
			}

			if (empty($digitalItems)) { continue; }

			array_push($result, ...$digitalItems);
		}

		return $result;
	}

	protected function getProcedureTradingData()
	{
		$order = $this->getRequestOrder();

		return [
			'internalId' => $order->getInternalId(),
			'orderId' => $order->getId(),
			'orderNum' => $order->getAccountNumber(),
			'immediate' => true,
			'autoSubmit' => false,
		];
	}

	protected function callProcedure($path, $data)
	{
		$result = new Market\Result\Base();
		$result->setData([
			'PATH' => $path,
		]);

		try
		{
			$order = $this->getRequestOrder();
			$setup = $this->getSetup();

			$procedure = new Market\Trading\Procedure\Runner(
				Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER,
				$order->getAccountNumber()
			);

			$procedure->run($setup, $path, $data);
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Market\Error\Base($exception->getMessage()));
		}

		return $result;
	}

	protected function flushOrderCache()
	{
		Market\Trading\State\SessionCache::releaseByType('order');
	}

	public function show()
	{
		throw new Main\NotSupportedException();
	}

	/** @return TradingSetup\Model */
	protected function getSetup()
	{
		return $this->once('loadSetup');
	}

	protected function loadSetup()
	{
		$setupId = $this->getRequestOrder()->getSetupId();

		return TradingSetup\Model::loadById($setupId);
	}

	/** @return ShipmentRequest\Order */
	protected function getRequestOrder()
	{
		return $this->once('createRequestOrder');
	}

	protected function createRequestOrder()
	{
		$data = $this->request->getPost('YAMARKET_ORDER');

		if (!is_array($data))
		{
			$message = static::getLang('ADMIN_SHIPMENT_SUBMIT_SHIPMENT_MUST_BE_ARRAY');
			throw new Main\SystemException($message);
		}

		return new ShipmentRequest\Order($data);
	}

	protected function makeObjectPropertyEmptyResult($path, Market\Exceptions\Api\ObjectPropertyException $exception)
	{
		$parameter = $exception->getParameter();
		$message = $this->getObjectPropertyEmptyMessage($parameter) ?: $exception->getMessage();

		$result = new Market\Result\Base();
		$result->setData([ 'PATH' => $path ]);
		$result->addError(new Market\Error\Base($message));

		return $result;
	}

	protected function getObjectPropertyEmptyMessage($parameter)
	{
		list($fields, $variables) = $this->splitObjectProperty($parameter);

		$code = implode('_', $fields);

		return static::getLang('ADMIN_SHIPMENT_SUBMIT_FIELD_EMPTY_' . $code, $variables);
	}

	protected function splitObjectProperty($parameter)
	{
		$parts = explode('.', $parameter);
		$fields = [];
		$variables = [];

		foreach ($parts as $part)
		{
			if (preg_match('/^(.*?)\[(\d+)]$/', $part, $matches))
			{
				$field = $matches[1];
				$index = (int)$matches[2];

				$variables['#' . $field . '_NUMBER#'] = $index + 1;
			}
			else
			{
				$field = $part;
			}

			$fields[] = $field;
		}

		return [$fields, $variables];
	}
}
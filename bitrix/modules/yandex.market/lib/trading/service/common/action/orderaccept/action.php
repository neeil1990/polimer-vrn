<?php

namespace Yandex\Market\Trading\Service\Common\Action\OrderAccept;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Common\Provider $provider
*/
class Action extends TradingService\Common\Action\Cart\Action
{
	use TradingService\Common\Concerns\Action\HasTasks;

	/** @var Request */
	protected $request;
	/** @var TradingEntity\Reference\User */
	protected $originalUser;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::ORDER_ACCEPT;
	}

	public function process()
	{
		$locker = $this->createLocker();

		try
		{
			if ($this->testExistOrder()) { return; }

			$this->testBrokenOrder();

			$this->createUser();
			$this->createOrder();
			$this->initializeOrder();
			$this->fillOrder();
			$this->finalizeOrder();

			$this->freeze();
			$checkResult = $this->check();

			if ($checkResult->isSuccess())
			{
				$this->unfreeze();
				$hasWarnings = false;

				if ($checkResult->hasWarnings())
				{
					$hasWarnings = true;
					$this->markOrder($checkResult);
				}

				$locker->lock();

				if ($this->testExistOrder())
				{
					$locker->release();
					return;
				}

				$this->writeCache();

				$this->addOrder();
				$this->completeOrder();

				$orderNum = $this->getOrderNum();

				$this->logOrder($orderNum);
				$this->collectOrder($orderNum, $hasWarnings);
				$this->registerTasks();
				$this->saveState();
				$this->saveData();

				$locker->release();
			}
			else
			{
				$locker->lock();

				if ($this->testExistOrder())
				{
					$locker->release();
					return;
				}

				$this->collectDecline($checkResult);
				$this->logDecline($checkResult);

				$locker->release();
			}
		}
		catch (\Exception $exception)
		{
			$locker->release();
			throw $exception;
		}
		/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
		catch (\Throwable $exception)
		{
			$locker->release();
			throw $exception;
		}
	}

	protected function createLocker()
	{
		$sign = Market\Config::getLangPrefix() . 'ORDER_ACCEPT_' . $this->request->getOrder()->getId();

		return new Market\Trading\State\Locker($sign, 60);
	}

	protected function testExistOrder()
	{
		$orderId = $this->searchOrder();

		if ($orderId === null) { return false; }

		$this->loadOrder($orderId);

		$orderNum = $this->getOrderNum();
		$hasWarnings = $this->isExistOrderMarker();

		$this->collectOrder($orderNum, $hasWarnings);

		return true;
	}

	protected function searchOrder()
	{
		$orderRegistry = $this->environment->getOrderRegistry();
		$orderId = $this->request->getOrder()->getId();
		$platform = $this->getPlatform();

		return $orderRegistry->search($orderId, $platform, false);
	}

	protected function testBrokenOrder()
	{
		$orderId = $this->searchBrokenOrder();

		if ($orderId !== null)
		{
			$this->logBrokenOrder();
			$this->throwBrokenOrder();
		}
	}

	protected function logBrokenOrder()
	{
		$logger = $this->provider->getLogger();

		if ($logger instanceof Market\Logger\Reference\Logger)
		{
			$message = static::getLang('TRADING_ACTION_ORDER_ACCEPT_ORDER_BROKEN_LOG');

			$logger->error($message);
			$logger->setLevel(null);
		}
	}

	protected function throwBrokenOrder()
	{
		$message = static::getLang('TRADING_ACTION_ORDER_ACCEPT_ORDER_BROKEN');
		throw new Main\SystemException($message);
	}

	protected function searchBrokenOrder()
	{
		$orderRegistry = $this->environment->getOrderRegistry();
		$orderId = $this->request->getOrder()->getId();
		$platform = $this->getPlatform();

		return $orderRegistry->searchBroken($orderId, $platform, false);
	}

	protected function loadOrder($orderId)
	{
		$orderRegistry = $this->environment->getOrderRegistry();

		$this->order = $orderRegistry->loadOrder($orderId);
	}

	protected function fillOrder()
	{
		$this->fillAccountNumber();
		$this->fillXmlId();
		$this->fillStatus();
		$this->fillProfile();
		$this->fillRegion();
		$this->fillProperties();
		$this->fillBasket();
		$this->fillDelivery();
		$this->fillPaySystem();
		$this->fillBasketStore();
		$this->fillOutlet();
		$this->fillRelatedProperties();
		$this->fillNotes();
	}

	protected function fillAccountNumber()
	{
		$options = $this->provider->getOptions();

		if (!$options->useAccountNumberTemplate()) { return; }

		$this->order->fillAccountNumber(str_replace(
			[ '{id}', '{campaignId}' ],
			[
				$this->request->getOrder()->getId(),
				$options instanceof TradingService\Marketplace\Options ? $options->getCampaignId() : '',
			],
			$options->getAccountNumberTemplate()
		));
	}

	protected function fillXmlId()
	{
		$platform = $this->getPlatform();
		$externalId = $this->request->getOrder()->getId();
		$setupId = $this->provider->getOptions()->getSetupId();

		$this->order->fillXmlId($externalId, $platform);
		$this->order->fillTradingSetup($setupId, $platform);
	}

	protected function fillProperties()
	{
		$this->fillUtilProperties();
	}

	protected function fillUtilProperties()
	{
		$meaningfulValues = $this->request->getOrder()->getMeaningfulValues();

		$this->setMeaningfulPropertyValues($meaningfulValues);
	}

	protected function fillDelivery()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'fillDelivery');
	}

	protected function extendBasketData(Market\Api\Model\Cart\Item $item, $data)
	{
		if (!($item instanceof Market\Api\Model\Order\Item)) { return $data; }

		$xmlId = $this->provider->getDictionary()->getOrderItemXmlId($item);

		if ($data === null) { $data = []; }

		return $data + [
			'XML_ID' => $xmlId,
		];
	}

	protected function getBasketItemsMap(Market\Api\Model\Cart\ItemCollection $items)
	{
		$dictionary = $this->provider->getDictionary();
		$result = [];

		foreach ($items as $item)
		{
			$xmlId = $dictionary->getOrderItemXmlId($item);
			$basketCode = $this->order->getBasketItemCode($xmlId, 'XML_ID');

			if ($basketCode !== null)
			{
				$result[$item->getInternalId()] = $basketCode;
			}
		}

		return $result;
	}

	protected function fillBasketStore()
	{
		// nothing by default
	}

	protected function fillOutlet()
	{
		// nothing by default
	}

	protected function fillPaySystem()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'fillPaySystem');
	}

	protected function fillNotes()
	{
		$notes = $this->request->getOrder()->getNotes();

		if ($notes !== '')
		{
			$this->order->setNotes($notes);
		}
	}

	protected function fillStatus()
	{
		/** @var TradingService\Common\Options $options */
		$options = $this->provider->getOptions();
		$status = (string)$options->getStatusIn(TradingService\Common\Status::VIRTUAL_CREATED);

		if ($status !== '')
		{
			$this->order->setStatus($status);
		}
	}

	protected function freeze()
	{
		$this->order->freeze();
	}

	protected function unfreeze()
	{
		$this->order->unfreeze();
	}

	protected function check()
	{
		return Market\Result\Facade::merge([
			$this->checkBasket(),
			$this->checkPrice(),
		]);
	}

	protected function checkBasket()
	{
		$validationResult = $this->validateBasket();
		$result = new Market\Result\Base();

		if (!$validationResult->isSuccess())
		{
			$isAllowModify = $this->provider->getOptions()->isAllowModifyBasket();

			if ($isAllowModify)
			{
				$modifyResult = $this->modifyBasket();

				if ($modifyResult->isSuccess())
				{
					$result->addWarnings($validationResult->getErrors());
				}
				else
				{
					$result->addErrors($modifyResult->getErrors());
				}
			}
			else
			{
				$result->addErrors($validationResult->getErrors());
			}
		}

		return $result;
	}

	protected function modifyBasket()
	{
		$items = $this->request->getOrder()->getItems();
		$result = new Main\Result();

		/** @var Market\Api\Model\Order\Item $item */
		foreach ($items as $itemIndex => $item)
		{
			$offerId = $item->getOfferId();
			$ratio = isset($this->basketPackRatio[$itemIndex]) ? $this->basketPackRatio[$itemIndex] : 1;
			$count = $item->getCount() * $ratio;
			$basketCode = null;

			if (isset($this->basketMap[$itemIndex]))
			{
				$basketCode = $this->basketMap[$itemIndex];
			}
			else
			{
				$productId = isset($this->basketProducts[$itemIndex])
					? $this->basketProducts[$itemIndex]
					: $offerId;
				$basketData = isset($this->basketInvalidData[$itemIndex])
					? $this->basketInvalidData[$itemIndex]
					: [];
				$basketData += $item->getMeaningfulValues();
				$basketData = array_diff_key($basketData, [ 'ERROR' => true ]);

				$addResult = $this->order->addProduct($productId, 0, $basketData);
				$addData = $addResult->getData();

				if (isset($addData['BASKET_CODE']))
				{
					$basketCode = $addData['BASKET_CODE'];
					$this->basketMap[$itemIndex] = $basketCode;
				}
			}

			if ($basketCode !== null)
			{
				$basketResult = $this->order->setBasketItemQuantity($basketCode, $count, true);

				if (!$basketResult->isSuccess())
				{
					$result->addErrors($basketResult->getErrors());
				}
			}
		}

		return $result;
	}

	protected function checkPrice()
	{
		$validationResult = $this->validatePrice();
		$result = new Market\Result\Base();

		if (!$validationResult->isSuccess())
		{
			$allowModifyPrice = $this->provider->getOptions()->isAllowModifyPrice();
			$checkPriceData = $validationResult->getData();

			if ($checkPriceData['SIGN'] > 0) // requested price more than basket price
			{
				$allowModifyPrice = true;
			}

			if ($allowModifyPrice)
			{
				$modifyPrice = $this->modifyPrice();

				if (!$modifyPrice->isSuccess())
				{
					$result->addErrors($modifyPrice->getErrors());
				}
			}
			else
			{
				$result->addErrors($validationResult->getErrors());
			}
		}

		return $result;
	}

	protected function validatePrice()
	{
		$items = $this->request->getOrder()->getItems();
		$requestPrice = $this->getItemsSum($items);
		$basketPrice = $this->order->getBasketPrice();
		$result = new Market\Result\Base();

		if (Market\Data\Price::round($requestPrice) !== Market\Data\Price::round($basketPrice))
		{
			$currency = $this->order->getCurrency();

			$message = static::getLang('TRADING_ACTION_ORDER_ACCEPT_ORDER_PRICE_NOT_MATCH', [
				'#REQUEST_PRICE#' => Market\Data\Currency::format($requestPrice, $currency),
				'#BASKET_PRICE#' => Market\Data\Currency::format($basketPrice, $currency),
			]);
			$result->addError(new Market\Error\Base($message, 'PRICE_NOT_MATCH'));
			$result->setData([
				'SIGN' => $requestPrice < $basketPrice ? -1 : 1,
			]);
		}

		return $result;
	}

	protected function modifyPrice()
	{
		$items = $this->request->getOrder()->getItems();
		$result = new Market\Result\Base();

		/** @var Market\Api\Model\Order\Item $item */
		foreach ($items as $itemIndex => $item)
		{
			if (isset($this->basketMap[$itemIndex]))
			{
				$basketCode = $this->basketMap[$itemIndex];
				$ratio = isset($this->basketPackRatio[$itemIndex]) ? $this->basketPackRatio[$itemIndex] : 1;
				$price = $this->getItemPrice($item) / $ratio;
				$basketResult = $this->order->setBasketItemPrice($basketCode, $price);

				if (!$basketResult->isSuccess())
				{
					$result->addErrors($basketResult->getErrors());
				}
			}
		}

		return $result;
	}

	protected function getItemsSum(Market\Api\Model\Order\ItemCollection $items)
	{
		$result = 0;

		/** @var Market\Api\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$result += $this->getItemPrice($item) * $item->getCount();
		}

		return $result;
	}

	protected function getItemPrice(Market\Api\Model\Order\Item $item)
	{
		return $item->getPrice();
	}

	protected function isExistOrderMarker()
	{
		$codePrefix = $this->provider->getDictionary()->getErrorPrefix();

		return $this->order->isExistMarker($codePrefix, '%');
	}

	protected function markOrder(Market\Result\Base $checkResult)
	{
		$dictionary = $this->provider->getDictionary();

		foreach ($checkResult->getWarnings() as $warning)
		{
			$message = $warning->getMessage();
			$code = $dictionary->getErrorCode($warning);

			$addResult = $this->order->addMarker($message, $code);

			Market\Result\Facade::handleException($addResult);
		}
	}

	/**
	 * @deprecated
	 * @noinspection PhpUnused
	 */
	protected function getMarkerPrefix()
	{
		return $this->provider->getDictionary()->getErrorPrefix();
	}

	protected function writeCache()
	{
		Market\Trading\State\HitCache::set(
			'order',
			$this->request->getOrder()->getId(),
			$this->request->getOrder()->getFields()
		);
	}

	protected function addOrder()
	{
		$platform = $this->getPlatform();
		$externalId = $this->request->getOrder()->getId();
		$saveResult = $this->order->add($externalId, $platform);
		$saveData = $saveResult->getData();

		if (!$saveResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $saveResult->getErrorMessages());
			throw new Main\SystemException($errorMessage);
		}

		if (!isset($saveData['ID']))
		{
			$errorMessage = static::getLang('TRADING_ACTION_ORDER_ACCEPT_SAVE_RESULT_ID_NOT_SET');
			throw new Main\SystemException($errorMessage);
		}
	}

	protected function completeOrder()
	{
		$this->saveProfile();
	}

	protected function needSaveProfile()
	{
		$userId = $this->order->getUserId();

		return ($userId !== null && $userId !== $this->getAnonymousUser()->getId());
	}

	protected function saveProfile()
	{
		if (!$this->needSaveProfile()) { return; }

		$properties = !empty($this->filledProperties)
			? $this->filledProperties
			: $this->order->getPropertyValues();

		$command = new TradingService\Common\Command\SaveBuyerProfile(
			$this->provider,
			$this->environment,
			$this->order->getUserId(),
			$this->order->getPersonType(),
			$this->order->getProfileName(),
			$properties
		);
		$command->execute();
	}

	protected function getOrderNum()
	{
		return $this->order->getAccountNumber();
	}

	protected function logOrder($orderNum)
	{
		$logger = $this->provider->getLogger();
		$message = static::getLang('TRADING_ACTION_ORDER_ACCEPT_SAVE_LOG', [
			'#ORDER_ID#' => $orderNum,
			'#EXTERNAL_ID#' => $this->request->getOrder()->getId(),
		]);

		$logger->info($message, [
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $orderNum,
		]);
	}

	protected function collectOrder($orderNum, $hasWarnings = false)
	{
		$this->response->setField('order.id', (string)$orderNum);
		$this->response->setField('order.accepted', true);

		if ($hasWarnings)
		{
			$this->response->setField('order.subscribe', (bool)$hasWarnings);
		}
	}

	protected function logDecline(Market\Result\Base $result)
	{
		$logger = $this->provider->getLogger();
		$message = implode(PHP_EOL, $result->getErrorMessages());

		$logger->error($message, $this->makeErrorsContext($result));
	}

	protected function collectDecline(Market\Result\Base $result)
	{
		if ($this->request->isDownload())
		{
			Market\Result\Facade::handleException($result);
		}

		$this->response->setField('order.accepted', false);
		$this->response->setField('order.reason', 'OUT_OF_DATE');
	}

	protected function saveState()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();

		Market\Trading\State\OrderStatus::setValue($serviceKey, $orderId, TradingService\Common\Status::VIRTUAL_CREATED);
		Market\Trading\State\OrderStatus::commit($serviceKey, $orderId);
	}

	protected function saveData()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();
		$data = $this->makeData();

		Market\Trading\State\OrderData::setValues($serviceKey, $orderId, $data);
	}

	protected function makeData()
	{
		return [];
	}
}
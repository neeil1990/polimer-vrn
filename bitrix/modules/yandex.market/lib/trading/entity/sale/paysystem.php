<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market\Trading\Entity as TradingEntity;

class PaySystem extends Market\Trading\Entity\Reference\PaySystem
{
	/** @var Environment */
	protected $environment;

	public function __construct(Environment $environment)
	{
		parent::__construct($environment);
	}

	public function isRequired()
	{
		$saleVersion = Main\ModuleManager::getVersion('sale');

		return !CheckVersion($saleVersion, '17.0.0');
	}

	public function getEnum($siteId = null)
	{
		$result = [];
		$filter = [
			'=ACTIVE' => 'Y',
		];

		if (
			method_exists(Sale\Payment::class, 'getRegistryType')
			&& Sale\Internals\PaySystemActionTable::getEntity()->hasField('ENTITY_REGISTRY_TYPE')
		)
		{
			$filter['=ENTITY_REGISTRY_TYPE'] = Sale\Payment::getRegistryType();
		}

		$query = Sale\PaySystem\Manager::getList([
			'filter' => $filter,
			'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
			'select' => ['ID', 'NAME']
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
			];
		}

		return $result;
	}

	public function getCompatible(TradingEntity\Reference\Order $order, $deliveryId = null)
	{
		try
		{
			/** @var Sale\Order $calculatableOrder */
			$calculatableOrder = $this->getOrderCalculatable($order);
			$payment = $this->getOrderPayment($calculatableOrder);
			$needRemovePayment = false;

			if ($payment === null)
			{
				$needRemovePayment = true;
				$payment = $this->createOrderPayment($calculatableOrder);
			}

			if ($deliveryId !== null)
			{
				$this->configureShipment($order, $deliveryId);
			}

			$paySystems = Sale\PaySystem\Manager::getListWithRestrictions($payment);

			$result = array_keys($paySystems);

			if ($needRemovePayment && !$this->isCalculatableCloned($calculatableOrder))
			{
				$this->deleteOrderPayment($payment);
			}
		}
		catch (Main\SystemException $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getOrderCalculatable(TradingEntity\Reference\Order $order)
	{
		if (!($order instanceof Order))
		{
			throw new Main\NotSupportedException('only Sale\Order calculation supported');
		}

		return $order->getCalculatable();
	}

	protected function getOrderPayment(Sale\Order $calculatableOrder)
	{
		$paymentCollection = $calculatableOrder->getPaymentCollection();
		$result = null;

		/** @var Sale\Payment $payment */
		foreach ($paymentCollection as $payment)
		{
			if (!$payment->isInner())
			{
				$result = $payment;
				break;
			}
		}

		return $result;
	}

	protected function createOrderPayment(Sale\Order $calculatableOrder)
	{
		$paymentCollection = $calculatableOrder->getPaymentCollection();
		$filledSum = $paymentCollection->getSum();
		$result = $paymentCollection->createItem();

		$result->setField('SUM', $calculatableOrder->getPrice() - $filledSum);

		return $result;
	}

	protected function isCalculatableCloned(Sale\Order $calculatableOrder)
	{
		return method_exists($calculatableOrder, 'createClone');
	}

	protected function deleteOrderPayment(Sale\Payment $payment)
	{
		$payment->delete();
	}

	protected function configureShipment(TradingEntity\Reference\Order $calculatableOrder, $deliveryId)
	{
		$deliveryEntity = $this->environment->getDelivery();

		if (!($deliveryEntity instanceof Delivery))
		{
			throw new Main\NotSupportedException('configureShipment available only for Sale\Delivery');
		}

		$deliveryEntity->configureShipment($calculatableOrder, $deliveryId);
	}

	public function suggestPaymentMethod($paySystemId, array $supportedMethods = null)
	{
		if ((int)$paySystemId === Sale\PaySystem\Manager::getInnerPaySystemId()) { return null; }

		$paySystemRow = Sale\PaySystem\Manager::getById($paySystemId);

		if (!$paySystemRow) { return null; }

		$result = null;

		foreach ($this->getSuggestPaymentMethodTests() as $test)
		{
			$testResult = $this->resolvePaymentMethodTest($paySystemRow, $test);

			if ($testResult === null) { continue; }

			$testResult = (array)$testResult;

			if ($supportedMethods !== null)
			{
				$testResult = array_intersect($testResult, $supportedMethods);
			}

			if (!empty($testResult))
			{
				$result = $testResult;
				break;
			}
		}

		return $result;
	}

	protected function getSuggestPaymentMethodTests()
	{
		return [
			'actionFile',
			'isCash',
		];
	}

	protected function resolvePaymentMethodTest($paySystemRow, $test)
	{
		$functionName = 'resolvePaymentMethodBy' . ucfirst($test);
		$result = null;

		if (method_exists($this, $functionName))
		{
			$result = $this->{$functionName}($paySystemRow);
		}

		return $result;
	}

	protected function resolvePaymentMethodByActionFile($paySystemRow)
	{
		if (!isset($paySystemRow['ACTION_FILE'])) { return null; }

		$actionFile = $paySystemRow['ACTION_FILE'];
		$result = null;

		foreach ($this->getActionFilePaymentMethodMap() as $actionName => $methods)
		{
			if (Market\Data\TextString::getPositionCaseInsensitive($actionFile, $actionName) !== false)
			{
				$result = $methods;
				break;
			}
		}

		return $result;
	}

	protected function getActionFilePaymentMethodMap()
	{
		return [
			'yandex' => [
				Market\Data\Trading\PaySystem::METHOD_YANDEX,
				Market\Data\Trading\PaySystem::METHOD_APPLE_PAY,
				Market\Data\Trading\PaySystem::METHOD_GOOGLE_PAY,
			],
			'cash' => [
				Market\Data\Trading\PaySystem::METHOD_CASH_ON_DELIVERY,
				Market\Data\Trading\PaySystem::METHOD_CARD_ON_DELIVERY,
			],
		];
	}

	protected function resolvePaymentMethodByIsCash($paySystemRow)
	{
		$result = null;

		if ($paySystemRow['IS_CASH'] === 'Y')
		{
			$result = [
				Market\Data\Trading\PaySystem::METHOD_CASH_ON_DELIVERY,
				Market\Data\Trading\PaySystem::METHOD_CARD_ON_DELIVERY,
			];
		}

		return $result;
	}
}
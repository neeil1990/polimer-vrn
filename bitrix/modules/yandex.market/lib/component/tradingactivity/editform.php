<?php

namespace Yandex\Market\Component\TradingActivity;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Service as TradingService;

class EditForm extends Market\Component\TradingActivityView\EditForm
{
	use Market\Reference\Concerns\HasMessage;

	public function update($primary, $fields)
	{
		$result = new Main\Entity\UpdateResult();
		$hasSuccess = false;
		$groupPrimaries = $this->getGroupPrimary($primary);
		$onlyOne = count($groupPrimaries) === 1;

		foreach ($groupPrimaries as $onePrimary)
		{
			$activity = $this->getActivity();
			$entityType = $activity->getSourceType();
			$tradingInfo = $this->getTradingInfo($entityType, $onePrimary);

			$procedure = new Market\Trading\Procedure\Runner(
				$entityType,
				$tradingInfo['ACCOUNT_NUMBER']
			);

			try
			{
				$procedure->run(
					$this->getSetup(),
					$this->getActionPath(),
					$this->getActivity()->getPayload($fields) + $this->getTradingPayload($entityType, $tradingInfo)
				);

				$hasSuccess = true;
			}
			catch (Market\Exceptions\Trading\NotImplementedAction $exception)
			{
				$result->addError(new Main\Error($exception->getMessage()));
			}
			catch (Market\Exceptions\Api\Request $exception)
			{
				$exceptionMessage = $exception->getMessage();
				$message = $onlyOne ? $exceptionMessage : self::getMessage('PROCEDURE_ERROR', [
					'#ORDER_ID#' => $onePrimary,
					'#MESSAGE#' => $exceptionMessage,
				], $exceptionMessage);

				$result->addError(new Main\Error($message));
			}
			catch (\Exception $exception)
			{
				$procedure->logException($exception);

				$result->addError(new Main\Error($exception->getMessage()));
			}
		}

		if ($hasSuccess)
		{
			Market\Trading\State\SessionCache::releaseByType('order');
		}

		return $result;
	}

	protected function getGroupPrimary($default)
	{
		$group = $this->getComponentParam('GROUP_PRIMARY');

		return is_array($group) ? $group : [$default];
	}

	protected function getTradingInfo($entityType, $primary)
	{
		if ($entityType === Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER)
		{
			$result = $this->getOrderTradingInfo($primary);
		}
		else
		{
			$result = [
				'ID' => $primary,
				'ACCOUNT_NUMBER' => $primary,
			];
		}

		return $result;
	}

	protected function getOrderTradingInfo($primary)
	{
		$platform = $this->getSetup()->getPlatform();
		$orderRegistry = $this->getSetup()->getEnvironment()->getOrderRegistry();

		return [
			'INTERNAL_ORDER_ID' => $orderRegistry->search($primary, $platform, false),
			'EXTERNAL_ORDER_ID' => $primary,
			'ACCOUNT_NUMBER' => $orderRegistry->search($primary, $platform),
		];
	}

	protected function getTradingPayload($entityType, array $tradingInfo)
	{
		if ($entityType === Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER)
		{
			$result = $this->getOrderTradingPayload($tradingInfo);
		}
		else if ($entityType === Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT)
		{
			$result = $this->getShipmentTradingPayload($tradingInfo);
		}
		else
		{
			$result = [];
		}

		return $result + [
			'immediate' => true,
		];
	}

	protected function getOrderTradingPayload(array $tradingInfo)
	{
		return [
			'internalId' => $tradingInfo['INTERNAL_ORDER_ID'],
			'orderId' => $tradingInfo['EXTERNAL_ORDER_ID'],
			'orderNum' => $tradingInfo['ACCOUNT_NUMBER'],
		];
	}

	protected function getShipmentTradingPayload(array $tradingInfo)
	{
		return [
			'shipmentId' => $tradingInfo['ID'],
		];
	}

	/** @return TradingService\Reference\Action\FormActivity */
	protected function getActivity()
	{
		$action = $this->getComponentParam('TRADING_ACTIVITY');

		Assert::notNull($action, 'TRADING_ACTIVITY');
		Assert::typeOf($action, TradingService\Reference\Action\FormActivity::class, 'TRADING_ACTIVITY');

		return $action;
	}
}
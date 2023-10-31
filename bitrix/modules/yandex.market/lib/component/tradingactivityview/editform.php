<?php

namespace Yandex\Market\Component\TradingActivityView;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Service as TradingService;

class EditForm extends Market\Component\Plain\EditForm
{
	protected $entity;

	public function load($primary, array $select = [], $isCopy = false)
	{
		$entity = $this->loadEntity($primary);

		return $this->getActivity()->getEntityValues($entity);
	}

	protected function loadEntity($primary)
	{
		$activity = $this->getActivity();
		$sourceType = $activity->getSourceType();

		if ($activity instanceof TradingService\Reference\Action\HasActivityEntityLoader)
		{
			$this->getSetup()->wakeupService(); // fill options

			return $activity->loadEntity($primary);
		}

		if ($sourceType === Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER)
		{
			return $this->loadOrder($primary);
		}

		return $this->loadEntityByFacade($sourceType, $primary);
	}

	protected function loadOrder($primary)
	{
		$service = $this->getSetup()->wakeupService();
		$options = $service->getOptions();

		if (Market\Trading\State\SessionCache::has('order', $primary))
		{
			$orderClassName = $service->getModelFactory()->getOrderClassName();
			$fields = Market\Trading\State\SessionCache::get('order', $primary);

			$order = $orderClassName::initialize($fields);
		}
		else
		{
			$orderFacade = $service->getModelFactory()->getOrderFacadeClassName();

			$order = $orderFacade::load($options, $primary);
		}

		return $order;
	}

	protected function loadEntityByFacade($entityType, $primary)
	{
		$service = $this->getSetup()->wakeupService();
		$options = $service->getOptions();
		$facade = $service->getModelFactory()->getEntityFacadeClassName($entityType);

		return $facade::load($options, $primary);
	}

	public function getFields(array $select = [], $item = null)
	{
		$result = parent::getFields($select, $item);

		return $this->getActivity()->extendFields($result, $item);
	}

	public function add($fields)
	{
		throw new Main\NotSupportedException();
	}

	public function update($primary, $fields)
	{
		throw new Main\NotSupportedException();
	}

	/** @return TradingSetup\Model */
	protected function getSetup()
	{
		$action = $this->getComponentParam('TRADING_SETUP');

		Assert::notNull($action, 'TRADING_SETUP');
		Assert::typeOf($action, TradingSetup\Model::class, 'TRADING_SETUP');

		return $action;
	}

	/** @return string */
	protected function getActionPath()
	{
		$path = $this->getComponentParam('TRADING_PATH');

		Assert::notNull($path, 'TRADING_PATH');

		return (string)$path;
	}

	/** @return TradingService\Reference\Action\DataAction */
	protected function getAction()
	{
		$action = $this->getComponentParam('TRADING_ACTION');

		Assert::notNull($action, 'TRADING_ACTION');
		Assert::typeOf($action, TradingService\Reference\Action\DataAction::class, 'TRADING_ACTION');

		return $action;
	}

	/** @return TradingService\Reference\Action\ViewActivity */
	protected function getActivity()
	{
		$action = $this->getComponentParam('TRADING_ACTIVITY');

		Assert::notNull($action, 'TRADING_ACTIVITY');
		Assert::typeOf($action, TradingService\Reference\Action\ViewActivity::class, 'TRADING_ACTIVITY');

		return $action;
	}
}
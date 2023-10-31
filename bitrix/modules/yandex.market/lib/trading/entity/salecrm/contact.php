<?php

namespace Yandex\Market\Trading\Entity\SaleCrm;

use Bitrix\Main;
use Bitrix\Crm;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Entity as TradingEntity;

class Contact extends TradingEntity\Reference\Contact
{
	use Concerns\HasMessage;

	protected function search()
	{
		$order = $this->makeOrder();

		return $this->makeMatchManager()->search($order);
	}

	public function install(array $data = [])
	{
		$order = $this->makeOrder($data);
		$contacts = $this->makeMatchManager()->match($order);

		$result = new Main\Entity\AddResult();
		$result->setId($contacts);

		return $result;
	}

	protected function makeOrder(array $data = [])
	{
		$siteId = isset($data['SITE_ID']) ? $data['SITE_ID'] : SITE_ID;

		/** @var Crm\Order\Order $order */
		$order = Crm\Order\Order::create($siteId);
		$order->setPersonTypeId($this->personTypeId);
		$order->getPropertyCollection()->setValuesFromPost([ 'PROPERTIES' => $this->properties ], []);

		return $order;
	}

	/** @return Crm\Order\Matcher\EntityMatchManager */
	protected function makeMatchManager()
	{
		return Crm\Order\Matcher\EntityMatchManager::getInstance();
	}
}
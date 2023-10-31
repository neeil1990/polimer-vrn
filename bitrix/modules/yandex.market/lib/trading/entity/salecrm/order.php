<?php

namespace Yandex\Market\Trading\Entity\SaleCrm;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Bitrix\Main;
use Bitrix\Crm;

/**
 * @property Crm\Order\Order $internalOrder
 */
class Order extends TradingEntity\Sale\Order
{
	public function getAdminEditUrl()
	{
		if (Main\Context::getCurrent()->getRequest()->isAdminSection())
		{
			return parent::getAdminEditUrl();
		}

		return sprintf(
			'/shop/orders/details/%s/',
			(int)$this->getId()
		);
	}

	public function getContacts()
	{
		$communication = $this->internalOrder->getContactCompanyCollection();

		if ($communication === null) { return []; }

		$result = [];

		/** @var Crm\Order\ContactCompanyEntity $contact */
		foreach ($communication as $contact)
		{
			if (!$contact->isPrimary()) { continue; }

			$result[$contact::getEntityType()] = $contact->getField('ENTITY_ID');
		}

		return $result;
	}

	public function fillContacts(array $contacts)
	{
		return Market\Result\Facade::merge([
			$this->setOrderContacts($contacts),
			$this->setDealContacts($contacts),
			$this->resetOrderRequisiteLink(),
		]);
	}

	protected function setOrderContacts(array $contacts)
	{
		try
		{
			$communication = $this->internalOrder->getContactCompanyCollection();
			$result = new Main\Result();

			if ($communication === null) { return $result; }

			$communication->clearCollection();

			if (isset($contacts[\CCrmOwnerType::Contact]))
			{
				$contact = Crm\Order\Contact::create($communication);
				$contact->setField('ENTITY_ID', $contacts[\CCrmOwnerType::Contact]);
				$contact->setField('IS_PRIMARY', 'Y');

				$communication->addItem($contact);
			}

			if (isset($contacts[\CCrmOwnerType::Company]))
			{
				$company = Crm\Order\Company::create($communication);
				$company->setField('ENTITY_ID', $contacts[\CCrmOwnerType::Company]);
				$company->setField('IS_PRIMARY', 'Y');

				$communication->addItem($company);
			}
		}
		catch (Main\SystemException $exception)
		{
			$result = new Main\Result();
			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	protected function resetOrderRequisiteLink()
	{
		$result = new Main\Result();
		$collection = $this->internalOrder->getContactCompanyCollection();

		if ($collection === null) { return $result; }

		$entity = $collection->getPrimaryCompany() ?: $collection->getPrimaryContact();

		if ($entity === null) { return $result; }

		$requisites = $entity->getRequisiteList();
		$requisite = end($requisites);
		$bankRequisites = $entity->getBankRequisiteList();
		$bankRequisite = end($bankRequisites);
		$fields = [
			'REQUISITE_ID' => 0,
			'BANK_DETAIL_ID' => 0,
		];

		if ($requisite !== false)
		{
			$fields['REQUISITE_ID'] = $requisite['ID'];
		}

		if ($bankRequisite !== false)
		{
			$fields['BANK_DETAIL_ID'] = $bankRequisite['ID'];
		}

		$this->internalOrder->setRequisiteLink($fields);

		return $result;
	}

	protected function setDealContacts(array $contacts)
	{
		$result = new Main\Result();
		$dealId = $this->dealId();

		if ($dealId <= 0) { return $result; }

		$fields = [];

		if (isset($contacts[\CCrmOwnerType::Company]))
		{
			$fields['COMPANY_ID'] = $contacts[\CCrmOwnerType::Company];
		}

		if (isset($contacts[\CCrmOwnerType::Contact]))
		{
			$fields['CONTACT_ID'] = $contacts[\CCrmOwnerType::Contact];
		}

		if (empty($fields)) { return $result; }

		$updater = new \CCrmDeal(false);
		$updated = $updater->Update($dealId, $fields, [
			'DISABLE_USER_FIELD_CHECK' => true,
		]);

		if (!$updated)
		{
			$result->addError(new Main\Error($updater->LAST_ERROR));
		}

		return $result;
	}

	protected function dealId()
	{
		if (method_exists($this->internalOrder, 'getDealBinding'))
		{
			$binding = $this->internalOrder->getDealBinding();

			if ($binding === null) { return null; }

			$result = $binding->getDealId();
		}
		else if (method_exists($this->internalOrder, 'getEntityBinding'))
		{
			$binding = $this->internalOrder->getEntityBinding();

			if ($binding === null || $binding->getOwnerTypeId() !== \CCrmOwnerType::Deal) { return null; }

			$result = $binding->getOwnerId();
		}
		else
		{
			$result = null;
		}

		return $result;
	}
}
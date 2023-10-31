<?php

namespace Yandex\Market\Trading\Entity\SaleCrm;

use Yandex\Market\Trading\Entity as TradingEntity;

class ContactRegistry extends TradingEntity\Reference\ContactRegistry
{
	protected function createAnonymous($serviceCode, $personTypeId)
	{
		return new AnonymousContact($this->environment, $serviceCode, $personTypeId);
	}

	protected function createContact($personTypeId, array $properties)
	{
		return new Contact($this->environment, $personTypeId, $properties);
	}
}
<?php

namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class OrderContact
{
	protected $provider;
	protected $environment;
	protected $order;

	public function __construct(
		TradingService\Reference\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		TradingEntity\Reference\Order $order
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
		$this->order = $order;
	}

	public function needExecute()
	{
		if (!($this->environment instanceof TradingEntity\Reference\HasContactRegistry)) { return false; }

		$personTypeId = $this->order->getPersonType();
		$existContacts = $this->order->getContacts();
		$anonymousContact = $this->environment->getContactRegistry()->getAnonymous($this->provider->getServiceCode(), $personTypeId);
		$realContacts = array_diff($existContacts, $anonymousContact->getId());

		return empty($realContacts) && !empty($existContacts);
	}

	public function execute()
	{
		if (!($this->environment instanceof TradingEntity\Reference\HasContactRegistry)) { return; }

		$personTypeId = $this->order->getPersonType();
		$properties = $this->order->getPropertyValues();
		$properties = array_diff_assoc($properties, $this->getProfileValues());

		$contactEntity = $this->environment->getContactRegistry()->getContact($personTypeId, $properties);
		$installResult = $contactEntity->install([
			'SITE_ID' => $this->order->getSiteId(),
		]);

		Market\Result\Facade::handleException($installResult);

		$fillResult = $this->order->fillContacts($installResult->getId());

		Market\Result\Facade::handleException($fillResult);
	}

	protected function getProfileValues()
	{
		$options = $this->provider->getOptions();
		$profileId = method_exists($options, 'getProfileId') ? (string)$options->getProfileId() : '';

		if ($profileId === '') { return []; }

		return $this->environment->getProfile()->getValues($profileId);
	}
}
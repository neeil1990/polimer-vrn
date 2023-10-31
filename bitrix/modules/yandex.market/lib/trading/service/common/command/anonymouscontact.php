<?php

namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class AnonymousContact
{
	protected $provider;
	protected $environment;

	public function __construct(
		TradingService\Reference\Provider $provider,
		TradingEntity\Reference\Environment $environment
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
	}

	public function execute()
	{
		if (!($this->environment instanceof TradingEntity\Reference\HasContactRegistry)) { return []; }

		$serviceCode = $this->provider->getServiceCode();
		$personTypeId = $this->provider->getOptions()->getPersonType();
		$anonymousContact = $this->environment->getContactRegistry()->getAnonymous($serviceCode, $personTypeId);

		if ($anonymousContact->isInstalled())
		{
			$result = $anonymousContact->getId();
		}
		else
		{
			$installResult = $anonymousContact->install([
				'COMPANY' => $this->provider->getInfo()->getCompanyData(),
				'CONTACT' => $this->provider->getInfo()->getContactData(),
			]);

			Market\Result\Facade::handleException($installResult);

			$result = $installResult->getId();
		}

		return $result;
	}
}
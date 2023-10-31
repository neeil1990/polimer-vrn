<?php

namespace Yandex\Market\Trading\Entity\SaleCrm;

use Bitrix\Crm;
use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;

class AnonymousContact extends TradingEntity\Reference\Contact
{
	use Market\Reference\Concerns\HasMessage;

	protected $serviceCode;

	public function __construct(TradingEntity\Reference\Environment $environment, $serviceCode, $personTypeId)
	{
		parent::__construct($environment, $personTypeId);
		$this->serviceCode = $serviceCode;
	}

	protected function search()
	{
		return $this->getOptionValue();
	}

	public function install(array $data = [])
	{
		$supported = [
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Contact,
		];
		$entities = Crm\Order\Matcher\FieldMatcher::getMatchedEntities($this->personTypeId);
		$entities = array_intersect($supported, $entities);
		$primaries = [];
		$errors = [];

		foreach ($entities as $ownerType)
		{
			$ownerType = (int)$ownerType;

			if ($ownerType === \CCrmOwnerType::Company)
			{
				$entity = new \CCrmCompany(false);
				$fields = isset($data['COMPANY']) ? (array)$data['COMPANY'] : [];
				$fields = array_filter($fields);
				$fields += [
					'TITLE' => self::getMessage('COMPANY_TITLE'),
				];
			}
			else if ($ownerType === \CCrmOwnerType::Contact)
			{
				$entity = new \CCrmContact(false);
				$fields = isset($data['CONTACT']) ? (array)$data['CONTACT'] : [];
				$fields = array_filter($fields);
				$fields += [
					'NAME' => self::getMessage('CONTACT_NAME'),
				];

				if (isset($primaries[\CCrmOwnerType::Company]))
				{
					$fields['COMPANY_ID'] = $primaries[\CCrmOwnerType::Company];
				}
			}
			else
			{
				continue;
			}

			$primary = $entity->Add($fields, [
				'DISABLE_USER_FIELD_CHECK' => true,
			]);

			if ($primary)
			{
				$primaries[$ownerType] = $primary;
			}
			else
			{
				$errors[$ownerType] = $entity->LAST_ERROR;
			}
		}

		$result = new Main\Entity\AddResult();

		$this->id = $primaries;
		$result->setId($primaries);

		if (!empty($primaries))
		{
			$this->saveOptionValue($primaries);
		}
		else
		{
			foreach ($errors as $error)
			{
				$result->addError(new Main\Error($error));
			}
		}

		return $result;
	}

	protected function getOptionValue()
	{
		$name = $this->getOptionName();
		$option = (string)Market\Config::getOption($name);

		if ($option === '') { return []; }

		$stored = unserialize($option, ['allowed_classes' => false]);

		if (!is_array($stored)) { return []; }

		return $stored;
	}

	protected function saveOptionValue(array $contacts)
	{
		$name = $this->getOptionName();

		Market\Config::setOption($name, serialize($contacts));
	}

	protected function getOptionName()
	{
		return 'trading_' . $this->serviceCode . '_contact_' . $this->personTypeId;
	}
}
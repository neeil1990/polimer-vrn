<?php

namespace Yandex\Market\Ui\Trading\Reference;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

abstract class PropertyCreator extends Market\Ui\Reference\Page
{
	public function processRequest()
	{
		$personTypeId = $this->getPersonTypeId();
		$environment = Market\Trading\Entity\Manager::createEnvironment();
		$service = $this->getService();
		$fields = $this->makePropertyFields($service);
		$propertyEntity = $environment->getProperty();

		$addResult = $propertyEntity->add($personTypeId, $fields);

		Market\Result\Facade::handleException($addResult);

		$propertyId = $addResult->getId();

		return [
			'ID' => $propertyId,
			'VALUE' => $fields['NAME'],
			'EDIT_URL' => $propertyEntity->getEditUrl($propertyId),
		];
	}

	public function sync()
	{
		foreach ($this->searchUsedProperties() as $propertyId => $fields)
		{
			$fields = array_diff_key($fields, [
				'NAME' => true,
				'CODE' => true,
			]);

			if (empty($fields)) { continue; }

			$environment = Market\Trading\Entity\Manager::createEnvironment();
			$propertyEntity = $environment->getProperty();

			$propertyEntity->update($propertyId, $fields);
		}
	}

	/** @return array<int, array> */
	abstract protected function searchUsedProperties();

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	protected function getWriteRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	protected function getPersonTypeId()
	{
		$personTypeId = (int)$this->request->getPost('PERSON_TYPE_ID');

		if ($personTypeId <= 0)
		{
			throw new Main\ArgumentNullException('PERSON_TYPE');
		}

		return $personTypeId;
	}

	protected function getService()
	{
		$code = $this->getServiceCode();

		return TradingService\Manager::createProvider($code);
	}

	protected function getServiceCode()
	{
		$code = (string)$this->request->getPost('SERVICE_CODE');

		if ($code === '')
		{
			throw new Main\ArgumentNullException('SERVICE_CODE');
		}

		return $code;
	}

	abstract protected function makePropertyFields(TradingService\Reference\Provider $service);
}
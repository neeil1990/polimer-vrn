<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class CancelReasonCreator extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function processRequest()
	{
		$personTypeId = $this->getPersonTypeId();
		$environment = Market\Trading\Entity\Manager::createEnvironment();
		$service = $this->getService();
		$cancelReason = $this->getCancelReason($service);
		$fields = $this->makePropertyFields($cancelReason);
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

	/**
	 * @param TradingService\Reference\Provider $service
	 *
	 * @return TradingService\MarketplaceDbs\CancelReason
	 * @throws Main\NotSupportedException
	 */
	protected function getCancelReason(TradingService\Reference\Provider $service)
	{
		if (!method_exists($service, 'getCancelReason'))
		{
			throw new Main\NotSupportedException('service hasn\'t cancel reason entity');
		}

		return $service->getCancelReason();
	}

	protected function makePropertyFields(TradingService\MarketplaceDbs\CancelReason $cancelReason)
	{
		return [
			'TYPE' => 'ENUM',
			'NAME' => static::getLang('ADMIN_TRADING_CANCEL_REASON_CREATOR_NAME', null, 'CANCEL_REASON'),
			'CODE' => 'MARKET_CANCEL_REASON',
			'VARIANTS' => $this->makePropertyEnum($cancelReason),
		];
	}

	protected function makePropertyEnum(TradingService\MarketplaceDbs\CancelReason $cancelReason)
	{
		$result = [];

		foreach ($cancelReason->getVariants() as $variant)
		{
			$result[] = [
				'ID' => $variant,
				'VALUE' => $cancelReason->getTitle($variant),
			];
		}

		return $result;
	}
}
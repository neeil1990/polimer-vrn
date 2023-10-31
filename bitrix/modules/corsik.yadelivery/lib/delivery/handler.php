<?php

namespace Corsik\YaDelivery\Delivery;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Shipment;
use Corsik\YaDelivery\Handler;

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/corsik.yadelivery/install/index.php');
Loader::registerAutoLoadClasses('corsik.yadelivery',
	[__NAMESPACE__ . '\YandexDeliveryProfile' => 'lib/delivery/profile.php']);

class YandexDeliveryHandler extends Base
{

	protected static $canHasProfiles = true;
	protected $handlerCode = 'CORSIK_YADELIVERY';
	protected $profilesListFull = null;

	public function __construct(array $initParams)
	{
		parent::__construct($initParams);
	}

	public static function getClassTitle()
	{
		return Loc::getMessage("CORSIK_DELIVERY_MODULE_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("CORSIK_DELIVERY_MODULE_DESCRIPTION");
	}

	public static function canHasProfiles()
	{
		return self::$canHasProfiles;
	}

	/*Profile*/

	public static function getChildrenClassNames()
	{
		return [
			__NAMESPACE__ . '\YandexDeliveryProfile',
		];
	}

	public static function getAdminFieldsList()
	{
		$result = parent::getAdminFieldsList();
		$result["STORES"] = true;
		return $result;
	}

	public function getProfilesList()
	{
		return [Loc::getMessage("CORSIK_DELIVERY_NEW_PROFILE")];
	}

	public function isCalculatePriceImmediately()
	{
		return self::$isCalculatePriceImmediately;
	}

	protected function calculateConcrete(Shipment $shipment)
	{
		/*All calculations in the profile*/
		throw new SystemException("Only Additional Profiles can calculate concrete");
	}

}

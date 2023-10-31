<?php

namespace Corsik\YaDelivery;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals;
use CSalePersonType;

Loc::loadMessages(__FILE__);

class Options
{
	public static string $module_id = 'corsik.yadelivery';
	public static string $lang_name = 'CORSIK';

	public static function getOptionsArrayBySite($name, $personType = false): array
	{
		$arrOptions = [];
		$siteID = Handler::getSiteId();
		if ($personType)
		{
			foreach (self::getTypePayers() as $personType)
			{
				$option = self::getOptionByName($name, $siteID, $personType['ID']);
				if ($option)
				{
					$arrOptions[$personType['ID']] = explode(',', $option);
				}
			}
		}
		else
		{
			$arrOptions = explode(',', self::getOptionByName($name, $siteID));
		}

		return $arrOptions;
	}

	public static function getTypePayers($siteID = false): array
	{
		$arrPayers = [];
		$filter = ["ACTIVE" => 'Y'];
		if ($siteID)
		{
			$filter['LID'] = $siteID;
		}
		$dbPayers = CSalePersonType::GetList(["SORT" => "ASC"], $filter);
		while ($payer = $dbPayers->Fetch())
		{
			$arrPayers[] = $payer;
		}

		return $arrPayers;
		//        return Internals\PersonTypeTable::getList(['select' => ['LID', 'ID', 'NAME'], 'filter' => $filter])->fetchAll();
	}

	public static function getOptionByName($name, $siteID = false, $personType = false): string
	{
		$siteID = $siteID === true ? Handler::getSiteId() : $siteID;
		$optionName = implode("_", array_filter([$name, $siteID, $personType], fn($type) => (bool)$type));

		return Option::get(self::$module_id, $optionName);
	}

	public static function getBoolOptionByName($name): bool
	{
		return self::getOptionByName($name) === 'Y';
	}

	public static function getPropertiesOrder(int $payerType = 0): array
	{
		$filter = ($payerType > 0) ? ['PERSON_TYPE_ID' => $payerType] : [];

		return Internals\OrderPropsTable::getList([
			'select' => ['*'],
			'filter' => array_merge($filter, ["ACTIVE" => "Y"]),
			'order' => ['SORT' => 'ASC'],
		])->fetchAll();
	}

	public static function DaDataType()
	{
		return [
			"N" => Loc::getMessage("CORSIK_DADATA_NO"),
			/*NAME*/
			'GROUP_START_FIO' => Loc::getMessage("CORSIK_DADATA_GROUP_FIO"),
			'NAME' => Loc::getMessage("CORSIK_DADATA_FIO"),
			'PARAMS-NAME-NAME' => Loc::getMessage("CORSIK_DADATA_NAME"),
			'PARAMS-NAME-SURNAME' => Loc::getMessage("CORSIK_DADATA_SURNAME"),
			'PARAMS-NAME-PATRONYMIC' => Loc::getMessage("CORSIK_DADATA_PATRONYMIC"),
			'GROUP_END_FIO' => 'stop',
			/*EMAIL*/
			'GROUP_START_EMAIL' => Loc::getMessage("CORSIK_DADATA_GROUP_EMAIL"),
			'EMAIL' => Loc::getMessage("CORSIK_DADATA_EMAIL"),
			'PARAMS-EMAIL-SUGGEST_LOCAL' => Loc::getMessage("CORSIK_DADATA_DOMAIN"),
			'GROUP_END_EMAIL' => 'stop',
			/*ADDRESS*/
			'GROUP_START_ADDRESS' => Loc::getMessage("CORSIK_DADATA_GROUP_ADDRESS"),
			'ADDRESS' => Loc::getMessage("CORSIK_DADATA_ADDRESS"),
			'AFTER-ADDRESS-postal_code' => Loc::getMessage("CORSIK_DADATA_INDEX"),
			'AFTER-ADDRESS-city' => Loc::getMessage("CORSIK_DADATA_CITY"),
			'AFTER-ADDRESS-street' => Loc::getMessage("CORSIK_DADATA_STREET"),
			'AFTER-ADDRESS-house' => Loc::getMessage("CORSIK_DADATA_HOME"),
			'AFTER-ADDRESS-flat' => Loc::getMessage("CORSIK_DADATA_FLAT"),
			'GROUP_END_ADDRESS' => 'stop',
			/*PARTY*/
			'GROUP_START_PARTY' => Loc::getMessage("CORSIK_DADATA_GROUP_PARTY"),
			'PARTY' => Loc::getMessage("CORSIK_DADATA_PARTY"),
			'AFTER-PARTY-management.name' => Loc::getMessage("CORSIK_DADATA_PARTY_MANAGEMENT_NAME"),
			'AFTER-PARTY-address.value' => Loc::getMessage("CORSIK_DADATA_PARTY_ADDRESS"),
			'AFTER-PARTY-inn' => Loc::getMessage("CORSIK_DADATA_PARTY_INN"),
			'AFTER-PARTY-kpp' => Loc::getMessage("CORSIK_DADATA_PARTY_KPP"),
			'AFTER-PARTY-ogrn' => Loc::getMessage("CORSIK_DADATA_PARTY_OGRN"),
			'AFTER-PARTY-ogrn_date' => Loc::getMessage("CORSIK_DADATA_PARTY_OGRN_DATE"),
			'GROUP_END_PARTY' => 'stop',
			/*BANK*/
			'GROUP_START_BANK' => Loc::getMessage("CORSIK_DADATA_GROUP_BANK"),
			'BANK' => Loc::getMessage("CORSIK_DADATA_BANK"),
			'AFTER-BANK-name.full' => Loc::getMessage("CORSIK_DADATA_BANK_NAME_FULL"),
			'AFTER-BANK-name.payment' => Loc::getMessage("CORSIK_DADATA_BANK_NAME_PAYMENT"),
			'AFTER-BANK-address.value' => Loc::getMessage("CORSIK_DADATA_BANK_ADDRESS"),
			'AFTER-BANK-phone' => Loc::getMessage("CORSIK_DADATA_BANK_PHONE"),
			'AFTER-BANK-correspondent_account' => Loc::getMessage("CORSIK_DADATA_BANK_CORRESPONDENT_ACCOUNT"),
			'AFTER-BANK-registration_number' => Loc::getMessage("CORSIK_DADATA_BANK_REGISTRATION_NUMBER"),
			'AFTER-BANK-bic' => Loc::getMessage("CORSIK_DADATA_BANK_BIC"),
			'AFTER-BANK-okpo' => Loc::getMessage("CORSIK_DADATA_BANK_OKPO"),
			'AFTER-BANK-swift' => Loc::getMessage("CORSIK_DADATA_BANK_SWIFT"),
			'GROUP_END_BANK' => 'stop',
		];
	}

}

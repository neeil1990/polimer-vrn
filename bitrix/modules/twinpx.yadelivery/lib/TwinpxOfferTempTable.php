<?php
namespace Twinpx\Yadelivery;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class YadeliveryOfferTempTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> ORDER_DATE datetime optional
 * <li> PVZ_ID string(50) optional
 * <li> PAYCONFIRM int mandatory
 * <li> PAYMENT string(50) optional
 * <li> LOCATION string(256) optional
 * <li> DELIVERY_INTERVAL string(100) optional
 * <li> DELIVERYDATE datetime optional
 * <li> INSURANCE int optional
 * </ul>
 *
 * @package Bitrix\Twpx
 **/

class TwinpxOfferTempTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_twpx_yadelivery_offer_temp';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_ID_FIELD')
				]
			),
			new IntegerField(
				'ORDER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_ORDER_ID_FIELD')
				]
			),
			new DatetimeField(
				'ORDER_DATE',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_ORDER_DATE_FIELD')
				]
			),
			new StringField(
				'PVZ_ID',
				[
					'validation' => [__CLASS__, 'validatePvzId'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_PVZ_ID_FIELD')
				]
			),
			new IntegerField(
				'PAYCONFIRM',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_PAYCONFIRM_FIELD')
				]
			),
			new StringField(
				'PAYMENT',
				[
					'validation' => [__CLASS__, 'validatePayment'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_PAYMENT_FIELD')
				]
			),
			new StringField(
				'LOCATION',
				[
					'validation' => [__CLASS__, 'validateLocation'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_LOCATION_FIELD')
				]
			),
			new StringField(
				'DELIVERY_INTERVAL',
				[
					'validation' => [__CLASS__, 'validateDeliveryInterval'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_DELIVERY_INTERVAL_FIELD')
				]
			),
			new DatetimeField(
				'DELIVERYDATE',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_DELIVERYDATE_FIELD')
				]
			),
			new IntegerField(
				'INSURANCE',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_TEMP_ENTITY_INSURANCE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for PVZ_ID field.
	 *
	 * @return array
	 */
	public static function validatePvzId()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for PAYMENT field.
	 *
	 * @return array
	 */
	public static function validatePayment()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for LOCATION field.
	 *
	 * @return array
	 */
	public static function validateLocation()
	{
		return [
			new LengthValidator(null, 256),
		];
	}

	/**
	 * Returns validators for DELIVERY_INTERVAL field.
	 *
	 * @return array
	 */
	public static function validateDeliveryInterval()
	{
		return [
			new LengthValidator(null, 100),
		];
	}
}
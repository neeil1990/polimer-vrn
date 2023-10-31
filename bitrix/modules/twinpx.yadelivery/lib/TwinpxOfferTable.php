<?php
namespace Twinpx\Yadelivery;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\FloatField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;


Loc::loadMessages(__FILE__);
/**
 * Class YadeliveryOfferTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OFFER_ID string(50) mandatory
 * <li> PVZ_ID string(50) optional
 * <li> REQUEST_ID string(50) optional
 * <li> ORDER_ID int optional
 * <li> ORDER_DATE datetime optional
 * <li> ADDRESS string(255) mandatory
 * <li> LOCATION string(20) mandatory
 * <li> DELIVERY_INTERVAL string(100) optional
 * <li> STATUS string(50) optional
 * <li> STATUS_DESCRIPTION string(255) optional
 * <li> JSON_REQUEST text optional
 * <li> JSON_RESPONS text optional
 * <li> PAYMENT string(50) optional
 * <li> PICKUPDATE datetime optional
 * <li> PAYCONFIRM int mandatory
 * <li> PRICE double optional
 * <li> PRICE_FIX double optional
 * <li> PRICE_DELIVERY double optional
 * <li> CANCEL int optional default 0
 * <li> CHECK_AGENT int optional default 0
 * <li> PICKUP string(100) optional
 * <li> CREATE_ERROR string(50) optional
 * <li> BARCODE string(50) optional
 * <li> DIVIDE string(50) optional default '1'
 * </ul>
 *
 **/

class TwinpxOfferTable extends DataManager
{
    
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_twpx_yadelivery_offer';
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
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'OFFER_ID',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateOfferId'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_OFFER_ID_FIELD')
				]
			),
			new StringField(
				'PVZ_ID',
				[
					'validation' => [__CLASS__, 'validatePvzId'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_PVZ_ID_FIELD')
				]
			),
			new StringField(
				'REQUEST_ID',
				[
					'validation' => [__CLASS__, 'validateRequestId'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_REQUEST_ID_FIELD')
				]
			),
			new IntegerField(
				'ORDER_ID',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_ORDER_ID_FIELD')
				]
			),
			new DatetimeField(
				'ORDER_DATE',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_ORDER_DATE_FIELD')
				]
			),
			new StringField(
				'ADDRESS',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateAddress'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_ADDRESS_FIELD')
				]
			),
			new StringField(
				'LOCATION',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateLocation'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_LOCATION_FIELD')
				]
			),
			new StringField(
				'DELIVERY_INTERVAL',
				[
					'validation' => [__CLASS__, 'validateDeliveryInterval'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_DELIVERY_INTERVAL_FIELD')
				]
			),
			new StringField(
				'STATUS',
				[
					'validation' => [__CLASS__, 'validateStatus'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_STATUS_FIELD')
				]
			),
			new StringField(
				'STATUS_DESCRIPTION',
				[
					'validation' => [__CLASS__, 'validateStatusDescription'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_STATUS_DESCRIPTION_FIELD')
				]
			),
			new TextField(
				'JSON_REQUEST',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_JSON_REQUEST_FIELD')
				]
			),
			new TextField(
				'JSON_RESPONS',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_JSON_RESPONS_FIELD')
				]
			),
			new StringField(
				'PAYMENT',
				[
					'validation' => [__CLASS__, 'validatePayment'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_PAYMENT_FIELD')
				]
			),
			new DatetimeField(
				'PICKUPDATE',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_PICKUPDATE_FIELD')
				]
			),
			new IntegerField(
				'STATUSCONFIRM',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_STATUSCONFIRM_FIELD')
				]
			),
			new FloatField(
				'PRICE',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_PRICE_FIELD')
				]
			),
			new FloatField(
				'PRICE_FIX',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_PRICE_FIX_FIELD')
				]
			),
			new FloatField(
				'PRICE_DELIVERY',
				[
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_PRICE_DELIVERY_FIELD')
				]
			),
			new IntegerField(
				'CANCEL',
				[
					'default' => 0,
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_CANCEL_FIELD')
				]
			),
			new IntegerField(
				'CHECK_AGENT',
				[
					'default' => 0,
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_CHECK_AGENT_FIELD')
				]
			),
			new StringField(
				'PICKUP',
				[
					'validation' => [__CLASS__, 'validatePickup'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_PICKUP_FIELD')
				]
			),
			new StringField(
				'CREATE_ERROR',
				[
					'validation' => [__CLASS__, 'validateCreateError'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_CREATE_ERROR_FIELD')
				]
			),
			new StringField(
				'BARCODE',
				[
					'validation' => [__CLASS__, 'validateBarcode'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_BARCODE_FIELD')
				]
			),
			new StringField(
				'DIVIDE',
				[
					'default' => '1',
					'validation' => [__CLASS__, 'validateDivide'],
					'title' => Loc::getMessage('YADELIVERY_OFFER_ENTITY_DIVIDE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for OFFER_ID field.
	 *
	 * @return array
	 */
	public static function validateOfferId()
	{
		return [
			new LengthValidator(null, 50),
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
	 * Returns validators for REQUEST_ID field.
	 *
	 * @return array
	 */
	public static function validateRequestId()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for ADDRESS field.
	 *
	 * @return array
	 */
	public static function validateAddress()
	{
		return [
			new LengthValidator(null, 255),
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
			new LengthValidator(null, 20),
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

	/**
	 * Returns validators for STATUS field.
	 *
	 * @return array
	 */
	public static function validateStatus()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for STATUS_DESCRIPTION field.
	 *
	 * @return array
	 */
	public static function validateStatusDescription()
	{
		return [
			new LengthValidator(null, 255),
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
	 * Returns validators for PICKUP field.
	 *
	 * @return array
	 */
	public static function validatePickup()
	{
		return [
			new LengthValidator(null, 100),
		];
	}

	/**
	 * Returns validators for CREATE_ERROR field.
	 *
	 * @return array
	 */
	public static function validateCreateError()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for BARCODE field.
	 *
	 * @return array
	 */
	public static function validateBarcode()
	{
		return [
			new LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for DIVIDE field.
	 *
	 * @return array
	 */
	public static function validateDivide()
	{
		return [
			new LengthValidator(null, 50),
		];
	}
	
	/**
	* 
	* @param undefined $filterData
	* @param undefined $filterable
	* 
	* @return
	*/
    public static function prepareFilter($filterData, $filterable = array())
    {
        $arFilter = array();
        unset($filterData['PRESET_ID']);
        unset($filterData['FILTER_ID']);
        unset($filterData['FILTER_APPLIED']);

        foreach ($filterData as $fieldId => $fieldValue) {
            /**
            *
            * @var exact
            * @var more
            * @var less
            *
            * @var CURRENT_DAY
            * @var YESTERDAY
            * @var TOMORROW
            * @var TOMORROW
            * @var CURRENT_WEEK
            * @var CURRENT_MONTH
            * @var QUARTER
            * @var LAST_7_DAYS
            * @var LAST_30_DAYS
            * @var LAST_60_DAYS
            * @var LAST_90_DAYS
            * @var PREV_DAYS
            * @var NEXT_DAYS
            * @var MONTH
            * @var QUARTER
            * @var YEAR
            * @var EXACT
            * @var LAST_WEEK
            * @var LAST_MONTH
            * @var RANGE
            * @var NEXT_WEEK
            * @var NEXT_MONTH
            * @var NEXT_MONTH
            *
            *
            */
            //пропускаем пустые значение
            if ((is_array($fieldValue) && empty($fieldValue)) || (is_string($fieldValue) && $fieldValue == '')) {
                continue;
            }

            if ( (stripos($fieldId, '_numsel') != false) ||
                (stripos($fieldId, '_datesel') != false) ||
                (stripos($fieldId, '_year') != false) ||
                (stripos($fieldId, '_quarter') != false) ||
                (stripos($fieldId, '_days') != false) ||
                (stripos($fieldId, '_month') != false)
            ) {
                continue;
            }


            if (mb_substr($fieldId, - 5) == "_from") {
                $realFieldId = mb_substr($fieldId, 0, mb_strlen($fieldId) - 5);


                //тип номер
                if (!empty($filterData[$realFieldId."_numsel"]) && $filterData[$realFieldId."_numsel"] == "range")
                $filterPrefix = ">=";
                elseif (!empty($filterData[$realFieldId."_numsel"]) && $filterData[$realFieldId."_numsel"] == "more")
                $filterPrefix = ">";
                //тип дата
                elseif (!empty($filterData[$realFieldId."_datesel"]))
                $filterPrefix = ">=";
                else
                $filterPrefix = "";


                $arFilter[$filterPrefix.$realFieldId] = trim($fieldValue);
            }

            elseif (mb_substr($fieldId, - 3) == "_to") {
                $realFieldId = mb_substr($fieldId, 0, mb_strlen($fieldId) - 3);

                //тип номер
                if (!empty($filterData[$realFieldId."_numsel"]) && $filterData[$realFieldId."_numsel"] == "range" )
                $filterPrefix = "<=";
                elseif (!empty($filterData[$realFieldId."_numsel"]) && $filterData[$realFieldId."_numsel"] == "less")
                $filterPrefix = "<";
                elseif (!empty($filterData[$realFieldId."_datesel"]))
                $filterPrefix = "<=";
                else
                $filterPrefix = "";


                $arFilter[$filterPrefix.$realFieldId] = trim($fieldValue);
            }

            else {
                if ($fieldId == 'FIND') {
                    if (!empty($filterable)) {
                        $arFilter[] = array('LOGIC'=> 'OR');
                        foreach ($filterable as $code) {
                            $arFilter[0][] = array($code=> '%'.$fieldValue.'%');
                        }
                    }
                }
                else {
                    if ($fieldId == 'PVZ_ID') {
                        if ($fieldValue == 'P') {
                            $arFilter["!".$fieldId] = false;
                        }
                        if ($fieldValue == 'C') {
                            $arFilter[$fieldId] = false;
                        }
                    }
                    else {
                        $arFilter[$fieldId] = (is_array($fieldValue) || is_numeric($fieldValue)) ? $fieldValue : "%".trim($fieldValue)."%";
                    }
                }
            }

        }
//      \Bitrix\Main\Diag\Debug::dump($arFilter);

        return $arFilter;
    }
}
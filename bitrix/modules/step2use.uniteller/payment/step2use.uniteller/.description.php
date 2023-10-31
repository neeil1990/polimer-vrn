<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	exit();
}
include(GetLangFileName(dirname(__FILE__) . '/', '/uniteller.php'));

$psTitle = 'Uniteller';
$psDescription = GetMessage('SALE_UNITELLER_DESCRIPTION');

$arPSCorrespondence = array(
	'DESC' => array(
		'NAME' => GetMessage('SALE_UNITELLER_DESC'),
		'DESCR' => '',
		'VALUE' => '',
		'TYPE' => '',
	),
	'USE_FISKAL' => array(
		'NAME' => GetMessage('SALE_UNITELLER_USE_FISKAL'),
		'DESCR' => GetMessage('SALE_UNITELLER_USE_FISKAL_DESC'),
		'VALUE' => 'N',
		'TYPE' => 'VALUE',
	),
	//ILONA
	'AUTO_STATUS' => array(
		'NAME' => GetMessage('SALE_UNITELLER_AUTO_STATUS'),
		'DESCR' => GetMessage('SALE_UNITELLER_AUTO_STATUS_DESC'),
		'VALUE' => 'Y',
		'TYPE' => 'VALUE',
	),
	'INCLUDE_DELIVERY' => array(
		'NAME' => GetMessage('SALE_UNITELLER_INCLUDE_DELIVERY'),
		'DESCR' => GetMessage('Y/N'),
		'VALUE' => 'Y',
		'TYPE' => 'VALUE',
	),
    'DELIVERY_VAT' => array(
		'NAME' => GetMessage('SALE_UNITELLER_DELIVERY_VAT'),
		'DESCR' => GetMessage('SALE_UNITELLER_DELIVERY_VAT_DESC'),
		'VALUE' => '20',
		'TYPE' => 'VALUE',
	),
	'TAXMODE' => array(
		'NAME' => GetMessage('SALE_UNITELLER_TAXMODE'),
		'DESCR' => GetMessage('SALE_UNITELLER_TAXMODE_DESC'),
		'VALUE' => '0',
		'TYPE' => 'VALUE',
	),
    'PAYATTR' => array(
		'NAME' => GetMessage('SALE_UNITELLER_PAYATTR'),
		'DESCR' => GetMessage('SALE_UNITELLER_PAYATTR_DESC'),
		'VALUE' => '0',
		'TYPE' => 'VALUE',
	),
    'LINEATTR' => array(
		'NAME' => GetMessage('SALE_UNITELLER_LINEATTR'),
		'DESCR' => GetMessage('SALE_UNITELLER_LINEATTR_DESC'),
		'VALUE' => '1',
		'TYPE' => 'VALUE',
	),
	'SHOP_IDP' => array(
		'NAME' => GetMessage('SALE_UNITELLER_SHOP_IDP'),
		'DESCR' => GetMessage('SALE_UNITELLR_SHOP_IDP_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'SHOP_LOGIN' => array(
		'NAME' => GetMessage('SALE_UNITELLER_SHOP_LOGIN'),
		'DESCR' => GetMessage('SALE_UNITELLER_SHOP_LOGIN_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'SHOP_PASSWORD' => array(
		'NAME' => GetMessage('SALE_UNITELLER_SHOP_PASSWORD'),
		'DESCR' => GetMessage('SALE_UNITELLER_SHOP_PASSWORD_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'UNI_SITE_NAME_LAT' => array(
		'NAME' => GetMessage('SALE_UNITELLER_SITE_NAME_LAT'),
		'DESCR' => GetMessage('SALE_UNITELLER_SITE_NAME_LAT_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'LIFE_TIME' => array(
		'NAME' => GetMessage('SALE_UNITELLER_LIFE_TIME'),
		'DESCR' => '',
		'VALUE' => '',
		'TYPE' => '',
	),
	'UT_TIME_PAID_CHANGE' => array(
		'NAME' => GetMessage('SALE_UNITELLER_TIME_PAID_CHANGE'),
		'DESCR' => GetMessage('SALE_UNITELLER_TIME_PAID_CHANGE_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'UT_TIME_ORDER_SYNC' => array(
		'NAME' => GetMessage('SALE_UNITELLER_TIME_ORDER_SYNC'),
		'DESCR' => GetMessage('SALE_UNITELLER_TIME_ORDER_SYNC_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'SUCCESS_URL' => array(
		'NAME' => GetMessage('SALE_UNITELLER_SUCCESS_URL'),
		'DESCR' => GetMessage('SALE_UNITELLER_SUCCESS_URL_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'FAIL_URL' => array(
		'NAME' => GetMessage('SALE_UNITELLER_FAIL_URL'),
		'DESCR' => GetMessage('SALE_UNITELLER_FAIL_URL_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'ORDER_ID' => array(
		'NAME' => GetMessage('SALE_UNITELLER_ORDER_ID'),
		'DESCR' => GetMessage('SALE_UNITELLER_ORDER_ID_DESC'),
		'VALUE' => 'ID',
		'TYPE' => 'ORDER',
	),
	"DATE_INSERT" => array(
		"NAME" => GetMessage("DATE_INSERT"),
		"DESCR" => GetMessage("DATE_INSERT_DESCR"),
		"VALUE" => "",
		"TYPE" => ""
	),
	"SHOULD_PAY" => array(
				"NAME" => GetMessage("SHOULD_PAY"),
				"DESCR" => GetMessage("SHOULD_PAY_DESCR"),
				"VALUE" => "SHOULD_PAY",
				"TYPE" => "ORDER"
			),
	"CURRENCY" => array(
				"NAME" => GetMessage("CURRENCY"),
				"DESCR" => GetMessage("CURRENCY_DESCR"),
				"VALUE" => "",
				"TYPE" => ""
			),
	
	'EMAIL' => array(
		'NAME' => GetMessage('SALE_UNITELLER_EMAIL'),
		'DESCR' => GetMessage('SALE_UNITELLER_EMAIL_DESC'),
		'VALUE' => 'EMAIL',
		'TYPE' => 'PROPERTY',
	),
	'FIRST_NAME' => array(
		'NAME' => GetMessage('SALE_UNITELLER_FIRST_NAME'),
		'DESCR' => GetMessage('SALE_UNITELLER_FIRST_NAME_DESC'),
		'VALUE' => 'NAME',
		'TYPE' => 'USER',
	),
	'MIDDLE_NAME' => array(
		'NAME' => GetMessage('SALE_UNITELLER_MIDDLE_NAME'),
		'DESCR' => GetMessage('SALE_UNITELLER_MIDDLE_NAME_DESC'),
		'VALUE' => 'SECOND_NAME',
		'TYPE' => 'USER',
	),
	'LAST_NAME' => array(
		'NAME' => GetMessage('SALE_UNITELLER_LAST_NAME'),
		'DESCR' => GetMessage('SALE_UNITELLER_LAST_NAME_DESC'),
		'VALUE' => 'LAST_NAME',
		'TYPE' => 'USER',
	),
	'ADDRESS' => array(
		'NAME' => GetMessage('SALE_UNITELLER_ADDRESS'),
		'DESCR' => GetMessage('SALE_UNITELLER_ADDRESS_DESC'),
		'VALUE' => 'ADDRESS',
		'TYPE' => 'PROPERTY',
	),
	'PHONE' => array(
		'NAME' => GetMessage('SALE_UNITELLER_PHONE'),
		'DESCR' => GetMessage('SALE_UNITELLER_PHONE_DESC'),
		'VALUE' => 'PHONE',
		'TYPE' => 'PROPERTY',
	),
	'CITY' => array(
		'NAME' => GetMessage('SALE_UNITELLER_CITY'),
		'DESCR' => GetMessage('SALE_UNITELLER_CITY_DESC'),
		'VALUE' => 'CITY',
		'TYPE' => 'PROPERTY',
	),
	'ZIP' => array(
		'NAME' => GetMessage('SALE_UNITELLER_ZIP'),
		'DESCR' => GetMessage('SALE_UNITELLER_ZIP_DESC'),
		'VALUE' => 'ZIP',
		'TYPE' => 'PROPERTY',
	),
	'LANGUAGE' => array(
		'NAME' => GetMessage('SALE_UNITELLER_LANGUAGE'),
		'DESCR' => GetMessage('SALE_UNITELLER_LANGUAGE_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'COMMENT' => array(
		'NAME' => GetMessage('SALE_UNITELLER_COMMENT'),
		'DESCR' => GetMessage('SALE_UNITELLER_COMMENT_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'COUNTRY' => array(
		'NAME' => GetMessage('SALE_UNITELLER_COUNTRY'),
		'DESCR' => GetMessage('SALE_UNITELLER_COUNTRY_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	'STATE' => array(
		'NAME' => GetMessage('SALE_UNITELLER_STATE'),
		'DESCR' => GetMessage('SALE_UNITELLER_STATE_DESC'),
		'VALUE' => '',
		'TYPE' => '',
	),
	
);
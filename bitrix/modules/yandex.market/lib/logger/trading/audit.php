<?php

namespace Yandex\Market\Logger\Trading;

use Bitrix\Main;
use Yandex\Market;

class Audit
{
	use Market\Reference\Concerns\HasLang;

	const INCOMING_REQUEST = 'incoming_request';
	const INCOMING_RESPONSE = 'incoming_response';
	const OUTGOING_REQUEST = 'outgoing_request';
	const OUTGOING_RESPONSE = 'outgoing_response';
	const CART = 'cart';
	const ORDER_ACCEPT = 'order_accept';
	const ORDER_STATUS = 'order_status';
	const SEND_STATUS = 'send_status';
	const SEND_BOXES = 'send_boxes';
	const SEND_ITEMS = 'send_items';
	const SEND_CIS = 'send_cis';
	const SEND_TRACK = 'send_track';
	const SEND_CANCELLATION_ACCEPT = 'send_cancellation_accept';
	const SEND_DELIVERY_DATE = 'send_delivery_date';
	const SEND_DELIVERY_STORAGE_LIMIT = 'send_delivery_storage_limit';
	const SEND_SHIPMENT_CONFIRM = 'send_shipment_confirm';
	const SEND_SHIPMENT_EXCLUDE_ORDERS = 'send_shipment_exclude';
	const SALES_BOOST = 'boost';
	const PROCEDURE = 'procedure';
	const INTERNAL = 'internal';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getVariants()
	{
		return [
			static::CART,
			static::ORDER_ACCEPT,
			static::ORDER_STATUS,
			static::SEND_STATUS,
			static::SEND_BOXES,
			static::SEND_ITEMS,
			static::SEND_CIS,
			static::SEND_TRACK,
			static::SEND_CANCELLATION_ACCEPT,
			static::SEND_DELIVERY_DATE,
			static::SEND_DELIVERY_STORAGE_LIMIT,
			static::SEND_SHIPMENT_CONFIRM,
			static::SEND_SHIPMENT_EXCLUDE_ORDERS,
			static::INCOMING_REQUEST,
			static::INCOMING_RESPONSE,
			static::OUTGOING_REQUEST,
			static::OUTGOING_RESPONSE,
			static::SALES_BOOST,
			static::PROCEDURE,
		];
	}

	public static function getTitle($variant)
	{
		return static::getLang('LOGGER_TRADING_AUDIT_' . Market\Data\TextString::toUpper($variant), null, $variant);
	}
}
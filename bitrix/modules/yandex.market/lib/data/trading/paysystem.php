<?php

namespace Yandex\Market\Data\Trading;

use Bitrix\Main;
use Yandex\Market;

class PaySystem
{
	const TYPE_PREPAID = 'PREPAID';
	const TYPE_POSTPAID = 'POSTPAID';

	const METHOD_YANDEX = 'YANDEX';
	const METHOD_APPLE_PAY = 'APPLE_PAY';
	const METHOD_GOOGLE_PAY = 'GOOGLE_PAY';
	const METHOD_CREDIT = 'CREDIT';
	const METHOD_CERTIFICATE = 'CERTIFICATE';
	const METHOD_CARD_ON_DELIVERY = 'CARD_ON_DELIVERY';
	const METHOD_CASH_ON_DELIVERY = 'CASH_ON_DELIVERY';
}
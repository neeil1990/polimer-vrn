<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

/** @var \CBitrixComponentTemplate $this */

if ($this->__page !== 'print')
{
	switch ($arResult['ENTITY_TYPE'])
	{
		case Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER:
			include __DIR__ . '/modifier/group-additional-orders.php';
		break;

		case Market\Trading\Entity\Registry::ENTITY_TYPE_BOX:
			include __DIR__ . '/modifier/box-number.php';
		break;
	}
}

<?php

namespace Yandex\Market\Export\Run\Data;

use Yandex\Market;

/** @deprecated */
class PromoStatus
{
	const PROMO_INACTIVE = EntityStatus::STATE_INACTIVE;
	const PROMO_IN_PAST = EntityStatus::STATE_IN_PAST;
	const PROMO_IN_FUTURE = EntityStatus::STATE_IN_FUTURE;
	const PROMO_READY = EntityStatus::STATE_READY;

	const EXPORT_WAIT = EntityStatus::EXPORT_WAIT;
	const EXPORT_FAIL = EntityStatus::EXPORT_FAIL;
	const EXPORT_PARTIALLY = EntityStatus::EXPORT_PARTIALLY;
	const EXPORT_READY = EntityStatus::EXPORT_READY;

	public static function preload($promoIds)
	{
		EntityStatus::preload(Market\Export\Run\Manager::ENTITY_TYPE_PROMO, $promoIds);
	}

	public static function getPromoState(Market\Export\Promo\Model $promo)
	{
		return EntityStatus::modelState($promo);
	}

	public static function getExportState(Market\Export\Promo\Model $promo)
	{
		return EntityStatus::exportState(Market\Export\Run\Manager::ENTITY_TYPE_PROMO, $promo->getId());
	}
}
<?php
namespace Yandex\Market\Export\Run;

use Bitrix\Main;
use Yandex\Market\Export\Glossary;
use Yandex\Market\Reference\Concerns;

class Manager
{
	use Concerns\HasMessage;

	const STEP_ROOT = 'root';
	const STEP_OFFER = 'offer';
	const STEP_CURRENCY = 'currency';
	const STEP_CATEGORY = 'category';
	const STEP_COLLECTION = 'collection';
	const STEP_COLLECTION_OFFER_COLLECT = 'collection_offer_collect';
	const STEP_COLLECTION_OFFER_INSERT = 'collection_offer_insert';
	const STEP_PROMO_PRODUCT = 'promo_product';
	const STEP_PROMO_GIFT = 'promo_gift';
	const STEP_PROMO = 'promo';
	const STEP_GIFT = 'gift';

	/** @noinspection PhpUnused */
	const ENTITY_TYPE_ROOT = 'root';
	const ENTITY_TYPE_OFFER = Glossary::ENTITY_OFFER;
	const ENTITY_TYPE_CATEGORY = Glossary::ENTITY_CATEGORY;
	const ENTITY_TYPE_COLLECTION = Glossary::ENTITY_COLLECTION;
	const ENTITY_TYPE_CURRENCY = Glossary::ENTITY_CURRENCY;
	const ENTITY_TYPE_PROMO = Glossary::ENTITY_PROMO;
	const ENTITY_TYPE_GIFT = Glossary::ENTITY_GIFT;

	/**
	 * @return String[]
	 */
	public static function getSteps()
	{
		return [
			static::STEP_ROOT,
			static::STEP_OFFER,
			static::STEP_CATEGORY,
			static::STEP_COLLECTION_OFFER_COLLECT,
			static::STEP_COLLECTION,
			static::STEP_COLLECTION_OFFER_INSERT,
			static::STEP_CURRENCY,
			static::STEP_PROMO_PRODUCT,
			static::STEP_PROMO_GIFT,
            static::STEP_GIFT,
			static::STEP_PROMO,
		];
	}

	/**
	 * @param $stepName
	 * @param Processor $processor
	 *
	 * @return Steps\Base
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getStepProvider($stepName, Processor $processor)
	{
		switch ($stepName)
		{
			case static::STEP_ROOT:
				$result = new Steps\Root($processor);
			break;

			case static::STEP_OFFER:
				$result = new Steps\Offer($processor);
			break;

			case static::STEP_CATEGORY:
				$result = new Steps\Category($processor);
			break;

			case static::STEP_COLLECTION_OFFER_COLLECT:
				$result = new Steps\CollectionOfferCollect($processor);
			break;

			case static::STEP_COLLECTION:
				$result = new Steps\Collection($processor);
			break;

			case static::STEP_COLLECTION_OFFER_INSERT:
				$result = new Steps\CollectionOfferInsert($processor);
			break;

			case static::STEP_CURRENCY:
				$result = new Steps\Currencies($processor);
			break;

			case static::STEP_PROMO_PRODUCT:
				$result = new Steps\PromoProduct($processor);
			break;

			case static::STEP_PROMO_GIFT:
				$result = new Steps\PromoGift($processor);
			break;

			case static::STEP_PROMO:
				$result = new Steps\Promo($processor);
			break;

			case static::STEP_GIFT:
				$result = new Steps\Gift($processor);
			break;

			default:
				throw new Main\SystemException('not found export run step');
		}

		return $result;
	}

	public static function getStepTitle($stepName)
	{
		return self::getMessage('STEP_' . mb_strtoupper($stepName));
	}

	/**
	 * @deprecated
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function isChangeRegistered($setupId, $entityType, $entityId)
	{
		return false;
	}

	/**
	 * @deprecated
	 * @noinspection PhpUnused
	 * @noinspection PhpDeprecationInspection
	 */
	public static function registerChange($setupId, $entityType, $entityId)
	{
		Changes::register($setupId, $entityType, $entityId);
	}

	/**
	 * @deprecated
	 * @noinspection PhpDeprecationInspection
	 */
	public static function releaseChanges($setupId, Main\Type\DateTime $dateTime)
	{
		Changes::release($setupId, $dateTime);
	}
}
<?php

namespace Yandex\Market\Export\Xml\Format\Reference;

abstract class Base
{
	/**
	 * @return string|null
	 */
	abstract public function getDocumentationLink();

	/**
	 * @return bool
	 */
	abstract public function isSupportDeliveryOptions();

	/**
	 * @return string
	 */
	abstract public function getHeader();

	/**
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
	abstract public function getRoot();

	/**
	 * @return string
	 */
	abstract public function getCategoryParentName();

	/**
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
	abstract public function getCategory();

	/**
	 * @return string
	 */
	abstract public function getCurrencyParentName();

	/**
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
	abstract public function getCurrency();

	/**
	 * @return string
	 */
	abstract public function getPromoParentName();

	/**
     * @param $type string|null
     *
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
	abstract public function getPromo($type = null);

    /**
     * @return string
     */
    abstract public function getPromoProductParentName();

	/**
     * @param $type string|null
     *
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
	abstract public function getPromoProduct($type = null);

    /**
     * @return string
     */
    abstract public function getPromoGiftParentName();

	/**
     * @param $type string|null
     *
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
	abstract public function getPromoGift($type = null);

	/**
	 * @return string
	 */
	abstract public function getGiftParentName();

	/**
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
	abstract public function getGift();

	/**
	 * @return string
	 */
	abstract public function getOfferParentName();

	/**
	 * @return \Yandex\Market\Export\Xml\Tag\Base
	 * */
    abstract public function getOffer();

	/**
	 * @return string
	 */
	abstract public function getType();
}

<?php

namespace Yandex\Market\Api\Model;

use Bitrix\Main;
use Yandex\Market;

class Order extends Market\Api\Reference\Model
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getMeaningfulFields()
	{
		return [
			'EXTERNAL_ID',
		];
	}

	public static function getMeaningfulFieldTitle($fieldName)
	{
		return static::getLang('API_MODEL_ORDER_FIELD_' . $fieldName, null, $fieldName);
	}

	public static function getMeaningfulFieldHelp($fieldName)
	{
		return static::getLang('API_MODEL_ORDER_HELP_' . $fieldName, null, '');
	}

	public function getMeaningfulValues()
	{
		return array_filter([
			'EXTERNAL_ID' => $this->getId(),
		]);
	}

	public function getServiceUrl(Market\Api\Reference\HasOauthConfiguration $options)
	{
		return 'https://partner.market.yandex.ru/order-info?' . http_build_query([
			'id' => $options->getCampaignId(),
			'orderId' => $this->getId(),
		]);
	}

	public function getId()
	{
		return (int)$this->getRequiredField('id');
	}

	public function isFake()
	{
		return (bool)$this->getField('fake');
	}

	public function isCancelRequested()
	{
		return (bool)$this->getField('cancelRequested');
	}

	public function getCreationDate()
	{
		return Market\Data\DateTime::convertFromService($this->getField('creationDate'));
	}

	public function getStatus()
	{
		return (string)$this->getRequiredField('status');
	}

	public function getSubStatus()
	{
		return (string)$this->getField('substatus');
	}

	public function getPaymentType()
	{
		return (string)$this->getRequiredField('paymentType');
	}

	public function getPaymentMethod()
	{
		return $this->getField('paymentMethod');
	}

	public function getCurrency()
	{
		return (string)$this->getRequiredField('currency');
	}

	public function getItemsTotal()
	{
		return Market\Data\Number::normalize($this->getField('itemsTotal'));
	}

	public function getSubsidyTotal()
	{
		return Market\Data\Number::normalize($this->getField('subsidyTotal'));
	}

	public function getTotal()
	{
		return Market\Data\Number::normalize($this->getField('total'));
	}

	public function getNotes()
	{
		return (string)$this->getField('notes');
	}

	public function hasDelivery()
	{
		return $this->hasField('delivery');
	}

	/**
	 * @return Order\Delivery
	 */
	public function getDelivery()
	{
		return $this->getRequiredModel('delivery');
	}

	/**
	 * @return Order\ItemCollection
	 */
	public function getItems()
	{
		return $this->getRequiredCollection('items');
	}

	protected function getChildModelReference()
	{
		return [
			'delivery' => Order\Delivery::class
		];
	}

	protected function getChildCollectionReference()
	{
		return [
			'items' => Order\ItemCollection::class
		];
	}
}
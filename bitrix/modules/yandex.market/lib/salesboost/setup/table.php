<?php

namespace Yandex\Market\SalesBoost\Setup;

use Bitrix\Main;
use Yandex\Market\Reference;
use Yandex\Market\SalesBoost;
use Yandex\Market\Trading;
use Yandex\Market\Ui;

class Table extends Reference\Storage\Table
{
	use Reference\Concerns\HasMessage;

	const BID_FORMAT_PERCENT = 'percent';
	const BID_FORMAT_NUMBER = 'number';

	public static function getTableName()
	{
		return 'yamarket_sales_boost';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Main\Entity\BooleanField('ACTIVE', [
				'default_value' => static::BOOLEAN_Y,
				'values' => [
					static::BOOLEAN_N,
					static::BOOLEAN_Y,
				],
			]),
			new Reference\Storage\Field\CanonicalDateTime('START_DATE', array_merge(
				Reference\Storage\Field\CanonicalDateTimeStrict::getParameters(),
				[
					'validation' => function() {
						return [
							function ($value, $primary, $row) {
								$result = true;

								if (
									!empty($value) && $value instanceof Main\Type\Date
									&& !empty($row['FINISH_DATE']) && $row['FINISH_DATE'] instanceof Main\Type\Date
									&& $row['FINISH_DATE']->getTimestamp() <= $value->getTimestamp()
								)
								{
									$result = self::getMessage('VALIDATE_START_DATE_MUST_LESS_FINISH_DATE');
								}

								return $result;
							},
						];
					},
				]
			)),
			new Reference\Storage\Field\CanonicalDateTime(
				'FINISH_DATE',
				Reference\Storage\Field\CanonicalDateTimeStrict::getParameters()
			),
			new Main\Entity\StringField('NAME', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('SORT', [
				'required' => true,
				'default_value' => 500,
			]),
			new Main\Entity\IntegerField('BUSINESS_ID', [
				'required' => true,
			]),
			new Main\Entity\ReferenceField('BUSINESS', Trading\Business\Table::class, [
				'=this.BUSINESS_ID' => 'ref.ID',
			]),

			new Main\Entity\EnumField('BID_FORMAT', [
				'values' => [
					static::BID_FORMAT_PERCENT,
					static::BID_FORMAT_NUMBER,
				],
			]),

			new Main\Entity\IntegerField('BID_DEFAULT', [
				'validation' => function() {
					return [
						function ($value, $primary, $row, $field) {
							$format = isset($row['BID_FORMAT']) ? $row['BID_FORMAT'] : null;

							if ($format === static::BID_FORMAT_PERCENT)
							{
								$validator = new Main\Entity\Validator\Range(1, 99);
							}
							else if ($format === static::BID_FORMAT_NUMBER)
							{
								$validator = new Main\Entity\Validator\Range(50, 9999);
							}
							else
							{
								return true;
							}

							return $validator->validate($value, $primary, $row, $field);
						},
					];
				},
			]),
			new Main\Entity\StringField('BID_FIELD', [
				'serialized' => true,
			]),
			new Main\Entity\ReferenceField('SALES_BOOST_PRODUCT', SalesBoost\Product\Table::class, [
				'=this.ID' => 'ref.SETUP_ID',
			]),
		];
	}

	public static function getReference($primary = null)
	{
		return [
			'SALES_BOOST_PRODUCT' => [
				'TABLE' => SalesBoost\Product\Table::class,
				'LINK_FIELD' => 'SETUP_ID',
				'LINK' => [
					'SETUP_ID' => $primary,
				],
			],
		];
	}

	public static function getMapDescription()
	{
		self::includeSelfMessages();

		$result = parent::getMapDescription();
		$result['NAME']['USER_TYPE'] = Ui\UserField\Manager::getUserType('name');
		$result['NAME']['SETTINGS']['DEFAULT_VALUE'] = self::getMessage('NAME_DEFAULT');
		$result['BUSINESS']['USER_TYPE'] = Ui\UserField\Manager::getUserType('tradingBusiness');
		$result['BUSINESS']['MANDATORY'] = 'Y';
		$result['BUSINESS']['SETTINGS']['ALLOW_UNKNOWN'] = 'Y';
		$result['BID_FORMAT']['SETTINGS']['ALLOW_NO_VALUE'] = 'N';
		$result['BID_FIELD']['MULTIPLE'] = 'Y';
		$result['BID_FIELD']['USER_TYPE'] = Ui\UserField\Manager::getUserType('exportParam');
		$result['SORT']['USER_TYPE'] = Ui\UserField\Manager::getUserType('number');
		$result['SORT']['SETTINGS']['SIZE'] = 4;

		return $result;
	}

	public static function saveExtractReference(array &$data)
	{
		$result = parent::saveExtractReference($data);

		if (array_key_exists('BUSINESS', $data))
		{
			unset($data['BUSINESS']);
		}

		return $result;
	}

	protected static function onBeforeRemove($primary)
	{
		$model = Model::loadById($primary);

		$model->onBeforeRemove();
	}

	protected static function onAfterSave($primary)
	{
		$model = Model::loadById($primary);

		$model->onAfterSave();
	}
}
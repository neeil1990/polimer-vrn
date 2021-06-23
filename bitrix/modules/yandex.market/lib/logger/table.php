<?php

namespace Yandex\Market\Logger;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Table extends Market\Reference\Storage\Table
{
	const ENTITY_TYPE_EXPORT_RUN_ROOT = 'export_run_root';
	const ENTITY_TYPE_EXPORT_RUN_OFFER = 'export_run_offer';
	const ENTITY_TYPE_EXPORT_RUN_CATEGORY = 'export_run_category';
	const ENTITY_TYPE_EXPORT_RUN_CURRENCY = 'export_run_currency';
	const ENTITY_TYPE_EXPORT_RUN_PROMO_PRODUCT = 'export_promo_product';
	const ENTITY_TYPE_EXPORT_RUN_PROMO_GIFT = 'export_promo_gift';
	const ENTITY_TYPE_EXPORT_RUN_PROMO = 'export_promo';
	const ENTITY_TYPE_EXPORT_RUN_GIFT = 'export_gift';
	const ENTITY_TYPE_EXPORT_AGENT = 'export_agent';

	public static function getTableName()
	{
		return 'yamarket_log';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_0', [ 'ENTITY_PARENT' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'ENTITY_TYPE', 'ENTITY_ID' ]);
	}

	public static function getUfId()
	{
		return 'YAMARKET_LOG';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Main\Entity\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
			new Main\Entity\EnumField('LEVEL', [
				'values' => Level::getVariants(),
				'required' => true,
			]),
			new Main\Entity\StringField('MESSAGE', [
				'required' => true,
				'size' => 255
			]),
			new Main\Entity\IntegerField('ERROR_CODE'),
			new Main\Entity\StringField('ENTITY_TYPE', [
				'size' => 20,
				'validation' => [__CLASS__, 'validateEntityType'],
			]),
			new Main\Entity\IntegerField('ENTITY_PARENT'),
			new Main\Entity\StringField('ENTITY_ID', [
				'size' => 20,
				'validation' => [__CLASS__, 'validateEntityId'],
			]),

            new Main\Entity\ExpressionField('ENTITY_ID_PART1', 'SUBSTRING_INDEX(%s, ":", 1)', 'ENTITY_ID'),
            new Main\Entity\ExpressionField('ENTITY_ID_PART2', 'SUBSTRING_INDEX(%s, ":", -1)', 'ENTITY_ID'),

			new Main\Entity\TextField(
				'CONTEXT',
				Market\Reference\Storage\Field\Serializer::getParameters()
			),

			// OFFER_ID

			new Main\Entity\ReferenceField('RUN_OFFER', Market\Export\Run\Storage\OfferTable::getClassName(), [
				'=this.ENTITY_PARENT' => 'ref.SETUP_ID',
				[
				    'LOGIC' => 'OR',
                    [
                        '=this.ENTITY_TYPE' => [ '?', static::ENTITY_TYPE_EXPORT_RUN_OFFER ],
                        '=this.ENTITY_ID' => 'ref.ELEMENT_ID',
                    ],
                    [
                        '=this.ENTITY_TYPE' => [ '?', static::ENTITY_TYPE_EXPORT_RUN_PROMO_PRODUCT ],
                        '=this.ENTITY_ID_PART2' => 'ref.ELEMENT_ID',
                    ]
                ]
			]),

			new Main\Entity\ExpressionField('OFFER_ID', '%s', 'RUN_OFFER.ELEMENT_ID'),

            // GIFT_ID

            new Main\Entity\ReferenceField('RUN_GIFT', Market\Export\Run\Storage\PromoGiftTable::getClassName(), [
                '=this.ENTITY_PARENT' => 'ref.SETUP_ID',
                [
                    'LOGIC' => 'OR',
                    [
                        '=this.ENTITY_TYPE' => [ '?', static::ENTITY_TYPE_EXPORT_RUN_GIFT ],
                        '=this.ENTITY_ID' => 'ref.ELEMENT_ID',
                    ],
                    [
                        '=this.ENTITY_TYPE' => [ '?', static::ENTITY_TYPE_EXPORT_RUN_PROMO_GIFT ],
                        '=this.ENTITY_ID_PART2' => 'ref.ELEMENT_ID',
                    ]
                ]
            ]),

            new Main\Entity\ExpressionField('GIFT_ID', '%s', 'RUN_GIFT.ELEMENT_ID'),

            // PROMO_ID

            new Main\Entity\ReferenceField('RUN_PROMO', Market\Export\Run\Storage\PromoTable::getClassName(), [
                '=this.ENTITY_PARENT' => 'ref.SETUP_ID',
                [
                    'LOGIC' => 'OR',
                    [
                        '=this.ENTITY_TYPE' => [ '?', static::ENTITY_TYPE_EXPORT_RUN_PROMO ],
                        '=this.ENTITY_ID' => 'ref.ELEMENT_ID',
                    ],
                    [
                        '=this.ENTITY_TYPE' => [ '?', static::ENTITY_TYPE_EXPORT_RUN_PROMO_PRODUCT ],
                        '=this.ENTITY_ID_PART1' => 'ref.ELEMENT_ID',
                    ],
                    [
                        '=this.ENTITY_TYPE' => [ '?', static::ENTITY_TYPE_EXPORT_RUN_PROMO_GIFT ],
                        '=this.ENTITY_ID_PART1' => 'ref.ELEMENT_ID',
                    ],
                ]
            ]),

            new Main\Entity\ExpressionField('PROMO_ID', '%s', 'RUN_PROMO.ELEMENT_ID'),

            // SETUP

			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::getClassName(), [
				'=this.ENTITY_PARENT' => 'ref.ID'
			]),
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		static::migrateIndexes($connection);
		static::migrateContext($connection);
	}

	protected static function migrateIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		if (!$connection->isIndexExists($tableName, ['ENTITY_TYPE', 'ENTITY_ID']))
		{
			$entity = static::getEntity();

			Market\Migration\StorageFacade::dropIndexes($connection, $entity, [
				'IX_' . $tableName . '_0',
				'IX_' . $tableName . '_1',
			]);

			static::createIndexes($connection);
		}
	}

	protected static function migrateContext(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();

		$connection->queryExecute(sprintf(
			'ALTER TABLE %s MODIFY COLUMN %s text NOT NULL',
			$sqlHelper->quote($tableName),
			$sqlHelper->quote('CONTEXT')
		));
	}

	public static function validateEntityType()
	{
		return [
			new Main\Entity\Validator\Length(null, 20)
		];
	}

	public static function validateEntityId()
	{
		return [
			new Main\Entity\Validator\Length(null, 20)
		];
	}

	public static function getMapDescription()
	{
		$result = parent::getMapDescription();

		$result['LEVEL'] = static::extendLevelDescription($result['LEVEL']);
		$result['ENTITY_TYPE'] = static::extendEntityTypeDescription($result['ENTITY_TYPE']);
		$result['SETUP'] = static::extendSetupDescription($result['SETUP']);
		$result['PROMO_ID'] = static::extendPromoIdDescription($result['PROMO_ID']);
		$result['OFFER_ID'] = static::extendOfferIdDescription($result['OFFER_ID']);
		$result['GIFT_ID'] = static::extendGiftIdDescription($result['GIFT_ID']);

		return $result;
	}

	protected static function extendLevelDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('log');
		$allowedTypes = [
			Level::CRITICAL => true,
			Level::WARNING => true
		];

		foreach ($field['VALUES'] as $optionKey => &$option)
		{
			if (isset($allowedTypes[$option['ID']]))
			{
				$option['LOG_LEVEL'] = $option['ID'];
			}
			else
			{
				unset($field['VALUES'][$optionKey]);
			}
		}
		unset($option);

		return $field;
	}

	protected static function extendEntityTypeDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('enumeration');
		$field['VALUES'] = [];
		$entityTypeList = [
			static::ENTITY_TYPE_EXPORT_RUN_ROOT,
			static::ENTITY_TYPE_EXPORT_RUN_OFFER,
			static::ENTITY_TYPE_EXPORT_RUN_CATEGORY,
			static::ENTITY_TYPE_EXPORT_RUN_CURRENCY,
			static::ENTITY_TYPE_EXPORT_RUN_PROMO_PRODUCT,
			static::ENTITY_TYPE_EXPORT_RUN_PROMO_GIFT,
			static::ENTITY_TYPE_EXPORT_RUN_PROMO,
			static::ENTITY_TYPE_EXPORT_RUN_GIFT,
			static::ENTITY_TYPE_EXPORT_AGENT,
		];

		foreach ($entityTypeList as $entityType)
		{
			$field['VALUES'][] = [
				'ID' => $entityType,
				'VALUE' => static::getFieldEnumTitle('ENTITY_TYPE', $entityType)
			];
		}

		return $field;
	}

	protected static function extendSetupDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('setup');
		$field['SETTINGS']['EDIT_URL'] =
			Market\Ui\Admin\Path::getModuleUrl('setup_edit', [ 'lang' => LANGUAGE_ID ])
			. '&id=#ID#';

		return $field;
	}

	protected static function extendPromoIdDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('promo');
		$field['SETTINGS'] = [
			'DATA_CLASS' => Market\Export\Promo\Table::class,
		];

		return $field;
	}

	protected static function extendOfferIdDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('iblockElement');

		return $field;
	}

	protected static function extendGiftIdDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('iblockElement');

		return $field;
	}
}

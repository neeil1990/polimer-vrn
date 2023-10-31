<?php

namespace Yandex\Market\Export\Run\Storage;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class OfferTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_run_offer';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'STATUS', 'HASH' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_3', [ 'IBLOCK_LINK_ID' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PRIMARY' ]);
	}

	public static function getMap()
	{
		return [
			// base fields

			new Main\Entity\IntegerField('SETUP_ID', [
				'required' => true,
				'primary' => true
			]),
			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::getClassName(), [
				'=this.SETUP_ID' => 'ref.ID'
			]),
			new Main\Entity\IntegerField('ELEMENT_ID', [
				'required' => true,
				'primary' => true
			]),
			new Main\Entity\StringField('PRIMARY', [
				'size' => 80,
				'validation' => [__CLASS__, 'getValidationForPrimary'],
			]),
			new Main\Entity\StringField('HASH', [
				'size' => 33, // md5
				'validation' => [__CLASS__, 'getValidationForHash'],
			]),
			new Main\Entity\StringField('STATUS', [
				'size' => 1,
				'validation' => [__CLASS__, 'getValidationForStatus'],
			]),
			new Market\Reference\Storage\Field\CanonicalDateTime('TIMESTAMP_X', [
				'required' => true
			]),

			// additional fields

			new Main\Entity\IntegerField('IBLOCK_LINK_ID'),
			new Main\Entity\IntegerField('FILTER_ID'),
			new Main\Entity\IntegerField('PARENT_ID'),
			new Main\Entity\IntegerField('CATEGORY_ID'),
			new Main\Entity\StringField('CURRENCY_ID', [
				'size' => 15,
				'validation' => [__CLASS__, 'getValidationForCurrencyId']
			]),

			new Main\Entity\ReferenceField('LOG', Market\Logger\Table::getClassName(), [
				'=ref.OFFER_ID' => 'this.ID'
			])
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();
		$tableFields = $connection->getTableFields($tableName);

		Market\Migration\StorageFacade::addNewFields($connection, static::getEntity());
		Market\Migration\StorageFacade::updateFieldsLength($connection, static::getEntity(), [
			'PRIMARY',
		]);

		if (!isset($tableFields['PRIMARY']))
		{
			// fill primary for success exported elements

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s=%s WHERE %s=%s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('PRIMARY'),
				$sqlHelper->quote('ELEMENT_ID'),
				$sqlHelper->quote('STATUS'),
				$sqlHelper->forSql(Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS)
			));

			// primary index

			$connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PRIMARY' ]);
		}
	}

	public static function getValidationForPrimary()
	{
		return [
			new Main\Entity\Validator\Length(null, 80)
		];
	}

	public static function getValidationForHash()
	{
		return [
			new Main\Entity\Validator\Length(null, 33)
		];
	}

	public static function getValidationForStatus()
	{
		return [
			new Main\Entity\Validator\Length(null, 1)
		];
	}

	public static function getValidationForCurrencyId()
	{
		return [
			new Main\Entity\Validator\Length(null, 15)
		];
	}

	public static function getMapDescription()
	{
		global $USER_FIELD_MANAGER;

		$result = parent::getMapDescription();

		// element

		if (isset($result['ELEMENT_ID']))
		{
			$result['ELEMENT_ID']['USER_TYPE']['CLASS_NAME'] = 'Yandex\Market\Ui\UserField\IblockElementType';
		}

		if (isset($result['PARENT_ID']))
		{
			$result['ELEMENT_ID']['USER_TYPE']['CLASS_NAME'] = 'Yandex\Market\Ui\UserField\IblockElementType';
		}

		// status

		if (isset($result['STATUS']))
		{
			$result['STATUS']['USER_TYPE'] = $USER_FIELD_MANAGER->GetUserType('enumeration');
			$result['STATUS']['USER_TYPE']['CLASS_NAME'] = 'Yandex\Market\Ui\UserField\LogType';
			$result['STATUS']['VALUES'] = [];
			$statusList = [
				Market\Export\Run\Steps\Base::STORAGE_STATUS_FAIL => Market\Psr\Log\LogLevel::CRITICAL,
				Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS => null,
				Market\Export\Run\Steps\Base::STORAGE_STATUS_DUPLICATE => Market\Psr\Log\LogLevel::WARNING,
				Market\Export\Run\Steps\Base::STORAGE_STATUS_DELETE => Market\Psr\Log\LogLevel::WARNING,
			];

			foreach ($statusList as $status => $logLevel)
			{
				$result['STATUS']['VALUES'][] = [
					'ID' => $status,
					'VALUE' => Market\Export\Run\Steps\Base::getStorageStatusTitle($status),
					'LOG_LEVEL' => $logLevel
				];
			}
		}

		// log

		if (isset($result['LOG']))
		{
			$result['LOG']['USER_TYPE']['CLASS_NAME'] = 'Yandex\Market\Ui\UserField\LogRowType';
		}

		return $result;
	}

	protected static function saveApplyReference($primary, $fields)
	{
		// nothing (no support multiple primary)
	}

	public static function deleteReference($primary)
	{
		// nothing (controlled inside processor)
	}

	public static function getReference($primary = null)
	{
		$isMultipleLink = false;
		$linkFilter = null;
		$link = [];

		if (is_array($primary) && !isset($primary['SETUP_ID'])) // make filter
		{
			$linkFilter = [];

			foreach ($primary as $primaryItem)
			{
				if (isset($primaryItem['SETUP_ID']) && isset($primaryItem['ELEMENT_ID']))
				{
					$isMultipleLink = true;

					$linkFilter[] = [
						'ENTITY_PARENT' => $primaryItem['SETUP_ID'],
						'OFFER_ID' => $primaryItem['ELEMENT_ID']
					];
				}
			}

			if (count($linkFilter) > 1)
			{
				$linkFilter['LOGIC'] = 'OR';
			}
		}

		if ($isMultipleLink)
		{
			$link[] = $linkFilter;
		}
		else
		{
			$link['ENTITY_PARENT'] = isset($primary['SETUP_ID']) ? $primary['SETUP_ID'] : null;
			$link['OFFER_ID'] = isset($primary['ELEMENT_ID']) ? $primary['ELEMENT_ID'] : null;
		}

		return [
			'LOG' => [
				'TABLE' => Market\Logger\Table::getClassName(),
				'LINK_FIELD' => [ 'ENTITY_PARENT', 'OFFER_ID' ],
				'LINK' => $link
			]
		];
	}
}
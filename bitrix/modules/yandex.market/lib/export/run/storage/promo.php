<?php

namespace Yandex\Market\Export\Run\Storage;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class PromoTable extends Market\Reference\Storage\Table
{
    public static function getTableName()
    {
        return 'yamarket_export_run_promo';
    }

    public static function createIndexes(Main\DB\Connection $connection)
    {
        $tableName = static::getTableName();

        $connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'STATUS', 'HASH' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PRIMARY' ]);
    }

    public static function getMap()
    {
        return [
            new Main\Entity\IntegerField('SETUP_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\IntegerField('ELEMENT_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\StringField('HASH', [
                'size' => 33, // md5
                'validation' => [__CLASS__, 'getValidationForHash'],
            ]),
	        new Main\Entity\StringField('PRIMARY', [
		        'size' => 30,
		        'validation' => [__CLASS__, 'getValidationForPrimary'],
	        ]),
            new Main\Entity\StringField('STATUS', [
                'size' => 1,
                'validation' => [__CLASS__, 'getValidationForStatus'],
            ]),
            new Main\Entity\DatetimeField('TIMESTAMP_X', [
                'required' => true
            ]),

            new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::getClassName(), [
                '=this.SETUP_ID' => 'ref.ID'
            ]),

            new Main\Entity\ReferenceField('PROMO', Market\Export\Promo\Table::getClassName(), [
                '=this.ELEMENT_ID' => 'ref.ID'
            ]),

            new Main\Entity\ReferenceField('LOG', Market\Logger\Table::getClassName(), [
                '=ref.PROMO_ID' => 'this.ID'
            ]),

        ];
    }

	public static function migrate(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();
		$tableFields = $connection->getTableFields($tableName);

		if (!isset($tableFields['PRIMARY']))
		{
			// add column

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s varchar(30) NOT NULL',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('PRIMARY')
			));

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

		if (isset($tableFields['PROMO_ID']))
		{
			// restore element id from additional column

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s=%s WHERE %s > 0',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('ELEMENT_ID'),
				$sqlHelper->quote('PROMO_ID'),
				$sqlHelper->quote('PROMO_ID')
			));

			// drop column

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s DROP COLUMN %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('PROMO_ID')
			));
		}
	}

    public static function getValidationForHash()
    {
        return [
            new Main\Entity\Validator\Length(null, 33)
        ];
    }

	public static function getValidationForPrimary()
	{
		return [
			new Main\Entity\Validator\Length(null, 30)
		];
	}

    public static function getValidationForStatus()
    {
        return [
            new Main\Entity\Validator\Length(null, 1)
        ];
    }

    public static function getMapDescription()
    {
        global $USER_FIELD_MANAGER;

        $result = parent::getMapDescription();

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

        // element id

		if (isset($result['ELEMENT_ID']) && isset($result['PROMO']))
		{
			$result['ELEMENT_ID']['USER_TYPE'] = $result['PROMO']['USER_TYPE'];
			$result['ELEMENT_ID']['SETTINGS'] = $result['PROMO']['SETTINGS'];
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
                        'PROMO_ID' => $primaryItem['ELEMENT_ID']
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
            $link['PROMO_ID'] = isset($primary['ELEMENT_ID']) ? $primary['ELEMENT_ID'] : null;
        }

        return [
            'LOG' => [
                'TABLE' => Market\Logger\Table::getClassName(),
                'LINK_FIELD' => [ 'ENTITY_PARENT', 'PROMO_ID' ],
                'LINK' => $link
            ]
        ];
    }
}
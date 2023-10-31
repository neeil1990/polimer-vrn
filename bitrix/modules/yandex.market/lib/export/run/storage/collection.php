<?php

namespace Yandex\Market\Export\Run\Storage;

use Bitrix\Main;
use Yandex\Market;

class CollectionTable extends Market\Reference\Storage\Table
{
    public static function getTableName()
    {
        return 'yamarket_export_run_collection';
    }

    public static function createIndexes(Main\DB\Connection $connection)
    {
        $tableName = static::getTableName();

        $connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'STATUS', 'HASH' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_3', [ 'COLLECTION_ID' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PRIMARY' ]);
    }

    public static function getMap()
    {
        return [
            new Main\Entity\IntegerField('SETUP_ID', [
                'required' => true,
                'primary' => true,
            ]),
            new Main\Entity\StringField('ELEMENT_ID', [ // COLLECTION_SIGN
                'required' => true,
                'primary' => true,
	            'validation' => function() {
		            return [
			            new Main\Entity\Validator\Length(null, 20),
		            ];
	            },
            ]),
	        new Main\Entity\IntegerField('COLLECTION_ID', [
                'required' => true,
            ]),
	        new Main\Entity\StringField('PRIMARY', [
		        'validation' => function() {
			        return [
				        new Main\Entity\Validator\Length(null, 80),
			        ];
		        },
	        ]),
            new Main\Entity\StringField('HASH', [
                'size' => 33, // md5
	            'validation' => function() {
		            return [
			            new Main\Entity\Validator\Length(null, 33),
		            ];
	            },
            ]),
            new Main\Entity\StringField('STATUS', [
                'size' => 1,
                'validation' => function() {
	                return [
		                new Main\Entity\Validator\Length(null, 1),
	                ];
                },
            ]),
            new Market\Reference\Storage\Field\CanonicalDateTime('TIMESTAMP_X', [
                'required' => true
            ]),

            new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::class, [
                '=this.SETUP_ID' => 'ref.ID'
            ]),

            new Main\Entity\ReferenceField('COLLECTION_OFFER', CollectionOfferTable::class, [
                '=this.SETUP_ID' => 'ref.SETUP_ID',
                '=this.COLLECTION_ID' => 'ref.COLLECTION_ID',
                '=this.ELEMENT_ID' => 'ref.COLLECTION_SIGN',
            ]),

            new Main\Entity\ReferenceField('COLLECTION', Market\Export\Collection\Table::class, [
                '=this.COLLECTION_ID' => 'ref.ID'
            ]),

            new Main\Entity\ReferenceField('LOG', Market\Logger\Table::class, [
                '=ref.COLLECTION_ID' => 'this.COLLECTION_ID'
            ]),

        ];
    }

	public static function getMapDescription()
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		$result = parent::getMapDescription();

		// status

		if (isset($result['STATUS']))
		{
			$result['STATUS']['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('log');
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
					'LOG_LEVEL' => $logLevel,
				];
			}
		}

		// element id

		if (isset($result['COLLECTION_ID'], $result['COLLECTION']))
		{
			$result['COLLECTION_ID']['USER_TYPE'] = $result['COLLECTION']['USER_TYPE'];
			$result['COLLECTION_ID']['SETTINGS'] = $result['COLLECTION']['SETTINGS'];
		}

		// log

		if (isset($result['LOG']))
		{
			$result['LOG']['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('logRow');
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
				if (isset($primaryItem['SETUP_ID'], $primaryItem['ELEMENT_ID']))
				{
					$isMultipleLink = true;

					$linkFilter[] = [
						'ENTITY_PARENT' => $primaryItem['SETUP_ID'],
						'COLLECTION_SIGN' => $primaryItem['ELEMENT_ID']
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
			$link['COLLECTION_SIGN'] = isset($primary['ELEMENT_ID']) ? $primary['ELEMENT_ID'] : null;
		}

		return [
			'LOG' => [
				'TABLE' => Market\Logger\Table::getClassName(),
				'LINK_FIELD' => [ 'ENTITY_PARENT', 'COLLECTION_SIGN' ],
				'LINK' => $link,
			],
		];
	}
}
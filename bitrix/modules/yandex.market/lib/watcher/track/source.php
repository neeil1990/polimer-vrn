<?php
namespace Yandex\Market\Watcher\Track;

use Bitrix\Main;
use Yandex\Market;

class SourceTable extends Market\Reference\Storage\Table
{
    public static function getTableName()
    {
        return 'yamarket_export_track';
    }

    public static function createIndexes(Main\DB\Connection $connection)
    {
        $tableName = static::getTableName();

        $connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'SERVICE', 'ENTITY_TYPE', 'ENTITY_ID' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'SOURCE_TYPE' ]);
    }

    public static function getMap()
    {
        return [
            new Main\Entity\IntegerField('ID', [
                'autocomplete' => true,
                'primary' => true
            ]),
            new Main\Entity\EnumField('SERVICE', [
                'required' => true,
                'values' => [
                    Market\Glossary::SERVICE_EXPORT,
                    Market\Glossary::SERVICE_SALES_BOOST,
                ]
            ]),
            new Main\Entity\EnumField('ENTITY_TYPE', [
                'required' => true,
                'values' => [
	                Market\Glossary::ENTITY_SETUP,
                    Market\Export\Glossary::ENTITY_PROMO,
                    Market\Export\Glossary::ENTITY_COLLECTION,
                ]
            ]),
            new Main\Entity\IntegerField('ENTITY_ID', [
                'required' => true
            ]),
            new Main\Entity\StringField('SOURCE_TYPE', [
                'required' => true,
                'validation' => function() {
	                return [
		                new Main\Entity\Validator\Length(null, 40)
	                ];
                },
            ]),
            new Main\Entity\StringField(
            	'SOURCE_PARAMS',
	            Market\Reference\Storage\Field\Serializer::getParameters()
            ),
        ];
    }

	public static function migrate(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();
		$known = $connection->getTableFields($tableName);

		parent::migrate($connection);
		Market\Migration\StorageFacade::updateFieldsLength($connection, static::getEntity(), [
			'ENTITY_TYPE'
		]);

		if (!isset($known['SERVICE']))
		{
			$indexName = 'IX_' . $tableName . '_1';

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s="%s"',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SERVICE'),
				$sqlHelper->forSql(Market\Glossary::SERVICE_EXPORT)
			));

			$connection->query(sprintf('DROP INDEX %s ON %s', $sqlHelper->quote($indexName),  $sqlHelper->quote($tableName)));
			$connection->createIndex($tableName, $indexName, [ 'SERVICE', 'ENTITY_TYPE', 'ENTITY_ID' ]);
		}
	}
}
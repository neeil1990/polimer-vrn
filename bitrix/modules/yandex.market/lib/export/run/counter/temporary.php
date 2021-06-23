<?php

namespace Yandex\Market\Export\Run\Counter;

use Bitrix\Main;
use Yandex\Market;

class Temporary extends Base
{
	protected $previousCount = 0;
	protected $iteration = 0;
	protected $countOfferParentsExists = false;

	public function start()
	{
		$this->previousCount = 0;
		$this->iteration = 0;
		$this->countOfferParentsExists = false;

		$this->createElementTable();
		$this->createOfferTable();
	}

	public function count($filter, $context)
	{
		++$this->iteration;

		if ($filter['DIRECTION'] === 'OFFER')
		{
			$this->fillByOffers($filter, $context);
			$this->countOfferParentsExists = true;
		}
		else
		{
			$this->fillByElements($filter, $context);
		}

		return $this->updateCount($context);
	}

	public function finish()
	{
		$this->dropElementTable();
		$this->dropOfferTable();
	}

	protected function getElementTableName()
	{
		return 'yamarket_export_counter_element';
	}

	protected function getOfferTableName()
	{
		return 'yamarket_export_counter_offer';
	}

	protected function createElementTable()
	{
		$connection = $this->getConnection();
		$tableName = $this->getElementTableName();

		if ($connection->isTableExists($tableName))
		{
			$connection->truncateTable($tableName);
		}
		else
		{
			$sqlHelper = $connection->getSqlHelper();

			$connection->query(
				'CREATE TEMPORARY TABLE ' . $sqlHelper->quote($tableName) . '('
					. $sqlHelper->quote('ID') . ' int NOT NULL'
					. ', ' . $sqlHelper->quote('CATALOG_TYPE') . ' int(1) NOT NULL DEFAULT 0'
					. ', ' . $sqlHelper->quote('ITERATION') . ' int(2) NOT NULL DEFAULT 0'
					. ', PRIMARY KEY(' . $sqlHelper->quote('ID') . ')'
				. ') ENGINE=MEMORY;'
			);
		}
	}

	protected function createOfferTable()
	{
		$connection = $this->getConnection();
		$tableName = $this->getOfferTableName();

		if ($connection->isTableExists($tableName))
		{
			$connection->truncateTable($tableName);
		}
		else
		{
			$sqlHelper = $connection->getSqlHelper();

			$connection->query(
				'CREATE TEMPORARY TABLE ' . $sqlHelper->quote($tableName) . '('
					. $sqlHelper->quote('ID') . ' int NOT NULL'
					. ', ' . $sqlHelper->quote('PARENT_ID') . ' int NOT NULL DEFAULT 0'
					. ', ' . $sqlHelper->quote('ITERATION') . ' int(2) NOT NULL DEFAULT 0'
					. ', PRIMARY KEY(' . $sqlHelper->quote('ID') . ')'
				. ') ENGINE=MEMORY;'
			);
		}
	}

	protected function dropElementTable()
	{
		$connection = $this->getConnection();
		$tableName = $this->getElementTableName();
		$sqlHelper = $connection->getSqlHelper();

		$connection->query('DROP TEMPORARY TABLE IF EXISTS ' . $sqlHelper->quote($tableName));
	}

	protected function dropOfferTable()
	{
		$connection = $this->getConnection();
		$tableName = $this->getOfferTableName();
		$sqlHelper = $connection->getSqlHelper();

		$connection->query('DROP TEMPORARY TABLE IF EXISTS ' . $sqlHelper->quote($tableName));
	}

	protected function getConnection()
	{
		return Main\Application::getConnection();
	}

	protected function fillByElements($filter, $context)
	{
		$this->insertElements($filter['ELEMENT'], $context);

		if ($context['HAS_OFFER'])
		{
			$this->insertOffers($filter['OFFERS'], $context, true);
		}
	}

	protected function fillByOffers($filter, $context)
	{
		if ($context['HAS_OFFER'])
		{
			$this->insertOffers($filter['OFFERS'], $context);
			$this->insertElements($filter['ELEMENT'], $context, true);
		}
	}

	protected function insertElements($filter, $context, $checkExistsOffers = false)
	{
		$connection = $this->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$tableName = $this->getElementTableName();
		$isNeedCatalogType = false;
		$select = [ 'ID' ];
		$elementSqlExtension = [ 'select' => $this->iteration ];

		if (!$context['HAS_OFFER'])
		{
			// nothing
		}
		else if ($context['OFFER_ONLY'])
		{
			$isNeedCatalogType = true;
			$elementSqlExtension['select'] = Market\Export\Run\Steps\Offer::ELEMENT_TYPE_SKU . ', ' . $elementSqlExtension['select'];
		}
		else
		{
			$isNeedCatalogType = true;
			$select[] = Market\Export\Entity\Catalog\Provider::useCatalogShortFields()
				? 'TYPE'
				: 'CATALOG_TYPE';
		}

		$elementSql = $this->getElementSql($filter, $select, $elementSqlExtension);

		if ($checkExistsOffers)
		{
			$offerTableName = $this->getOfferTableName();

			$elementSql = $this->appendWhereInQuery($elementSql, 'ID', sprintf(
				'SELECT %s FROM %s WHERE %s=%s',
				$sqlHelper->quote('PARENT_ID'),
				$sqlHelper->quote($offerTableName),
				$sqlHelper->quote('ITERATION'),
				$this->iteration
			));
		}

		$insertSql =
			'INSERT INTO ' . $sqlHelper->quote($tableName) . ' ('
				. $sqlHelper->quote('ID')
				. ($isNeedCatalogType ? ', ' . $sqlHelper->quote('CATALOG_TYPE') : '')
				. ', ' . $sqlHelper->quote('ITERATION')
			. ') '
			. PHP_EOL . $elementSql
			. PHP_EOL . 'ON DUPLICATE KEY UPDATE '
			. PHP_EOL . $sqlHelper->quote('ITERATION') . ' = ' . $this->iteration;

		$connection->queryExecute($insertSql);
	}

	protected function insertOffers($filter, $context, $checkExistsParents = false)
	{
		$connection = $this->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$tableName = $this->getOfferTableName();
		$select = [ 'ID', 'PROPERTY_' . $context['OFFER_PROPERTY_ID'] ];
		$elementSqlExtension = [ 'select' => $this->iteration ];

		$elementSql = $this->getElementSql($filter, $select, $elementSqlExtension);

		if ($checkExistsParents)
		{
			$skuPropertyName = 'PROPERTY_' . $context['OFFER_PROPERTY_ID'] . '_VALUE';
			$elementTableName = $this->getElementTableName();

			$elementSql = $this->appendWhereInQuery($elementSql, $skuPropertyName, sprintf(
				'SELECT %s FROM %s WHERE %s=%s',
				$sqlHelper->quote('ID'),
				$sqlHelper->quote($elementTableName),
				$sqlHelper->quote('ITERATION'),
				$this->iteration
			));
		}

		$insertSql =
			'INSERT IGNORE INTO ' . $sqlHelper->quote($tableName) . ' ('
				. $sqlHelper->quote('ID')
				. ', ' . $sqlHelper->quote('PARENT_ID')
				. ', ' . $sqlHelper->quote('ITERATION')
			. ') '
			. $elementSql;

		$connection->queryExecute($insertSql);
	}

	protected function updateCount($context)
	{
		$currentCount = $this->countElements($context) + $this->countOffers($context);
		$result = $currentCount - $this->previousCount;

		$this->previousCount = $currentCount;

		return $result;
	}

	protected function countElements($context)
	{
		$result = null;
		$connection = $this->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$tableName = $this->getElementTableName();
		$sql =
			'SELECT COUNT(' . $sqlHelper->quote('ID') . ') as CNT '
			. 'FROM ' . $sqlHelper->quote($tableName);

		if (!$context['HAS_OFFER'])
		{
			// nothing
		}
		else if ($context['OFFER_ONLY'])
		{
			$result = 0;
		}
		else
		{
			$sql .= 'WHERE '
				. $sqlHelper->quote('CATALOG_TYPE') . ' NOT IN (' . implode(',', [
					Market\Export\Run\Steps\Offer::ELEMENT_TYPE_SKU,
					Market\Export\Run\Steps\Offer::ELEMENT_TYPE_EMPTY_SKU,
				]) . ')';

			if ($context['CATALOG_TYPE_COMPATIBILITY'])
			{
				$offerTableName = $this->getOfferTableName();

				$sql .=
					' AND '
					. $sqlHelper->quote('ID') .' NOT IN ('
						. 'SELECT ' . $sqlHelper->quote('PARENT_ID') .' FROM ' . $sqlHelper->quote($offerTableName)
					. ')';
			}
		}

		if ($result === null)
		{
			$queryResult = $connection->query($sql);
			$row = $queryResult->fetch();

			if ($row === false) { throw new Main\ObjectNotFoundException(); }

			$result = (int)$row['CNT'];
		}

		return $result;
	}

	protected function countOffers($context)
	{
		if ($context['HAS_OFFER'])
		{
			$connection = $this->getConnection();
			$sqlHelper = $connection->getSqlHelper();
			$tableName = $this->getOfferTableName();
			$tableNameQuoted = $sqlHelper->quote($tableName);
			$elementTableName = $this->getElementTableName();

			if ($context['USE_DISTINCT'])
			{
				$countField = sprintf('DISTINCT %s.%s', $tableNameQuoted, $sqlHelper->quote('PARENT_ID'));
			}
			else
			{
				$countField = sprintf('%s.%s', $tableNameQuoted, $sqlHelper->quote('ID'));
			}

			$sql =
				'SELECT COUNT(' . $countField . ') as CNT'
				. ' FROM ' . $sqlHelper->quote($tableName);

			if ($this->countOfferParentsExists)
			{
				$sql .= ' ' . sprintf(
					'INNER JOIN %1$s ON %1$s.%2$s=%3$s.%4$s',
					$sqlHelper->quote($elementTableName),
					$sqlHelper->quote('ID'),
					$tableNameQuoted,
					$sqlHelper->quote('PARENT_ID')
				);
			}

			$queryResult = $connection->query($sql);
			$row = $queryResult->fetch();

			if ($row === false) { throw new Main\ObjectNotFoundException(); }

			$result = (int)$row['CNT'];
		}
		else
		{
			$result = 0;
		}

		return $result;
	}

	protected function getElementSql($filter, $select, $extension = [])
	{
		$catalogSelectSql = $this->getCatalogSelect($select);

		if (method_exists('\CIBlockElement', 'prepareSql'))
		{
			$queryProvider = new \CIBlockElement();
			$queryProvider->prepareSql($select, $filter, false, false);

			$sql =
				'SELECT '
				. $this->filterSelect($queryProvider->sSelect . $catalogSelectSql, $select)
				. (isset($extension['select']) ? ', ' . $extension['select'] : '')
				. ' FROM ' . $queryProvider->sFrom
				. ' WHERE 1=1 '. $queryProvider->sWhere
				. $queryProvider->sGroupBy;
		}
		else
		{
			$queryProvider = new \CIBlockElement();
			$queryProvider->strField = 'ID';

			$sql = $queryProvider->GetList([], $filter, false, false, $select);

			if (!preg_match('/SELECT\s(.*?)\sFROM/si', $sql, $matches))
			{
				throw new Main\SystemException('can\'t parse CIBlockElement::GetList sql');
			}

			$sqlSelect =
				$this->filterSelect($matches[1] . $catalogSelectSql, $select)
				. (isset($extension['select']) ? ', ' . $extension['select'] : '');

			$sql =
				'SELECT ' . $sqlSelect
				. ' FROM ' . $queryProvider->sFrom
				. ' WHERE 1=1 ' . $queryProvider->sWhere;
		}

		return $sql;
	}

	protected function getCatalogSelect($select)
	{
		$catalogSelect = [];
		$result = '';

		foreach ($select as $field)
		{
			if (Market\Data\TextString::getPosition($field, 'CATALOG_') === 0)
			{
				$catalogSelect[] = $field;
			}
		}

		if (!empty($catalogSelect) && Main\Loader::includeModule('catalog'))
		{
			$catalogSelectResult = \CCatalogProduct::GetQueryBuildArrays([], [], $catalogSelect);

			if (isset($catalogSelectResult['SELECT']))
			{
				$result = $catalogSelectResult['SELECT'];
			}
		}

		return $result;
	}

	protected function filterSelect($sqlSelect, $fields)
	{
		$fieldsMap = array_flip($fields);
		$sqlSelectParts = explode(',', $sqlSelect);
		$alreadyAdded = [];
		$result = '';

		foreach ($sqlSelectParts as $sqlSelectPart)
		{
			if (preg_match('/as\s+([A-Z0-9_]+)/i', $sqlSelectPart, $matches))
			{
				$fieldName = trim($matches[1]);
				$fieldName = $this->removeQuotes($fieldName);

				if (preg_match('/^(.*)_VALUE$/', $fieldName, $matches))
				{
					$fieldName = $matches[1];
				}

				if (isset($fieldsMap[$fieldName]) && !isset($alreadyAdded[$fieldName]))
				{
					$alreadyAdded[$fieldName] = true;
					$result .= ($result === '' ? '' : ', ') . trim($sqlSelectPart);
				}
			}
		}

		return $result;
	}

	protected function appendWhereInQuery($sql, $fieldName, $whereQuery)
	{
		$propertyOriginalField = $this->getSqlOriginalField($sql, $fieldName);
		$propertyFilter = $propertyOriginalField . ' IN (' . $whereQuery . ')';

		return preg_replace('/(\sWHERE\s.*)(\sGROUP BY\s.*)?$/si', '$1 AND ' . $propertyFilter . ' $2', $sql);
	}

	protected function getSqlOriginalField($sql, $fieldName)
	{
		if (!preg_match('/([A-Za-z0-9._]+) as .?' . $fieldName . '.?/', $sql, $matches))
		{
			throw new Main\ObjectNotFoundException();
		}

		$originalName = trim($matches[1]);
		$originalName = $this->removeQuotes($originalName);

		return $originalName;
	}

	protected function removeQuotes($fieldName, Main\DB\Connection $connection = null)
	{
		if ($connection === null) { $connection = $this->getConnection(); }

		$sqlHelper = $connection->getSqlHelper();
		$sqlLeftQuote = (string)$sqlHelper->getLeftQuote();
		$sqlLeftQuoteLength = null;
		$sqlRightQuoteLength = null;

		if ($sqlLeftQuote !== '' && Market\Data\TextString::getPosition($fieldName, $sqlLeftQuote) === 0)
		{
			$sqlLeftQuoteLength = Market\Data\TextString::getLength($sqlLeftQuote);
			$sqlRightQuoteLength = Market\Data\TextString::getLength($sqlHelper->getRightQuote());

			$result = Market\Data\TextString::getSubstring($fieldName, $sqlLeftQuoteLength, -1 * $sqlRightQuoteLength);
		}
		else
		{
			$result = $fieldName;
		}

		return $result;
	}
}
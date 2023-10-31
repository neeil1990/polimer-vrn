<?php
namespace Yandex\Market\Export\Collection;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Reference;
use Yandex\Market\Export;
use Yandex\Market\Ui;

class Table extends Reference\Storage\Table
{
	use Reference\Concerns\HasMessage;

	public static function getTableName()
	{
		return 'yamarket_export_collection';
	}

	/** @noinspection DuplicatedCode */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
			]),
			new Main\Entity\StringField('STRATEGY', [
				'required' => true,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 20),
					];
				},
			]),
			new Main\Entity\TextField('STRATEGY_SETTINGS', [
				'serialized' => true,
			]),
			new Main\Entity\TextField('LIMIT_SETTINGS', [
				'serialized' => true,
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
			new Main\Entity\BooleanField('SETUP_EXPORT_ALL', [
				'default_value' => static::BOOLEAN_N,
				'values' => [
					static::BOOLEAN_N,
					static::BOOLEAN_Y,
				],
			]),
			new Main\Entity\ReferenceField('SETUP_LINK', Internals\SetupLinkTable::class, [
				'=this.ID' => 'ref.COLLECTION_ID'
			]),
			new Main\Entity\ReferenceField('SETUP', Market\Export\Setup\Table::class, [
				'=this.SETUP_LINK.SETUP_ID' => 'ref.ID',
			]),
			new Main\Entity\ReferenceField('COLLECTION_PRODUCT', Export\CollectionProduct\Table::class, [
				'=this.ID' => 'ref.COLLECTION_ID'
			]),
		];
	}

	public static function getReference($primary = null)
	{
		return [
			'SETUP_LINK' => [
				'TABLE' => Internals\SetupLinkTable::class,
				'LINK_FIELD' => 'COLLECTION_ID',
				'LINK' => [
					'COLLECTION_ID' => $primary,
				],
			],
			'COLLECTION_PRODUCT' => [
				'TABLE' => Export\CollectionProduct\Table::class,
				'LINK_FIELD' => 'COLLECTION_ID',
				'LINK' => [
					'COLLECTION_ID' => $primary,
				],
			],
		];
	}

	public static function getMapDescription()
	{
		self::includeSelfMessages();

		$strategies = Strategy\Registry::getStrategies();

		$result = parent::getMapDescription();
		$result['NAME']['USER_TYPE'] = Ui\UserField\Manager::getUserType('name');
		$result['NAME']['SETTINGS']['DEFAULT_VALUE'] = self::getMessage('NAME_DEFAULT');
		$result['SETUP']['MULTIPLE'] = 'Y';
		$result['SETUP']['USER_TYPE'] = Ui\UserField\Manager::getUserType('setupLink');
		$result['SETUP']['SETTINGS'] = [
			'ENTITY_TYPE' => Export\Run\Manager::ENTITY_TYPE_COLLECTION,
		];
		$result['STRATEGY']['USER_TYPE'] = Ui\UserField\Manager::getUserType('enumeration');
		$result['STRATEGY']['VALUES'] = array_map(static function($type, Strategy\Strategy $strategy) {
			return [
				'ID' => $type,
				'VALUE' => $strategy->getTitle(),
			];
		}, array_keys($strategies), array_values($strategies));

		return $result;
	}

	public static function loadExternalReference($primaryList, $select = null, $isCopy = false)
	{
		$result = parent::loadExternalReference($primaryList, $select, $isCopy);

		if (!empty($primaryList))
		{
			$referenceMap = [
				'SETUP' => 'loadExternalReferenceSetup',
			];

			foreach ($referenceMap as $field => $method)
			{
				if (empty($select) || in_array($field, $select, true))
				{
					$referenceDataList = static::$method($primaryList);

					foreach ($referenceDataList as $primary => $referenceValue)
					{
						if (!isset($result[$primary]))
						{
							$result[$primary] = [];
						}

						$result[$primary][$field] = $referenceValue;
					}
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	protected static function loadExternalReferenceSetup($primaryList)
	{
		$result = [];

		// load row data

		$query = Internals\SetupLinkTable::getList([
			'filter' => [
				'=COLLECTION_ID' => $primaryList,
			],
			'select' => [
				'ID',
				'COLLECTION_ID',
				'SETUP_ID',
			],
		]);

		while ($row = $query->fetch())
		{
			if (!isset($result[$row['COLLECTION_ID']]))
			{
				$result[$row['COLLECTION_ID']] = [];
			}

			$result[$row['COLLECTION_ID']][$row['ID']] = $row['SETUP_ID'];
		}

		return $result;
	}

	public static function saveExtractReference(array &$data)
	{
		$result = parent::saveExtractReference($data);

		if (array_key_exists('SETUP', $data))
		{
			unset($data['SETUP']);
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
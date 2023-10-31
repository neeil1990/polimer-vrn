<?php

namespace Yandex\Market\Component\ExportLog;

use Bitrix\Main;
use Yandex\Market;

class GridList extends Market\Component\Log\GridList
{
	use Market\Reference\Concerns\HasLang;
	use Market\Component\Concerns\HasCalculatedFields;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getFields(array $select = [])
	{
		return
			parent::getFields($select)
			+ $this->getCalculatedFields();
	}

	protected function getCalculatedFields()
	{
		return [
			'TRACE' => [
				'USER_TYPE' => Market\Ui\UserField\Manager::getUserType('trace'),
				'FIELD_NAME' => 'TRACE',
				'LIST_COLUMN_LABEL' => static::getLang('COMPONENT_EXPORT_LOG_GRID_LIST_FIELD_TRACE', null, 'TRACE'),
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'USES' => [
					'CONTEXT',
				]
			],
		];
	}

	public function load(array $queryParameters = [])
	{
		list($commonParameters, $calculatedParameters) = $this->extractLoadCalculatedParameters($queryParameters);

		$result = parent::load($commonParameters);
		$result = $this->loadCalculated($result, $calculatedParameters);

		return $result;
	}

	protected function loadCalculatedValue($item, $fieldName)
	{
		switch ($fieldName)
		{
			case 'TRACE':
				$result = isset($item['CONTEXT']['TRACE']) ? $item['CONTEXT']['TRACE'] : null;
			break;

			default:
				$result = null;
			break;
		}

		return $result;
	}

}
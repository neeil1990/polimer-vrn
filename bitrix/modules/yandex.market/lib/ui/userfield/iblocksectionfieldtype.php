<?php
namespace Yandex\Market\Ui\UserField;

use Yandex\Market\Reference\Concerns;
use Bitrix\Main;

/** @noinspection PhpUnused */
class IblockSectionFieldType extends EnumerationType
{
	use Concerns\HasMessage;
	use Concerns\HasOnceStatic;

	protected static $variants = [];

	public static function GetList($arUserField)
	{
		$variants = static::getVariants($arUserField);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	protected static function getVariants($userField)
	{
		return array_merge(
			static::getCommonVariants(),
			static::getUserVariants($userField)
		);
	}

	protected static function getCommonVariants()
	{
		$whitelist = [
			'NAME',
			'PICTURE',
			'DESCRIPTION',
			'SECTION_PAGE_URL',
		];

		return array_map(static function($name) {
			return [
				'ID' => $name,
				'VALUE' => self::getMessage('FIELD_' . $name),
			];
		}, $whitelist);
	}

	protected static function getUserVariants($userField)
	{
		if (empty($userField['SETTINGS']['IBLOCK_ID'])) { return []; }

		$iblockIds = (array)$userField['SETTINGS']['IBLOCK_ID'];

		Main\Type\Collection::normalizeArrayValuesByInt($iblockIds);

		return static::onceStatic('getUserVariants', [ $iblockIds ], static function(array $iblockIds) {
			global $USER_FIELD_MANAGER;

			$result = [];

			foreach ($iblockIds as $iblockId)
			{
				foreach ($USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $iblockId . '_SECTION', 0, LANGUAGE_ID) as $name => $field)
				{
					$title = Main\Type\Collection::firstNotEmpty($field, 'EDIT_FORM_LABEL', 'LIST_COLUMN_LABEL', 'LIST_FILTER_LABEL');

					$result[$name] = [
						'ID' => $name,
						'VALUE' => sprintf('[%s] %s', $name, $title),
					];
				}
			}

			return array_values($result);
		});
	}
}
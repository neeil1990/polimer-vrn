<?php

namespace Yandex\Market\Ui\UserField\Listing;

use Bitrix\Main;
use Yandex\Market\Export\Xml;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui\UserField\Helper\Attributes;
use Yandex\Market\Ui\UserField\View;

abstract class Property
{
	use Concerns\HasMessage;

	public static function GetUserTypeDescription()
	{
		return [
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => static::userType(),
			'DESCRIPTION' => static::getMessage('DESCRIPTION'),
			'GetPropertyFieldHtml' => [static::class, 'getPropertyFieldHtml'],
			'GetAdminListViewHTML' => [static::class, 'getAdminListViewHTML'],
			'GetAdminFilterHTML' => [static::class, 'getAdminFilterHTML'],
			'GetUIFilterProperty' => [static::class, 'getUIFilterProperty'],
			'AddFilterFields' => [static::class, 'addFilterFields'],
		];
	}

	public static function getPropertyFieldHtml($property, $value, $controlName)
	{
		// select

		$result = View\Select::getControl(static::options(), static::sanitize($value['VALUE']), [
			'name' => $controlName['VALUE'],
			'style' => 'max-width: 300px',
		], [
			'ALLOW_NO_VALUE' => $property['IS_REQUIRED'] === 'Y' ? 'N' : 'Y',
		]);

		// description

		if ($property['WITH_DESCRIPTION'] === 'Y')
		{
			$descriptionLabel = static::getMessage('VALUE_DESCRIPTION');

			$result .= "&nbsp;<span>{$descriptionLabel}:&nbsp;";
			/** @noinspection HtmlUnknownAttribute */
			$result .= sprintf('<input %s />', Attributes::stringify([
				'type' => 'text',
				'name' => $controlName['DESCRIPTION'],
				'value' => $value['DESCRIPTION'],
				'size' => 30,
			]));
			$result .= '</span>';
		}

		return  $result;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function getAdminListViewHTML($property, $value, $controlName)
	{
		$options = static::options();
		$optionsMap = array_column($options, 'VALUE', 'ID');
		$values = is_array($value['VALUE']) ? $value['VALUE'] : [ $value['VALUE'] ];
		$parts = [];

		foreach ($values as $one)
		{
			if (empty($one)) { continue; }

			$one = static::sanitize($one);
			$display = isset($optionsMap[$one]) ? $optionsMap[$one] : $one;

			$parts[] = htmlspecialcharsbx($display);
		}

		return implode(' / ', $parts);
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function getUIFilterProperty($property, $controlName, &$fields)
	{
		$fields['type'] = 'list';
		$fields['items'] = [];

		foreach (static::options() as $option)
		{
			$fields['items'][$option['ID']] = $option['VALUE'];
		}
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function getAdminFilterHTML($property, $controlName)
	{
		$filterRequest = static::filterRequestValue($controlName);

		return View\Select::getControl(static::options(), $filterRequest, [
			'name' => $controlName['VALUE'] . '[]',
		], [
			'ALLOW_NO_VALUE' => 'Y',
			'CAPTION_NO_VALUE' => self::getMessage('FILTER_ANY'),
		]);
	}

	public static function addFilterFields($property, $controlName, &$listFilter, &$hasFilter)
	{
		$filterRequest = static::filterRequestValue($controlName);

		if (!empty($filterRequest))
		{
			$hasFilter = true;
			$listFilter['=PROPERTY_' . $property['ID']] = $filterRequest;
		}
	}

	/** @noinspection DuplicatedCode */
	protected static function filterRequestValue($controlName)
	{
		$result = [];

		if (isset($_REQUEST[$controlName['VALUE']]) && (is_array($_REQUEST[$controlName['VALUE']]) || (int)$_REQUEST[$controlName['VALUE']] > 0))
		{
			$result = (array)$_REQUEST[$controlName['VALUE']];
		}
		else if (isset($GLOBALS[$controlName['VALUE']]) && (is_array($GLOBALS[$controlName['VALUE']]) || (int)$GLOBALS[$controlName['VALUE']] > 0))
		{
			$result = (array)$GLOBALS[$controlName['VALUE']];
		}

		return $result;
	}

	protected static function sanitize($value)
	{
		$listing = static::listing();

		if ($listing instanceof Xml\Listing\ListingWithMigration)
		{
			$migrated = $listing->migrate($value);

			if ($migrated !== null)
			{
				$value = $migrated;
			}
		}

		return $value;
	}

	protected static function options()
	{
		$listing = static::listing();
		$result = [];

		foreach ($listing->values() as $value)
		{
			$result[] = [
				'ID' => $value,
				'VALUE' => $listing->display($value),
			];
		}

		return $result;
	}

	/** @return Xml\Listing\Listing */
	protected static function listing()
	{
		throw new Main\NotImplementedException();
	}

	/** @return string */
	protected static function userType()
	{
		throw new Main\NotImplementedException();		
	}
}
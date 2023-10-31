<?php
/** @noinspection PhpUnused */
namespace Yandex\Market\Ui\UserField;

class CompoundType
{
	public static function GetFilterHTML($userField, $htmlControl)
	{
		return static::callChild('GetFilterHTML', [$userField, $htmlControl]);
	}

	public static function GetFilterData($userField, $htmlControl)
	{
		return static::callChild('GetFilterData', [$userField, $htmlControl]);
	}

	public static function GetAdminListViewHtml($userField, $htmlControl)
	{
		$result = '';
		$hasFirst = false;
		$hasAdditional = false;

		foreach ($userField['FIELDS'] as $siblingName => $siblingField)
		{
			if (empty($userField['ROW'][$siblingName])) { continue; }

			$siblingValue = $userField['ROW'][$siblingName];

			if (static::needSkip($siblingField, $siblingValue)) { continue; }
			if (!static::matchFilter($userField, $siblingName, $hasAdditional)) { continue; }

			$siblingHtml = Helper\Renderer::getViewHtml($siblingField, $siblingValue, $userField['ROW']);

			if (empty($siblingHtml)) { continue; }

			if (!$hasFirst)
			{
				$hasFirst = true;
				$result = $siblingHtml;
			}
			else
			{
				$hasAdditional = true;
				$fieldTitle = $siblingField['LIST_COLUMN_LABEL'] ?: $siblingField['EDIT_FORM_LABEL'] ?: $siblingName;

				if ($siblingField['TYPE'] === 'boolean')
				{
					$siblingHtml = (string)$siblingValue === BooleanType::VALUE_Y
						? $fieldTitle
						: sprintf('%s: %s', $fieldTitle, $siblingHtml);
				}
				else if (in_array($siblingField['TYPE'], [
					'string',
					'date',
					'datetime',
					'dateTimePeriod',
				], true))
				{
					$siblingHtml = sprintf('%s: %s', $fieldTitle, $siblingHtml);
				}

				$result .= sprintf('<br /><small>%s</small>', $siblingHtml);
			}
		}

		return $result;
	}

	protected static function needSkip(array $field, $value)
	{
		return (isset($field['SKIP']) && in_array($value, $field['SKIP'], true));
	}

	protected static function matchFilter(array $userField, $siblingName, $hasAdditional)
	{
		if (!isset($userField['FILTER'][$siblingName])) { return true; }

		$filter = $userField['FILTER'][$siblingName];

		if (is_array($filter))
		{
			$result = true;

			foreach ($filter as $name => $constraints)
			{
				$value = isset($userField['ROW'][$name]) ? $userField['ROW'][$name] : null;
				$matched = is_array($constraints) ? in_array($value, $constraints, true) : ($value === $constraints);

				if (!$matched)
				{
					$result = false;
					break;
				}
			}
		}
		else if ($filter === false)
		{
			$result = !$hasAdditional;
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected static function callChild($method, $userField, ...$arguments)
	{
		$firstField = reset($userField['FIELDS']);
		$className = $firstField['USER_TYPE']['CLASS_NAME'];

		return call_user_func([$className, $method], $firstField, ...$arguments);
	}
}
<?php

namespace Yandex\Market\Confirmation\Setup;

use Yandex\Market;
use Bitrix\Main;

class Table extends Market\Reference\Storage\Table
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getTableName()
	{
		return 'yamarket_confirmation_setup';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Main\Entity\StringField('DOMAIN', [
				'required' => true,
				'validation' => [__CLASS__, 'getValidationForDomain'],
			]),
			new Main\Entity\EnumField('BEHAVIOR', [
				'required' => true,
				'values' => Market\Confirmation\Behavior\Manager::getTypes(),
			]),
			new Main\Entity\TextField('CONTENTS', [
				'required' => true,
				'validation' => [__CLASS__, 'getValidationForContents'],
			]),
		];
	}

	public static function getValidationForDomain()
	{
		return [
			[ static::class, 'validateDomain' ],
		];
	}

	public static function validateDomain($value, $primary, $row, $field)
	{
		$result = true;
		$value = trim($value);

		if (preg_match('#^(https?:)?//#i', $value))
		{
			$result = static::getLang('CONFIRMATION_SETUP_VALIDATE_DOMAIN_WITHOUT_PROTOCOL');
		}

		return $result;
	}

	public static function getValidationForContents()
	{
		return [
			[ static::class, 'validateContents' ],
		];
	}

	public static function validateContents($value, $primary, $row, $field)
	{
		try
		{
			if ((string)$value !== '')
			{
				$behavior = Market\Confirmation\Behavior\Manager::getBehavior($row['BEHAVIOR']);
				$behavior->validate($value);
			}

			return true;
		}
		catch (Main\ArgumentException $exception)
		{
			$parameter = $exception->getParameter();
			$exceptionMessage = $exception->getMessage();
			$langCode =
				'CONFIRMATION_SETUP_VALIDATE_'
				. Market\Data\TextString::toUpper($row['BEHAVIOR'])
				. '_'
				. Market\Data\TextString::toUpper($parameter)
				. '_ERROR';

			return static::getLang($langCode, [ '#EXCEPTION#' => $exceptionMessage ], $exceptionMessage);
		}
		catch (Main\SystemException $exception)
		{
			return $exception->getMessage();
		}
	}

	public static function getMapDescription()
	{
		static::loadMessages();

		$result = parent::getMapDescription();
		$result['SITE_ID'] = static::extendSiteDescription($result['SITE_ID']);
		$result['BEHAVIOR'] = static::extendBehaviorDescription($result['BEHAVIOR']);
		$result['CONTENTS'] = static::extendContentsDescription($result['CONTENTS']);

		return $result;
	}

	protected static function extendSiteDescription($field)
	{
		if (isset($field['VALUES']))
		{
			foreach ($field['VALUES'] as &$option)
			{
				$option['VALUE'] = '[' . $option['ID'] . '] ' . Market\Data\Site::getTitle($option['ID']);
			}
			unset($option);
		}

		return $field;
	}

	protected static function extendBehaviorDescription($field)
	{
		if (isset($field['VALUES']))
		{
			foreach ($field['VALUES'] as &$option)
			{
				$option['VALUE'] = Market\Confirmation\Behavior\Manager::getTitle($option['ID']);
			}
			unset($option);
		}

		return $field;
	}

	protected static function extendContentsDescription($field)
	{
		$field['USER_TYPE']['CLASS_NAME'] = Market\Ui\UserField\ConfirmationContentsType::class;
		$field['SETTINGS']['SIZE'] = 60;
		$field['SETTINGS']['ROWS'] = 8;
		$field['NOTE'] = static::getLang('CONFIRMATION_SETUP_CONTENTS_FIELD_NOTE');

		return $field;
	}

	protected static function onBeforeRemove($primary)
	{
		static::installModel($primary, false);
	}

	protected static function onBeforeSave($primary)
	{
		static::installModel($primary, false);
	}

	protected static function onAfterSave($primary)
	{
		static::installModel($primary, true);
	}

	protected static function installModel($primary, $direction)
	{
		/** @var Model $model */
		$model = Model::loadById($primary);

		if ($direction)
		{
			$model->install();
		}
		else
		{
			$model->uninstall();
		}
	}
}
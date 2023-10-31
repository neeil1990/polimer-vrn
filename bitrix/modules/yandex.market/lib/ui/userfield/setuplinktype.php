<?php
namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;
use Yandex\Market\Data\Type;
use Yandex\Market\Export;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Ui;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Reference\Storage;
use Yandex\Market\Export\Run as ExportRun;

class SetupLinkType extends EnumerationType
{
	use Concerns\HasMessage;
	use Concerns\HasOnceStatic;

	public static function GetList($arUserField)
	{
		$values = static::variants($arUserField);

		$queryResult = new \CDBResult();
		$queryResult->InitFromArray($values);

		return $queryResult;
	}

	protected static function variants($arUserField)
	{
		$entityType = static::entityType($arUserField);

		return static::onceStatic('variants', $entityType, static function($entityType) {
			$result = [];

			foreach (Export\Setup\Model::loadList() as $setup)
			{
				if (!static::isSupported($entityType, $setup)) { continue; }

				$result[] = [
					'ID' => $setup->getId(),
					'VALUE' => sprintf('[%s] %s', $setup->getId(), $setup->getField('NAME')),
				];
			}

			return $result;
		});
	}

	protected static function isSupported($entityType, Export\Setup\Model $setup)
	{
		try
		{
			$format = $setup->getFormat();

			if ($entityType === ExportRun\Manager::ENTITY_TYPE_PROMO)
			{
				$result = ($format->getPromo() !== null);
			}
			else if ($entityType === ExportRun\Manager::ENTITY_TYPE_COLLECTION)
			{
				$result = ($format->getCollection() !== null);
			}
			else
			{
				throw new Main\ArgumentException(sprintf('unknown entity type %s', $entityType));
			}
		}
		catch (Main\SystemException $exception)
		{
			$result = false;
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}

		return $result;
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if (static::isExportAllRow($arUserField))
		{
			$result = self::getMessage('EXPORT_ALL');
		}
		else
		{
			$result = parent::GetAdminListViewHTML($arUserField, $arHtmlControl);
		}

		$result = static::exportStatus(
			static::entityType($arUserField),
			$arUserField['ROW'],
			$result
		);

		return $result;
	}

	public static function GetAdminListViewHTMLMulty($arUserField, $arHtmlControl)
	{
		if (static::isExportAllRow($arUserField))
		{
			$result = self::getMessage('EXPORT_ALL');
		}
		else
		{
			$result = parent::GetAdminListViewHTMLMulty($arUserField, $arHtmlControl);
		}

		$result = static::exportStatus(
			static::entityType($arUserField),
			$arUserField['ROW'],
			$result
		);

		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		if (!isset($arUserField['SETTINGS'])) { $arUserField['SETTINGS'] = []; }

		$arUserField['MANDATORY'] = 'Y';
		$arUserField['SETTINGS']['DISPLAY'] = 'CHECKBOX';

		return parent::GetEditFormHTML($arUserField, $arHtmlControl);
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		if (!isset($userField['SETTINGS'])) { $userField['SETTINGS'] = []; }

		$userField['MANDATORY'] = 'Y';
		$userField['SETTINGS']['DISPLAY'] = 'CHECKBOX';

		return parent::GetEditFormHTMLMulty($userField, $htmlControl);
	}

	protected static function isExportAllRow($arUserField)
	{
		$exportAllFieldName = $arUserField['FIELD_NAME'] . '_EXPORT_ALL';

		return (
			isset($arUserField['ROW'][$exportAllFieldName])
			&& $arUserField['ROW'][$exportAllFieldName] === Storage\Table::BOOLEAN_Y
		);
	}

	protected static function exportStatus($type, array $row, $displayValue)
	{
		$model = static::entityModel($type, $row);
		$modelState = ExportRun\Data\EntityStatus::modelState($model);
		$exportState = ExportRun\Data\EntityStatus::exportState($type, $model->getId());
		$messageData = [
			'#EXPORT_URL#' => Ui\Admin\Path::getModuleUrl($type . '_run', [
				'lang' => LANGUAGE_ID,
				'id' => $model->getId(),
			]),
			'#LOG_URL#' => Ui\Admin\Path::getModuleUrl('log', [
				'lang' => LANGUAGE_ID,
				'find_' . $type . '_id' => $model->getId(),
				'set_filter' => 'Y',
				'apply_filter' => 'Y',
			]),
		];

		if ($modelState === ExportRun\Data\EntityStatus::STATE_READY)
		{
			if ($exportState === ExportRun\Data\EntityStatus::EXPORT_PARTIALLY)
			{
				$status = 'yellow';
				$reason = self::getMessage('EXPORT_REASON_FAIL_PART', $messageData);
			}
			else if ($exportState === ExportRun\Data\EntityStatus::EXPORT_FAIL)
			{
				$status = 'red';
				$reason = self::getMessage('EXPORT_REASON_FAIL_ALL', $messageData);
			}
			else if ($exportState === ExportRun\Data\EntityStatus::EXPORT_READY)
			{
				$status = 'green';
				$reason = self::getMessage('EXPORT_REASON_SUCCESS', $messageData);
			}
			else
			{
				$status = 'red';
				$reason = self::getMessage('EXPORT_REASON_NO_RESULT', $messageData);
			}
		}
		else if (
			$exportState === ExportRun\Data\EntityStatus::EXPORT_READY
			|| $exportState === ExportRun\Data\EntityStatus::EXPORT_PARTIALLY
		)
		{
			$status = 'red';
			$message = static::entityStatusTitle($model, $modelState);
			$reason =
				($message ? $message . ', ' : '')
				. self::getMessage('EXPORT_REASON_NEED_DELETE', $messageData);
		}
		else
		{
			$status = 'grey';
			$reason = static::entityStatusTitle($model, $modelState);
		}

		$result = '<img class="b-log-icon" src="/bitrix/images/yandex.market/' . $status . '.gif" width="14" height="14" alt="" />';
		$result .= $displayValue;

		if ($reason !== null)
		{
			$result .= ', ' . $reason;
		}

		return $result;
	}

	protected static function entityStatusTitle(ExportRun\Data\EntityExportable $model, $state)
	{
		$nextDate = $model->getNextActiveDate();

		if ($nextDate instanceof Type\CanonicalDateTime)
		{
			$nextDate = Main\Type\DateTime::createFromTimestamp($nextDate->getTimestamp());
		}

		return self::getMessage('MODEL_STATE_' . $state, [
			'#NEXT_DATE#' => $nextDate,
		]);
	}

	protected static function entityType(array $userField)
	{
		Assert::notNull($userField['SETTINGS']['ENTITY_TYPE'], 'userField[SETTINGS][ENTITY_TYPE]');

		return $userField['SETTINGS']['ENTITY_TYPE'];
	}

	protected static function entityModel($entityType, array $row)
	{
		if ($entityType === ExportRun\Manager::ENTITY_TYPE_PROMO)
		{
			$result = new Export\Promo\Model($row);
		}
		else if ($entityType === ExportRun\Manager::ENTITY_TYPE_COLLECTION)
		{
			$result = new Export\Collection\Model($row);
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown entity type %s', $entityType));
		}

		return $result;
	}
}
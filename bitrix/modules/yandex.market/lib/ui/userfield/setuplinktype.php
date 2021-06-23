<?php

namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Export\Run as ExportRun;

class SetupLinkType extends EnumerationType
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function GetList($arUserField)
	{
		static $result = null;

		if ($result === null)
		{
			$result = [];

			if (Main\Loader::includeModule('yandex.market'))
			{
				$querySetupList = Market\Export\Setup\Table::getList([
					'select' => [ 'ID', 'NAME' ]
				]);

				while ($setup = $querySetupList->fetch())
				{
					$result[] = [
						'ID' => $setup['ID'],
						'VALUE' => '[' . $setup['ID'] . '] ' . $setup['NAME']
					];
				}
			}
		}

		$queryResult = new \CDBResult();
		$queryResult->InitFromArray($result);

		return $queryResult;
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if (static::isExportAllRow($arUserField))
		{
			$result = static::getLang('USER_FIELD_SETUP_LINK_TYPE_EXPORT_ALL');
		}
		else
		{
			$result = parent::GetAdminListViewHTML($arUserField, $arHtmlControl);
		}

		$result = static::getExportStatusInfo($arUserField['ROW'], $result);

		return $result;
	}

	public static function GetAdminListViewHTMLMulty($arUserField, $arHtmlControl)
	{
		if (static::isExportAllRow($arUserField))
		{
			$result = static::getLang('USER_FIELD_SETUP_LINK_TYPE_EXPORT_ALL');
		}
		else
		{
			$result = parent::GetAdminListViewHTMLMulty($arUserField, $arHtmlControl);
		}

		$result = static::getExportStatusInfo($arUserField['ROW'], $result);

		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		if (isset($arUserField['SETTINGS'])) { $arUserField['SETTINGS'] = []; }

		$arUserField['MANDATORY'] = 'Y';
		$arUserField['SETTINGS']['DISPLAY'] = 'CHECKBOX';

		return parent::GetEditFormHTML($arUserField, $arHtmlControl);
	}

	public static function GetEditFormHTMLMulty($arUserField, $arHtmlControl)
	{
		if (isset($arUserField['SETTINGS'])) { $arUserField['SETTINGS'] = []; }

		$arUserField['MANDATORY'] = 'Y';
		$arUserField['SETTINGS']['DISPLAY'] = 'CHECKBOX';

		return parent::GetEditFormHTMLMulty($arUserField, $arHtmlControl);
	}

	protected static function isExportAllRow($arUserField)
	{
		$exportAllFieldName = $arUserField['FIELD_NAME'] . '_EXPORT_ALL';

		return (
			isset($arUserField['ROW'][$exportAllFieldName])
			&& $arUserField['ROW'][$exportAllFieldName] === Market\Reference\Storage\Table::BOOLEAN_Y
		);
	}

	protected static function getExportStatusInfo($row, $displayValue)
	{
		$promo = new Market\Export\Promo\Model($row);
		$promoState = ExportRun\Data\PromoStatus::getPromoState($promo);
		$exportState = ExportRun\Data\PromoStatus::getExportState($promo);
		$messageData = [
			'#EXPORT_URL#' => Market\Ui\Admin\Path::getModuleUrl('promo_run', [
				'lang' => LANGUAGE_ID,
				'id' => $promo->getId(),
			]),
			'#LOG_URL#' => Market\Ui\Admin\Path::getModuleUrl('log', [
				'lang' => LANGUAGE_ID,
				'find_promo_id' => $promo->getId(),
				'set_filter' => 'Y',
				'apply_filter' => 'Y',
			]),
		];

		if ($promoState === ExportRun\Data\PromoStatus::PROMO_READY)
		{
			if ($exportState === ExportRun\Data\PromoStatus::EXPORT_PARTIALLY)
			{
				$status = 'yellow';
				$reason = static::getLang('USER_FIELD_SETUP_LINK_TYPE_EXPORT_REASON_FAIL_PART', $messageData);
			}
			else if ($exportState === ExportRun\Data\PromoStatus::EXPORT_FAIL)
			{
				$status = 'red';
				$reason = static::getLang('USER_FIELD_SETUP_LINK_TYPE_EXPORT_REASON_FAIL_ALL', $messageData);
			}
			else if ($exportState === ExportRun\Data\PromoStatus::EXPORT_READY)
			{
				$status = 'green';
				$reason = static::getLang('USER_FIELD_SETUP_LINK_TYPE_EXPORT_REASON_SUCCESS', $messageData);
			}
			else
			{
				$status = 'red';
				$reason = static::getLang('USER_FIELD_SETUP_LINK_TYPE_EXPORT_REASON_NO_RESULT', $messageData);
			}
		}
		else if (
			$exportState === ExportRun\Data\PromoStatus::EXPORT_READY
			|| $exportState === ExportRun\Data\PromoStatus::EXPORT_PARTIALLY
		)
		{
			$status = 'red';
			$message = static::getPromoStatusTitle($promo, $promoState);
			$reason =
				($message ? $message . ', ' : '')
				. static::getLang('USER_FIELD_SETUP_LINK_TYPE_EXPORT_REASON_NEED_DELETE', $messageData);
		}
		else
		{
			$status = 'grey';
			$reason = static::getPromoStatusTitle($promo, $promoState);
		}

		$result = '<img class="b-log-icon" src="/bitrix/images/yandex.market/' . $status . '.gif" width="14" height="14" alt="" />';
		$result .= $displayValue;

		if ($reason !== null)
		{
			$result .= ', ' . $reason;
		}

		return $result;
	}

	protected static function getPromoStatusTitle(Market\Export\Promo\Model $promo, $state)
	{
		return static::getLang('USER_FIELD_SETUP_LINK_TYPE_PROMO_STATE_' . $state, [
			'#NEXT_DATE#' => $promo->getNextActiveDate(),
		]);
	}
}
<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class ExportFileType extends StringType
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public static function GetAdminListViewHTML($userField, $htmlControl)
	{
		$result = '';
		$fileName = trim($htmlControl['VALUE']);

		if ($fileName !== '')
		{
			$setup = static::makeSetup($userField, $htmlControl);
			$status = Market\Export\Run\Data\SetupStatus::getExportState($setup);

			switch ($status)
			{
				case Market\Export\Run\Data\SetupStatus::EXPORT_READY:
					$filePath = $setup->getFileRelativePath();

					$result = '<img class="b-log-icon" src="/bitrix/images/yandex.market/green.gif" width="14" height="14" alt="" />';
					$result .= sprintf(
						'<a href="%s" target="_blank">%s</a>',
						htmlspecialcharsbx($filePath),
						htmlspecialcharsbx($fileName)
					);
				break;

				case  Market\Export\Run\Data\SetupStatus::EXPORT_PROGRESS:
					$result = '<img class="b-log-icon" src="/bitrix/images/yandex.market/yellow.gif" width="14" height="14" alt="" />';
					$result .= static::getLang('UI_USER_FIELD_EXPORT_FILE_TYPE_FILE_PROGRESS');
				break;

				default:
					$setupId = $setup->getId();

					$result = '<img class="b-log-icon" src="/bitrix/images/yandex.market/red.gif" width="14" height="14" alt="" />';
					$result .= static::getLang('UI_USER_FIELD_EXPORT_FILE_TYPE_FILE_' . $status);

					if ($setupId > 0)
					{
						$result .=
							'<a href="yamarket_setup_run.php?lang=' . LANGUAGE_ID . '&id=' . $setupId . '">'
							. static::getLang('UI_USER_FIELD_EXPORT_FILE_TYPE_RUN_EXPORT')
							. '</a>';
					}
					else
					{
						$result = $fileName;
					}
				break;
			}
		}

		return $result;
	}

	protected static function makeSetup($userField, $htmlControl)
	{
		$fields = [
			'FILE_NAME' => $htmlControl['VALUE'],
		];

		if (isset($userField['ROW']))
		{
			$fields += $userField['ROW'];
		}

		return new Market\Export\Setup\Model($fields);
	}
}
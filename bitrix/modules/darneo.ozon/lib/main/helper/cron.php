<?php

namespace Darneo\Ozon\Main\Helper;

use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;

class Cron
{
    public static function getLang(string $code): array
    {
        $keyId = HelperSettings::getKeyIdCurrent();

        $data = [
            'IMPORT_ANALYTIC' => [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_ANALYTIC_TITLE'),
                'DESCRIPTION' => Loc::getMessage(
                    'DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_ANALYTIC_DESCRIPTION',
                    [
                        '#PATH#' => $_SERVER['DOCUMENT_ROOT'],
                        '#CLIENT_KEY_ID#' => $keyId,
                    ]
                ),
                'HELPER' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_ANALYTIC_HELPER'),
            ],
            'IMPORT_CATALOG' => [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CATALOG_TITLE'),
                'DESCRIPTION' => Loc::getMessage(
                    'DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CATALOG_DESCRIPTION',
                    [
                        '#PATH#' => $_SERVER['DOCUMENT_ROOT'],
                        '#CLIENT_KEY_ID#' => $keyId,
                    ]
                ),
                'HELPER' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CATALOG_HELPER'),
            ],
            'IMPORT_CORE' => [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CORE_TITLE'),
                'DESCRIPTION' => Loc::getMessage(
                    'DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CORE_DESCRIPTION',
                    [
                        '#PATH#' => $_SERVER['DOCUMENT_ROOT'],
                        '#CLIENT_KEY_ID#' => $keyId,
                    ]
                ),
                'HELPER' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_IMPORT_CORE_HELPER'),
            ],
            'EXPORT_CATALOG' => [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_CATALOG_TITLE'),
                'DESCRIPTION' => Loc::getMessage(
                    'DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_CATALOG_DESCRIPTION',
                    [
                        '#PATH#' => $_SERVER['DOCUMENT_ROOT'],
                        '#CLIENT_KEY_ID#' => $keyId,
                    ]
                ),
                'HELPER' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_CATALOG_HELPER'),
            ],
            'EXPORT_PRICE' => [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_PRICE_TITLE'),
                'DESCRIPTION' => Loc::getMessage(
                    'DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_PRICE_DESCRIPTION',
                    [
                        '#PATH#' => $_SERVER['DOCUMENT_ROOT'],
                        '#CLIENT_KEY_ID#' => $keyId,
                    ]
                ),
                'HELPER' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_PRICE_HELPER'),
            ],
            'EXPORT_STOCK' => [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_STOCK_TITLE'),
                'DESCRIPTION' => Loc::getMessage(
                    'DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_STOCK_DESCRIPTION',
                    [
                        '#PATH#' => $_SERVER['DOCUMENT_ROOT'],
                        '#CLIENT_KEY_ID#' => $keyId,
                    ]
                ),
                'HELPER' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_CRON_EXPORT_STOCK_HELPER'),
            ],
        ];

        return $data[$code] ?: [];
    }
}

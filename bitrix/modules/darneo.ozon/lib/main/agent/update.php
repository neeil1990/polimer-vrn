<?php

namespace Darneo\Ozon\Main\Agent;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use CAdminNotify;
use CUpdateClientPartner;
use Darneo\Ozon\Configuration;

class Update
{
    public const NOTIFY_TAG = 'DARNEO_OZON_UPDATE_CHECK';

    public static function check(): string
    {
        $moduleName = Configuration::MODULE_NAME;
        $currentVersion = static::getCurrentVersion($moduleName);
        $newVersion = static::getNewVersion($moduleName);

        if (static::compareVersion($newVersion, $currentVersion) > 0) {
            static::addNotify($moduleName, $newVersion);
        }

        return '\Darneo\Ozon\Main\Agent\Update::check();';
    }

    protected static function getCurrentVersion(string $moduleName): string
    {
        return Main\ModuleManager::getVersion($moduleName);
    }

    protected static function getNewVersion(string $moduleName): string
    {
        $result = '';
        if (static::loadUpdater()) {
            $errorMessage = '';
            $updateList = CUpdateClientPartner::GetUpdatesList($errorMessage, false, 'Y', [$moduleName]);
            if (!empty($updateList['MODULE']) && is_array($updateList['MODULE'])) {
                foreach ($updateList['MODULE'] as $update) {
                    $isTargetModule = (isset($update['@']['ID']) && $update['@']['ID'] === $moduleName);
                    $hasNewVersion = (isset($update['#']['VERSION']) && is_array($update['#']['VERSION']));
                    if ($isTargetModule && $hasNewVersion) {
                        foreach ($update['#']['VERSION'] as $updateVersion) {
                            if (isset($updateVersion['@']['ID'])) {
                                $result = $updateVersion['@']['ID'];
                            }
                        }
                        break;
                    }
                }
            }
        }

        return $result;
    }

    protected static function loadUpdater(): bool
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/update_client_partner.php';
        $result = false;

        if (file_exists($path)) {
            require_once $path;
            $result = class_exists(CUpdateClientPartner::class);
        }

        return $result;
    }

    protected static function compareVersion(string $versionA, string $versionB): int
    {
        $versionAParts = explode('.', $versionA);
        $versionBParts = explode('.', $versionB);
        $result = 0;

        foreach ($versionAParts as $index => $partA) {
            $partAInteger = (int)$partA;
            $partB = $versionBParts[$index] ?? null;
            $partBInteger = (int)$partB;

            if ($partAInteger < $partBInteger) {
                $result = -1;
                break;
            }

            if ($partAInteger > $partBInteger) {
                $result = 1;
                break;
            }
        }

        return $result;
    }

    protected static function addNotify(string $moduleName, string $version): void
    {
        $query = http_build_query(
            [
                'tabControl_active_tab' => 'tab2',
                'addmodule' => $moduleName,
                'lang' => LANGUAGE_ID
            ]
        );
        $message = Loc::getMessage('DARNEO_OZON_MAIN_AGENT_UPDATE_NOTIFY', [
            '#MODULE_NAME#' => $moduleName,
            '#VERSION#' => $version,
            '#LINK#' => '/bitrix/admin/update_system_partner.php?' . $query
        ]);

        CAdminNotify::Add(
            [
                'MESSAGE' => $message,
                'MODULE_ID' => $moduleName,
                'TAG' => static::NOTIFY_TAG
            ]
        );
    }
}

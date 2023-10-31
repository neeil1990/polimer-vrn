<?php
/**
 * Created: 21.10.2021, 11:47
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class logOperations {
    /**
     * Delete selected log file
     * @param $params
     * @return array|string
     */
    public static function deleteLog($params)
    {
        $resultOperation['status'] = 'failure';
        // get moduleID & params for delete
        $moduleID = '';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getModuleID')) {
            $moduleID = \s34web\mailSMTPB24\mainSettings::getModuleID();
        }
        if (empty($moduleID))
        {
            $resultOperation['error'] = Loc::getMessage('S34WEB_MAILSMTPB24_ERROR_MODULE');
            return $resultOperation;
        }
        $moduleCode = strtoupper(str_replace('.', '_', $moduleID));
        // check file name
        if(!empty($params['file']))
        {
            $logFileName = $params['file'];
        }
        else
        {
            $resultOperation['error'] = Loc::getMessage($moduleCode.'_ERROR_FILE_NAME');
            return $resultOperation;
        }
        $docRoot = \Bitrix\Main\Application::getInstance()->getContext()->getServer()->getDocumentRoot();
        $moduleInstallDir = 'bitrix';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getInstallDir')) {
            $moduleInstallDir = \s34web\mailSMTPB24\mainSettings::getInstallDir();
        }
        $mailLogsPath = $docRoot . '/' . $moduleInstallDir . '/modules/' . $moduleID . '/logs';
        $mailLogFile = $mailLogsPath . '/' . $logFileName;
        // delete operation
        if (\Bitrix\Main\IO\Directory::isDirectoryExists($mailLogsPath)) {
            if (\Bitrix\Main\IO\File::isFileExists($mailLogFile)) {
                \Bitrix\Main\IO\File::deleteFile($mailLogFile);
                $resultOperation['status'] = 'success';
            } else {
                $resultOperation['error'] = Loc::getMessage($moduleCode . '_ERROR_LOG_PATH');
            }
        }
        return $resultOperation;
    }

    /**
     * Delete all log files
     * @return array
     */
    public static function deleteAllLogs()
    {
        $resultOperation['status'] = 'failure';
        // get moduleID & params for delete
        $moduleID = '';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getModuleID')) {
            $moduleID = \s34web\mailSMTPB24\mainSettings::getModuleID();
        }
        if (empty($moduleID))
        {
            $resultOperation['error'] = Loc::getMessage('S34WEB_MAILSMTPB24_ERROR_MODULE');
            return $resultOperation;
        }
        $docRoot = \Bitrix\Main\Application::getInstance()->getContext()->getServer()->getDocumentRoot();
        $moduleInstallDir = 'bitrix';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getInstallDir')) {
            $moduleInstallDir = \s34web\mailSMTPB24\mainSettings::getInstallDir();
        }
        $mailLogsPath = $docRoot . '/' . $moduleInstallDir . '/modules/' . $moduleID . '/logs';
        // delete operation
        if (\Bitrix\Main\IO\Directory::isDirectoryExists($mailLogsPath)) {
            $iterator = new \RecursiveDirectoryIterator($mailLogsPath);
            foreach (new \RecursiveIteratorIterator($iterator) as $file) {
                if ($file->isDir()) {
                    continue;
                }
                if ($file->isFile()) {
                    \Bitrix\Main\IO\File::deleteFile($mailLogsPath . '/' . $file->getFilename());
                }
            }
            unset($file);

            $resultOperation['status'] = 'success';
        }
        return $resultOperation;
    }
    /**
     * Clear cache file for select type
     * @param $type
     */
    public static function clearLogDataCache($type)
    {
        $moduleID = '';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getModuleID')) {
            $moduleID = \s34web\mailSMTPB24\mainSettings::getModuleID();
        }
        if (empty($moduleID))
        {
            return;
        }
        $moduleLowerCode = strtolower(str_replace(".", "_", $moduleID));
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->clean('cache_'.$moduleLowerCode.'_log_file_create_date_' . $type, $moduleLowerCode);
    }
    /**
     * Set data create log into rows tables
     * @param $type
     * @param $mailID
     */
    public static function setLogDate($type, $mailID)
    {
        $resAccountUpdate = null;
        if ($type == 'admin_smtp') {
            if(method_exists('\s34web\mailSMTPB24\smtpAccountsTable','update')) {
                $resAccountUpdate = \s34web\mailSMTPB24\smtpAccountsTable::update($mailID,
                    ['DATE_CREATE_LOG' => new \Bitrix\Main\Type\Date()]);
            }
        }
        if ($type == 'users_smtp') {
            $userSMTPAccount = [];
            if(method_exists('\s34web\mailSMTPB24\usersSmtpAccountsTable','getList')) {
                $userSMTPAccountRes = \s34web\mailSMTPB24\usersSmtpAccountsTable::getList([
                    'filter' => ['SMTP_ID' => $mailID],
                    'select' => ['ID']
                ]);
                if($userSMTPAccountElem = $userSMTPAccountRes->fetch())
                {
                    $userSMTPAccount = $userSMTPAccountElem;
                }
            }
            if (!empty($userSMTPAccount)) {
                if(method_exists('\s34web\mailSMTPB24\usersSmtpAccountsTable','update')) {
                    $resAccountUpdate = \s34web\mailSMTPB24\usersSmtpAccountsTable::update($userSMTPAccount['ID'],
                        ['DATE_CREATE_LOG' => new \Bitrix\Main\Type\Date()]);
                }
            }
        }
        if (!empty($resAccountUpdate) && !$resAccountUpdate->isSuccess()) {
            \Bitrix\Main\Diag\Debug::dumpToFile($resAccountUpdate->getErrorMessages());
        }
        if (!empty($type) && method_exists('\s34web\mailSMTPB24\logOperations', 'clearLogDataCache')) {
            \s34web\mailSMTPB24\logOperations::clearLogDataCache($type);
        }
    }
    /**
     * Get log date create from cache or tables
     * @param $type
     * @param $mailID
     * @return mixed|string
     */
    public static function getLogDate($type, $mailID)
    {
        $fileCreateDateLog = '';
        $mailAccountsFileCreateDate = [];

        if (empty($type) || empty($mailID)) {
            return $fileCreateDateLog;
        }

        $moduleID = '';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getModuleID')) {
            $moduleID = \s34web\mailSMTPB24\mainSettings::getModuleID();
        }
        if (empty($moduleID))
        {
            return $fileCreateDateLog;
        }
        $moduleLowerCode = strtolower(str_replace(".", "_", $moduleID));

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(3600, 'cache_'.$moduleLowerCode.'_log_file_create_date_' . $type,
            $moduleLowerCode)) {
            $vars = $cache->getVars();
            if (!empty($vars['ACCOUNTS'])) {
                $mailAccountsFileCreateDate = $vars['ACCOUNTS'];
                if (!empty($mailAccountsFileCreateDate[$mailID])) {
                    $fileCreateDateLog = $mailAccountsFileCreateDate[$mailID];
                }
            }
        } elseif ($cache->startDataCache()) {
            if ($type == 'admin_smtp') {
                if (method_exists('\s34web\mailSMTPB24\smtpAccountsTable', 'getList')) {
                    $resAccounts = \s34web\mailSMTPB24\smtpAccountsTable::getList([
                        'select' => ['ID', 'DATE_CREATE_LOG']
                    ]);
                    while ($arAccount = $resAccounts->fetch()) {
                        $mailAccountsFileCreateDate[$arAccount['ID']] =
                            $arAccount['DATE_CREATE_LOG']->format("d.m.Y H:i:s");
                    }
                    unset($arAccount, $resAccounts);
                }

                if (!empty($mailAccountsFileCreateDate[$mailID])) {
                    $fileCreateDateLog = $mailAccountsFileCreateDate[$mailID];
                }
            }
            if ($type == 'users_smtp') {
                if (method_exists('\s34web\mailSMTPB24\usersSmtpAccountsTable', 'getList')) {
                    $resAccounts = \s34web\mailSMTPB24\usersSmtpAccountsTable::getList([
                        'order' => ['ID' => 'desc'],
                        'select' => ['ID', 'SMTP_ID', 'DATE_CREATE_LOG']
                    ]);
                    while ($arAccount = $resAccounts->fetch()) {
                        if (empty($mailAccountsFileCreateDate[$arAccount['SMTP_ID']])) {
                            $mailAccountsFileCreateDate[$arAccount['SMTP_ID']] =
                                $arAccount['DATE_CREATE_LOG']->format("d.m.Y H:i:s");
                        }
                    }
                    unset($arAccount, $resAccounts);
                }

                if (!empty($mailAccountsFileCreateDate[$mailID])) {
                    $fileCreateDateLog = $mailAccountsFileCreateDate[$mailID];
                }
            }

            $cache->endDataCache(['ACCOUNTS' => $mailAccountsFileCreateDate]);
        }
        return $fileCreateDateLog;
    }

    /**
     * Sort array by column
     * @param $array
     * @param $column
     * @param int $order
     */
    public static function sortByColumn(&$array, $column, $order = SORT_ASC)
    {
        array_multisort(
            array_column($array, $column),
            $order,
            $array
        );
    }
}

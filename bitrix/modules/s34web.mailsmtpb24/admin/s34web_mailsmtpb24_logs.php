<?php
/**
 * Created: 17.04.2021, 16:54
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
// used classes
use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader,
    Bitrix\Main\Config\Option,
    Bitrix\Main\Application;

//  Loads language messages for specified file in a lazy way
Loc::loadMessages(__FILE__);

// get module variables
$moduleID = basename(pathinfo(dirname(__FILE__))['dirname']);
$moduleCode = strtoupper(str_replace('.', '_', $moduleID));
$moduleInstallDir = basename(pathinfo(pathinfo(pathinfo(dirname(__FILE__))['dirname'])['dirname'])['dirname']);
$logViewPage = 's34web_mailsmtpb24_log_view.php';
$flagIncludeModule = false;
$flagActiveModule = false;
$flagClassSMTPAccountsExist = false;
// server & site variables
$request = Application::getInstance()->getContext()->getRequest();
$docRoot = Application::getInstance()->getContext()->getServer()->getDocumentRoot();

// check include & active module
$moduleStatus = Loader::includeSharewareModule($moduleID);
if ($moduleStatus == Loader::MODULE_DEMO || $moduleStatus == Loader::MODULE_INSTALLED) {
    $flagIncludeModule = true;

    $activeModule = Option::get($moduleID, 'active_module', 'N');
    if ($activeModule == 'Y') {
        $flagActiveModule = true;
    }
}
// check smtpAccountsTable class
if(class_exists('s34web\mailSMTPB24\smtpAccountsTable'))
{
    $flagClassSMTPAccountsExist = true;
}

global $APPLICATION;
$ajaxPath = '';
// page before
if ($flagIncludeModule && $flagActiveModule && $flagClassSMTPAccountsExist) {

    // check module right
    if ($APPLICATION->GetGroupRight($moduleID) < 'R')
        $APPLICATION->AuthForm(Loc::getMessage($moduleCode . '_LOGS_ACCESS_DENIED'));
    // variables
    $mailLogsList = [];
    $adminSMTPAccounts = [];
    $adminSMTPEmails = [];
    $adminSenderSMTPAccounts = [];
    $usersSMTPAccounts = [];
    $mailSMTPAccounts = [];
    $mailSMTPEmails = [];
    $mailLogsRes = [];
    $mailLogsPath = '';
    // set path
    $mailLogsPath = $docRoot . '/' . $moduleInstallDir . '/modules/' . $moduleID . '/logs';
    $ajaxPath = '/' . $moduleInstallDir . '/tools/' . $moduleID . '/ajax.php';
    // get log files
    if (\Bitrix\Main\IO\Directory::isDirectoryExists($mailLogsPath)) {
        $iterator = new RecursiveDirectoryIterator($mailLogsPath);
        foreach (new RecursiveIteratorIterator($iterator) as $file) {
            if ($file->isDir()) {
                continue;
            }
            if ($file->isFile()) {
                $mailLogsList[] = [
                    'DATE_CHANGE' => date("d.m.Y H:i:s", filemtime(
                            $mailLogsPath . '/' . $file->getFilename())
                    ),
                    'PATH' => $mailLogsPath . '/' . $file->getFilename()
                ];
            }
        }
        unset($file);
    }
    // sort logs
    if (!empty($mailLogsList) && method_exists('s34web\mailSMTPB24\logOperations', 'sortByColumn')) {
        s34web\mailSMTPB24\logOperations::sortByColumn($mailLogsList, 'PATH', SORT_DESC);
    }

    // set logs array
    if (!empty($mailLogsList)) {
        foreach ($mailLogsList as $log) {
            $tmpNameLog = str_replace($mailLogsPath, '', $log['PATH']);
            $filePathNameLog = str_replace($docRoot, '', $log['PATH']);
            $fileNameLog = str_replace($mailLogsPath, '', $log['PATH']);
            $fileNameLog = str_replace('/', '', $fileNameLog);
            $tmpNameLog = str_replace('.log', '', $tmpNameLog);
            $tmpNameLog = str_replace('/', '', $tmpNameLog);
            // get admin smtp accounts IDs
            $adminSMTPLog = strpos($tmpNameLog, 'admin_smtp');
            if ($adminSMTPLog !== false) {
                $adminIDSmtpLogSend = str_replace('send_admin_smtp_', '', $tmpNameLog);
                if (intval($adminIDSmtpLogSend) > 0) {
                    if (!in_array($adminIDSmtpLogSend, $adminSMTPAccounts)) {
                        $adminSMTPAccounts[] = $adminIDSmtpLogSend;
                    }
                    $mailLogsRes['ADMIN_SMTP'][$adminIDSmtpLogSend]['SEND']['LINK'] = $filePathNameLog;
                    $mailLogsRes['ADMIN_SMTP'][$adminIDSmtpLogSend]['SEND']['NAME'] = $fileNameLog;
                    $mailLogsRes['ADMIN_SMTP'][$adminIDSmtpLogSend]['SEND']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
                if (is_numeric($adminIDSmtpLogSend) && $adminIDSmtpLogSend == 0) {
                    $mailLogsRes['ADMIN_SMTP_NEW']['SEND']['LINK'] = $filePathNameLog;
                    $mailLogsRes['ADMIN_SMTP_NEW']['SEND']['NAME'] = $fileNameLog;
                    $mailLogsRes['ADMIN_SMTP_NEW']['SEND']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
                $adminIDSmtpLogCheck = str_replace('check_admin_smtp_', '', $tmpNameLog);
                if (intval($adminIDSmtpLogCheck) > 0) {
                    if (!in_array($adminIDSmtpLogCheck, $adminSMTPAccounts)) {
                        $adminSMTPAccounts[] = $adminIDSmtpLogCheck;
                    }
                    $mailLogsRes['ADMIN_SMTP'][$adminIDSmtpLogCheck]['CHECK']['LINK'] = $filePathNameLog;
                    $mailLogsRes['ADMIN_SMTP'][$adminIDSmtpLogCheck]['CHECK']['NAME'] = $fileNameLog;
                    $mailLogsRes['ADMIN_SMTP'][$adminIDSmtpLogCheck]['CHECK']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
                if (is_numeric($adminIDSmtpLogCheck) && $adminIDSmtpLogCheck == 0) {
                    $mailLogsRes['ADMIN_SMTP_NEW']['CHECK']['LINK'] = $filePathNameLog;
                    $mailLogsRes['ADMIN_SMTP_NEW']['CHECK']['NAME'] = $fileNameLog;
                    $mailLogsRes['ADMIN_SMTP_NEW']['CHECK']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
            }
            // get users smtp accounts IDs
            $usersSMTPLog = strpos($tmpNameLog, 'users_smtp');
            if ($usersSMTPLog !== false) {
                $usersIDSmtpLogSend = str_replace('send_users_smtp_', '', $tmpNameLog);
                if (intval($usersIDSmtpLogSend) > 0) {
                    if(!in_array($usersIDSmtpLogSend, $usersSMTPAccounts)) {
                        $usersSMTPAccounts[] = $usersIDSmtpLogSend;
                    }
                    $mailLogsRes['USERS_SMTP'][$usersIDSmtpLogSend]['SEND']['LINK'] = $filePathNameLog;
                    $mailLogsRes['USERS_SMTP'][$usersIDSmtpLogSend]['SEND']['NAME'] = $fileNameLog;
                    $mailLogsRes['USERS_SMTP'][$usersIDSmtpLogSend]['SEND']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
                if (is_numeric($usersIDSmtpLogSend) && $usersIDSmtpLogSend == 0) {
                    $mailLogsRes['USERS_SMTP_NEW']['SEND']['LINK'] = $filePathNameLog;
                    $mailLogsRes['USERS_SMTP_NEW']['SEND']['NAME'] = $fileNameLog;
                    $mailLogsRes['USERS_SMTP_NEW']['SEND']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
                $usersIDSmtpLogCheck = str_replace('check_users_smtp_', '', $tmpNameLog);
                if (intval($usersIDSmtpLogCheck) > 0) {
                    if(!in_array($usersIDSmtpLogCheck, $usersSMTPAccounts)) {
                        $usersSMTPAccounts[] = $usersIDSmtpLogCheck;
                    }
                    $mailLogsRes['USERS_SMTP'][$usersIDSmtpLogCheck]['CHECK']['LINK'] = $filePathNameLog;
                    $mailLogsRes['USERS_SMTP'][$usersIDSmtpLogCheck]['CHECK']['NAME'] = $fileNameLog;
                    $mailLogsRes['USERS_SMTP'][$usersIDSmtpLogCheck]['CHECK']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
                if (is_numeric($usersIDSmtpLogCheck) && $usersIDSmtpLogCheck == 0) {
                    $mailLogsRes['USERS_SMTP_NEW']['CHECK']['LINK'] = $filePathNameLog;
                    $mailLogsRes['USERS_SMTP_NEW']['CHECK']['NAME'] = $fileNameLog;
                    $mailLogsRes['USERS_SMTP_NEW']['CHECK']['DATE_CHANGE'] = $log['DATE_CHANGE'];
                }
            }
            // system log
            $systemLogSend = strpos($tmpNameLog, 'send_system');
            if ($systemLogSend !== false) {
                $mailLogsRes['SYSTEM_SMTP_SEND']['LINK'] = $filePathNameLog;
                $mailLogsRes['SYSTEM_SMTP_SEND']['NAME'] = $fileNameLog;
                $mailLogsRes['SYSTEM_SMTP_SEND']['DATE_CHANGE'] = $log['DATE_CHANGE'];
            }
            $systemLogCheck = strpos($tmpNameLog, 'check_system');
            if ($systemLogCheck !== false) {
                $mailLogsRes['SYSTEM_SMTP_CHECK']['LINK'] = $filePathNameLog;
                $mailLogsRes['SYSTEM_SMTP_CHECK']['NAME'] = $fileNameLog;
                $mailLogsRes['SYSTEM_SMTP_CHECK']['DATE_CHANGE'] = $log['DATE_CHANGE'];
            }
        }
        unset($log);
    }
    // admin smtp res
    if (!empty($adminSMTPAccounts)) {
        if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'getList')) {
            $resAccountsList = s34web\mailSMTPB24\smtpAccountsTable::getList([
                'filter' => ['ID' => $adminSMTPAccounts],
                'select' => ['ID', 'NAME', 'EMAIL']
            ]);
            while ($arAccount = $resAccountsList->fetch()) {
                $mailLogsRes['ADMIN_SMTP'][$arAccount['ID']]['NAME'] = $arAccount['NAME'];
                $mailLogsRes['ADMIN_SMTP'][$arAccount['ID']]['EMAIL'] = $arAccount['EMAIL'];
                //$adminSMTPEmails[] = $arAccount['EMAIL'];
            }
            unset($arAccount, $resAccountsList);
        }
    }
    /*
     * Add name from STP accounts (draft)
     * if(!empty($adminSMTPEmails))
    {
        $resAccountsList = Bitrix\Main\Mail\Internal\SenderTable::getList([
            'order' => [
                'ID' => 'desc',
            ],
            'filter' => [
                'IS_CONFIRMED' => true,
                'EMAIL' => $adminSMTPEmails,
            ],
            'select' => ['NAME', 'EMAIL', 'OPTIONS']
        ]);
        while ($arAccount = $resAccountsList->fetch()) {
            if(!empty($arAccount['OPTIONS']['source'])) {
                if($arAccount['OPTIONS']['source'] == 'main.mail.confirm' &&
                    intval($arAccount['OPTIONS']['confirm_time']) > 0)
                {
                    $adminSenderSMTPAccounts[] = $arAccount;
                }
            }
        }
        unset($arAccount, $resAccountsList);
    }
    if(!empty($mailLogsRes['ADMIN_SMTP']) && !empty($adminSenderSMTPAccounts))
    {
        foreach ($mailLogsRes['ADMIN_SMTP'] as $accountID => &$accountValue)
        {
            foreach ($adminSenderSMTPAccounts as $adminSenderSMTPAccount) {
                if($accountValue['EMAIL'] == $adminSenderSMTPAccount['EMAIL'] && !empty($adminSenderSMTPAccount['NAME']))
                {
                    $accountValue['NAME'] .= ' / '.$adminSenderSMTPAccount['NAME'];
                    break;
                }
            }
            unset($adminSenderSMTPAccount);
        }
        unset($accountID, $accountValue);
    }*/
    // users smtp res
    if (!empty($usersSMTPAccounts)) {
        $resAccountsList = Bitrix\Main\Mail\Internal\SenderTable::getList([
            'order' => [
                'ID' => 'desc',
            ],
            'filter' => [
                'IS_CONFIRMED' => true,
                'ID' => $usersSMTPAccounts
            ],
            'select' => ['ID', 'NAME', 'EMAIL', 'OPTIONS']
        ]);
        while ($arAccount = $resAccountsList->fetch()) {
            $mailLogsRes['USERS_SMTP'][$arAccount['ID']]['NAME'] = $arAccount['NAME'];
            $mailLogsRes['USERS_SMTP'][$arAccount['ID']]['EMAIL'] = $arAccount['EMAIL'];
            if(!empty($arAccount['OPTIONS']['source'])) {
                if($arAccount['OPTIONS']['source'] == 'mail.client.config')
                {
                    $mailSMTPEmails[] = $arAccount['EMAIL'];
                }
                else
                {
                    $mailLogsRes['USERS_SMTP'][$arAccount['ID']]['TYPE'] = 'CRM';
                }
            }
        }
        unset($arAccount, $resAccountsList);
    }
    // mail smtp res
    if (!empty($mailSMTPEmails)) {
        if (Loader::IncludeModule('mail')) {
            $resAccountsList = Bitrix\Mail\MailboxTable::getList([
                'filter' => array(
                    '=EMAIL' => $mailSMTPEmails,
                    '=ACTIVE' => 'Y',
                    '=SERVER_TYPE' => 'imap',
                ),
                'select' => ['USERNAME', 'EMAIL']
            ]);
            while ($arAccount = $resAccountsList->fetch()) {
                $mailSMTPAccounts[] = $arAccount;
            }
            unset($arAccount, $resAccountsList);
        }
    }
    // set name from mailbox
    if(!empty($mailLogsRes['USERS_SMTP']) && !empty($mailSMTPAccounts))
    {
        foreach ($mailSMTPAccounts as $mailSMTPAccount) {
            foreach ($mailLogsRes['USERS_SMTP'] as $accountID => &$accountValue) {
                if(isset($mailSMTPAccount['EMAIL']) && isset($accountValue['EMAIL']) &&
                    $accountValue['EMAIL'] == $mailSMTPAccount['EMAIL'])
                {
                    if(!empty($mailSMTPAccount['USERNAME'])) {
                        $accountValue['NAME'] = $mailSMTPAccount['USERNAME'];
                    }
                    $accountValue['TYPE'] = 'MAIL';
                    break;
                }
            }
            unset($accountID, $accountValue);
        }
        unset($mailSMTPAccount);
    }
}

// set title
$APPLICATION->SetTitle(Loc::getMessage($moduleCode . '_LOGS_TITLE'));
// prolog admin after
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
// page content
if ($flagIncludeModule && $flagActiveModule && $flagClassSMTPAccountsExist) {
    // register CSS & JS for logs page
    $arCssJsConfig = array(
        'mailSMTPB24Logs' => [
            'js' => '/' . $moduleInstallDir . '/js/' . $moduleID . '/logs.min.js',
            'css' => '/' . $moduleInstallDir . '/css/' . $moduleID . '/logs.min.css',
        ]
    );
    foreach ($arCssJsConfig as $ext => $arExt) {
        CJSCore::RegisterExt($ext, $arExt);
    }
    CJSCore::Init(['mailSMTPB24Logs']);

    // display demo mode text
    if ($moduleStatus == Loader::MODULE_DEMO) {
        CAdminMessage::ShowMessage([
            'TYPE' => 'OK',
            'MESSAGE' => Loc::getMessage($moduleCode . '_LOGS_MODULE_DEMO_TITLE'),
            'DETAILS' => Loc::getMessage($moduleCode . '_LOGS_MODULE_DEMO_TEXT'),
            'HTML' => true
        ]);
    }

    // show tables with logs
    $messages = Loc::loadLanguageFile(__FILE__);
    ?>
    <div class="smtp_mail_log_buttons_block">
        <div class="smtp_mail_log_result_delete"><span id="SMTPMAIL_DELETE_LOGS_RESULT"></span>
        </div>
        <div class="smtp_mail_log_buttons_right">
            <button id="SMTPMAIL_DELETE_ALL_LOGS" class="delete-all-logs-btn"><?=
                Loc::getMessage($moduleCode . '_LOGS_DELETE_LOGS_BTN') ?></button>
        </div>
    </div>
    <?php
    if (!empty($mailLogsList)) {
        // system logs
        if (!empty($mailLogsRes['SYSTEM_SMTP_CHECK']) || !empty($mailLogsRes['SYSTEM_SMTP_SEND'])) {
            ?>
            <div class="smtp_mail_log_type_block">
                <p><?=
                    Loc::getMessage($moduleCode . '_LOGS_HEADER_SYSTEM_LOGS') ?></p></div>
            <table class="smtp_mail_log_table">
                <thead class="grid-header">
                <tr class="grid-row">
                    <th class="grid-cell-head grid-cell-left">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_LOG_NAME') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 40%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_VIEW') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 20%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_DATE_CHANGE') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 10%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_DELETE') ?></span>
                        </span></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($mailLogsRes['SYSTEM_SMTP_CHECK'])): ?>
                    <tr class="grid-row">
                        <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                Loc::getMessage($moduleCode . '_LOGS_SYSTEM_NAME_CHECK') ?></span>
                        </td>
                        <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                $mailLogsRes['SYSTEM_SMTP_CHECK']['NAME'] ?>"
                                   target="_blank"><?= $mailLogsRes['SYSTEM_SMTP_CHECK']['NAME'] ?></a></span></td>
                        <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                $mailLogsRes['SYSTEM_SMTP_CHECK']['DATE_CHANGE'] ?></span></td>
                        <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $mailLogsRes['SYSTEM_SMTP_CHECK']['NAME'] ?>">
                                    <span class="delete-log-btn"></span></button></span></td>
                    </tr>
                <?php endif ?>
                <?php if (!empty($mailLogsRes['SYSTEM_SMTP_SEND'])): ?>
                    <tr class="grid-row">
                        <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                Loc::getMessage($moduleCode . '_LOGS_SYSTEM_NAME_SEND') ?></span>
                        </td>
                        <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                $mailLogsRes['SYSTEM_SMTP_SEND']['NAME'] ?>"
                                   target="_blank"><?= $mailLogsRes['SYSTEM_SMTP_SEND']['NAME'] ?></a></span></td>
                        <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                $mailLogsRes['SYSTEM_SMTP_SEND']['DATE_CHANGE'] ?></span></td>
                        <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $mailLogsRes['SYSTEM_SMTP_SEND']['NAME'] ?>">
                                    <span class="delete-log-btn"></span></button></span></td>
                    </tr>
                <?php endif ?>
                </tbody>
            </table>
            <?php
        }
        // admin smtp logs
        if (!empty($mailLogsRes['ADMIN_SMTP']) || !empty($mailLogsRes['ADMIN_SMTP_NEW'])) {
            ?>
            <div class="smtp_mail_log_type_block">
                <p><?=
                    Loc::getMessage($moduleCode . '_LOGS_HEADER_ADMIN_SMTP_LOGS') ?></p></div>
            <table class="smtp_mail_log_table">
                <thead class="grid-header">
                <tr class="grid-row">
                    <th class="grid-cell-head grid-cell-left">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_LOG_NAME') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 40%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_VIEW') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 20%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_DATE_CHANGE') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 10%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_DELETE') ?></span>
                        </span></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($mailLogsRes['ADMIN_SMTP_NEW'])): ?>
                    <?php if (!empty($mailLogsRes['ADMIN_SMTP_NEW']['CHECK'])): ?>
                        <tr class="grid-row">
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    Loc::getMessage($moduleCode . '_LOGS_NEW_ADMIN_SMTP_NAME_CHECK') ?></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $mailLogsRes['ADMIN_SMTP_NEW']['CHECK']['NAME'] ?>"
                                       target="_blank"><?= $mailLogsRes['ADMIN_SMTP_NEW']['CHECK']['NAME'] ?></a></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    $mailLogsRes['ADMIN_SMTP_NEW']['CHECK']['DATE_CHANGE'] ?></span></td>
                            <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $mailLogsRes['ADMIN_SMTP_NEW']['CHECK']['NAME'] ?>">
                                    <span class="delete-log-btn"></span></button></span></td>
                        </tr>
                    <?php endif ?>
                    <?php if (!empty($mailLogsRes['ADMIN_SMTP_NEW']['SEND'])): ?>
                        <tr class="grid-row">
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    Loc::getMessage($moduleCode . '_LOGS_NEW_ADMIN_SMTP_NAME_SEND') ?></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $mailLogsRes['ADMIN_SMTP_NEW']['SEND']['NAME'] ?>"
                                       target="_blank"><?= $mailLogsRes['ADMIN_SMTP_NEW']['SEND']['NAME'] ?></a></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    $mailLogsRes['ADMIN_SMTP_NEW']['SEND']['DATE_CHANGE'] ?></span></td>
                            <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $mailLogsRes['ADMIN_SMTP_NEW']['SEND']['NAME'] ?>">
                                    <span class="delete-log-btn"></span></button></span></td>
                        </tr>
                    <?php endif ?>
                <?php endif ?>
                <?php if (!empty($mailLogsRes['ADMIN_SMTP'])): ?>
                    <?php foreach ($mailLogsRes['ADMIN_SMTP'] as $adminSMTPAccountID => $adminSMTPAccounValue): ?>
                        <?php
                        $nameAccount = '';
                        if (!empty($adminSMTPAccounValue['NAME'])) {
                            $nameAccount .= $adminSMTPAccounValue['NAME'];
                        }
                        if (!empty($adminSMTPAccounValue['EMAIL'])) {
                            if (!empty($nameAccount)) {
                                $nameAccount .= ' ';
                            }
                            $nameAccount .= '&lt;' . $adminSMTPAccounValue['EMAIL'] . '&gt;';
                        }
                        if (empty($nameAccount)) {
                            $nameAccount = '-- '.Loc::getMessage($moduleCode . '_LOGS_ACCOUNT_DELETE').' --';
                        }
                        if (!empty($adminSMTPAccounValue['CHECK'])) {
                            ?>
                            <tr class="grid-row">
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?php
                                        $checkAccountName = $nameAccount . ' ' .
                                            Loc::getMessage($moduleCode . '_LOGS_TYPE_LOG_CHECK');
                                        ?><?= $checkAccountName ?></span>
                                </td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $adminSMTPAccounValue['CHECK']['NAME'] ?>" target="_blank"><?=
                                        $adminSMTPAccounValue['CHECK']['NAME'] ?></a></span></td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                        $adminSMTPAccounValue['CHECK']['DATE_CHANGE'] ?></span></td>
                                <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $adminSMTPAccounValue['CHECK']['NAME']
                                ?>"><span class="delete-log-btn"></span></button></span></td>
                            </tr>
                            <?php
                        }
                        if (!empty($adminSMTPAccounValue['SEND'])) {
                            ?>
                            <tr class="grid-row">
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?php
                                        $sendAccountName = $nameAccount . ' ' .
                                            Loc::getMessage($moduleCode . '_LOGS_TYPE_LOG_SEND');
                                        ?><?= $sendAccountName ?></span>
                                </td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $adminSMTPAccounValue['SEND']['NAME'] ?>" target="_blank"><?=
                                        $adminSMTPAccounValue['SEND']['NAME'] ?></a></span></td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                        $adminSMTPAccounValue['SEND']['DATE_CHANGE'] ?></span></td>
                                <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $adminSMTPAccounValue['SEND']['NAME']
                                ?>"><span class="delete-log-btn"></span></button></span></td>
                            </tr>
                            <?php
                        } ?>
                    <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
            <?php
        }
        // users smtp logs
        if (!empty($mailLogsRes['USERS_SMTP']) || !empty($mailLogsRes['USERS_SMTP_NEW'])) {
            ?>
            <div class="smtp_mail_log_type_block">
                <p><?=
                    Loc::getMessage($moduleCode . '_LOGS_HEADER_USERS_SMTP_LOGS') ?></p></div>
            <table class="smtp_mail_log_table">
                <thead class="grid-header">
                <tr class="grid-row">
                    <th class="grid-cell-head grid-cell-left">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_LOG_NAME') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 40%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_VIEW') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 20%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_DATE_CHANGE') ?></span>
                            <span class="grid-splitter"></span>
                        </span></th>
                    <th class="grid-cell-head grid-cell-left" style="width: 10%;">
                        <span class="grid-cell-head-container">
                            <span class="grid-head-title"><?=
                                Loc::getMessage($moduleCode . '_LOGS_HEADER_DELETE') ?></span>
                        </span></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($mailLogsRes['USERS_SMTP_NEW'])): ?>
                    <?php if (!empty($mailLogsRes['USERS_SMTP_NEW']['CHECK'])): ?>
                        <tr class="grid-row">
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    Loc::getMessage($moduleCode . '_LOGS_NEW_USERS_SMTP_NAME_CHECK') ?></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $mailLogsRes['USERS_SMTP_NEW']['CHECK']['NAME'] ?>"
                                       target="_blank"><?= $mailLogsRes['USERS_SMTP_NEW']['CHECK']['NAME'] ?></a></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    $mailLogsRes['USERS_SMTP_NEW']['CHECK']['DATE_CHANGE'] ?></span></td>
                            <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $mailLogsRes['USERS_SMTP_NEW']['CHECK']['NAME'] ?>">
                                    <span class="delete-log-btn"></span></button></span></td>
                        </tr>
                    <?php endif ?>
                    <?php if (!empty($mailLogsRes['USERS_SMTP_NEW']['SEND'])): ?>
                        <tr class="grid-row">
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    Loc::getMessage($moduleCode . '_LOGS_NEW_USERS_SMTP_NAME_SEND') ?></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $mailLogsRes['USERS_SMTP_NEW']['SEND']['NAME'] ?>"
                                       target="_blank"><?= $mailLogsRes['USERS_SMTP_NEW']['SEND']['NAME'] ?></a></span>
                            </td>
                            <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                    $mailLogsRes['USERS_SMTP_NEW']['SEND']['DATE_CHANGE'] ?></span></td>
                            <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $mailLogsRes['USERS_SMTP_NEW']['SEND']['NAME'] ?>">
                                    <span class="delete-log-btn"></span></button></span></td>
                        </tr>
                    <?php endif ?>
                <?php endif ?>
                <?php if (!empty($mailLogsRes['USERS_SMTP'])): ?>
                    <?php foreach ($mailLogsRes['USERS_SMTP'] as $usersSMTPAccountID => $usersSMTPAccounValue): ?>
                        <?php
                        $nameAccount = '';
                        if (!empty($usersSMTPAccounValue['NAME'])) {
                            $nameAccount .= $usersSMTPAccounValue['NAME'];
                        }
                        if (!empty($usersSMTPAccounValue['EMAIL'])) {
                            if (!empty($nameAccount)) {
                                $nameAccount .= ' ';
                            }
                            $nameAccount .= '&lt;' . $usersSMTPAccounValue['EMAIL'] . '&gt;';
                        }
                        if (empty($nameAccount)) {
                            $nameAccount = '-- '.Loc::getMessage($moduleCode . '_LOGS_ACCOUNT_DELETE').' --';
                        }

                        if (!empty($usersSMTPAccounValue['TYPE'])) {
                            if($usersSMTPAccounValue['TYPE'] == 'MAIL')
                            {
                                $nameAccount .= ' '.Loc::getMessage($moduleCode.'_LOGS_TYPE_ACCOUNT_MAIL');
                            }
                            elseif($usersSMTPAccounValue['TYPE'] == 'CRM')
                            {
                                $nameAccount .= ' '.Loc::getMessage($moduleCode.'_LOGS_TYPE_ACCOUNT_CRM');
                            }
                        }

                        if (!empty($usersSMTPAccounValue['CHECK'])) {
                            ?>
                            <tr class="grid-row">
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?php
                                        $checkAccountName = $nameAccount . ' ' .
                                            Loc::getMessage($moduleCode . '_LOGS_TYPE_LOG_CHECK');
                                        ?><?= $checkAccountName ?></span>
                                </td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $usersSMTPAccounValue['CHECK']['NAME'] ?>"
                                       target="_blank"><?= $usersSMTPAccounValue['CHECK']['NAME'] ?></a></span></td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                        $usersSMTPAccounValue['CHECK']['DATE_CHANGE'] ?></span></td>
                                <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $usersSMTPAccounValue['CHECK']['NAME']
                                ?>"><span class="delete-log-btn"></span></button></span></td>
                            </tr>
                            <?php
                        }
                        if (!empty($usersSMTPAccounValue['SEND'])) {
                            ?>
                            <tr class="grid-row">
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?php
                                        $sendAccountName = $nameAccount . ' ' .
                                            Loc::getMessage($moduleCode . '_LOGS_TYPE_LOG_SEND');
                                        ?><?= $sendAccountName ?></span>
                                </td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content">
                                    <a href="<?= $logViewPage ?>?lang=<?= LANGUAGE_ID ?>&read_log=Y&file=<?=
                                    $usersSMTPAccounValue['SEND']['NAME'] ?>"
                                       target="_blank"><?= $usersSMTPAccounValue['SEND']['NAME'] ?></a></span></td>
                                <td class="grid-cell grid-cell-left"><span class="grid-cell-content"><?=
                                        $usersSMTPAccounValue['SEND']['DATE_CHANGE'] ?></span></td>
                                <td class="grid-cell grid-cell-left">
                            <span class="grid-cell-content"><button class="delete-log-block" data-file="<?=
                                $usersSMTPAccounValue['SEND']['NAME']
                                ?>"><span class="delete-log-btn"></span></button></span></td>
                            </tr>
                            <?php
                        }
                        ?>
                    <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
            <?php
        }
    } else {
        ?>
        <div class="smtp_mail_log_type_block">
            <p><?= Loc::getMessage($moduleCode . '_LOGS_EMPTY_LOG_FILES') ?></p></div>
        <?php
    }
    ?>
    <script>
        BX.message(<?=CUtil::PhpToJSObject($messages)?>);
        BX.ready(function () {
            BX.mailSMTPB24Logs.init({params: <?=CUtil::PhpToJSObject(['ajaxPath' => $ajaxPath])?>});
        });
    </script>
    <?php
} else {
    if (!$flagIncludeModule) {
        // show module error
        if ($moduleStatus == Loader::MODULE_DEMO_EXPIRED) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_LOGS_MODULE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_LOGS_MODULE_ERROR_DEMO_EXPIRED_TEXT'),
                'HTML' => true
            ]);
        }
        if ($moduleStatus == Loader::MODULE_NOT_FOUND) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_LOGS_MODULE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_LOGS_MODULE_ERROR_NOT_FOUND_TEXT'),
                'HTML' => true
            ]);
        }
    } else {
        if (!$flagActiveModule) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_LOGS_MODULE_ACTIVE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_LOGS_MODULE_ACTIVE_ERROR_TEXT'),
                'HTML' => true
            ]);
        }
        elseif (!$flagClassSMTPAccountsExist)
        {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_LOGS_MODULE_CLASS_SMTP_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_LOGS_MODULE_CLASS_SMTP_ERROR_TEXT'),
                'HTML' => true
            ]);
        }
    }
}
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
<?php
/**
 * Created: 25.03.2021, 10:04
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @global string $by
 * @global string $order
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader,
    Bitrix\Main\Config\Option;

//  Loads language messages for specified file in a lazy way
Loc::loadMessages(__FILE__);

// get module variables
$moduleID = basename(pathinfo(dirname(__FILE__))['dirname']);
$moduleCode = strtoupper(str_replace('.', '_', $moduleID));
$accountsEditPage = 's34web_mailsmtpb24_accounts_edit.php';
$flagIncludeModule = false;
$flagActiveModule = false;
$flagClassSMTPAccountsExist = false;

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();

// check include & active module
$moduleStatus = Loader::includeSharewareModule($moduleID);
if ($moduleStatus == Loader::MODULE_DEMO || $moduleStatus == Loader::MODULE_INSTALLED) {
    $flagIncludeModule = true;

    $activeModule = Option::get($moduleID, 'active_module', 'N');
    if($activeModule == 'Y')
    {
        $flagActiveModule = true;
    }
}
// check smtpAccountsTable class
if(class_exists('s34web\mailSMTPB24\smtpAccountsTable'))
{
    $flagClassSMTPAccountsExist = true;
}

global $APPLICATION, $DB;

$lAdmin = null;
// filter params
$filterFields = [
    ['id' => 'ID', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_ID'),
        'filterable' => '', 'default' => true],
    ['id' => 'ACTIVE', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_ACTIVE'), 'type' => 'list',
        'items' => ['Y' => Loc::getMessage($moduleCode . '_ACCOUNTS_FIELD_YES'),
            'N' => Loc::getMessage($moduleCode . '_ACCOUNTS_FIELD_NO')],
        'filterable' => ''],
    ['id' => 'NAME', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_NAME'),
        'filterable' => '%', 'default' => true],
    ['id' => 'EMAIL', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_EMAIL'),
        'filterable' => '%', 'default' => true],
    ['id' => 'SERVER', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_SERVER'),
        'filterable' => '%', 'default' => true],
    ['id' => 'PORT', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_PORT'),
        'filterable' => '', 'default' => true],
    ['id' => 'SECURE', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_SECURE'), 'type' => 'list',
        'items' => ['N' => Loc::getMessage($moduleCode.'_ACCOUNTS_FIELD_NO'), 'Y' => 'TLS', 'S' => 'SSL'],
        'filterable' => ''],
    ['id' => 'AUTH', 'name' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_AUTH'), 'type' => 'list',
        'items' => ['C' => Loc::getMessage($moduleCode.'_ACCOUNTS_FIELD_YES'),
            'N' => Loc::getMessage($moduleCode.'_ACCOUNTS_FIELD_NO')],
        'filterable' => ''],
];

if ($flagIncludeModule && $flagActiveModule && $flagClassSMTPAccountsExist) {
    // check module right
    if ($APPLICATION->GetGroupRight($moduleID) < 'R')
        $APPLICATION->AuthForm(Loc::getMessage($moduleCode.'_ACCOUNTS_ACCESS_DENIED'));

    $sTableID = 'tbl_smtp_accounts';

    $excelMode = ($request->getQuery('mode') == 'excel');

    $oSort = new CAdminUiSorting($sTableID, 'ID', 'desc');
    $lAdmin = new CAdminUiList($sTableID, $oSort);
    // add filter
    $arFilter = [];
    if(!empty($lAdmin)) {
        $lAdmin->AddFilter($filterFields, $arFilter);
    }

    // actions
    if (!empty($lAdmin) && $lAdmin->EditAction()) {
        $editableFields = ['ACTIVE' => 1, 'NAME' => 1];

        foreach ($request->getPost("FIELDS") as $ID => $arFields) {
            $ID = intval($ID);
            if (!$lAdmin->IsUpdated($ID))
                continue;
            foreach ($arFields as $key => $field) {
                if (!isset($editableFields[$key]) && strpos($key, "UF_") !== 0) {
                    unset($arFields[$key]);
                }
            }
            if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'update')) {
                $DB->StartTransaction();
                $smtpAccountsUpdateRes = s34web\mailSMTPB24\smtpAccountsTable::update($ID, $arFields);
                if (!$smtpAccountsUpdateRes->isSuccess()) {
                    $errorsList = $smtpAccountsUpdateRes->getErrorMessages();
                    $errorsString = '';
                    if(is_array($errorsList))
                    {
                        $errorsString = implode(', ', $errorsList);
                    }
                    $lAdmin->AddUpdateError(Loc::getMessage($moduleCode . '_ACCOUNTS_SAVE_ERROR') . $ID .
                        ": " . $errorsString, $ID);
                    $DB->Rollback();
                }
                $DB->Commit();
            }
            else
            {
                $lAdmin->AddUpdateError(Loc::getMessage($moduleCode . '_ACCOUNTS_SAVE_ERROR') . $ID .
                    ": " .Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_CLASS_SMTP_ERROR_TEXT') , $ID);
            }
        }
    }
    // group actions
    if (!empty($lAdmin) && ($arID = $lAdmin->GroupAction()) && $APPLICATION->GetGroupRight($moduleID) >= 'W') {

        if (!empty($request->getPost('action_all_rows_' . $sTableID)) &&
            $request->getPost('action_all_rows_' . $sTableID) === 'Y') {
            $resAccountsList = s34web\mailSMTPB24\smtpAccountsTable::getList([
                'order' => [],
                'filter' => $arFilter,
                'select' => ['ID']
            ]);
            while ($arAccount = $resAccountsList->fetch()) {
                $arID[] = $arAccount['ID'];
            }
        }
        foreach ($arID as $ID) {

            $ID = intval($ID);
            if ($ID < 1)
                continue;

            switch ($request->getPost('action_button_'.$sTableID)) {
                case 'delete':
                    @set_time_limit(0);
                    if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'delete')) {
                        $DB->StartTransaction();
                        if (!s34web\mailSMTPB24\smtpAccountsTable::delete($ID)) {
                            $DB->Rollback();
                            $err = '';
                            if ($ex = $APPLICATION->GetException())
                                $err = '<br>' . $ex->GetString();
                            $lAdmin->AddGroupError(
                                Loc::getMessage($moduleCode . '_ACCOUNTS_DELETE_ERROR') . $err, $ID);
                        }
                        $DB->Commit();
                    }
                    else
                    {
                        $lAdmin->AddGroupError(
                            Loc::getMessage($moduleCode . '_ACCOUNTS_DELETE_ERROR') . '<br>'.
                            Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_CLASS_SMTP_ERROR_TEXT'), $ID);
                    }
                    break;
                case 'activate':
                case 'deactivate':
                    $arFields = array('ACTIVE' => ($request->getPost('action_button_'.$sTableID) ==
                    'activate' ? 'Y' : 'N'));

                    if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'update')) {
                        if (!$resUpdate = s34web\mailSMTPB24\smtpAccountsTable::update($ID, $arFields)) {
                            $errorsList = $resUpdate->getErrorMessages();
                            $errorsString = '';
                            if(is_array($errorsList))
                            {
                                $errorsString = implode(', ', $errorsList);
                            }
                            $lAdmin->AddGroupError(Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR') .
                                '<br>'. $errorsString, $ID);
                        }
                    }
                    else
                    {
                        $lAdmin->AddGroupError(Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR') .
                            '<br>'. Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_CLASS_SMTP_ERROR_TEXT'), $ID);
                    }
                    break;
            }
        }
    }
    // table header
    $arHeaders = [
        ['id' => 'ID', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_ID'),
            'sort' => 'ID', 'default' => true],
        ['id' => 'ACTIVE', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_ACTIVE'),
            'sort' => 'ACTIVE', 'default' => true],
        ['id' => 'NAME', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_NAME'),
            'sort' => 'NAME', 'default' => true],
        ['id' => 'EMAIL', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_EMAIL'),
            'sort' => 'EMAIL', 'default' => true],
        ['id' => 'SERVER', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_SERVER'),
            'sort' => 'SERVER', 'default' => true],
        ['id' => 'PORT', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_PORT'),
            'sort' => 'PORT', 'default' => true],
        ['id' => 'SECURE', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_SECURE'),
            'sort' => 'SECURE', 'default' => true],
        ['id' => 'AUTH', 'content' => Loc::getMessage($moduleCode . '_ACCOUNTS_HEADER_AUTH'),
            'sort' => 'AUTH', 'default' => true],
    ];
    if(!empty($lAdmin)) {
        $lAdmin->addHeaders($arHeaders);
    }
    // sort & navigation
    $nav = null;
    if(!empty($lAdmin)) {
        $nav = $lAdmin->getPageNavigation('pages-user-admin');
    }
    global $by, $order;
    $sortOrder = strtoupper($order);
    $sortBy = strtoupper($by);
    if ($sortOrder <> 'DESC' && $sortOrder <> 'ASC') {
        $sortOrder = 'DESC';
    }
    // get rows
    $resSMTPAccounts = null;
    if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'getList')) {
        $resSMTPAccounts = s34web\mailSMTPB24\smtpAccountsTable::getList([
            'order' => [$sortBy => $sortOrder],
            'filter' => $arFilter,
            'offset' => ($offset = $nav->getOffset()),
            'limit' => (($limit = $nav->getLimit()) > 0 ? $limit + 1 : 0),
            'count_total' => true,
            'select' => ['ID', 'ACTIVE', 'NAME', 'EMAIL', 'SERVER', 'PORT', 'SECURE', 'AUTH']
        ]);
    }
    // get count rows
    $totalCountRequest = false;
    if(!empty($lAdmin)) {
        $totalCountRequest = $lAdmin->isTotalCountRequest();
    }
    $n = 0;

    if ($totalCountRequest && !empty($resSMTPAccounts) && !empty($lAdmin)) {
        $lAdmin->sendTotalCountResponse($resSMTPAccounts->getCount());
    }

    $pageSize = 0;
    if(!empty($lAdmin)) {
        $pageSize = $lAdmin->getNavSize();
    }
    // set rows to table
    if(!empty($resSMTPAccounts)) {
        while ($accountsData = $resSMTPAccounts->fetch()) {
            $n++;
            if ($n > $pageSize && !$excelMode) {
                break;
            }

            if(!empty($accountsData['ID']) && !empty($lAdmin)) {
                $row =& $lAdmin->addRow($accountsData['ID'], $accountsData, '');
                $row->addViewField('ID', '<a href="' . $accountsEditPage . '?lang=' .
                    LANGUAGE_ID . '&ID=' . $accountsData['ID'] . '" title="' .
                    Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_TITLE') . '">' .
                    $accountsData['ID'] . '</a>');
            }
            if(!empty($row)) {
                $row->AddCheckField('ACTIVE');
            }
            if(!empty($row) && !empty($accountsData['SECURE'])) {
                $row->addViewField('SECURE', ($accountsData['SECURE'] == 'S' || $accountsData['SECURE'] == 'Y') ?
                    Loc::getMessage($moduleCode . '_ACCOUNTS_FIELD_YES') :
                    Loc::getMessage($moduleCode . '_ACCOUNTS_FIELD_NO'));
            }
            if(!empty($row) && !empty($accountsData['AUTH'])) {
                $row->addViewField('AUTH', ($accountsData['AUTH'] == 'C') ?
                    Loc::getMessage($moduleCode . '_ACCOUNTS_FIELD_YES') :
                    Loc::getMessage($moduleCode . '_ACCOUNTS_FIELD_NO'));
            }
            if(!empty($row) &&!empty($accountsData['NAME'])) {
                $row->addViewField('NAME', $accountsData['NAME']);
                $row->addInputField('NAME');
            }

            // set actions for rows
            if (!empty($row) && !empty($lAdmin) && $APPLICATION->GetGroupRight($moduleID) >= 'W') {
                $arActions = [
                    ['ICON' => 'edit', 'TEXT' => Loc::getMessage($moduleCode . '_ACCOUNTS_ADMIN_MENU_EDIT'),
                        'DEFAULT' => true, 'LINK' => $accountsEditPage . '?ID=' . $accountsData['ID'] .
                        '&lang=' . LANGUAGE_ID],
                    ['SEPARATOR' => true],
                    ['ICON' => 'delete', 'TEXT' => Loc::getMessage($moduleCode . '_ACCOUNTS_ADMIN_MENU_DELETE'),
                        'DEFAULT' => true, 'ACTION' => 'if(confirm("' .
                        Loc::getMessage($moduleCode . '_ACCOUNTS_CONFIRM_DEL') . '")) ' .
                        $lAdmin->actionDoGroup($accountsData['ID'], 'delete')]
                ];
                $row->addActions($arActions);
            }
        }
    }
    // set count & navigation
    if(!empty($nav)) {
        $nav->setRecordCount($nav->getOffset() + $n);
    }
    if(!empty($lAdmin)) {
        $lAdmin->setNavigation($nav, Loc::getMessage($moduleCode . '_ACCOUNTS_ADMIN_PAGES'), false);
    }
    // set actions for table
    if ($APPLICATION->GetGroupRight($moduleID) >= 'W') {

        $aContext[] = array(
            'TEXT' => Loc::getMessage($moduleCode . '_ACCOUNTS_ADD_ACCOUNT'),
            'LINK' => $accountsEditPage . '?lang=' . LANGUAGE_ID,
            'TITLE' => Loc::getMessage($moduleCode . '_ACCOUNTS_ADD_ACCOUNT_TITLE'),
            'ICON' => 'btn_new'
        );

        if(!empty($lAdmin)) {
            $lAdmin->AddAdminContextMenu($aContext);

            $lAdmin->AddGroupActionTable(array(
                "edit" => true,
                "delete" => true,
                "for_all" => true,
                "activate" => Loc::getMessage($moduleCode . '_ACCOUNTS_LIST_ACTIVATE'),
                "deactivate" => Loc::getMessage($moduleCode . '_ACCOUNTS_LIST_DEACTIVATE'),
            ));
        }
    }
    if(!empty($lAdmin)) {
        $lAdmin->CheckListMode();
    }
}

// set title
$APPLICATION->SetTitle(Loc::getMessage($moduleCode . '_ACCOUNTS_ADMIN_TITLE'));

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

if ($flagIncludeModule && $flagActiveModule && $flagClassSMTPAccountsExist) {
    // display demo mode text
    if($moduleStatus == Loader::MODULE_DEMO) {
        CAdminMessage::ShowMessage([
            'TYPE' => 'OK',
            'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_DEMO_TITLE'),
            'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_DEMO_TEXT'),
            'HTML' => true
        ]);
    }
    // display table & rows
    if(!empty($lAdmin)) {
        $lAdmin->DisplayFilter($filterFields);
        $lAdmin->DisplayList(["SHOW_COUNT_HTML" => true]);
    }
} else {
    // display errors
    if(!$flagIncludeModule) {
        if($moduleStatus == Loader::MODULE_DEMO_EXPIRED) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_ERROR_DEMO_EXPIRED_TEXT'),
                'HTML' => true
            ]);
        }
        if($moduleStatus == Loader::MODULE_NOT_FOUND) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_ERROR_NOT_FOUND_TEXT'),
                'HTML' => true
            ]);
        }
    }
    else {
        if (!$flagActiveModule) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_ACTIVE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_ACTIVE_ERROR_TEXT'),
                'HTML' => true
            ]);
        }
        elseif (!$flagClassSMTPAccountsExist)
        {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_CLASS_SMTP_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_MODULE_CLASS_SMTP_ERROR_TEXT'),
                'HTML' => true
            ]);
        }
    }
}
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');

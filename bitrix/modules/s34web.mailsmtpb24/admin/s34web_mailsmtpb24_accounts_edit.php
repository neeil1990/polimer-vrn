<?php
/**
 * Created: 25.03.2021, 15:36
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
$moduleInstallDir = basename(pathinfo(pathinfo(pathinfo(dirname(__FILE__))['dirname'])['dirname'])['dirname']);
$accountsPage = 's34web_mailsmtpb24_accounts.php';
$accountsEditPage = 's34web_mailsmtpb24_accounts_edit.php';
$flagIncludeModule = false;
$flagActiveModule = false;
$flagClassSMTPAccountsExist = false;

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$strError = '';
$formFields = [];

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
if (class_exists('s34web\mailSMTPB24\smtpAccountsTable')) {
    $flagClassSMTPAccountsExist = true;
}

global $APPLICATION;

$accountID = intval($request->getQuery('ID'));
$tabControl = null;

if ($flagIncludeModule && $flagActiveModule && $flagClassSMTPAccountsExist) {
    // check module right
    if ($APPLICATION->GetGroupRight($moduleID) < 'W')
        $APPLICATION->AuthForm(Loc::getMessage($moduleCode . '_ACCOUNT_EDIT_ACCESS_DENIED'));

    // register CSS & JS for logs page
    $arCssJsConfig = array(
        'mailSMTPB24Accounts' => [
            'js' => '/' . $moduleInstallDir . '/js/' . $moduleID . '/accounts.min.js',
        ]
    );
    foreach ($arCssJsConfig as $ext => $arExt) {
        CJSCore::RegisterExt($ext, $arExt);
    }
    CJSCore::Init(['mailSMTPB24Accounts']);

    // tabs
    $aTabs = [];
    $aTabs[] = ['DIV' => 'edit1', 'TAB' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_TAB1'),
        'TITLE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_TAB1_TITLE')];
    $tabControl = new CAdminForm('smtp_accounts_edit', $aTabs);

    if (
        $_SERVER['REQUEST_METHOD'] == 'POST'
        && (
            $request->getPost('save') <> ''
            || $request->getPost('apply') <> ''
            || $request->getPost('save_and_add') <> ''
        )
        && check_bitrix_sessid()
    ) {

        $secureType = 'N';

        // form fields
        $formFields = [
            'ACTIVE' => !(empty($request->getPost('ACTIVE'))) ?
                $request->getPost('ACTIVE') : 'N',
            'NAME' => !(empty($request->getPost('NAME'))) ?
                $request->getPost('NAME') : '',
            'EMAIL' => !(empty($request->getPost('EMAIL'))) ?
                $request->getPost('EMAIL') : '',
            'SERVER' => !(empty($request->getPost('SERVER'))) ?
                $request->getPost('SERVER') : '',
            'PORT' => (strlen($request->getPost('PORT')) > 0) ?
                $request->getPost('PORT') : '',
            'SECURE' => !(empty($request->getPost('SECURE'))) ?
                $request->getPost('SECURE') : 'N',
            'AUTH' => !(empty($request->getPost('AUTH'))) ?
                $request->getPost('AUTH') : '',
            'LOGIN' => !(empty($request->getPost('LOGIN'))) ?
                $request->getPost('LOGIN') : '',
            'PASSWORD' => !(empty($request->getPost('PASSWORD'))) ?
                $request->getPost('PASSWORD') : ''
        ];

        // find exist mail
        $findExistEmail = null;
        if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'getList')) {
            $findExistEmailRes = s34web\mailSMTPB24\smtpAccountsTable::getList([
                'filter' => ['EMAIL' => $formFields['EMAIL'], 'ID' => $accountID],
                'select' => ['ID', 'EMAIL', 'PASSWORD']
            ]);
            if ($findExistEmailElem = $findExistEmailRes->fetch()) {
                $findExistEmail = [
                    'ID' => $findExistEmailElem['ID'],
                    'EMAIL' => $findExistEmailElem['EMAIL'],
                    'PASSWORD' => $findExistEmailElem['PASSWORD'],
                ];
            }
        }

        // check double email (disabled)
        /*if ((!empty($findExistEmail['EMAIL']) && $accountID != 0 &&
                $findExistEmail['ID'] != $accountID) || ($accountID == 0 && !empty($findExistEmail['EMAIL']))) {
            $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_DOUBLE_EMAIL') . '<br />';
        }*/

        // check fields
        // check email
        if (empty($formFields['EMAIL'])) {
            $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_EMAIL_EMPTY') . '<br />';
        } else {
            $address = new Bitrix\Main\Mail\Address($formFields['EMAIL']);
            if (!$address->validate()) {
                $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_EMAIL_BAD') . '<br />';
            }
        }
        // check server address
        if (empty($formFields['SERVER'])) {
            $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_SERVER_EMPTY') . '<br />';
        } else {
            $regex = '/^(?:(?:http|https|ssl|tls|smtp):\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)$/i';
            if (!preg_match($regex, $formFields['SERVER'], $matches) || empty($matches[1])) {
                $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_SERVER_BAD') . '<br />';
            }
            if (preg_match('#^(tls)://#', $formFields['SERVER'])) {
                $secureType = 'Y';
            }
            if (preg_match('#^(ssl)://#', $formFields['SERVER'])) {
                $secureType = 'S';
            }
            $formFields['SERVER'] = $matches[1];
        }
        // set type secure SSL
        if ($secureType == 'S' && ($formFields['SECURE'] == 'N' || $formFields['SECURE'] == 'Y')) {
            $formFields['SECURE'] = 'S';
        }
        // set type secure TLS
        if ($secureType == 'Y' && ($formFields['SECURE'] == 'N' || $formFields['SECURE'] == 'S')) {
            $formFields['SECURE'] = 'Y';
        }
        // check server port
        if (strlen($formFields['PORT']) == 0) {
            $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_PORT_EMPTY') . '<br />';
        } else {
            if ($formFields['PORT'] <= 0 || $formFields['PORT'] > 65535) {
                $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_PORT_BAD') . '<br />';
            }
        }
        // check server password
        if (!empty($formFields['PASSWORD'])) {
            if (preg_match('/^\^/', $formFields['PASSWORD'])) {
                $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_PASSWORD_BAD_CARET') . '<br />';
            } else if (preg_match('/\x00/', $formFields['PASSWORD'])) {
                $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_PASSWORD_BAD_NULL') . '<br />';
            }
        }

        // check connection
        if ($strError == '') {
            $checkSMTP = null;
            if (class_exists('s34web\mailSMTPB24\mailSender')) {
                $checkSMTP = new s34web\mailSMTPB24\mailSender();
            }
            if (empty($checkSMTP)) {
                $strError .= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_CLASS_MAILSENDER');
            }

            // change connection data
            if (method_exists('\s34web\mailSMTPB24\mailSender', 'modifySMTPData')) {
                $checkSMTP->modifySMTPData($formFields);
            }
            $checkData = [];
            if (!empty($formFields['SERVER'])) {
                $checkData['SERVER'] = $formFields['SERVER'];
            }
            if (!empty($formFields['PORT'])) {
                $checkData['PORT'] = $formFields['PORT'];
            }
            if (!empty($formFields['SECURE'])) {
                $checkData['SECURE'] = $formFields['SECURE'];
            }
            if (!empty($formFields['AUTH'])) {
                $checkData['AUTH'] = $formFields['AUTH'];
            }
            if (!empty($formFields['LOGIN'])) {
                $checkData['LOGIN'] = $formFields['LOGIN'];
            }
            if (!empty($formFields['PASSWORD'])) {
                $checkData['PASSWORD'] = $formFields['PASSWORD'];
            }
            $checkData['EMAIL'] = $formFields['EMAIL'];
            if (!empty($findExistEmail)) {
                $checkData['EMAIL_ID'] = $findExistEmail['ID'];
                if (!empty($findExistEmail['PASSWORD']) && $formFields['PASSWORD'] == '************') {
                    $checkData['PASSWORD'] = $findExistEmail['PASSWORD'];
                }
            }
            $checkConnection = [];
            // check SMTP connection & send email
            if (method_exists('\s34web\mailSMTPB24\mailSender', 'checkConnect')) {
                $checkSMTP->typeEmail = 'admin_smtp';
                $checkConnection = $checkSMTP->checkConnect($checkData);
            }

            if (!empty($checkConnection)) {
                if ($checkConnection['status'] == 'error') {
                    $strError .= $checkConnection['error'];
                }
            }
        }
        // add or update account
        if ($strError == '') {
            // set date create for log file
            $formFields['DATE_CREATE_LOG'] = new Bitrix\Main\Type\Date();
            $updateAccountResult = null;
            // save or update account
            if ($accountID == 0) {
                if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'add')) {
                    $updateAccountResult = s34web\mailSMTPB24\smtpAccountsTable::add($formFields);
                    $accountID = $updateAccountResult->getId();
                }
            } else {
                if ($formFields['PASSWORD'] == '************') {
                    unset($formFields['PASSWORD']);
                }
                if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'update')) {
                    $updateAccountResult = s34web\mailSMTPB24\smtpAccountsTable::update($accountID, $formFields);
                }
            }
            if (!empty($updateAccountResult) && !$updateAccountResult->isSuccess()) {
                $changeErrors = $updateAccountResult->getErrors();
                $textChangeErrors = '';
                if (is_array($changeErrors)) {
                    $textChangeErrors = implode(',', $changeErrors);
                } else {
                    $textChangeErrors = $changeErrors;
                }
                $strError .= $textChangeErrors;
                \Bitrix\Main\Diag\Debug::dumpToFile($strError);
            }
        }
        if ($strError == '') {
            if ($request->getPost('save') <> '')
                LocalRedirect($accountsPage . '?lang=' . LANGUAGE_ID);
            elseif ($request->getPost('apply') <> '' && !empty($accountID))
                LocalRedirect($accountsEditPage . '?ID=' . $accountID . '&' . $tabControl->ActiveTabParam() .
                    '&lang=' . LANGUAGE_ID);
            elseif ($request->getPost('save_and_add') <> '')
                LocalRedirect($accountsEditPage . '?' . $tabControl->ActiveTabParam() . '&lang=' . LANGUAGE_ID);
        }
    } else {
        if ($accountID > 0) {
            // get ID account
            $arEditAccount = null;
            if (method_exists('s34web\mailSMTPB24\smtpAccountsTable', 'getById')) {
                $arEditAccountRes = s34web\mailSMTPB24\smtpAccountsTable::getById($accountID);
                if ($arEditAccountElem = $arEditAccountRes->fetch()) {
                    $arEditAccount = $arEditAccountElem;
                }
            }
            if (!empty($arEditAccount) && is_array($arEditAccount)) {
                $formFields = [
                    'ACTIVE' => $arEditAccount['ACTIVE'],
                    'NAME' => $arEditAccount['NAME'],
                    'EMAIL' => $arEditAccount['EMAIL'],
                    'SERVER' => $arEditAccount['SERVER'],
                    'PORT' => $arEditAccount['PORT'],
                    'SECURE' => $arEditAccount['SECURE'],
                    'AUTH' => $arEditAccount['AUTH'],
                    'LOGIN' => $arEditAccount['LOGIN'],
                    'PASSWORD' => $arEditAccount['PASSWORD']
                ];
            } else {
                $accountID = 0;
            }
        }
    }
}

// set title
if ($accountID > 0)
    $APPLICATION->SetTitle(Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ADMIN_TITLE',
        ['#ID#' => $accountID]));
else
    $APPLICATION->SetTitle(Loc::getMessage($moduleCode . '_ACCOUNTS_NEW_ADMIN_TITLE'));
// prolog admin after
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
// page content
if ($flagIncludeModule && $flagActiveModule && $flagClassSMTPAccountsExist) {
    // display demo mode text
    if ($moduleStatus == Loader::MODULE_DEMO) {
        CAdminMessage::ShowMessage([
            'TYPE' => 'OK',
            'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_DEMO_TITLE'),
            'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_DEMO_TEXT'),
            'HTML' => true
        ]);
    }
    // show errors
    if ($strError <> '') {
        $e = new CAdminException(array(array('text' => $strError)));
        $message = new CAdminMessage(loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_ERROR_SAVING'), $e);
        echo $message->Show();
    }
    // set menu buttons
    $aMenu = [];
    $aMenu[] = array(
        'TEXT' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MENU_ACCOUNTS_LIST'),
        'LINK' => $accountsPage . '?lang=' . LANGUAGE_ID,
        'ICON' => 'btn_list',
        'TITLE' => Loc::getMessage('_ACCOUNTS_EDIT_MENU_ACCOUNTS_LIST_TITLE'),
    );
    // context menu
    if ($accountID > 0) {
        $aMenu[] = array(
            'TEXT' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MENU_ADD_BUTTON'),
            'LINK' => $accountsEditPage . '?lang=' . LANGUAGE_ID,
            'ICON' => 'btn_new',
            'TITLE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MENU_ADD_BUTTON_TITLE'),
        );
        $aMenu[] = array(
            'TEXT' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MENU_DELETE_BUTTON'),
            'LINK' => 'javascript:if(confirm("' .
                Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MENU_DELETE_RECORD_CONF') . '")) window.location="' .
                $accountsPage . '?action=delete&ID=' . $accountID . '&lang=' . LANGUAGE_ID . '&' .
                bitrix_sessid_get() . ';"',
            'ICON' => 'btn_delete',
            'TITLE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MENU_DELETE_BUTTON_TITLE'),
        );
    }
    $contextMenu = new CAdminContextMenu($aMenu);
    $contextMenu->Show();
    // start form
    if (!empty($tabControl)) {
        $tabControl->BeginEpilogContent();
        ?>
        <?= bitrix_sessid_post() ?>
        <?php
        $tabControl->EndEpilogContent();
        $tabControl->Begin(array(
            'FORM_ACTION' => $APPLICATION->GetCurPage() . '?ID=' . intval($accountID) . '&lang=' . LANGUAGE_ID,
        ));
        $tabControl->BeginNextFormTab();
        $tabControl->BeginCustomField('ACTIVE', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_ACTIVE'));
    }
    ?>
    <tr id="tr_ACTIVE">
        <td class="adm-detail-content-cell-l" style="width: 40%;"><?=
            Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_ACTIVE') ?></td>
        <td class="adm-detail-content-cell-r">
            <input type="checkbox" name="ACTIVE" id="ACTIVE_ACCOUNT_SMTP" class="adm-designed-checkbox" value="Y" <?php
            if (!empty($formFields['ACTIVE']) && $formFields['ACTIVE'] == 'Y') echo " checked" ?>>
            <label class="adm-designed-checkbox-label" for="ACTIVE_ACCOUNT_SMTP" title=""></label>
        </td>
    </tr>
    <?php
    if (!empty($tabControl)) {
        $tabControl->EndCustomField('ACTIVE');
        $tabControl->AddEditField('NAME', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_NAME'), false,
            ['size' => 30, 'maxlength' => 100], (!empty($formFields['NAME'])) ? $formFields['NAME'] : '');
        $tabControl->AddEditField('EMAIL', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_EMAIL'), true,
            ['size' => 30, 'maxlength' => 100], (!empty($formFields['EMAIL'])) ? $formFields['EMAIL'] : '');
        $tabControl->AddEditField('SERVER', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_SERVER'), true,
            ['size' => 30, 'maxlength' => 100], (!empty($formFields['SERVER'])) ? $formFields['SERVER'] : '');
        $tabControl->AddEditField('PORT', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_PORT'), true,
            ['size' => 30, 'maxlength' => 5], (!empty($formFields['PORT'])) ? $formFields['PORT'] : '');
        $tabControl->BeginCustomField('SECURE', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_SECURE'));
    }
    ?>
    <tr id="tr_SECURE">
        <td class="adm-detail-content-cell-l"><label for="SECURE_ACCOUNT_SMTP"><?=
                Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_SECURE') ?></label></td>
        <td class="adm-detail-content-cell-r">
            <select name="SECURE" id="SECURE_ACCOUNT_SMTP">
                <option value="N"<?= (!empty($formFields['SECURE']) && $formFields['SECURE'] == 'N') ?
                    ' selected="selected"' : '' ?>><?=
                    Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_SECURE_NO') ?></option>
                <option value="Y"<?= (!empty($formFields['SECURE']) && $formFields['SECURE'] == 'Y') ?
                    ' selected="selected"' : '' ?>>TLS
                </option>
                <option value="S"<?= (!empty($formFields['SECURE']) && $formFields['SECURE'] == 'S') ?
                    ' selected="selected"' : '' ?>>SSL
                </option>
            </select>
        </td>
    </tr>
    <?php
    if (!empty($tabControl)) {
        $tabControl->EndCustomField('SECURE');
        $tabControl->BeginCustomField('AUTH', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_AUTH'));
    }
    ?>
    <tr id="tr_AUTH">
        <td class="adm-detail-content-cell-l"><label for="AUTH_ACCOUNT_SMTP"><?=
                Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_AUTH') ?></label></td>
        <td class="adm-detail-content-cell-r">
            <select name="AUTH" id="AUTH_ACCOUNT_SMTP">
                <option value="N"<?= (!empty($formFields['AUTH']) && $formFields['AUTH'] == 'N') ?
                    ' selected="selected"' : '' ?>><?=
                    Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_AUTH_NO') ?></option>
                <option value="C"<?= (!empty($formFields['AUTH']) && $formFields['AUTH'] == 'C') ?
                    ' selected="selected"' : '' ?>><?=
                    Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_AUTH_CUSTOM') ?></option>
            </select>
        </td>
    </tr>
    <?php
    if (!empty($tabControl)) {
        $tabControl->EndCustomField('AUTH');
        $tabControl->BeginCustomField('LOGIN', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_LOGIN'));
    }
    ?>
    <tr id="tr_LOGIN">
        <td class="adm-detail-content-cell-l"><label for="AUTH_LOGIN_SMTP"><?=
                Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_LOGIN') ?></label></td>
        <td class="adm-detail-content-cell-r">
            <input type="text" name="LOGIN" id="AUTH_LOGIN_SMTP" size="30" maxlength="100"
                   autocomplete="off" value="<?php echo (!empty($formFields['LOGIN'])) ?
                $formFields['LOGIN'] : '' ?>">
        </td>
    </tr>
    <?php
    $passwordValue = '';
    if (!empty($tabControl)) {
        $tabControl->EndCustomField('LOGIN');
        $tabControl->BeginCustomField('PASSWORD', Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_PASSWORD'));
        if ($strError == '') {
            if (!empty($formFields['PASSWORD'])) {
                $passwordValue = '************';
            }
        } else {
            $passwordValue = $formFields['PASSWORD'];
        }
    }
    ?>
    <tr id="tr_PASSWORD">
        <td class="adm-detail-content-cell-l"><label for="AUTH_PASSWORD_SMTP"><?=
                Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELD_PASSWORD') ?></label></td>
        <td class="adm-detail-content-cell-r">
            <input type="password" name="PASSWORD" id="AUTH_PASSWORD_SMTP" size="30" maxlength="100"
                   autocomplete="off" value="<?= $passwordValue ?>">
        </td>
    </tr>
    <?php
    if (!empty($tabControl)) {
        $tabControl->EndCustomField('PASSWORD');
        $tabControl->Buttons([
            'btnSave' => true,
            'back_url' => $accountsPage . '?lang=' . LANGUAGE_ID,
            'btnSaveAndAdd' => true,
        ]);
        $tabControl->Show();
    }
    // end form
    echo BeginNote(); ?>
    <p>
        <?= Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_FIELDS_HINT') ?>
    </p>
    <?php echo EndNote(); ?>
    <script>
        BX.ready(function () {
            BX.mailSMTPB24Accounts.init();
        });
    </script>
    <?php
} else {
    // display errors
    if (!$flagIncludeModule) {
        // show module error
        if ($moduleStatus == Loader::MODULE_DEMO_EXPIRED) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_ERROR_DEMO_EXPIRED_TEXT'),
                'HTML' => true
            ]);
        }
        if ($moduleStatus == Loader::MODULE_NOT_FOUND) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_ERROR_NOT_FOUND_TEXT'),
                'HTML' => true
            ]);
        }
    } else {
        if (!$flagActiveModule) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_ACTIVE_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_ACTIVE_ERROR_TEXT'),
                'HTML' => true
            ]);
        } elseif (!$flagClassSMTPAccountsExist) {
            CAdminMessage::ShowMessage([
                'TYPE' => 'ERROR',
                'MESSAGE' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_CLASS_SMTP_ERROR_TITLE'),
                'DETAILS' => Loc::getMessage($moduleCode . '_ACCOUNTS_EDIT_MODULE_CLASS_SMTP_ERROR_TEXT'),
                'HTML' => true
            ]);
        }
    }
}
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');

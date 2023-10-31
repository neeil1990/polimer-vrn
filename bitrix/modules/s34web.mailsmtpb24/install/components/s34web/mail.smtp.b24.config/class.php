<?php
/**
 * Created: 20.03.2021, 19:22
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
    Bitrix\Mail,
    Bitrix\Main\Application,
    Bitrix\Main\Loader;

class mailSMTPB24Config extends \CBitrixComponent
{
    /**
     * Function execute the component
     * @return mixed|void|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function executeComponent()
    {
        global $USER, $APPLICATION;

        if (!is_object($USER) || !$USER->isAuthorized()) {
            $APPLICATION->authForm('');
            return;
        }

        // set title
        $APPLICATION->setTitle(Loc::getMessage('MAIL_SMTP_B24_CONFIG_TITLE'));

        if (!Loader::includeModule('mail')) {
            showError(Loc::getMessage('MAIL_SMTP_B24_CONFIG_ERROR_MAIL_MODULE'));
            return;
        }

        $moduleStatus = Loader::includeSharewareModule('s34web.mailsmtpb24');
        if ($moduleStatus == \Bitrix\Main\Loader::MODULE_DEMO_EXPIRED)
        {
            showError(Loc::getMessage('MAIL_SMTP_B24_CONFIG_ERROR_DEMO_EXPIRED_TEXT'));
            return;
        }
        if ($moduleStatus == \Bitrix\Main\Loader::MODULE_NOT_FOUND)
        {
            showError(Loc::getMessage('MAIL_SMTP_B24_CONFIG_ERROR_NOT_FOUND_TEXT'));
            return;
        }


        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $mailID = $request->getQuery('id');

        $mailbox = [];

        if (!empty($mailID)) {
            $mailbox = Mail\MailboxTable::getList(array(
                'filter' => array(
                    '=ID' => $mailID,
                    '=ACTIVE' => 'Y',
                    '=SERVER_TYPE' => 'imap',
                ),
                'select' => ['ID', 'USER_ID', 'NAME', 'USERNAME', 'EMAIL', 'LOGIN']
            ))->fetch();
        }

        // check mail account
        if (empty($mailbox)) {
            showError(Loc::getMessage('MAIL_SMTP_B24_CONFIG_ELEMENT_NOT_FOUND'));
            return;
        }

        if ($USER->getId() != $mailbox['USER_ID'] && !$USER->isAdmin() &&
            !$USER->canDoOperation('bitrix24_config')) {
                showError(Loc::getMessage('MAIL_SMTP_B24_CONFIG_DENIED'));
                return;
        }

        // get email from fields
        foreach ([$mailbox['EMAIL'], $mailbox['NAME'], $mailbox['LOGIN']] as $item) {
            $address = new \Bitrix\Main\Mail\Address($item);
            if ($address->validate()) {
                $mailbox['EMAIL'] = $address->getEmail();
                break;
            }
        }

        $this->arResult['MAILBOX'] = ['ID' => $mailbox['ID'], 'EMAIL' => $mailbox['EMAIL'], 'NAME' => $mailbox['NAME']];

        $mailAccount = \s34web\mailSMTPB24\mailAccountsTable::GetList([
            'order' => ['ID' => 'desc'],
            'filter' => ['MAIL_ID' => $this->arResult['MAILBOX']['ID']]
        ])->fetch();

        if (!empty($mailAccount)) {
            $this->arResult['SMTP'] = $mailAccount;
        }


        $this->includeComponentTemplate();
    }

    /**
     * Function execute the component by ajax
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function executeComponentAjax()
    {
        $result = [];

        global $USER;

        if (!$USER->IsAuthorized()) {
            $result = [
                'status' => 'error',
                'error' => Loc::getMessage('MAIL_SMTP_B24_CONFIG_AUTH_ERROR')
            ];
            echo CUtil::PhpToJsObject($result);
            return;
        }

        if (!Loader::includeModule('mail')) {
            $result = [
                'status' => 'error',
                'error' => Loc::getMessage('MAIL_SMTP_B24_CONFIG_ERROR_MAIL_MODULE')
            ];
            echo CUtil::PhpToJsObject($result);
            return;
        }

        $moduleStatus = Loader::includeSharewareModule('s34web.mailsmtpb24');
        if ($moduleStatus == \Bitrix\Main\Loader::MODULE_DEMO_EXPIRED)
        {
            $result = [
                'status' => 'error',
                'error' => Loc::getMessage('_CONFIG_ERROR_DEMO_EXPIRED_TEXT')
            ];
            echo CUtil::PhpToJsObject($result);
            return;
        }
        if ($moduleStatus == \Bitrix\Main\Loader::MODULE_NOT_FOUND)
        {
            $result = [
                'status' => 'error',
                'error' => Loc::getMessage('_CONFIG_ERROR_NOT_FOUND_TEXT')
            ];
            echo CUtil::PhpToJsObject($result);
            return;
        }

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        // get request params
        $operationSave = $request->getPost('saveSMTPConfig');
        $operationDelete = $request->getPost('deleteSMTPConfig');

        // save send params
        if($operationSave == 'Y') {

            $requestData = [];
            $listData = [
                'MAIL_ID' => ['NAME' => 'mailConfigMailID', 'TYPE' => 'int'],
                'SMTP_MAIL_ID' => ['NAME' => 'mailConfigSMTPMailID', 'TYPE' => 'int'],
                'SERVER' => ['NAME' => 'mailConfigSMTPAddress', 'TYPE' => 'string'],
                'PORT' => ['NAME' => 'mailConfigSMTPPort', 'TYPE' => 'int'],
                'SECURE' => ['NAME' => 'mailConfigSecure', 'TYPE' => 'bool'],
                'AUTH' => ['NAME' => 'mailConfigAuthType', 'TYPE' => 'bool'],
                'LOGIN' => ['NAME' => 'mailConfigLogin', 'TYPE' => 'string'],
                'PASSWORD' => ['NAME' => 'mailConfigPassword', 'TYPE' => 'string'],
            ];

            // get form params
            foreach ($listData as $dataKey => $dataValue) {

                $dataVar = $request->getPost($dataValue['NAME']);

                if ($dataValue['TYPE'] == 'int') {
                    if (isset($dataVar)) {
                        $requestData[$dataKey] = intval($dataVar);
                    }
                }
                if ($dataValue['TYPE'] == 'string' && !empty($dataVar)) {
                    $requestData[$dataKey] = $dataVar;
                }
                if ($dataValue['TYPE'] == 'bool') {
                    if (!empty($dataVar)) {
                        $requestData[$dataKey] = $dataVar;
                    } else {
                        $requestData[$dataKey] = 'N';
                    }
                }
            }
            unset($dataKey, $dataValue);

            $mailbox = [];

            // get mail account params
            if (!empty($requestData['MAIL_ID'])) {
                $mailbox = Mail\MailboxTable::getList(array(
                    'filter' => array(
                        '=ID' => $requestData['MAIL_ID'],
                        '=ACTIVE' => 'Y',
                        '=SERVER_TYPE' => 'imap',
                    ),
                    'select' => ['ID', 'USER_ID', 'NAME', 'USERNAME', 'EMAIL', 'LOGIN', 'PASSWORD']
                ))->fetch();
            }

            if (empty($mailbox)) {
                $result = [
                    'status' => 'error',
                    'error' => Loc::getMessage('SMTPMAIL_MAILCONFIG_ELEMENT_NOT_FOUND')
                ];
                echo CUtil::PhpToJsObject($result);
                return;
            }

            if ($USER->getId() != $mailbox['USER_ID'] && !$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config')) {
                $result = [
                    'status' => 'error',
                    'error' => Loc::getMessage('SMTPMAIL_MAILCONFIG_DENIED')
                ];
                echo CUtil::PhpToJsObject($result);
                return;
            }

            if ($requestData['AUTH'] == 'G') {
                if (!empty($mailbox['LOGIN'])) {
                    $requestData['LOGIN'] = $mailbox['LOGIN'];
                }
                if (!empty($mailbox['PASSWORD'])) {
                    $requestData['PASSWORD'] = $mailbox['PASSWORD'];
                }
            }

            $checkErrors = $this->checkFormData($requestData);

            if (!empty($checkErrors)) {

                $result = [
                    'status' => 'error',
                    'errors' => $checkErrors
                ];
                echo CUtil::PhpToJsObject($result);
                return;
            }

            if (class_exists('\s34web\mailSMTPB24\mailSender')) {
                $checkSMTP = new \s34web\mailSMTPB24\mailSender();
            }

            if (empty($checkSMTP)) {
                $result = [
                    'status' => 'error',
                    'errors' => Loc::getMessage('MAIL_SMTP_B24_CONFIG_ERROR_CLASS_MAILSENDER')
                ];
                echo CUtil::PhpToJsObject($result);
                return;
            }

            // change connection data
            if (method_exists('\s34web\mailSMTPB24\mailSender', 'modifySMTPData')) {
                $checkSMTP->modifySMTPData($requestData);
            }

            // check connection data
            $existSMTP = [];
            $checkData = [];

            if (!empty($requestData['SERVER'])) {
                $checkData['SERVER'] = $requestData['SERVER'];
            }
            if (!empty($requestData['PORT'])) {
                $checkData['PORT'] = $requestData['PORT'];
            }
            if (!empty($requestData['SECURE'])) {
                $checkData['SECURE'] = $requestData['SECURE'];
            }
            if (!empty($requestData['AUTH'])) {
                $checkData['AUTH'] = $requestData['AUTH'];
            }
            if (!empty($requestData['LOGIN'])) {
                $checkData['LOGIN'] = $requestData['LOGIN'];
            }
            if (!empty($requestData['PASSWORD'])) {
                $checkData['PASSWORD'] = $requestData['PASSWORD'];
            }

            $checkData['EMAIL'] = $mailbox['EMAIL'];

            if (!empty($requestData['SMTP_MAIL_ID'])) {
                $existSMTP = \s34web\mailSMTPB24\mailAccountsTable::getById($requestData['SMTP_MAIL_ID'])->fetch();
            }

            if (!empty($existSMTP)) {
                $checkData['EMAIL_ID'] = $existSMTP['ID'];
                if ($requestData['AUTH'] == 'C') {
                    if (!empty($existSMTP['PASSWORD']) && (empty($requestData['PASSWORD']) ||
                            !empty($requestData['PASSWORD']) && $requestData['PASSWORD'] == '************')) {
                        $checkData['PASSWORD'] = $existSMTP['PASSWORD'];
                    }
                }
            }

            $checkConnection = [];

            if (method_exists('\s34web\mailSMTPB24\mailSender', 'checkConnect')) {
                $checkSMTP->typeEmail = 'mail_smtp';
                $checkConnection = $checkSMTP->checkConnect($checkData);
            }

            if (!empty($checkConnection)) {
                if ($checkConnection['status'] == 'error') {
                    $result = [
                        'status' => 'error',
                        'error' => $checkConnection['error']
                    ];
                    echo CUtil::PhpToJsObject($result);
                    return;
                }
            }

            $updateFields = [
                'EMAIL' => $mailbox['EMAIL'],
                'SERVER' => $requestData['SERVER'],
                'PORT' => $requestData['PORT']
            ];

            if (!empty($requestData['SECURE'])) {
                $updateFields['SECURE'] = $requestData['SECURE'];
            }
            if (!empty($requestData['AUTH'])) {
                $updateFields['AUTH'] = $requestData['AUTH'];
            }
            if (!empty($requestData['LOGIN'])) {
                if ($requestData['AUTH'] == 'C') {
                    $updateFields['LOGIN'] = $requestData['LOGIN'];
                } else {
                    $updateFields['LOGIN'] = '';
                }
            }
            if (!empty($requestData['PASSWORD'])) {
                if ($requestData['AUTH'] == 'C') {
                    $updateFields['PASSWORD'] = $requestData['PASSWORD'];
                } else {
                    $updateFields['PASSWORD'] = '';
                }
            }

            $updateFields['DATE_CREATE_LOG'] = new \Bitrix\Main\Type\Date();

            // update or add connection data for email account
            if (!empty($existSMTP)) {
                if (!empty($requestData['PASSWORD']) && $requestData['PASSWORD'] == '************') {
                    unset($updateFields['PASSWORD']);
                }
                $changeMailAccountResult = \s34web\mailSMTPB24\mailAccountsTable::update(
                    $requestData['SMTP_MAIL_ID'],
                    $updateFields
                );
            } else {
                $updateFields['MAIL_ID'] = $mailbox['ID'];
                $changeMailAccountResult = \s34web\mailSMTPB24\mailAccountsTable::add($updateFields);
                $existSMTP['ID'] = $changeMailAccountResult->getId();
            }
            if (!$changeMailAccountResult->isSuccess()) {
                $result = [
                    'status' => 'error',
                    'error' => $changeMailAccountResult->getErrors()
                ];
                echo CUtil::PhpToJsObject($result);
                return;
            }


            $result['status'] = 'done';
            $result['smtp_mail_id'] = $existSMTP['ID'];
        }

        // delete send params
        if($operationDelete == 'Y')
        {
            $smtpMailID = $request->getPost('mailConfigSMTPMailID');
            if(!empty($smtpMailID))
            {
                $deleteMailAccountResult = \s34web\mailSMTPB24\mailAccountsTable::delete($smtpMailID);
                if (!$deleteMailAccountResult->isSuccess()) {
                    $result = [
                        'status' => 'error',
                        'error' => $deleteMailAccountResult->getErrors()
                    ];
                    echo CUtil::PhpToJsObject($result);
                    return;
                }
                else
                {
                    $result['status'] = 'done';
                    $result['smtp_mail_id'] = 0;
                }
            }
            else
            {
                $result = [
                    'status' => 'error',
                    'error' => Loc::getMessage('MAIL_SMTP_B24_CONFIG_ERROR_MAIL_ID')
                ];
                echo CUtil::PhpToJsObject($result);
                return;
            }
        }

        echo CUtil::PhpToJsObject($result);
    }


    /**
     * Function check connection data
     * @param $requestData
     * @return array
     */
    private function checkFormData(&$requestData)
    {
        $formErrors = [];

        // check server address
        if (empty($requestData['SERVER'])) {
            $formErrors['SERVER'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_SERVER_BAD');
        } else {
            $regex = '/^(?:(?:http|https|ssl|tls|smtp):\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)$/i';
            if (!preg_match($regex, $requestData['SERVER'], $matches) || empty($matches[1])) {
                $formErrors['SERVER'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_SERVER_BAD_FORMAT');
            }

            if (preg_match('#^(tls)://#', $requestData['SERVER'])) {
                $requestData['SECURE'] = 'Y';
            }
            if (preg_match('#^(ssl)://#', $requestData['SERVER'])) {
                $requestData['SECURE'] = 'S';
            }

            $requestData['SERVER'] = $matches[1];
        }
        // check server port
        if (isset($requestData['PORT'])) {
            if (intval($requestData['PORT']) <= 0 || intval($requestData['PORT']) > 65535) {
                $formErrors['PORT'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_PORT_BAD');
            }
        } else {
            $formErrors['PORT'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_PORT_BAD');
        }

        // check server login & password
        if ($requestData['AUTH'] == 'C') {
            if (empty($requestData['LOGIN'])) {
                $formErrors['LOGIN'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_LOGIN_BAD');
            }
            if (empty($requestData['PASSWORD'])) {
                $formErrors['PASSWORD'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_PASS_BAD');
            }
        }
        // check server password
        if ($requestData['AUTH'] == 'C' && !empty($requestData['PASSWORD'])) {
            if (preg_match('/^\^/', $requestData['PASSWORD'])) {
                $formErrors['PASSWORD'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_PASS_BAD_CARET');
            } else if (preg_match('/\x00/', $requestData['PASSWORD'])) {
                $formErrors['PASSWORD'] = Loc::getMessage('MAIL_SMTP_B24_CONFIG_SMTP_PASS_BAD_NULL');
            }
        }

        return $formErrors;
    }
}
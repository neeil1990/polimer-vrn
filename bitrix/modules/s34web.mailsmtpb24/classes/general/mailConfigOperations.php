<?php
/**
 * Created: 22.10.2021, 16:02
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class mailConfigOperations
{
    /**
     * Check SMTP connect on mail config form
     * @param $params
     * @return array
     */
    public static function checkSMTPConnect($params)
    {
        $resultOperation['status'] = 'failure';
        // get moduleID & params for delete
        $moduleID = '';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getModuleID')) {
            $moduleID = \s34web\mailSMTPB24\mainSettings::getModuleID();
        }
        if (empty($moduleID)) {
            $resultOperation['error'] = Loc::getMessage('S34WEB_MAILSMTPB24_ERROR_MODULE');
            return $resultOperation;
        }
        $moduleCode = strtoupper(str_replace('.', '_', $moduleID));
        // check params
        if (empty($params)) {
            $resultOperation['error'] = Loc::getMessage($moduleCode . '_ERROR_EMPTY_PARAMS');
            return $resultOperation;
        }
        // check use_smtp
        if (empty($params['USE_SMTP']) || $params['USE_SMTP'] != 'Y') {
            $resultOperation['error'] = Loc::getMessage($moduleCode . '_ERROR_USE_SMTP');
            return $resultOperation;
        }
        // get mail services & other service
        $mailServices = [];
        $otherMailServiceIDs = [];
        if (method_exists('\s34web\mailSMTPB24\mailConfigOperations', 'getMailServices')) {
            $mailServices = \s34web\mailSMTPB24\mailConfigOperations::getMailServices();
        }
        if (!empty($mailServices)) {
            foreach ($mailServices as $serviceKey => $serviceValue) {
                if (empty($serviceValue['SERVER'])) {
                    $otherMailServiceIDs[] = $serviceKey;
                }
            }
        }
        // check empty params
        if (!empty($params['SERVICE_ID']) && !empty($otherMailServiceIDs) &&
            in_array(intval($params['SERVICE_ID']), $otherMailServiceIDs)) {
            $fieldErrors = [];
            if (empty($params['SERVER'])) {
                $fieldErrors[] = Loc::getMessage($moduleCode . '_ERROR_SMTP_SERVER');
            }
            if (empty($params['PORT'])) {
                $fieldErrors[] = Loc::getMessage($moduleCode . '_ERROR_SMTP_PORT');
            }
            if (empty($params['LOGIN'])) {
                $fieldErrors[] = Loc::getMessage($moduleCode . '_ERROR_SMTP_LOGIN');
            }
            if (empty($params['PASSWORD'])) {
                $fieldErrors[] = Loc::getMessage($moduleCode . '_ERROR_SMTP_PASSWORD');
            }
            if (!empty($fieldErrors)) {
                $resultOperation['error'] = implode(', ', $fieldErrors);
                return $resultOperation;
            }
        }
        $smtpEmailID = 0;
        // get password for update mailbox
        if (!empty($params['MAILBOX_ID']) && !empty($params['EMAIL'])) {
            $resSMTP = \Bitrix\Main\Mail\Internal\SenderTable::getList([
                'filter' => ['IS_CONFIRMED' => true, '=EMAIL' => $params['EMAIL']],
                'order' => ['ID' => 'DESC']
            ]);
            if ($smtpAccount = $resSMTP->fetch()) {
                if (!empty($smtpAccount['ID'])) {
                    $smtpEmailID = $smtpAccount['ID'];
                }
                if (!empty($params['PASSWORD_PLACEHOLDER']) && !empty($params['PASSWORD']) &&
                    $params['PASSWORD'] == $params['PASSWORD_PLACEHOLDER']) {
                    if (!empty($smtpAccount['OPTIONS']['smtp']['password'])) {
                        $params['PASSWORD'] = $smtpAccount['OPTIONS']['smtp']['password'];
                    }
                }
                if (!empty($params['SERVICE_ID']) && !in_array(intval($params['SERVICE_ID']), $otherMailServiceIDs)) {
                    if (!empty($smtpAccount['OPTIONS']['smtp']['server'])) {
                        $params['SERVER'] = $smtpAccount['OPTIONS']['smtp']['server'];
                    }
                    if (!empty($smtpAccount['OPTIONS']['smtp']['port'])) {
                        $params['PORT'] = $smtpAccount['OPTIONS']['smtp']['port'];
                    }
                    if (!empty($smtpAccount['OPTIONS']['smtp']['protocol'])) {
                        if ($smtpAccount['OPTIONS']['smtp']['protocol'] == 'smtp') {
                            $params['SECURE'] = 'Y';
                        }
                        if ($smtpAccount['OPTIONS']['smtp']['protocol'] == 'smtps') {
                            $params['SECURE'] = 'S';
                        }
                    }
                    if (!empty($smtpAccount['OPTIONS']['smtp']['login'])) {
                        $params['LOGIN'] = $smtpAccount['OPTIONS']['smtp']['login'];
                    }
                    if (!empty($smtpAccount['OPTIONS']['smtp']['password'])) {
                        $params['PASSWORD'] = $smtpAccount['OPTIONS']['smtp']['password'];
                    }
                }
            }
        }
        // check error params
        $checkErrors = self::checkFormData($moduleCode, $params);
        if (!empty($checkErrors)) {
            $errorList = [];
            foreach ($checkErrors as $errorKey => $errorValue) {
                $errorList[] = $errorValue;
            }
            if (!empty($errorList)) {
                $resultOperation['error'] = implode(', ', $checkErrors);
            }
            return $resultOperation;
        }
        // change params
        self::modifySMTPData($params);
        //check mailSender class
        $checkSMTP = null;
        if (class_exists('\s34web\mailSMTPB24\mailSender')) {
            $checkSMTP = new \s34web\mailSMTPB24\mailSender();
        }
        if (empty($checkSMTP)) {
            $resultOperation['error'] = Loc::getMessage($moduleCode . '_ERROR_CLASS_MAILSENDER');
            return $resultOperation;
        }
        // get connection params
        $checkData = [];
        if (!empty($params['EMAIL'])) {
            $checkData['EMAIL'] = $params['EMAIL'];
        }
        if (!empty($params['SERVER'])) {
            $checkData['SERVER'] = $params['SERVER'];
        }
        if (!empty($params['PORT'])) {
            $checkData['PORT'] = $params['PORT'];
        }
        if (!empty($params['SECURE'])) {
            $checkData['SECURE'] = $params['SECURE'];
        }
        if (!empty($params['AUTH'])) {
            $checkData['AUTH'] = $params['AUTH'];
        }
        if (!empty($params['LOGIN'])) {
            $checkData['LOGIN'] = $params['LOGIN'];
        }
        if (!empty($params['PASSWORD'])) {
            $checkData['PASSWORD'] = $params['PASSWORD'];
        }
        if (!empty($smtpEmailID)) {
            $checkData['EMAIL_ID'] = $smtpEmailID;
        }
        // check SMTP connection
        $checkConnection = null;
        if (method_exists('\s34web\mailSMTPB24\mailSender', 'checkConnect')) {
            $checkSMTP->typeEmail = 'users_smtp';
            $checkConnection = $checkSMTP->checkConnect($checkData);
        }
        // show send result
        if (!empty($checkConnection)) {
            if (!empty($checkConnection['status']) && $checkConnection['status'] == 'error') {
                $resultOperation['error'] = $checkConnection['error'];
                return $resultOperation;
            } else {
                $resultOperation['status'] = 'success';
            }
        } else {
            $resultOperation['error'] = Loc::getMessage($moduleCode . '_ERROR_CONNECTION_DATA');
        }
        return $resultOperation;
    }

    /**
     * Function check connection data
     * @param $requestData
     * @return array
     */
    private static function checkFormData($moduleCode, &$requestData)
    {
        $formErrors = [];
        // check server address
        if (!empty($requestData['SERVER'])) {
            $regex = '/^(?:(?:http|https|ssl|tls|smtp):\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)$/i';
            if (!preg_match($regex, $requestData['SERVER'], $matches) || empty($matches[1])) {
                $formErrors['SERVER'] = Loc::getMessage($moduleCode . '_ERROR_SMTP_SERVER_BAD');
            }

            if (preg_match('#^(tls)://#', $requestData['SERVER'])) {
                $requestData['SECURE'] = 'Y';
            }
            if (preg_match('#^(ssl)://#', $requestData['SERVER'])) {
                $requestData['SECURE'] = 'S';
            }
            if (!empty($matches[1])) {
                $requestData['SERVER'] = $matches[1];
            }
        }
        // check server port
        if (isset($requestData['PORT'])) {
            if (intval($requestData['PORT']) <= 0 || intval($requestData['PORT']) > 65535) {
                $formErrors['PORT'] = Loc::getMessage($moduleCode . '_ERROR_SMTP_PORT_BAD');
            }
        }
        // check server password
        if (!empty($requestData['PASSWORD'])) {
            if (!isset($params['PASSWORD_PLACEHOLDER']) || (!empty($params['PASSWORD_PLACEHOLDER']) &&
                    $requestData['PASSWORD'] != $requestData['PASSWORD_PLACEHOLDER'])) {
                if (preg_match('/^\^/', $requestData['PASSWORD'])) {
                    $formErrors['PASSWORD'] = Loc::getMessage($moduleCode . '_ERROR_PASS_BAD_CARET');
                } else if (preg_match('/\x00/', $requestData['PASSWORD'])) {
                    $formErrors['PASSWORD'] = Loc::getMessage($moduleCode . '_ERROR_PASS_BAD_NULL');
                }
            }
        }
        return $formErrors;
    }

    /**
     * Function modify data
     * @param $connectionData
     */
    private static function modifySMTPData(&$connectionData)
    {
        if (empty($connectionData['SECURE'])) {
            $connectionData['SECURE'] = 'Y';
        }
        if (empty($connectionData['AUTH'])) {
            $connectionData['AUTH'] = 'C';
        }
    }

    /**
     * Get Mailbox services & cache
     * @return array|mixed
     */
    public static function getMailServices()
    {
        $servicesList = [];
        if (!\Bitrix\Main\Loader::includeModule('mail')) {
            return $servicesList;
        }
        $moduleID = '';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getModuleID')) {
            $moduleID = \s34web\mailSMTPB24\mainSettings::getModuleID();
        }
        if (empty($moduleID)) {
            return $servicesList;
        }
        $moduleLowerCode = strtolower(str_replace(".", "_", $moduleID));
        // cache mail services
        if (class_exists('\Bitrix\Mail\MailServicesTable')) {
            $cache = \Bitrix\Main\Data\Cache::createInstance();
            if ($cache->initCache(86400, 'cache_' . $moduleLowerCode . '_mail_services', $moduleLowerCode)) {
                $vars = $cache->getVars();
                if (!empty($vars['MAIL_SERVICES'])) {
                    $servicesList = $vars['MAIL_SERVICES'];
                }

            } elseif ($cache->startDataCache()) {
                $servicesRes = \Bitrix\Mail\MailServicesTable::getList(array(
                    'filter' => array(
                        'ACTIVE' => 'Y',
                        'SERVICE_TYPE' => 'imap',
                    ),
                ));
                while ($service = $servicesRes->fetch()) {
                    $servicesList[$service['ID']] = $service;
                }
                $cache->endDataCache(['MAIL_SERVICES' => $servicesList]);
            }
        }
        return $servicesList;
    }
}

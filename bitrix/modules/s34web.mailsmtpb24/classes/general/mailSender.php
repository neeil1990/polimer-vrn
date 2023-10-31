<?php
/**
 * Created: 04.08.2021, 11:42
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class mailSender
{
    public $moduleID;
    public $moduleCode;
    public $fromEmail;
    public $fromEmailID;
    public $typeEmail;
    public $checkSend;
    public $emailSenderPosting;
    public $emailSenderPostingEmail;
    public $emailSenderPostingName;

    /**
     * mailSender constructor.
     */
    function __construct()
    {
        $this->moduleID = '';
        if (method_exists('\s34web\mailSMTPB24\mainSettings', 'getModuleID')) {
            $this->moduleID = \s34web\mailSMTPB24\mainSettings::getModuleID();
        }
        if (empty($this->moduleID)) {
            $this->moduleID = 's34web.mailsmtpb24';
        }
        $this->moduleCode = strtoupper(str_replace('.', '_', $this->moduleID));
        $this->checkSend = false;
        // sender email posting
        $this->emailSenderPosting = false;
        $this->emailSenderPostingEmail = '';
        $this->emailSenderPostingName = '';
    }

    /**
     * Send Email by module
     * @param $to
     * @param $subject
     * @param $message
     * @param string $additional_headers
     * @param string $additional_parameters
     * @param \Bitrix\Main\Mail\Context|null $context
     * @return bool
     * @throws Exception
     */
    public function sendSMTPMail($to, $subject, $message, $additional_headers = '',
                                 $additional_parameters = '', \Bitrix\Main\Mail\Context $context = null)
    {
        $errorFlag = false;
        $errorsList = [];
        $emailFromLog = '';
        $smtpMailAccount = []; // selected smtp account
        // check module license
        $moduleStatus = \Bitrix\Main\Loader::includeSharewareModule($this->moduleID);
        if ($moduleStatus == \Bitrix\Main\Loader::MODULE_DEMO_EXPIRED ||
            $moduleStatus == \Bitrix\Main\Loader::MODULE_NOT_FOUND) {
            $logDir = pathinfo(pathinfo(dirname(__FILE__))['dirname'])['dirname'];
            if (!\Bitrix\Main\IO\Directory::isDirectoryExists($logDir . '/logs')) {
                \Bitrix\Main\IO\Directory::createDirectory($logDir . '/logs');
            }
            $fileLogName = $logDir . '/logs/send_system_0.log';

            file_put_contents($fileLogName,
                "\n" . "[ " . Loc::getMessage('S34WEB_MAILSMTPB24_LOG_TEXT_SYSTEM_ERROR') . ", " .
                Loc::getMessage('S34WEB_MAILSMTPB24_LOG_TEXT_DATETIME_SEND') .
                date("d.m.Y H:i:s") . " ]" . "\n" .
                "----------------------------------------------------------------------------" . "\n" .
                Loc::getMessage('S34WEB_MAILSMTPB24_ERROR_ACTIVE_LICENSE') . "\n" .
                "----------------------------------------------------------------------------" . "\n",
                FILE_APPEND | LOCK_EX);

            return false;
        }
        // get language ID for set lang for PHPMailer
        $langID = \Bitrix\Main\Application::getInstance()->getContext()->getLanguage();
        // check error flag

        if (!class_exists('\s34web\mailSMTPB24\mailParser')) {
            $errorsList[] = Loc::getMessage($this->moduleCode .
                '_ERROR_CLASS_MAILPARSER');
            $errorFlag = true;
        }

        // create mailParser
        $smtpParser = null;
        if (!$errorFlag) {
            if (class_exists('\s34web\mailSMTPB24\mailParser')) {
                $smtpParser = new \s34web\mailSMTPB24\mailParser($to, $subject, $message, $additional_headers,
                    $additional_parameters);
            }
            if (empty($smtpParser)) {
                $errorsList[] = Loc::getMessage($this->moduleCode .
                    '_EMPTY_CLASS_MAILPARSER');
                $errorFlag = true;
            }
            if (!$errorFlag) {
                if (!empty($smtpParser->emailFrom['address'])) {
                    $emailFromLog = $smtpParser->emailFrom['address'];
                } else {
                    $errorsList[] = Loc::getMessage($this->moduleCode .
                        '_EMPTY_EMAIL_FROM');
                    $errorFlag = true;
                }
                if (empty($smtpParser->emailTo)) {
                    $errorsList[] = Loc::getMessage($this->moduleCode .
                        '_EMPTY_EMAIL_TO');
                    $errorFlag = true;
                }
                // check parsed flag sender posting
                if ($smtpParser->emailSenderPosting) {
                    $this->emailSenderPosting = true;
                    if (!empty($smtpParser->emailFrom['address'])) {
                        $this->emailSenderPostingEmail = $smtpParser->emailFrom['address'];
                    }
                    if (!empty($smtpParser->emailFrom['name'])) {
                        $this->emailSenderPostingName = $smtpParser->emailFrom['name'];
                    }
                }
            }
        }

        // check error flag
        if (!$errorFlag) {
            $accountPriority = Option::get($this->moduleID, 'selection_priority',
                '1');
            if ($accountPriority == 2) {
                // admin smtp
                $smtpAdminAccounts = $this->getAdminSMTPAccounts();
                if (!empty($smtpAdminAccounts)) {
                    if (!empty($smtpAdminAccounts[$smtpParser->emailFrom['address']])) {
                        $smtpMailAccount = $smtpAdminAccounts[$smtpParser->emailFrom['address']];
                        $this->fromEmail = $smtpParser->emailFrom['address'];
                        $this->fromEmailID = $smtpMailAccount['EMAIL_ID'];
                        $this->typeEmail = 'admin_smtp';
                    }
                }
            }
            // users smtp
            if (empty($smtpMailAccount)) {
                $smtpUsersAccounts = $this->getUsersSMTPAccounts();
                if (!empty($smtpUsersAccounts)) {
                    if (!empty($smtpUsersAccounts[$smtpParser->emailFrom['address']])) {
                        $smtpMailAccount = $smtpUsersAccounts[$smtpParser->emailFrom['address']];
                        $this->fromEmail = $smtpParser->emailFrom['address'];
                        $this->fromEmailID = $smtpMailAccount['EMAIL_ID'];
                        $this->typeEmail = 'users_smtp';
                    }
                }
            }
            // admin smtp
            if ($accountPriority == 1) {
                if (empty($smtpMailAccount)) {
                    $smtpAdminAccounts = $this->getAdminSMTPAccounts();
                    if (!empty($smtpAdminAccounts)) {
                        if (!empty($smtpAdminAccounts[$smtpParser->emailFrom['address']])) {
                            $smtpMailAccount = $smtpAdminAccounts[$smtpParser->emailFrom['address']];
                            $this->fromEmail = $smtpParser->emailFrom['address'];
                            $this->fromEmailID = $smtpMailAccount['EMAIL_ID'];
                            $this->typeEmail = 'admin_smtp';
                        }
                    }
                }
            }
        }
        // check smtp account
        if (empty($smtpMailAccount)) {
            $errorsList[] = Loc::getMessage($this->moduleCode .
                    '_EMPTY_SMTP_ACCOUNT_SEND_FROM') .
                $emailFromLog;
            $errorFlag = true;
        }
        // set smtp params
        $smtpParamSend = [];
        if (!$errorFlag) {
            $smtpParamSend = $smtpMailAccount;
        }
        // check smtp params
        if (empty($smtpParamSend)) {
            $errorsList[] = 'Error SMTP Account parameters';
            $errorFlag = true;
        }
        // check class PHPMailer
        if (!class_exists('\s34web\mailSMTPB24\PHPMailer')) {
            $errorsList[] = Loc::getMessage($this->moduleCode .
                '_ERROR_CLASS_PHPMAILER');
            $errorFlag = true;
        }
        // create new PHPMailer
        $smtpMail = null;
        if (class_exists('\s34web\mailSMTPB24\PHPMailer')) {
            $smtpMail = new \s34web\mailSMTPB24\PHPMailer();
        }
        if (empty($smtpMail)) {
            $errorsList[] = Loc::getMessage($this->moduleCode .
                '_EMPTY_CLASS_PHPMAILER');
            $errorFlag = true;
        }
        // check error flag
        if (!$errorFlag) {

            // modify connection data
            $this->modifySMTPData($smtpParamSend);
            // set main smtp params
            $smtpMail->isSMTP();
            $smtpMail->setLanguage($langID);
            $smtpMail->SMTPAutoTLS = false;
            // set auth
            if ($smtpParamSend['AUTH'] == 'C' || $smtpParamSend['AUTH'] == 'G') {
                $smtpMail->SMTPAuth = true;
            } else {
                $smtpMail->SMTPAuth = false;
            }
            // set debug
            $smtpMail->SMTPDebug = Option::get($this->moduleID, 'log_level', '2');
            $smtpMail->Debugoutput = function ($str, $level) {
                $this->logSend($str);
            };
            // set host & port
            $smtpMail->Host = $smtpParamSend['HOST'];
            $smtpMail->Port = $smtpParamSend['PORT'];
            // SMTP SSL port & secure for connection
            if ($smtpParamSend['SECURE'] == 'Y') {
                $smtpMail->SMTPSecure = 'tls';
            }
            if ($smtpParamSend['SECURE'] == 'S') {
                $smtpMail->SMTPSecure = 'ssl';

                $smtpMail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ),
                );
            }
            // set connection timeout
            $smtpMail->Timeout = Option::get($this->moduleID, "smtp_timeout", 30);
            // set username & password
            $smtpMail->Username = $smtpParamSend['LOGIN'];
            $smtpMail->Password = $smtpParamSend['PASSWORD'];
            // get from & to address
            foreach ($smtpParser->emailTo as $addr)
                $smtpMail->addAddress($addr['address'], $addr['name']);
            // get name from
            $nameEmailFrom = '';
            if (!empty($smtpParser->emailFrom['name'])) {
                $nameEmailFrom = $smtpParser->emailFrom['name'];
            }
            if (empty($nameEmailFrom)) {
                $nameEmailFrom = $smtpParamSend['NAME'];
            }
            // check main mail options
            if (\Bitrix\Main\Config\Option::get('main', 'track_outgoing_emails_read', 'N') == 'Y' &&
                !$smtpParser->emailHtmlContent) {
                // set html content
                $smtpParser->emailHtmlContent = true;
            }

            // check boundary for set text body if not exist attachments
            if (empty($smtpParser->boundary)) {
                $smtpMail->AltBody = $smtpMail->html2text($message);
            }
            // check body & html on message & set tags
            if ($smtpParser->emailHtmlContent) {
                $flagHTMLMessage = false;
                $flagBodyMessage = false;
                if (preg_match("/\<html.*\>(.*?)\<\/html\>/si", $message)) {
                    $flagHTMLMessage = true;
                }
                if (preg_match("/\<body.*\>(.*?)\<\/body\>/si", $message)) {
                    $flagBodyMessage = true;
                }
                if (!$flagBodyMessage) {
                    $message = '<body>' . "\r\n" . $message . "\r\n" . '</body>';
                }
                if (!$flagHTMLMessage) {
                    $message = '<html>' . "\r\n" . $message . "\r\n" . '</html>';
                }
            }
            // set content type body & charset from header & body
            $smtpMail->isHTML($smtpParser->emailHtmlContent); // get from body
            $smtpMail->CharSet = $smtpParser->emailCharSet; // get from header
            // set other send params
            $smtpMail->Subject = $subject;
            $smtpMail->Body = $message;
            $smtpMail->From = $smtpParser->emailFrom['address'];
            $smtpMail->FromName = $nameEmailFrom;

            // BCC
            if (!empty($smtpParser->emailBcc)) {
                foreach ($smtpParser->emailBcc as $bcc) {
                    $smtpMail->addBCC($bcc['address'], $bcc['name']);
                }
                unset($bcc);
            }
            // Reply-To
            if (!empty($smtpParser->emailReplyTo)) {
                $smtpMail->addReplyTo($smtpParser->emailReplyTo['address'], $smtpParser->emailReplyTo['name']);
            }
            // Cc
            if (!empty($smtpParser->emailCc)) {
                foreach ($smtpParser->emailCc as $Cc) {
                    $smtpMail->addCC($Cc['address'], $Cc['name']);
                }
                unset($Cc);
            }
            // Content-Type
            $smtpMail->ContentType = $smtpParser->emailContentType;
            // set Additional Headers
            $arAdditionalHeaders = explode("\n", $additional_headers);
            if (is_array($arAdditionalHeaders)) {
                foreach ($arAdditionalHeaders as $addHeader) {
                    $arAdditionalHeaders_ = explode(':', $addHeader);
                    if (strtolower(trim($arAdditionalHeaders_[0])) !== 'bcc' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'cc' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'reply-to' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'content-type' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'content-transfer-encoding' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'from' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'date' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'mime-version' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'message-id' &&
                        strtolower(trim($arAdditionalHeaders_[0])) !== 'x-sender-posting') {
                        $smtpMail->AddCustomHeader($arAdditionalHeaders_[0], $arAdditionalHeaders_[1]);
                    }
                }
                unset($addHeader);
            }
            // add to log string with email & debug params
            $this->logSend("\n" . '[ ' .
                Loc::getMessage($this->moduleCode . '_LOG_TEXT_SMTP_ACCOUNT') .
                $this->fromEmail . ', ' .
                Loc::getMessage($this->moduleCode . '_LOG_TEXT_DEBUG_LEVEL') .
                $smtpMail->SMTPDebug . ', ' .
                Loc::getMessage($this->moduleCode . '_LOG_TEXT_DATETIME_SEND') .
                date("d.m.Y H:i:s") . ' ]' . "\n");
            $this->logSend("----------------------------------------------------------------------------" . "\n");
            //dump send params (not secure!!!)
            //$this->logSend("\n" . print_r($smtpParamSend, true) . "\n");
            // send email
            if ($sendRes = $smtpMail->send()) {
                $this->logSend("Successfully sent!" . "\n");
            } else {
                $errorsList[] = $smtpMail->ErrorInfo;
                $this->logSend("\n" . implode(", ", $errorsList) . "\n");
            }
            $this->logSend("----------------------------------------------------------------------------" . "\n");
            // return result
            if (!$sendRes) {
                return false;
            } else {
                return true;
            }
        } else {
            // add to log string with error email send
            $this->logSend("\n" . '[ ' .
                Loc::getMessage($this->moduleCode . '_LOG_TEXT_SYSTEM_ERROR') . ', ' .
                Loc::getMessage($this->moduleCode . '_LOG_TEXT_DATETIME_SEND') .
                date("d.m.Y H:i:s") .
                ' ]' . "\n");
            $this->logSend("----------------------------------------------------------------------------" . "\n");
            $this->logSend(implode(", ", $errorsList) . "\n");
            $this->logSend("----------------------------------------------------------------------------" . "\n");
            return false;
        }
        return false;
    }

    /**
     * Check connect to server
     * @param $connectionData
     * @return array|string[]
     */
    public function checkConnect($connectionData)
    {
        $connectionStatus = [
            'status' => 'error',
            'error' => ''
        ];

        $smtpEmail = '';
        $smtpEmailID = 0;
        $smtpHost = '';
        $smtpPort = '';
        $smtpAuth = false;
        $smtpLogin = '';
        $smtpPassword = '';
        $smtpSecure = 'N';

        $this->checkSend = true;

        $smtpMail = null;
        if (class_exists('\s34web\mailSMTPB24\SMTP')) {
            $smtpMail = new \s34web\mailSMTPB24\SMTP;
        }

        if (empty($smtpMail)) {
            $connectionStatus = ['status' => 'error', 'error' =>
                Loc::getMessage($this->moduleCode . '_EMPTY_CLASS_SMTP')];
            return $connectionStatus;
        }

        // get global
        $langID = \Bitrix\Main\Application::getInstance()->getContext()->getLanguage();
        $smtpTimeOut = Option::get($this->moduleID, 'smtp_timeout', 10);
        // modify connection
        if ($connectionData['PORT'] == '465') {
            $connectionData['SERVER'] = 'ssl://' . $connectionData['SERVER'];
        }
        // get connection params
        if (!empty($connectionData['SERVER'])) {
            $smtpHost = $connectionData['SERVER'];
        }
        if (!empty($connectionData['PORT'])) {
            $smtpPort = $connectionData['PORT'];
        }
        if (!empty($connectionData['SECURE'])) {
            $smtpSecure = $connectionData['SECURE'];
        }
        if (!empty($connectionData['LOGIN'])) {
            $smtpLogin = $connectionData['LOGIN'];
        }
        if (!empty($connectionData['PASSWORD'])) {
            $smtpPassword = $connectionData['PASSWORD'];
        }
        if (!empty($connectionData['AUTH']) && ($connectionData['AUTH'] == 'C' || $connectionData['AUTH'] == 'G')) {
            $smtpAuth = true;
        }
        if (!empty($connectionData['EMAIL'])) {
            $smtpEmail = $connectionData['EMAIL'];
        }
        if (!empty($connectionData['EMAIL_ID'])) {
            $smtpEmailID = $connectionData['EMAIL_ID'];
        }
        // set email params
        $this->fromEmail = $smtpEmail;
        $this->fromEmailID = $smtpEmailID;
        if (empty($this->fromEmail)) {
            $this->fromEmail = 'new';
        }
        // set debug param
        $smtpMail->do_debug = Option::get($this->moduleID, 'log_level', 2);
        $smtpMail->Debugoutput = function ($str, $level) {
            $this->logSend($str);
        };
        $stopFlag = false;
        // add to log string with email & debug level
        $this->logSend("\n" . '[ ' .
            Loc::getMessage($this->moduleCode . '_LOG_TEXT_SMTP_ACCOUNT') .
            $this->fromEmail . ', ' .
            Loc::getMessage($this->moduleCode . '_LOG_TEXT_DEBUG_LEVEL') .
            $smtpMail->do_debug . ', ' . Loc::getMessage($this->moduleCode .
                '_LOG_TEXT_DATETIME_SEND') .
            date("d.m.Y H:i:s") . ' ]' . "\n");
        $this->logSend("----------------------------------------------------------------------------" . "\n");
        //dump connection params (not secure!!!)
        //$this->logSend("\n" . print_r($connectionData, true) . "\n");
        // try SMTP connect
        try {
            //Connect to SMTP server
            if (!$smtpMail->connect($smtpHost, $smtpPort, $smtpTimeOut)) {
                $connectionStatus = ['status' => 'error', 'error' =>
                    Loc::getMessage($this->moduleCode . '_SMTP_CONNECTION_ERROR')];
                $stopFlag = true;
            }
            if (!$stopFlag) {
                //Say hello
                if (!$smtpMail->hello(gethostname())) {
                    $connectionStatus = ['status' => 'error', 'error' =>
                        Loc::getMessage($this->moduleCode . '_SMTP_EHLO_ERROR')];
                    $stopFlag = true;
                }
            }
            $extList = [];
            if (!$stopFlag) {
                //Get the list of ESMTP services the server offers
                $extList = $smtpMail->getServerExtList();
                if ($smtpSecure == 'Y') {
                    $setTLS = $smtpMail->startTLS();
                    if (!$setTLS) {
                        $connectionStatus = ['status' => 'error', 'error' =>
                            Loc::getMessage($this->moduleCode . '_SMTP_TLS_ERROR')];
                        $stopFlag = true;
                    }
                }
            }
            if (!$stopFlag) {
                if ($smtpPort == '465' && $smtpSecure == 'N') {
                    $connectionStatus = ['status' => 'error', 'error' =>
                        Loc::getMessage($this->moduleCode . '_SMTP_SSL_ERROR')];
                    $stopFlag = true;
                }
            }
            if (!$stopFlag) {
                //If server supports authentication, do it (even if no encryption)
                if ($smtpAuth && is_array($extList) && array_key_exists('AUTH', $extList)) {
                    if (!$smtpMail->authenticate($smtpLogin, $smtpPassword)) {
                        $connectionStatus = ['status' => 'error', 'error' =>
                            Loc::getMessage($this->moduleCode . '_SMTP_AUTH_ERROR')];
                        $stopFlag = true;
                    }
                } else {
                    // auth without login & password
                    if (!empty($extList['AUTH'])) {
                        $connectionStatus = ['status' => 'error', 'error' =>
                            Loc::getMessage($this->moduleCode . '_SMTP_AUTH_ERROR')];
                        $stopFlag = true;
                    }
                }
                if (!$stopFlag) {
                    $extendedCheckConnect = Option::get($this->moduleID,
                        'extended_check_connect', 'N');
                    if ($extendedCheckConnect == 'Y') {
                        // send email for finish check
                        $this->logSend(
                            "----------------------------------------------------------------------------" . "\n");
                        // create new PHPMailer for send email
                        $smtpMailSend = null;
                        if (class_exists('\s34web\mailSMTPB24\PHPMailer')) {
                            $smtpMailSend = new \s34web\mailSMTPB24\PHPMailer();
                        }
                        if (empty($smtpMailSend)) {
                            $connectionStatus = ['status' => 'error', 'error' =>
                                Loc::getMessage($this->moduleCode .
                                    '_ERROR_CLASS_PHPMAILER')];
                            $stopFlag = true;
                        }
                        if (!$stopFlag) {
                            // set main params
                            $smtpMailSend->isSMTP();
                            $smtpMailSend->setLanguage($langID);
                            // set auth params
                            $smtpMailSend->SMTPAutoTLS = false;
                            if ($smtpAuth) {
                                $smtpMailSend->SMTPAuth = true;
                            } else {
                                $smtpMailSend->SMTPAuth = false;
                            }
                            // set debug params
                            $smtpMailSend->SMTPDebug = Option::get($this->moduleID,
                                'log_level', '2');
                            $smtpMailSend->Debugoutput = function ($str, $level) {
                                $this->logSend($str);
                            };
                            // set host & port
                            $smtpMailSend->Host = $smtpHost;
                            $smtpMailSend->Port = $smtpPort;
                            // secure
                            if ($smtpSecure == 'Y') {
                                $smtpMailSend->SMTPSecure = 'tls';
                            }
                            if ($smtpSecure == 'S') {
                                $smtpMailSend->SMTPSecure = 'ssl';

                                $smtpMailSend->SMTPOptions = array(
                                    'ssl' => array(
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true
                                    ),
                                );
                            }
                            // timeout
                            $smtpMailSend->Timeout = Option::get($this->moduleID,
                                "smtp_timeout", 30);
                            // auth type
                            if ($smtpAuth) {
                                $smtpMailSend->Username = $smtpLogin;
                                $smtpMailSend->Password = $smtpPassword;
                            }
                            // use bitrix test email & send on this email
                            $smtpMailSend->addAddress('hosting_test@bitrixsoft.com');
                            $smtpMailSend->Subject = 'Bitrix site checker';
                            $smtpMailSend->Body = "Test message.\nDelete it.";
                            $smtpMailSend->From = $smtpEmail;
                            // send test message
                            if (!$smtpMailSend->send()) {
                                $connectionStatus = ['status' => 'error', 'error' =>
                                    Loc::getMessage($this->moduleCode .
                                        '_SEND_MAIL_ERROR') .
                                    $smtpMailSend->ErrorInfo];
                            } else {
                                $connectionStatus = ['status' => 'done'];
                            }
                        }
                    } else {
                        $connectionStatus = ['status' => 'done'];
                    }
                }
            }
        } catch (Exception $e) {
            $connectionStatus = ['status' => 'error', 'error' =>
                Loc::getMessage($this->moduleCode . '_SMTP_OTHER_ERROR')];
        }
        $this->logSend("----------------------------------------------------------------------------" . "\n");
        return $connectionStatus;
    }

    /**
     * Function modify data
     * @param $connectionData
     */
    public function modifySMTPData(&$connectionData)
    {
        // set secure type "S" if set 465 port & set type "Y" if set 587 port
        if (!empty($connectionData['PORT']) && $connectionData['PORT'] == '465') {
            if (!empty($connectionData['SECURE']) && $connectionData['SECURE'] == 'Y') {
                $connectionData['SECURE'] = 'S';
            }
        }
        if (!empty($connectionData['PORT']) && $connectionData['PORT'] == '587') {
            if (!empty($connectionData['SECURE']) && $connectionData['SECURE'] == 'N') {
                $connectionData['SECURE'] = 'Y';
            }
        }
    }

    /** Get users SMTP accounts
     * @return array
     */
    private function getUsersSMTPAccounts()
    {
        $smtpUsersAccounts = [];
        $multipleEmails = [];
        $senders = [];
        $sendersRes = \Bitrix\Main\Mail\Internal\SenderTable::getList([
                'order' => [
                    'ID' => 'desc',
                ],
                'filter' => [
                    'IS_CONFIRMED' => true
                ]
            ]
        );
        while($sendersElem = $sendersRes->fetch()) {
            $senders[] = $sendersElem;
        }
        unset($sendersElem, $sendersRes);

        if(!empty($senders)) {
            foreach ($senders as $sender) {
                if (!empty($sender['EMAIL']) && !empty($sender['OPTIONS']['smtp'])) {
                    // save for sender posting
                    if ($this->emailSenderPosting) {
                        if (empty($multipleEmails[$sender['EMAIL']][$sender['NAME']])) {
                            $multipleEmails[$sender['EMAIL']][$sender['NAME']] = [
                                'EMAIL_ID' => $sender['ID'],
                                'NAME' => $sender['NAME'],
                                'EMAIL' => $sender['EMAIL'],
                                'HOST' => $sender['OPTIONS']['smtp']['server'],
                                'PORT' => $sender['OPTIONS']['smtp']['port'],
                                'SECURE' => ($sender['OPTIONS']['smtp']['protocol'] == 'smtps' ? 'Y' : 'N'),
                                'AUTH' => 'C',
                                'LOGIN' => $sender['OPTIONS']['smtp']['login'],
                                'PASSWORD' => $sender['OPTIONS']['smtp']['password'],
                            ];
                        }
                    }
                    if (empty($smtpUsersAccounts[$sender['EMAIL']])) {
                        $smtpUsersAccounts[$sender['EMAIL']] = [
                            'EMAIL_ID' => $sender['ID'],
                            'NAME' => $sender['NAME'],
                            'EMAIL' => $sender['EMAIL'],
                            'HOST' => $sender['OPTIONS']['smtp']['server'],
                            'PORT' => $sender['OPTIONS']['smtp']['port'],
                            'SECURE' => ($sender['OPTIONS']['smtp']['protocol'] == 'smtps' ? 'Y' : 'N'),
                            'AUTH' => 'C',
                            'LOGIN' => $sender['OPTIONS']['smtp']['login'],
                            'PASSWORD' => $sender['OPTIONS']['smtp']['password'],
                        ];
                    }
                }
            }
            unset($sender);
        }

        // check email name for sender email posting
        if ($this->emailSenderPosting && !empty($this->emailSenderPostingEmail) && !empty($this->emailSenderPostingName)) {
            if (!empty($multipleEmails[$this->emailSenderPostingEmail][$this->emailSenderPostingName])) {
                $smtpUsersAccounts[$this->emailSenderPostingEmail] =
                    $multipleEmails[$this->emailSenderPostingEmail][$this->emailSenderPostingName];
            }
        }

        return $smtpUsersAccounts;
    }

    /** Get admin SMTP Accounts
     * @return array
     */
    private function getAdminSMTPAccounts()
    {
        $adminSMTPAccounts = [];
        $multipleEmails = [];
        if (class_exists('\s34web\mailSMTPB24\smtpAccountsTable')) {
            $resAccountsList = \s34web\mailSMTPB24\smtpAccountsTable::getList([
                'order' => ['ID' => 'desc'],
                'filter' => ['ACTIVE' => 'Y'],
                'select' => ['ID', 'NAME', 'EMAIL', 'SERVER', 'PORT', 'SECURE', 'AUTH', 'LOGIN', 'PASSWORD']
            ]);
            while ($arAccount = $resAccountsList->fetch()) {
                $tmpAdminAccount = [
                    'EMAIL_ID' => $arAccount['ID'],
                    'NAME' => $arAccount['NAME'],
                    'EMAIL' => $arAccount['EMAIL'],
                    'HOST' => $arAccount['SERVER'],
                    'PORT' => $arAccount['PORT'],
                    'SECURE' => $arAccount['SECURE'],
                    'AUTH' => $arAccount['AUTH']
                ];
                if ($arAccount['AUTH'] == 'C') {
                    $tmpAdminAccount['LOGIN'] = $arAccount['LOGIN'];
                    $tmpAdminAccount['PASSWORD'] = $arAccount['PASSWORD'];
                } else {
                    $tmpAdminAccount['LOGIN'] = '';
                    $tmpAdminAccount['PASSWORD'] = '';
                }
                // save for sender posting
                if ($this->emailSenderPosting) {
                    if (empty($multipleEmails[$tmpAdminAccount['EMAIL']][$tmpAdminAccount['NAME']])) {
                        $multipleEmails[$tmpAdminAccount['EMAIL']][$tmpAdminAccount['NAME']] = $tmpAdminAccount;
                    }
                }
                if (empty($adminSMTPAccounts[$tmpAdminAccount['EMAIL']])) {
                    $adminSMTPAccounts[$tmpAdminAccount['EMAIL']] = $tmpAdminAccount;
                }
            }
            unset($arAccount, $resAccountsList);

            // check email name for sender email posting
            if ($this->emailSenderPosting && !empty($this->emailSenderPostingEmail) && !empty($this->emailSenderPostingName)) {
                if (!empty($multipleEmails[$this->emailSenderPostingEmail][$this->emailSenderPostingName])) {
                    $adminSMTPAccounts[$this->emailSenderPostingEmail] =
                        $multipleEmails[$this->emailSenderPostingEmail][$this->emailSenderPostingName];
                }
            }
        }
        return $adminSMTPAccounts;
    }

    /**
     * Function log send or check email
     * @param $message
     */
    private function logSend($message)
    {
        $logSaveParam = Option::get($this->moduleID, 'log_sending', 'N');
        if ($logSaveParam == 'Y') {
            $logDir = pathinfo(pathinfo(dirname(__FILE__))['dirname'])['dirname'];
            $sendFile = 'send';
            if ($this->checkSend) {
                $sendFile = 'check';
            }
            if (empty($this->fromEmailID)) {
                $this->fromEmailID = '0';
            }
            if (empty($this->typeEmail)) {
                $this->typeEmail = 'system';
            }
            //$message = trim($message) . "\n";
            // create log directory if not exist
            if (!\Bitrix\Main\IO\Directory::isDirectoryExists($logDir . '/logs')) {
                \Bitrix\Main\IO\Directory::createDirectory($logDir . '/logs');
            }
            // set file log name
            $fileLogName = $logDir . '/logs/' . $sendFile . '_' . $this->typeEmail . '_' .
                $this->fromEmailID . '.log';
            // check exist file
            if (\Bitrix\Main\IO\File::isFileExists($fileLogName)) {
                // clear file if big size
                $maxLogSize = Option::get($this->moduleID, 'log_weight', '5');
                if ($maxLogSize != 0) {
                    $maxLogSize = $maxLogSize * 1024 * 1024;
                    $logSize = filesize($fileLogName);
                    if (!empty($logSize) && !empty($maxLogSize) && $logSize > $maxLogSize) {
                        // delete file & update date create log
                        \Bitrix\Main\IO\File::deleteFile($fileLogName);
                        if (method_exists('\s34web\mailSMTPB24\logOperations', 'setLogDate')) {
                            \s34web\mailSMTPB24\logOperations::setLogDate($this->typeEmail,
                                $this->fromEmailID);
                        }
                    }
                }
                // clear file if long period
                $logRewritePeriod = Option::get($this->moduleID, 'log_rewrite',
                    '1');
                if ($logRewritePeriod != 0) {
                    switch ($logRewritePeriod) {
                        case '2':
                            $rewritePeriod = 7;
                            break;
                        case '3':
                            $rewritePeriod = 30;
                            break;
                        case '4':
                            $rewritePeriod = 365;
                            break;
                        default:
                            $rewritePeriod = 1;
                            break;
                    }
                    $fileCreateDateLog = '';
                    if (method_exists('\s34web\mailSMTPB24\logOperations', 'getLogDate')) {
                        $fileCreateDateLog = \s34web\mailSMTPB24\logOperations::getLogDate($this->typeEmail,
                            $this->fromEmailID);
                    }
                    if (!empty($rewritePeriod) && !empty($fileCreateDateLog)) {
                        $dateCreateLog = new \Bitrix\Main\Type\DateTime();
                        $dateCreateLog = $dateCreateLog->tryParse($fileCreateDateLog, 'd.m.Y H:i:s');
                        $curDate = new \Bitrix\Main\Type\Date();
                        if (!empty($dateCreateLog)) {
                            $diffDate = $curDate->getDiff($dateCreateLog);
                            if (!empty($diffDate->days) && $diffDate->days >= $rewritePeriod) {
                                // delete file & update date create log
                                \Bitrix\Main\IO\File::deleteFile($fileLogName);
                                if (method_exists('\s34web\mailSMTPB24\logOperations', 'setLogDate')) {
                                    \s34web\mailSMTPB24\logOperations::setLogDate($this->typeEmail,
                                        $this->fromEmailID);
                                }
                            }
                        }
                    }
                }
            }
            file_put_contents($fileLogName,
                $message, FILE_APPEND | LOCK_EX);
        }
    }
}

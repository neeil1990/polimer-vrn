<?php
/**
 * Created: 21.10.2021, 11:46
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

class eventOperations
{
    /**
     * Change accounts data operations
     * @param \Bitrix\Main\Entity\Event $event
     */
    public static function changeAccountsData(\Bitrix\Main\Entity\Event $event)
    {
        $type = '';
        $accountsType = $event->getEntity()->getName();
        if (!empty($accountsType)) {
            switch ($accountsType) {
                case 'smtpAccounts':
                    $type = 'admin_smtp';
                    break;
                case 'usersSmtpAccounts':
                    $type = 'users_smtp';
                    break;
            }
        }
        if (!empty($type) && method_exists('\s34web\mailSMTPB24\logOperations', 'clearLogDataCache')) {
            \s34web\mailSMTPB24\logOperations::clearLogDataCache($type);
        }
    }

    /**
     * Set mailbox SMTP data after create or update mailbox account (module mail)
     * @param \Bitrix\Main\Entity\Event $event
     */
    public static function setMailboxSMTP(\Bitrix\Main\Entity\Event $event)
    {
        global $USER;
        // get smtp fields
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        $requestParams = $request->getPostList()->getValues();
        $settingFields = [];

        if (!empty($requestParams['fields'])) {
            $settingFields = $requestParams['fields'];
        }
        // check site id
        if (!empty($settingFields['site_id'])) {
            $currentSite = \CSite::getById($settingFields['site_id'])->fetch();
        }
        if (empty($currentSite)) {
            return;
        }
        // get & check service
        if (!empty($settingFields['service_id'])) {
            $mailServices = [];
            if (method_exists('\s34web\mailSMTPB24\mailConfigOperations', 'getMailServices')) {
                $mailServices = \s34web\mailSMTPB24\mailConfigOperations::getMailServices();
            }
            if (!empty($mailServices) && !empty($mailServices[$settingFields['service_id']])) {
                $service = $mailServices[$settingFields['service_id']];
            }
        }
        if (empty($service) || $service['SITE_ID'] != $currentSite['LID']) {
            return;
        }
        // check mailbox
        $mailbox = [];
        if (!empty($settingFields['mailbox_id']) && \Bitrix\Main\Loader::IncludeModule('mail')) {
            $mailboxRes = \Bitrix\Mail\MailboxTable::getList([
                'filter' => [
                    '=ID' => $settingFields['mailbox_id'],
                    '=ACTIVE' => 'Y',
                    '=SERVER_TYPE' => 'imap',
                ],
            ]);
            if ($mailboxElem = $mailboxRes->fetch()) {
                $mailbox = $mailboxElem;
            }

            if ((!empty($mailbox['USER_ID']) && $USER->getId() != $mailbox['USER_ID']) && !$USER->isAdmin() &&
                !$USER->canDoOperation('bitrix24_config')) {
                return;
            }
        }
        if (empty($mailbox)) {
            $mailboxData = [
                'EMAIL' => !empty(trim($settingFields['email'])) ? trim($settingFields['email']) : '',
                'NAME' => !empty(trim($settingFields['name'])) ? trim($settingFields['name']) : '',
                'USERNAME' => !empty(trim($settingFields['sender'])) ? trim($settingFields['sender']) : '',
                'LOGIN' => !empty($settingFields['login_imap']) ? $settingFields['login_imap'] : '',
                'PASSWORD' => !empty($settingFields['pass_imap']) ? $settingFields['pass_imap'] : ''
            ];

        } else {
            if (!empty($mailbox['SERVICE_ID']) && !empty($service['ID']) && $mailbox['SERVICE_ID'] != $service['ID']) {
                return;
            }
            $tmpMailbox = [
                'EMAIL' => !empty($mailbox['EMAIL']) ? $mailbox['EMAIL'] : '',
                'NAME' => !empty($mailbox['NAME']) ? $mailbox['NAME'] : '',
                'LOGIN' => !empty($mailbox['LOGIN']) ? $mailbox['LOGIN'] : '',
            ];
            foreach ($tmpMailbox as $item) {
                $address = new \Bitrix\Main\Mail\Address($item);
                if ($address->validate()) {
                    $mailbox['EMAIL'] = $address->getEmail();
                    break;
                }
            }
            $tmpMailboxEmail = '';
            if(!empty($mailbox['EMAIL']))
            {
                $tmpMailboxEmail = $mailbox['EMAIL'];
            }
            if (empty($tmpMailboxEmail) && !empty(trim($settingFields['email'])))
            {
                $tmpMailboxEmail = trim($settingFields['email']);
            }
            $mailboxData = [
                'EMAIL' => $tmpMailboxEmail,
                'NAME' => !empty(trim($settingFields['name'])) ? trim($settingFields['name']) : '',
                'USERNAME' => !empty(trim($settingFields['sender'])) ? trim($settingFields['sender']) : '',
                'LOGIN' => !empty($mailbox['LOGIN']) ? $mailbox['LOGIN'] : '',
                'PASSWORD' => !empty($mailbox['PASSWORD']) ? $mailbox['PASSWORD'] : ''
            ];

            if (isset($settingFields['pass_imap']) && $settingFields['pass_imap'] <> '' &&
                (!isset($settingFields['pass_placeholder']) ||
                    (!empty($settingFields['pass_placeholder']) &&
                        $settingFields['pass_imap'] != $settingFields['pass_placeholder']))) {
                $mailboxData['PASSWORD'] = $settingFields['pass_imap'];
            }
        }
        if (empty($settingFields['use_smtp']) && !empty($mailbox) && !empty($mailboxData['EMAIL'])) {
            $res = \Bitrix\Main\Mail\Internal\SenderTable::getList([
                'filter' => [
                    'IS_CONFIRMED' => true,
                    '=EMAIL' => $mailboxData['EMAIL'],
                ],
            ]);
            while ($item = $res->fetch()) {
                if (!empty($item['OPTIONS']['smtp']['server']) && !empty($item['ID']) && isset($item['OPTIONS'])) {
                    unset($item['OPTIONS']['smtp']);
                    \Bitrix\Main\Mail\Internal\SenderTable::update(
                        $item['ID'],
                        [
                            'OPTIONS' => $item['OPTIONS']
                        ]
                    );
                }
            }
            unset($item);
            \Bitrix\Main\Mail\Sender::clearCustomSmtpCache($mailboxData['EMAIL']);
        }
        $smtpConfirmed = [];
        if (!empty($settingFields['use_smtp'])) {
            $senderFields = [
                'NAME' => !empty($mailboxData['USERNAME']) ? $mailboxData['USERNAME'] : '',
                'EMAIL' => !empty($mailboxData['EMAIL']) ? $mailboxData['EMAIL'] : '',
                'USER_ID' => $USER->getId(),
                'IS_CONFIRMED' => false,
                'IS_PUBLIC' => false,
                'OPTIONS' => array(
                    'source' => 'mail.client.config',
                ),
            ];
            if(!empty($mailboxData['EMAIL'])) {
                $res = \Bitrix\Main\Mail\Internal\SenderTable::getList(array(
                    'filter' => array(
                        'IS_CONFIRMED' => true,
                        '=EMAIL' => $mailboxData['EMAIL'],
                    ),
                    'order' => array(
                        'ID' => 'DESC',
                    ),
                ));
                while ($item = $res->fetch()) {
                    if (empty($smtpConfirmed)) {
                        if (!empty($item['OPTIONS']['smtp']['server']) && empty($item['OPTIONS']['smtp']['encrypted'])) {
                            $smtpConfirmed = $item['OPTIONS']['smtp'];
                        }
                    }
                    if ((!empty($item['USER_ID']) && $senderFields['USER_ID'] == $item['USER_ID']) &&
                        (isset($item['NAME']) && $senderFields['NAME'] == $item['NAME'])) {
                        $senderFields = $item;
                        $senderFields['IS_CONFIRMED'] = false;
                        $senderFields['OPTIONS']['__replaces'] = $item['ID'];
                        unset($senderFields['ID']);
                        if (!empty($smtpConfirmed)) {
                            break;
                        }
                    }
                }
                unset($item);
            }
        }
        if (empty($settingFields['use_smtp']) && empty($smtpConfirmed)) {
            unset($senderFields);
        }
        if (!empty($senderFields)) {
            $tmpSmtpConfigServer = '';
            if(!empty($service['SMTP_SERVER']))
            {
                $tmpSmtpConfigServer = $service['SMTP_SERVER'];
            }
            if(empty($tmpSmtpConfigServer) && !empty(trim($settingFields['server_smtp'])))
            {
                $tmpSmtpConfigServer = trim($settingFields['server_smtp']);
            }
            $tmpSmtpConfigPort = 0;
            if(!empty(trim($service['SMTP_PORT'])))
            {
                $tmpSmtpConfigPort = (int)trim($service['SMTP_PORT']);
            }
            if(empty($tmpSmtpConfigPort) && !empty(trim($settingFields['port_smtp'])))
            {
                $tmpSmtpConfigPort = (int)trim($settingFields['port_smtp']);
            }
            $tmpSmtpConfigProtocol = 'smtp';
            if(!empty($service['SMTP_ENCRYPTION']))
            {
                if($service['SMTP_ENCRYPTION'] == 'Y') {
                    $tmpSmtpConfigProtocol = 'smtps';
                }
            }
            if(empty($tmpSmtpConfigProtocol) && !empty($settingFields['ssl_smtp']))
            {
                $tmpSmtpConfigProtocol = 'smtps';
            }
            $tmpSmtpConfigLogin = '';
            if(!empty($service['SMTP_LOGIN_AS_IMAP']))
            {
                if($service['SMTP_LOGIN_AS_IMAP'] == 'Y' && !empty($mailboxData['LOGIN'])) {
                    $tmpSmtpConfigLogin = $mailboxData['LOGIN'];
                }
            }
            if(empty($tmpSmtpConfigLogin) && !empty($settingFields['login_smtp']))
            {
                $tmpSmtpConfigLogin = $settingFields['login_smtp'];
            }
            $smtpConfig = [
                'server' => $tmpSmtpConfigServer,
                'port' => $tmpSmtpConfigPort,
                'protocol' => $tmpSmtpConfigProtocol,
                'login' => $tmpSmtpConfigLogin,
                'password' => '',
            ];
            if (!empty($smtpConfirmed) && is_array($smtpConfirmed)) {
                // server, port, protocol, login, password
                $smtpConfig = array_filter($smtpConfig) + $smtpConfirmed;
            }
            if (!empty($service['SMTP_PASSWORD_AS_IMAP']) && $service['SMTP_PASSWORD_AS_IMAP'] == 'Y' &&
                (!isset($settingFields['oauth_uid']) || !$settingFields['oauth_uid'])) {
                $smtpConfig['password'] = $mailboxData['PASSWORD'];
            } else if (isset($settingFields['pass_smtp']) && $settingFields['pass_smtp'] <> '' &&
                (!isset($settingFields['pass_placeholder']) ||
                    (!empty($settingFields['pass_placeholder']) &&
                        $settingFields['pass_smtp'] != $settingFields['pass_placeholder']))) {
                if (preg_match('/^\^/', $settingFields['pass_smtp'])) {
                    return;
                } else if (preg_match('/\x00/', $settingFields['pass_smtp'])) {
                    return;
                }
                $smtpConfig['password'] = $settingFields['pass_smtp'];
            }
            if (isset($service['SMTP_SERVER']) && !$service['SMTP_SERVER'] && isset($smtpConfig['server'])) {
                $regex = '/^(?:(?:http|https|ssl|tls|smtp):\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)$/i';
                if (!preg_match($regex, $smtpConfig['server'], $matches) && $matches[1] <> '') {
                    return;
                }
                $smtpConfig['server'] = $matches[1];
            }
            if (isset($service['SMTP_PORT']) && !$service['SMTP_PORT']) {
                if (isset($smtpConfig['port']) && ($smtpConfig['port'] <= 0 || $smtpConfig['port'] > 65535)) {
                    return;
                }
            }
            $senderFields['OPTIONS']['smtp'] = $smtpConfig;
            if (!empty($smtpConfirmed)) {
                $senderFields['IS_CONFIRMED'] = !array_diff(
                    array('server', 'port', 'protocol', 'login', 'password'),
                    array_keys(array_intersect_assoc($smtpConfig, $smtpConfirmed))
                );
            }
        }
        if (!empty($senderFields) && empty($senderFields['IS_CONFIRMED'])) {
            $result = \Bitrix\Main\Mail\Sender::add($senderFields);
            if (!empty($result['errors']) && $result['errors'] instanceof \Bitrix\Main\ErrorCollection) {
                // get add errors
                \Bitrix\Main\Diag\Debug::dumpToFile($result['errors']);
            }
        }
    }

    /**
     * Delete SMTP-account operations
     * @param \Bitrix\Main\Entity\Event $event
     */
    public static function setOnDeleteSenderOperations(\Bitrix\Main\Entity\Event $event)
    {
        $dataID = $event->getParameter('id');
        if (!empty($dataID)) {
            if (class_exists('\s34web\mailSMTPB24\usersSmtpAccountsTable')) {
                $userSMTPAccount = [];
                $userSMTPAccountRes = \s34web\mailSMTPB24\usersSmtpAccountsTable::getList([
                    'filter' => ['SMTP_ID' => $dataID],
                    'select' => ['ID']
                ]);
                if ($userSMTPAccountElem = $userSMTPAccountRes->fetch()) {
                    $userSMTPAccount = $userSMTPAccountElem;
                }
                if (!empty($userSMTPAccount['ID'])) {
                    $deleteCreateLogResult = \s34web\mailSMTPB24\usersSmtpAccountsTable::delete($userSMTPAccount['ID']);
                    if (!$deleteCreateLogResult->isSuccess()) {
                        $changeErrors = $deleteCreateLogResult->getErrors();
                        if (is_array($changeErrors)) {
                            $textChangeErrors = implode(',', $changeErrors);
                        } else {
                            $textChangeErrors = $changeErrors;
                        }
                        \Bitrix\Main\Diag\Debug::dumpToFile($textChangeErrors);
                    }
                }
            }
        }
    }

    /**
     * Change SMTP-users accounts operations
     * @param \Bitrix\Main\Entity\Event $event
     */
    public static function setOnChangeSenderOperations(\Bitrix\Main\Entity\Event $event)
    {
        $dataID = $event->getParameter('id');
        $updateFields = [
            'DATE_CREATE_LOG' => new \Bitrix\Main\Type\Date()
        ];
        if (!empty($dataID)) {
            // save date crete to table
            if (class_exists('\s34web\mailSMTPB24\usersSmtpAccountsTable')) {
                $userSMTPAccount = [];
                $userSMTPAccountRes = \s34web\mailSMTPB24\usersSmtpAccountsTable::getList([
                    'filter' => ['SMTP_ID' => $dataID],
                    'select' => ['ID']
                ]);
                if ($userSMTPAccountElem = $userSMTPAccountRes->fetch()) {
                    $userSMTPAccount = $userSMTPAccountElem;
                }

                if (!empty($userSMTPAccount['ID'])) {
                    $updateCreateLogResult = \s34web\mailSMTPB24\usersSmtpAccountsTable::update($userSMTPAccount['ID'],
                        $updateFields);
                } else {
                    $updateFields['SMTP_ID'] = $dataID;
                    $updateCreateLogResult = \s34web\mailSMTPB24\usersSmtpAccountsTable::add($updateFields);
                }
                if (!$updateCreateLogResult->isSuccess()) {
                    $changeErrors = $updateCreateLogResult->getErrors();
                    if (is_array($changeErrors)) {
                        $textChangeErrors = implode(',', $changeErrors);
                    } else {
                        $textChangeErrors = $changeErrors;
                    }
                    \Bitrix\Main\Diag\Debug::dumpToFile($textChangeErrors);
                }
            }
        }
    }

    /**
     * Set flag on mail headers if sender posting
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function onPostingSendRecipientEmailOperations(\Bitrix\Main\Event $event)
    {
        $eventMailParams = $event->getParameter(0);
        if (isset($eventMailParams['MAILING_CHAIN_ID'])) {
            $eventMailParams['HEADER']['X-Sender-Posting'] = $eventMailParams['MAILING_CHAIN_ID'];
        }
        return new \Bitrix\Main\EventResult($event->getEventType(), $eventMailParams);
    }
}

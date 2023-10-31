<?php
/**
 * Created: 12.04.2021, 12:00
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

use Bitrix\Main\Entity;

class usersSmtpAccountsTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'mail_smtp_b24_users_smtp_accounts';
    }

    public static function getUfId()
    {
        return 'MAIL_SMTP_b24_USERS_SMTP_ACCOUNTS';
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true
                ]
            ),
            new Entity\IntegerField(
                'SMTP_ID',
                [
                    'required' => true
                ]
            ),
            new Entity\DateField(
                'DATE_CREATE_LOG',
                [
                    'required' => true,
                    'default_value' => function(){ return new \Bitrix\Main\Type\Date();}
                ]
            )
        ];
    }
}
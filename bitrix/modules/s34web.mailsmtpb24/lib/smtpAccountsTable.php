<?php
/**
 * Created: 24.03.2021, 0:11
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

use Bitrix\Main\Entity,
    Bitrix\Main\Config\Option;

class smtpAccountsTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'mail_smtp_b24_smtp_accounts';
    }

    public static function getUfId()
    {
        return 'MAIL_SMTP_B24_SMTP_ACCOUNTS';
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
            new Entity\EnumField(
                'ACTIVE',
                [
                    'values' => ['Y', 'N']
                ]
            ),
            new Entity\StringField(
                'NAME',
                [
                    'required' => false
                ]
            ),
            new Entity\StringField(
                'EMAIL',
                [
                    'required' => true
                ]
            ),
            new Entity\StringField(
                'SERVER',
                [
                    'required' => true
                ]
            ),
            new Entity\IntegerField(
                'PORT',
                [
                    'required' => true
                ]
            ),
            new Entity\EnumField(
                'SECURE',
                [
                    'values' => ['N', 'Y', 'S']
                ]
            ),
            new Entity\EnumField(
                'AUTH',
                [
                    'values' => ['N', 'C']
                ]
            ),
            new Entity\StringField(
                'LOGIN',
                [
                    'required' => false
                ]
            ),
            new Entity\TextField(
                'PASSWORD',
                [
                    'save_data_modification' => function () {
                        return [
                            function ($value) {
                                return static::cryptoEnabled('PASSWORD') ? $value : self::crypt($value);
                            }
                        ];
                    },
                    'fetch_data_modification' => function () {
                        return [
                            function ($value) {
                                return static::cryptoEnabled('PASSWORD') ? $value : self::decrypt($value);
                            }
                        ];
                    }
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

    public static function BinMD5($val)
    {
        return(pack("H*",md5($val)));
    }

    public static function Decrypt($str, $key=false)
    {
        if($key===false)
            $key = Option::get("main", "pwdhashadd", "");
        $key1 = self::BinMD5($key);
        $str = base64_decode($str);
        $res = '';
        while ($str)
        {
            if (function_exists('mb_substr'))
            {
                $m = mb_substr($str, 0, 16, "ASCII");
                $str = mb_substr($str, 16, mb_strlen($str,"ASCII")-16, "ASCII");
            }
            else
            {
                $m = mb_substr($str, 0, 16);
                $str = mb_substr($str, 16);
            }

            $m = self::ByteXOR($m, $key1, 16);
            $res .= $m;
            $key1 = self::BinMD5($key.$key1.$m);
        }
        return $res;
    }

    public static function Crypt($str, $key=false)
    {
        if($key===false)
            $key = Option::get("main", "pwdhashadd", "");
        $key1 = self::BinMD5($key);
        $res = '';
        while ($str)
        {
            if (function_exists('mb_substr'))
            {
                $m = mb_substr($str, 0, 16, "ASCII");
                $str = mb_substr($str, 16, mb_strlen($str,"ASCII")-16, "ASCII");
            }
            else
            {
                $m = mb_substr($str, 0, 16);
                $str = mb_substr($str, 16);
            }

            $res .= self::ByteXOR($m, $key1, 16);
            $key1 = self::BinMD5($key.$key1.$m);
        }
        return(base64_encode($res));
    }

    public static function ByteXOR($a, $b, $l)
    {
        $c = "";
        for ($i = 0; $i < $l; $i++)
        {
            if (isset($a[$i]) && isset($b[$i]))
                $c .= $a[$i] ^ $b[$i];
        }

        return $c;
    }
}
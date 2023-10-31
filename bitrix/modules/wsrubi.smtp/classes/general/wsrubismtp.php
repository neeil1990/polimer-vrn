<?php
/**
 * @link      http://wsrubi.ru/dev/bitrixsmtp/
 * @author Sergey Blazheev <s.blazheev@gmail.com>
 * @copyright Copyright (c) 2011-2017 Altair TK. (http://www.wsrubi.ru)
 */

define('_SmptModuleName_','wsrubi.smtp');
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Zend/Loader/StandardAutoloader.php';

$loader = new Zend\Loader\StandardAutoloader(array('autoregister_zf' => true,'namespaces' => true));
$loader->register();
include_once __DIR__.DIRECTORY_SEPARATOR.'MyAddress.php';
include_once __DIR__.DIRECTORY_SEPARATOR.'IdnaConvert.php';
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Message;
use Zend\Mail\Headers;
use Zend\Mail\MyAddress;
use Zend\Mail\AddressList;

if (COption::GetOptionString(_SmptModuleName_,"active") == "Y"&&!function_exists("custom_mail")){
    function custom_mail($to, $subject, $message, $additional_headers = null, $additional_parameters = null)
    {
        $sFrom = null;
        $sFromName = null;
        $saveEmailError = (COption::GetOptionString(_SmptModuleName_, "save_email_error") == 'Y');
        $removeHeaders = COption::GetOptionString(_SmptModuleName_, "remove_headers");


        if(!empty($removeHeaders)){
            $removeHeaders = json_decode($removeHeaders);
            if(is_array($removeHeaders))
                $removeHeaders = array_filter($removeHeaders, function ($val){
                    if(!empty($val))
                        return true;
                });

                $removeHeaders = array_map(function ($val){
                    return strtolower($val);
                }, $removeHeaders);
        }else{
            $removeHeaders = [];
        }

        if (COption::GetOptionString(_SmptModuleName_, "addrtovalidation", "N") != "Y")
            $validation = false;
        else
            $validation = true;

        if ((stripos($additional_headers, 'x-bitrix-posting') !== FALSE && COption::GetOptionString(_SmptModuleName_, "posting") == 'Y') || (COption::GetOptionString(_SmptModuleName_, "onlyposting") == 'Y' && stripos($additional_headers, 'x-bitrix-posting') === FALSE)) {
            return @mail($to, $subject, $message, $additional_headers, $additional_parameters);
        }
        if (COption::GetOptionString(_SmptModuleName_, "convert_to_utf8")) {
            $message = WsRubiTools::ConvertToUtf($message);
            $to = WsRubiTools::ConvertToUtf($to);
            $additional_headers = str_ireplace("windows-1251", "utf-8", $additional_headers);
            $message = str_ireplace("windows-1251", "utf-8", $message);
        }

        $Message = new Message();

        if (!empty($additional_headers)) {
            try {
                $aHeaders = WsRubiTools::SplitHeaders($additional_headers);
                $oHeaders = new Headers();
                foreach($aHeaders as $key=>$item){
                    if($key=='From'){
                        $item = WsRubiTools::imap_utf8_fix($item);
                        $arrFrom = WsRubiTools::parseEmail($item);
                        if(empty($arrFrom['email'])){
                            $strFrom = $aHeaders['Reply-To'];
                        }else{
                            $strFrom = $arrFrom['email'];
                        }
                        $strFrom = WsRubiTools::encodeIdna($strFrom);
                        if(empty($arrFrom['name'])){
                            $strFromName = null;
                        }else{
                            $strFromName = WsRubiTools::base64UTF($arrFrom['name']);
                        }
                            $addressFrom = new \Zend\Mail\Address($strFrom, $strFromName);
                            $sFrom = $addressFrom->getEmail();
                            $headerFrom = new \Zend\Mail\Header\From();
                            $addressListFrom = new \Zend\Mail\AddressList();
                            $addressListFrom->add($addressFrom);
                            $headerFrom->setAddressList($addressListFrom);
                            $oHeaders->addHeader($headerFrom);
                            continue;

                    }
                    if($key=='Reply-To'){
                        $item = WsRubiTools::encodeIdna(WsRubiTools::imap_utf8_fix($item));
                    }

                    $oHeaders->addHeaderLine($key, $item);
                }

            } catch (\Exception $e) {
                WsRubiTools::WsrubiSMTPLog("<br/>Error: " . $e->getMessage() . "" . "\t\r\nline:" . __LINE__ . "\t" . $additional_headers, 'error');
            }

            try {
                $Message->setHeaders($oHeaders);
            } catch (\Exception $e) {
                WsRubiTools::WsrubiSMTPLog("<br/>Error: " . $e->getMessage() . "" . "\t\r\nline:" . __LINE__ . "\t", 'error');
            }

        }

        try {
            $Message->setBody($message);
            $Message->setSubject($subject);
        } catch (\Exception $e) {
            WsRubiTools::WsrubiSMTPLog("<br/>Error: " . $e->getMessage() . ""  . "\t\r\nline:" . __LINE__ . "\t", 'error');
        }

        $addressListTo = new AddressList();
        $arrTo = explode(',', $to);
        foreach ($arrTo as $to) {
            try {
                $arrTo = WsRubiTools::parseEmail($to);
                $toName = '';
                if (!empty($arrTo['email'])) {
                    $to = $arrTo['email'];
                    $to = WsRubiTools::encodeIdna($to);
                }
                if (!empty($arrTo['name'])) {
                    $toName = $arrTo['name'];
                    $toName = WsRubiTools::base64UTF($toName);
                }
                $to = new \Zend\Mail\MyAddress(trim($to), trim($toName), $validation);
                $addressListTo->add($to);
            } catch (Exception $e) {
                WsRubiTools::WsrubiSMTPLog($to . "<br/>Error: " . $e->getMessage() . ""  . "\t\r\nline:" . __LINE__, 'error');
            }
        }
        if(empty($sFrom))
            WsRubiTools::WsrubiSMTPLog("from-пустой адрес отправителя'" . __LINE__, 'error');

        try {
            $transport = WsRubiTools::GetSmtpTransport($sFrom);
        } catch (\Exception $e) {
            WsRubiTools::WsrubiSMTPLog("from-'" . $sFrom . "\t<br/>Error: " . $e->getMessage() . ""  . "\t" . "\t\r\nline:" . __LINE__, 'error');
            return false;
        }
        try {
            $emailTo = $to;
            $arrEmail = null;
            $firstEmail = $addressListTo->current();
            if (!empty($firstEmail))
                $arrEmail = array($firstEmail->getEmail());
            while ($email = $addressListTo->next()) {
                $arrEmail[] = $email->getEmail();
            }
            if (is_array($arrEmail)) {
                $emailTo = implode(',', $arrEmail);
            }
            $Message->setTo($addressListTo);
            $Message->getHeaders()->setEncoding('ASCII');
        } catch (\Exception $e) {
            WsRubiTools::WsrubiSMTPLog("from-'" . $sFrom . "'\tto-'" . $emailTo . "'\t<br/>Error: " . $e->getMessage() . "" .  ($saveEmailError?"\t" . $Message->toString():'') . "\t" . "\t\r\nline:" . __LINE__, 'error');
            return false;
        }
        try {
            foreach ($removeHeaders as $key => $value){
                $Message->getHeaders()->removeHeader($value);
            }
            $transport->send($Message);
            WsRubiTools::WsrubiSMTPLog("from-'" . $sFrom . "'\tto-'" . $emailTo . "'\tOK");
            return true;
        } catch (\Exception $e) {
            WsRubiTools::WsrubiSMTPLog("from-'" . print_r($Message->getFrom(), 1) . "'\tto-'" . $emailTo . "'\t<br/>Error: " . $e->getMessage() . ""  . ($saveEmailError?"\t" . $Message->toString():'') . "\t" . "\t\r\nline:" . __LINE__, 'error');
            return false;
        }
    }
}
if(!class_exists("WsRubiTools")) {
    class WsRubiTools
    {
        public static function encodeIdna($value) {
            $Idna = new IdnaConvert();
            $arEncode = explode('@', $value);
            if(empty($arEncode[1]))
                return false;
            return $arEncode[0].'@'.$Idna->encode($arEncode[1]);
        }
        public static function imap_utf8_fix($value) {
		  return iconv_mime_decode($value,0,"UTF-8"); 
		} 		
        public static function base64UTF ( $value )
        {
            return '=?UTF-8?B?'.base64_encode($value).'?=';
        }
        public static function ConvertToUtf ( $value )
        {
            return mb_convert_encoding ( $value, 'utf-8', 'cp-1251' );
        }
        /**
         * ������ 1-�� ������ ����������� �����
         * @return array('email','name')
         */
        public static function parseEmail ( $value )
        {
            $address_array = static::parseAddresses($value);

            $arrEmail = Array();
            if ($address_array===FALSE) {
                return array('email'=>$value,'name'=>null);
            }else{
                if(!empty($address_array->mailbox))
                    $arrEmail[] = $address_array->mailbox;
                if(!empty($address_array->host))
                    $arrEmail[] = $address_array->host;
                return array('email'=>implode('@',$arrEmail),'name'=>@$address_array->personal);
            }
        }
        protected static function parseAddresses($addrstr, $useimap = false)
        {
            if($useimap&&function_exists('imap_rfc822_parse_adrlist')){
                return current(imap_rfc822_parse_adrlist( $addrstr , '' ));
            }
            if(preg_match('/([^<]*)<([^@]*)@([^>]*)>/', $addrstr , $regs)){
                $oEmail = new stdClass();
                $oEmail->mailbox = $regs[2];
                $oEmail->host = $regs[3];
                $oEmail->personal = $regs[1];
                return $oEmail;
            }
            return false;
        }

        public static function SplitHeaders ( $sHeaders )
        {
            $aHeadersTmp = explode ( "\n", $sHeaders );
            foreach ( $aHeadersTmp as $item ) {
                $item = explode ( ': ', $item );
                $aHeaders[ $item[ 0 ] ] = trim ( $item[ 1 ] );
            }
            return $aHeaders;
        }

        /**
         * @return SmtpTransport
         */
        public static function GetSmtpTransport ( $sFrom = null )
        {

            $email = COption::GetOptionString ( _SmptModuleName_, "advemail" );

            // Setup SMTP transport
            //@TODO �������� ������ � ���������� ��������� �����������
            $transport = new SmtpTransport();

                $connection_config = array (
                    'username' => COption::GetOptionString ( _SmptModuleName_, "settings_smtp_login" ),
                    'password' => COption::GetOptionString ( _SmptModuleName_, "settings_smtp_password" ),
                );
                $ssl = COption::GetOptionString ( _SmptModuleName_, "settings_smtp_type_encryption" );
                if ( $ssl != 'no' ) {
                    $connection_config[ 'ssl' ] = $ssl;
                }
            $aOptions = array (
                        'host' => COption::GetOptionString ( _SmptModuleName_, "settings_smtp_host" ),
                        'port' => COption::GetOptionString ( _SmptModuleName_, "settings_smtp_port", 25 ),
                        'connection_class' => COption::GetOptionString ( _SmptModuleName_, "settings_smtp_type_auth" ),
                        'connection_config' => $connection_config,
                );
            if(!empty($sFrom)) {

                if ( !empty( $email ) ) {
                    $email = explode ( ',', $email );
                    if ( array_search ( $sFrom, $email ) !==FALSE) {
                        $connection_config = array (
                            'username' => COption::GetOptionString ( _SmptModuleName_, "advs[$sFrom][login]" ),
                            'password' => COption::GetOptionString ( _SmptModuleName_, "advs[$sFrom][password]" ),
                        );
                        $ssl = COption::GetOptionString ( _SmptModuleName_, "advs[$sFrom][encryption]" );
                        if ( $ssl != 'no' ) {
                            $connection_config[ 'ssl' ] = $ssl;
                        }
                        $aOptions = array (
                            'host' => COption::GetOptionString ( _SmptModuleName_, "advs[$sFrom][host]" ),
                            'port' => COption::GetOptionString ( _SmptModuleName_, "advs[$sFrom][port]" ),
                            'connection_class' => COption::GetOptionString ( _SmptModuleName_, "advs[$sFrom][type_auth]" ),
                            'connection_config' => $connection_config,
                        );
                    }
                }
            }
            $options = new SmtpOptions( $aOptions);
            $transport->setOptions( $options );
            return $transport;
        }

        public static function WsrubiSMTPLog ( $text, $type = "info" )
        {
            switch ( $type ) {
                case 'error':
                    $SEVERITY = 'ERROR';
                    break;
                default:
                case 'info':
                    $SEVERITY = 'INFO';
                    break;
            }
            if ( COption::GetOptionString ( _SmptModuleName_, "settings_smtp_log" ) ) {
                CEventLog::Log ( $SEVERITY, 'SEND_MESSAGE', _SmptModuleName_, _SmptModuleName_, $text, SITE_ID );
            }
            return true;
        }
    }
}
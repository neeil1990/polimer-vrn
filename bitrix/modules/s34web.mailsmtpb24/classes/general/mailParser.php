<?php
/**
 * Created: 04.08.2021, 11:40
 * Author : Dmitry Antiptsev <cto@34web.ru>
 * Company: 34web Studio
 */

namespace s34web\mailSMTPB24;

class mailParser
{
    public $emailFrom = [];
    public $emailTo = [];
    public $emailBcc = [];
    public $emailReplyTo = [];
    public $emailCc = [];
    public $emailHtmlContent = false;
    public $emailContentType = 'text/html';
    public $emailCharSet = 'UTF-8';
    public $boundary = '';
    public $emailSenderPosting = false;

    /**
     * mailParser constructor.
     * @param $to
     * @param $subject
     * @param $message
     * @param string $additionalHeaders
     * @param string $additionalParameters
     */
    function __construct($to, $subject, $message, $additionalHeaders = '', $additionalParameters = '')
    {
        $this->parseMainHeaders($additionalHeaders);
        $this->parseHeaders($additionalHeaders);
        $this->parseContent($message);
        $this->parseEmails($to);
    }
    /**
     * get main headers as charset & boundary
     * @param $emailHeaders
     */
    function parseMainHeaders($emailHeaders)
    {
        $headersString = str_replace(["\r\n", "\r"], "\n", $emailHeaders);
        $headersList = explode("\n", $headersString);
        $arHeaders = [];
        foreach ($headersList as $header) {
            $headerLine = explode(': ', $header);
            if (count($headerLine) > 1) {
                $arHeaders[$headerLine[0]] = trim($headerLine[1]);
            }
        }
        unset($header);
        if (!empty($arHeaders)) {
            foreach ($arHeaders as $headerKey => $headerValue) {
                if (strtolower($headerKey) == 'content-type') {
                    $this->emailContentType = $headerValue;
                    $tmpContentList = explode('; ', $headerValue);
                    if (count($tmpContentList) > 0) {
                        // check charset
                        if (trim($tmpContentList[0]) == 'text/html') {
                            $this->emailHtmlContent = true;
                            if (!empty($tmpContentList[1])) {
                                if (stripos($tmpContentList[1], 'charset') !== false) {
                                    $this->emailCharSet = str_replace('charset=', '',
                                        trim($tmpContentList[1]));
                                }
                            }
                        }
                        // check boundary
                        if (trim($tmpContentList[0]) == 'multipart/alternative' ||
                            trim($tmpContentList[0]) == 'multipart/mixed') {
                            if (!empty($tmpContentList[1])) {
                                if (stripos($tmpContentList[1], 'boundary') !== false) {
                                    $this->boundary = str_replace('boundary=', '',
                                        trim($tmpContentList[1]));
                                    if(!empty($this->boundary))
                                    {
                                        $this->boundary = str_replace('"', '', $this->boundary);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            unset($header);
        }
    }
    /**
     * Get params from headers
     * @param $emailHeaders
     */
    function parseHeaders($emailHeaders)
    {
        $headersString = str_replace(["\r\n", "\r"], "\n", $emailHeaders);
        $headersList = explode("\n", $headersString);
        $arHeaders = [];
        foreach ($headersList as $header) {
            $headerLine = explode(': ', $header);
            if (count($headerLine) > 1) {
                $arHeaders[$headerLine[0]] = trim($headerLine[1]);
            }
        }
        unset($header);
        if (!empty($arHeaders)) {
            $parserPhpMailer = new PHPMailer();
            foreach ($arHeaders as $headerKey => $headerValue) {
                // check main headers
                if (strtolower($headerKey) == 'from') {
                    $sender = $parserPhpMailer->parseAddresses($headerValue, true, $this->emailCharSet);
                    if (!empty($sender)) {
                        $this->emailFrom = current($sender);
                    }
                }
                if (strtolower($headerKey) == 'bcc') {
                    $bccList = $parserPhpMailer->parseAddresses($headerValue, true, $this->emailCharSet);
                    if (!empty($bccList)) {
                        foreach ($bccList as $bcc) {
                            $this->emailBcc[] = $bcc;
                        }
                        unset($bcc);
                    }
                }
                if (strtolower($headerKey) == 'cc') {
                    $ccList = $parserPhpMailer->parseAddresses($headerValue, true, $this->emailCharSet);
                    if (!empty($ccList)) {
                        foreach ($ccList as $cc) {
                            $this->emailCc[] = $cc;
                        }
                        unset($cc);
                    }
                }
                if (strtolower($headerKey) == 'reply-to') {
                    $replyTo = $parserPhpMailer->parseAddresses($headerValue, true, $this->emailCharSet);
                    if (!empty($replyTo)) {
                        $this->emailReplyTo = current($replyTo);
                    }
                }
                // check sender posting header
                if (strtolower($headerKey) == 'x-sender-posting') {
                    if (!empty($headerValue)) {
                        $this->emailSenderPosting = true;
                    }
                }
            }
            unset($header);
        }
    }
    /**
     * Get Emails for send
     * @param $emailRecievers
     */
    function parseEmails($emailRecievers)
    {
        $parserPhpMailer = new \s34web\mailSMTPB24\PHPMailer();
        $recievers = $parserPhpMailer->parseAddresses($emailRecievers, true, $this->emailCharSet);
        foreach ($recievers as $reciever) {
            $this->emailTo[] = $reciever;
        }
        unset($reciever);
    }
    /**
     * Get charset & html content flag from body
     * @param $content
     */
    function parseContent($content)
    {
        $contentHeadersString = str_replace(["\r\n", "\r"], "\n", $content);
        $contentHeadersList = explode("\n", $contentHeadersString);
        $arContentHeaders = [];
        foreach ($contentHeadersList as $header) {
            $headerLine = explode(': ', $header);
            if (count($headerLine) > 1) {
                $arContentHeaders[$headerLine[0]] = trim($headerLine[1]);
            }
        }
        unset($header);
        if (!empty($arContentHeaders)) {
            foreach ($arContentHeaders as $headerKey => $headerValue) {
                if (strtolower($headerKey) == 'content-type') {
                    $tmpContentList = explode('; ', $headerValue);
                    if (count($tmpContentList) > 0) {
                        if (trim($tmpContentList[0]) == 'text/html') {
                            $this->emailHtmlContent = true;
                        }
                    }
                }
            }
            unset($header);
        }
    }
}

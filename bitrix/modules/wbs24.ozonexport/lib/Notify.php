<?php
namespace Wbs24\Ozonexport;

class Notify
{
    protected const TAG = "OZONEXPORT";
    protected const MODULE_ID = "wbs24.ozonexport";

    public function addAdminNotify($message, $error = false)
    {
        $notify = [
            "MESSAGE" => $message,
            "TAG" => self::TAG.($error ? "_ERROR" : ""),
            "MODULE_ID" => self::MODULE_ID,
            "ENABLE_CLOSE" => "Y",
            "LANG" => [],
            "NOTIFY_TYPE" => $error ? "E" : "",
        ];
        \CAdminNotify::Add($notify);
    }
}

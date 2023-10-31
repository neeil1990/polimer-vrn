<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\SystemException;

trait Exception
{
    public function exceptionHandler($exception)
    {
        $this->lastError =
            $exception->getFile()."(".$exception->getLine()."): ".$exception->getMessage()."\r\n".
            $exception->getTraceAsString()
        ;
        $this->createReport('exception_log.txt', $this->lastError);

        // завершение отключено, чтобы не было аварийного завершения формирования фида
        //echo $this->lastError;
        //die();
    }

    public function createReport($fileName, $report)
    {
        $prefix = trim(strtolower(str_replace('\\', '_', __NAMESPACE__)), '_');

        $text = date('Y.m.d H:i:s')."\r\n";
        $text .= print_r($report, true)."\r\n\r\n";

        $handle = @fopen($_SERVER['DOCUMENT_ROOT'].'/upload/'.$prefix.'_'.$fileName, 'a');
        fwrite($handle, $text);
        fclose($handle);
    }
}

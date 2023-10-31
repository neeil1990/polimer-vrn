<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\SystemException;

class BasicAuth
{
    protected $param;

    public function __construct($param)
    {
        $this->param = $param;
    }

    public function getAuthConditionCode($fullPathExportNewLinkedFile)
    {
        $code = '';
        $basicCode = 'require("'.$fullPathExportNewLinkedFile.'");';

        if ($this->isNeedAuth()) {
            $code =
                '<?php header("Cache-Control: no-cache, must-revalidate, max-age=0");'
                .'$isEmptyCredentialsFilled = (empty($_SERVER["PHP_AUTH_USER"]) && empty($_SERVER["PHP_AUTH_PW"]));'
                .'$isNotPassedValidation = ('
                    .'$isEmptyCredentialsFilled'
                    .'|| md5($_SERVER["PHP_AUTH_USER"]) != "' .  md5($this->param['feedLogin']) . '"'
                    .'|| md5($_SERVER["PHP_AUTH_PW"]) != "' .  md5($this->param['feedPass']) . '"'
                .');'
                .'if ($isNotPassedValidation) {'
                    .'header("HTTP/1.1 401 Authorization Required");'
                    .'header("WWW-Authenticate: Basic realm=\"Access denied\"");'
                    .'exit;'
                .'}'
                . $basicCode
            ;
        } else {
            $code = '<?php ' . $basicCode;
        }

        return $code;
    }

    protected function isNeedAuth()
    {
        $needAuth = false;

        if (
            $this->param['feedProtectedFlag']
            && $this->param['feedLogin']
            && $this->param['feedPass']
        ) {
            $needAuth = true;
        }

        return $needAuth;
    }
}

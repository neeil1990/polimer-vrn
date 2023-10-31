<?php
IncludeModuleLangFile(__FILE__);

class thebrainstech_copyiblock extends CModule {
    public $MODULE_ID = "thebrainstech.copyiblock";

    function __construct(){

        $arModuleVersion = array();
        include(__DIR__.'/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_DESC');
        $this->PARTNER_NAME = GetMessage('THEBRAINSE_COPYIBLOCK_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('THEBRAINSE_COPYIBLOCK_PARTNER_URL');
    }

    function DoInstall(){
        global $APPLICATION;

        RegisterModule($this->MODULE_ID);
        RegisterModuleDependences('main', 'OnAdminContextMenuShow', $this->MODULE_ID, 'NBrains\CopyIBlock\Events','index');
        $APPLICATION->IncludeAdminFile(GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_INSTALL'),
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/$this->MODULE_ID/install/step.php");
    }

    function DoUninstall(){
        UnRegisterModule($this->MODULE_ID);
        UnRegisterModuleDependences('main', 'OnAdminContextMenuShow', $this->MODULE_ID, 'NBrains\CopyIBlock\Events','index');
    }

}

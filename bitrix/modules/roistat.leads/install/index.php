<?php

IncludeModuleLangFile(__FILE__);

class roistat_leads extends CModule {

    var $MODULE_ID = "roistat.leads";

    //при обявлении класса код выполняеться сразу
    function __construct(){

    $arModuleVersion = array();
    include(__DIR__.'/version.php');

    $this->MODULE_ID = 'roistat.leads';
    $this->MODULE_VERSION = $arModuleVersion['VERSION'];
    $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

    $this->MODULE_NAME = "roistat proxyLeads";
    $this->MODULE_DESCRIPTION = "";
    $this->PARTNER_NAME = "";
    $this->PARTNER_URI = "";

    }

    function DoInstall(){
        global $APPLICATION;
        RegisterModule($this->MODULE_ID);

        RegisterModuleDependences('main', 'OnBeforeEventSend', $this->MODULE_ID, 'RoiStat','send');

        $APPLICATION->IncludeAdminFile($this->MODULE_NAME,$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/roistat.leads/install/step.php");
    }

    function DoUninstall(){
        global $APPLICATION;
        UnRegisterModule($this->MODULE_ID);

        UnRegisterModuleDependences('main', 'OnBeforeEventSend', $this->MODULE_ID, 'RoiStat','send');

        $APPLICATION->IncludeAdminFile($this->MODULE_NAME,$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/roistat.leads/install/unstep.php");
    }

}
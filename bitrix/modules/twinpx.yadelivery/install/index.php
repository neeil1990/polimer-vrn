<?php
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\EventManager,
	\Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

if(class_exists("twinpx.yadelivery"))
{
	return;
}

Class twinpx_yadelivery extends CModule
{
    var $MODULE_ID = "twinpx.yadelivery";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $errors;
	var $MODULE_GROUP_RIGHTS = "Y";

    function __construct()
    {
		$arModuleVersion = array();

		include(__DIR__.'/version.php');
		
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = GetMessage("TWINPX_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("TWINPX_MODULE_DESCRIPTION");
        
        $this->PARTNER_NAME = GetMessage("TWINPX_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("TWINPX_PARTNER_URI");
    }

    function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallMessage();
        \Bitrix\Main\ModuleManager::RegisterModule($this->MODULE_ID);
        
        LocalRedirect('/bitrix/admin/settings.php?lang=ru&mid='.$this->MODULE_ID);
        return true;
    }

    function DoUninstall()
    {
		global $APPLICATION, $step;
        
    	$step = intval($step);		
        if($step < 2) { //выводим предупреждение
			$APPLICATION->IncludeAdminFile(GetMessage('TWINPX_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'. $this->MODULE_ID .'/install/unstep.php');
		} 
		elseif($step == 2) {
			//проверяем условие
			if($_REQUEST['save'] != 'Y' && !isset($_REQUEST['save'])) {
				$this->UnInstallDB();
			}
			$this->UnInstallFiles();
			$this->UnInstallEvents();
			$this->UnInstallMessage();
			
        	\Bitrix\Main\ModuleManager::UnRegisterModule($this->MODULE_ID);

	        return true;
		}
    }

    function InstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/". $this->MODULE_ID ."/install/db/install.sql");
        if (!$this->errors) {
            return true;
        } else {
        	$APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
		}
        return true;
    }

    function UnInstallDB()
    {
        global $DB, $DBType, $APPLICATION;

        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/". $this->MODULE_ID ."/install/db/uninstall.sql");
        if (!$this->errors) {
            return true;
        } 
        else {			
       	 	$APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
		}
    }

    function InstallEvents()
    {
        CModule::IncludeModule("sale");
        //событие дл¤ регистраци§ достави в список
        RegisterModuleDependences("sale", "onSaleDeliveryHandlersBuildList",
            $this->MODULE_ID, "TwinpxDelivery", "Init");
                
        //событие дл¤ срабатываение при переключение типов доставки   
        RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery",
            $this->MODULE_ID, "TwinpxDelivery", "OrderDeliveryBuildList");
		    
		EventManager::getInstance()->registerEventHandler("sale", "OnSaleOrderSaved",
		    $this->MODULE_ID, "TwinpxDelivery", "OrderSave");        
		
		//создание агента
		CAgent::AddAgent("TwinpxDelivery::Agent();", $this->MODULE_ID, "N", 1800, "Y");
		CAgent::AddAgent("TwinpxDelivery::AgentShipment();", $this->MODULE_ID, "N", 60, "Y");
		
		//создаем свойство для модуля
        $obPersonTyle = CSalePersonType::GetList(Array("SORT" => "ASC"), Array("ACTIVE" => "Y"));
        while ($arType = $obPersonTyle->Fetch()) {
            $listType[$arType['ID']] = $arType;
        }
        if (!empty($listType)) {
            foreach ($listType as $typeID => $type) {
                $arFields = array(
                    "PERSON_TYPE_ID"=> $typeID,
                    "NAME"          => GetMessage('ORDER_PROPS_PVZ'),
                    "TYPE"          => "TEXT",
                    "SORT"          => 900,
                    "CODE"          => "YD_PVZ",
                    "UTIL"          => "Y",
                    "PROPS_GROUP_ID"=> 2,
                );
                CSaleOrderProps::Add($arFields);
            }
        }
		
        return true;
    }

    function UnInstallEvents()
    {
        CModule::IncludeModule("sale");
        UnRegisterModuleDependences("sale", "onSaleDeliveryHandlersBuildList",
            $this->MODULE_ID, "TwinpxDelivery", "Init");
        		
        UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery",
            $this->MODULE_ID, "TwinpxDelivery", "OrderDeliveryBuildList");
		
        EventManager::getInstance()->unRegisterEventHandler("sale", "OnSaleOrderSaved",
		    $this->MODULE_ID, "TwinpxDelivery", "OrderSave"); 	
		
		//удаление агентов
		CAgent::RemoveAgent("TwinpxDelivery::Agent();", $this->MODULE_ID);
		CAgent::RemoveAgent("TwinpxDelivery::AgentShipment();", $this->MODULE_ID);
		
		//удаляем свойство для модуля
        $dborderprops = CSaleOrderProps::GetList(($f = "ID"), ($o = "ASC"), Array("CODE" => 'YD_PVZ'));
        while ($arorderprops = $dborderprops->Fetch()) {
            CSaleOrderProps::Delete($arorderprops['ID']);
        }
		
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER['DOCUMENT_ROOT']."/bitrix/admin/", true); //админка
		
    	CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID, true);
    	CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID.'/admin/', true);
    	
    	CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/css/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$this->MODULE_ID, true);
    	CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/css/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$this->MODULE_ID.'/admin/', true);
    	
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/tools/", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools/".$this->MODULE_ID, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/tools/admin/", $_SERVER['DOCUMENT_ROOT']."/bitrix/tools/".$this->MODULE_ID.'/admin/', true);
        
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/images/", $_SERVER['DOCUMENT_ROOT']."/bitrix/images/".$this->MODULE_ID, true);
        
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/js/admin/".$this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/js/".$this->MODULE_ID);
        
        DeleteDirFilesEx("/bitrix/css/admin/".$this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/css/".$this->MODULE_ID);
        
        DeleteDirFilesEx("/bitrix/tools/admin/".$this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/tools/".$this->MODULE_ID);
        
        DeleteDirFilesEx("/bitrix/images/".$this->MODULE_ID);
        
		//удал¤ю файлы из папку admin, указываем какие из модуль
        DeleteDirFiles(
            $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/admin",
            $_SERVER['DOCUMENT_ROOT']."/bitrix/admin"
        );
        
		
        return true;
    }
    
    function InstallMessage()
    {
		//список активных сайтов
		$db_res = CSite::GetList($by="sort", $order="desc", array("ACTIVE"=>"Y"));
		while($res = $db_res->Fetch()){
			$arSites[] = $res['LID']; //записываем ID сайтов
		}
		
		//создаем почтовый шаблон
		$arCEventTypes = array(
	    	'LID' => SITE_ID,
	        'EVENT_NAME' => 'TWPX_YANDEX_ORDER',
	        'NAME' => GetMessage('TWINPX_EVENT_TYPE_NAME'),
	        'DESCRIPTION' => GetMessage('TWINPX_EVENT_TYPE_DESCRIPTION')
		);
		$evType = new CEventType;
		$resType = $evType->Add($arCEventTypes);
		
		$arCEventTemplates = array(
	        'ACTIVE'=> 'Y',
	        'EVENT_NAME' => 'TWPX_YANDEX_ORDER',
	        'LID' => $arSites,
	        'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
	        'EMAIL_TO' => '#SALE_EMAIL#',
	        'SUBJECT' => GetMessage('TWINPX_EVENT_TEMPLATE_SUBJECT'),
	        'BODY_TYPE' => 'html',
	        'MESSAGE' => GetMessage('TWINPX_EVENT_TEMPLATE_MESSAGE')
		);
		$evMessage = new CEventMessage;
		$resMessage = $evMessage->Add($arCEventTemplates);
		
		//создаем почтовый шаблон
		$arCEventTypes = array(
			'LID' => SITE_ID,
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CREATE',
			'NAME' => Loc::GetMessage('TWINPX_EVENT2_TYPE_NAME'),
			'DESCRIPTION' => Loc::GetMessage('TWINPX_EVENT2_TYPE_DESCRIPTION')
		);
		$evType = new CEventType;
		$resType = $evType->Add($arCEventTypes);

		$arCEventTemplates = array(
			'ACTIVE'=> 'Y',
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CREATE',
			'LID' => $arSites,
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO' => '#SALE_EMAIL#',
			'SUBJECT' => Loc::GetMessage('TWINPX_EVENT2_TEMPLATE_SUBJECT'),
			'BODY_TYPE' => 'html',
			'MESSAGE' => Loc::GetMessage('TWINPX_EVENT2_TEMPLATE_MESSAGE')
		);
		$evMessage = new CEventMessage;
		$resMessage = $evMessage->Add($arCEventTemplates);		

		//создаем почтовый шаблон
		$arCEventTypes = array(
			'LID' => SITE_ID,
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CANCEL',
			'NAME' => Loc::GetMessage('TWINPX_EVENT3_TYPE_NAME'),
			'DESCRIPTION' => Loc::GetMessage('TWINPX_EVENT3_TYPE_DESCRIPTION')
		);
		$evType = new CEventType;
		$resType = $evType->Add($arCEventTypes);

		$arCEventTemplates = array(
			'ACTIVE'=> 'Y',
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CANCEL',
			'LID' => $arSites,
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO' => '#SALE_EMAIL#',
			'SUBJECT' => Loc::GetMessage('TWINPX_EVENT3_TEMPLATE_SUBJECT'),
			'BODY_TYPE' => 'html',
			'MESSAGE' => Loc::GetMessage('TWINPX_EVENT3_TEMPLATE_MESSAGE')
		);
		$evMessage = new CEventMessage;
		$resMessage = $evMessage->Add($arCEventTemplates);
		
		//создаем почтовый шаблон
		$arCEventTypes = array(
			'LID' => SITE_ID,
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CREATE_PAID',
			'NAME' => Loc::GetMessage('TWINPX_EVENT4_TYPE_NAME'),
			'DESCRIPTION' => Loc::GetMessage('TWINPX_EVENT4_TYPE_DESCRIPTION')
		);
		$evType = new CEventType;
		$resType = $evType->Add($arCEventTypes);

		$arCEventTemplates = array(
			'ACTIVE'=> 'Y',
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CREATE_PAID',
			'LID' => $arSites,
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO' => '#SALE_EMAIL#',
			'SUBJECT' => Loc::GetMessage('TWINPX_EVENT4_TEMPLATE_SUBJECT'),
			'BODY_TYPE' => 'html',
			'MESSAGE' => Loc::GetMessage('TWINPX_EVENT4_TEMPLATE_MESSAGE')
		);
		$evMessage = new CEventMessage;
		$resMessage = $evMessage->Add($arCEventTemplates);		
		
		//создаем почтовый шаблон
		$arCEventTypes = array(
			'LID' => SITE_ID,
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CANCEL_PAID',
			'NAME' => Loc::GetMessage('TWINPX_EVENT5_TYPE_NAME'),
			'DESCRIPTION' => Loc::GetMessage('TWINPX_EVENT5_TYPE_DESCRIPTION')
		);
		$evType = new CEventType;
		$resType = $evType->Add($arCEventTypes);

		$arCEventTemplates = array(
			'ACTIVE'=> 'Y',
			'EVENT_NAME' => 'TWPX_YANDEX_ORDER_CANCEL_PAID',
			'LID' => $arSites,
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO' => '#SALE_EMAIL#',
			'SUBJECT' => Loc::GetMessage('TWINPX_EVENT5_TEMPLATE_SUBJECT'),
			'BODY_TYPE' => 'html',
			'MESSAGE' => Loc::GetMessage('TWINPX_EVENT5_TEMPLATE_MESSAGE')
		);
		$evMessage = new CEventMessage;
		$resMessage = $evMessage->Add($arCEventTemplates);
		
		//создаем почтовый шаблон
		$arCEventTypes = array(
			'LID' => SITE_ID,
			'EVENT_NAME' => 'TWPX_YANDEX_CREATE_OFFER',
			'NAME' => Loc::GetMessage('TWINPX_EVENT6_TYPE_NAME'),
			'DESCRIPTION' => Loc::GetMessage('TWINPX_EVENT6_TYPE_DESCRIPTION')
		);
		$evType = new CEventType;
		$resType = $evType->Add($arCEventTypes);

		$arCEventTemplates = array(
			'ACTIVE'=> 'Y',
			'EVENT_NAME' => 'TWPX_YANDEX_CREATE_OFFER',
			'LID' => $arSites,
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO' => '#EMAIL#',
			'SUBJECT' => Loc::GetMessage('TWINPX_EVENT6_TEMPLATE_SUBJECT'),
			'BODY_TYPE' => 'html',
			'MESSAGE' => Loc::GetMessage('TWINPX_EVENT6_TEMPLATE_MESSAGE')
		);
		$evMessage = new CEventMessage;
		$resMessage = $evMessage->Add($arCEventTemplates);
	}

	function UnInstallMessage() 
	{
		//удаляем почтовые шаблоны
		$rsMess = CEventMessage::GetList($by="site_id", $order="asc", array("TYPE_ID" => array("TWPX_YANDEX_ORDER", "TWPX_YANDEX_ORDER_CREATE", "TWPX_YANDEX_ORDER_CANCEL", "TWPX_YANDEX_ORDER_CREATE_PAID", "TWPX_YANDEX_ORDER_CANCEL_PAID")));
		while($arMess = $rsMess->GetNext())
		{
			CEventMessage::Delete($arMess['ID']);
		}
		//удаляем тип почтовой событие
		$evType = new CEventType;
		$evType->Delete("TWPX_YANDEX_ORDER");
		$evType->Delete("TWPX_YANDEX_ORDER_CREATE");
		$evType->Delete("TWPX_YANDEX_ORDER_CANCEL");
		$evType->Delete("TWPX_YANDEX_ORDER_CREATE_PAID");
		$evType->Delete("TWPX_YANDEX_ORDER_CANCEL_PAID");
		$evType->Delete("TWPX_YANDEX_CREATE_OFFER");
		
	}

	function GetModuleRightList()
    {
        $arr = array(
            "reference_id" => array("D","R","W"),
            "reference"    => array(
                "[D] ".GetMessage("TWPX_PERM_D"),
                "[R] ".GetMessage("TWPX_PERM_R"),
                "[W] ".GetMessage("TWPX_PERM_W")
            )
        );
        return $arr;
    }
}
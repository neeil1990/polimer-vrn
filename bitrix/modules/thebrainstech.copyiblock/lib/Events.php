<?php
namespace NBrains\CopyIBlock;

IncludeModuleLangFile(__FILE__);

class Events {

    static public function index(&$menu){
        global $APPLICATION;
        /** @global $USER CUser */
        global $USER;

        if (!\CModule::IncludeModule("iblock") || !$USER->IsAdmin())
            return false;

        $action = new \ActionController();

        if ($_SERVER['REQUEST_METHOD'] == 'GET' && $APPLICATION->GetCurPage() == '/bitrix/admin/iblock_edit.php')
            $action->menuAction($menu);
        elseif ($_REQUEST['IBLOCKCOPY_ACTION'] == "COPY")
            $action->createAction($_REQUEST);

        return true;
    }
}

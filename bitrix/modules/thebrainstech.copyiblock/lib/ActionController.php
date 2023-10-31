<?php

use Bitrix\Main\Application;

class ActionController
{
    protected $app;
    protected $smarty;
    protected $moduleId = "thebrainstech.copyiblock";

    public function __construct()
    {
        global $APPLICATION;
        $this->app = $APPLICATION;

        $this->smarty = new Smarty();
        $this->root = Application::getDocumentRoot();
        $this->smarty->setTemplateDir($this->root . '/bitrix/modules/'. $this->moduleId .'/templates');
    }

    public function menuAction(&$menu)
    {
        $dialog = new DialogMenu();
        $menu[] = array(
            "ICON" => "btn_new",
            "MENU" => [
                $dialog->createMenu(),
            ],
        );
    }

    public function createAction($request)
    {
        if($IBLOCK_ID = intval($request['ID'])){
            $this->app->RestartBuffer();

            $iblock = new IBlockComponent($IBLOCK_ID, $_REQUEST["TYPE"]);

            if($_REQUEST["IBLOCKCOPIES"] === 'SECTIONS')
                $iblock = new SectionDecorator($iblock);

            if($_REQUEST["IBLOCKCOPIES"] === 'ELEMENTS'){
                $iblock = new SectionDecorator($iblock);
                $iblock = new ElementDecorator($iblock);
            }

            $iblock->create();

            $this->smarty->assign('id', $iblock->IBlockID);
            $this->smarty->assign('type', $request['TYPE']);

            print $this->smarty->fetch('done.tpl');
        }
        die;
    }
}

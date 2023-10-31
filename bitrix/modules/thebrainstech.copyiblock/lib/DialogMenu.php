<?php

class DialogMenu extends Menu
{
    public function __construct()
    {
        parent::__construct();

        $text = GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY');
        $this->title = $text;
        $this->text = $text;
    }

    public function createMenu()
    {
        global $APPLICATION;

        $ID = $_REQUEST['ID'];
        $types = CIBlockMain::getTypes();
        $name = CIBlockMain::getTypeNameByID($ID);
        $title = $this->getTitle() . " ID:" . $ID . " " . $name;

        $this->smarty->assign('title', $title);
        $this->smarty->assign('types', $types);
        $this->smarty->assign('ID', $ID);
        $this->smarty->assign('params', $APPLICATION->GetCurPageParam("", array("mode", "table_id")));

        $dialog = $this->smarty->fetch('dialog.tpl');
        $this->setAction($dialog);

        return $this->getMenu();
    }
}

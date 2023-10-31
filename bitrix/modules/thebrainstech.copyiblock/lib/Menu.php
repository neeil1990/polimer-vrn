<?php

use Bitrix\Main\Application;

IncludeModuleLangFile(__FILE__);

abstract class Menu
{
    protected $title = "Title";
    protected $text = "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aut consequatur, eaque ipsum nihil nulla placeat quo tempora. Consectetur doloribus fugiat illum iusto pariatur recusandae sed totam. Deleniti fugiat sit veniam!";
    protected $icon = "copy";
    protected $action = "";
    protected $moduleId = "thebrainstech.copyiblock";
    protected $smarty;
    protected $root;

    public function __construct()
    {
        $this->smarty = new Smarty();
        $this->root = Application::getDocumentRoot();
        $this->smarty->setTemplateDir($this->root . '/bitrix/modules/'. $this->moduleId .'/templates');
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getMenu()
    {
        return [
            "TEXT" => $this->getText(),
            "TITLE" => $this->getTitle(),
            "ACTION" => $this->getAction(),
            "ICON" => $this->getIcon(),
        ];
    }

    abstract public function createMenu();
}

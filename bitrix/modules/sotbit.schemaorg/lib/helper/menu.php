<?php

namespace Sotbit\Schemaorg\Helper;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class Menu
{
    public static function getAdminMenu(
        &$arGlobalMenu,
        &$arModuleMenu
    ) {

        $sites = Site::getList();
        $settings = [];
        $develop = [];
        foreach ($sites as $lid => $name) {
            $settings[$lid] = [
                "text"  => ' ['.$lid.'] '.$name,
                "url"   => '/bitrix/admin/sotbit_origami_settings.php?lang='
                    .LANGUAGE_ID.'&SITE_ID='.$lid,
                "title" => ' ['.$lid.'] '.$name,
            ];
            $develop[$lid] = [
                "text"  => ' ['.$lid.'] '.$name,
                "url"   => '/bitrix/admin/sotbit_origami_develop.php?lang='
                    .LANGUAGE_ID.'&SITE_ID='.$lid,
                "title" => ' ['.$lid.'] '.$name,
            ];
        }

        if (!isset($arGlobalMenu['global_menu_sotbit'])) {
            $arGlobalMenu['global_menu_sotbit'] = [
                'menu_id'   => 'sotbit',
                'text'      => Loc::getMessage(
                    \SotbitSchema::MODULE_ID.'_GLOBAL_MENU'
                ),
                'title'     => Loc::getMessage(
                    \SotbitSchema::MODULE_ID.'_GLOBAL_MENU'
                ),
                'sort'      => 1000,
                'items_id'  => 'global_menu_sotbit_items',
                "icon"      => "",
                "page_icon" => "",
            ];
        }

        $iModuleID = "sotbit.schemaorg";
        global $APPLICATION;
        if ($APPLICATION->GetGroupRight($iModuleID) != "D") {

            $rsSites = \CSite::GetList($by="sort", $order="ASC", Array("ACTIVE"=>"Y"));
            while ($arSite = $rsSites->Fetch())
            {
                $Sites[]=$arSite;
            }

            unset($rsSites);
            unset($arSite);

            $Paths=array('settings'=>'.php');
            if(count($Sites)==1)//If one site
            {
                $Site = current($Sites);
                $Settings[$key]=array(
                    "text" => "[$Site[LID]] ".GetMessage("MENU_SCHEMA_SETTING_TEXT"),
                    "items_id" => "menu_sotbit.schema_settings_" . $Site['LID'],
                    "dynamic" => true,
                    "items" =>  array(
                        array(
                            "text" => GetMessage("MENU_SCHEMA_URL_LIST_TEXT"),
                            "url" => "sotbit.schema_url_list.php?lang=".LANGUAGE_ID.'&SITE_ID='.$Site['LID'],
                            "title" => $Site['NAME'],
                            'more_url' => array(
                                "sotbit.schema_category_edit.php?lang=ru&SITE_ID=".$Site['LID'],
                                "sotbit.schema_edit.php?lang=ru&SITE_ID=".$Site['LID']
                            ),
                        ),
                        array(
                            "text" => GetMessage("MENU_SCHEMA_SETTINGS_TEXT"),
                            "url" => "sotbit.schema_settings.php?lang=".LANGUAGE_ID.'&SITE_ID='.$Site['LID'],
                            "title" => $Site['NAME'],
                            'more_url' => array(
                                "sotbit.schema_category_edit.php?lang=ru&SITE_ID=".$Site['LID'],

                            ),
                        )
                    ),
                    "title" => GetMessage("MENU_SCHEMA_" . strtoupper($key) . "_TEXT")
                );
            }
            else//If some site
            {
                foreach ($Paths as $key => $Path) {
                    foreach ($Sites as $Site) {
                        $Settings[] = array(
                            "text" => "[$Site[LID]] " . GetMessage("MENU_SCHEMA_EDIT_TEXT"),
                            "items_id" => "menu_sotbit.schema_settings_" . $Site['LID'],
                            "dynamic" => true,
                            "items" => array(
                                array(
                                    "text" => GetMessage("MENU_SCHEMA_URL_LIST_TEXT"),
                                    "url" => "sotbit.schema_url_list.php?lang=" . LANGUAGE_ID . '&SITE_ID=' . $Site['LID'],
                                    "title" => $Site['NAME'],
                                    'more_url' => array(
                                        "sotbit.schema_category_edit.php?lang=ru&SITE_ID=".$Site['LID'],
                                        "sotbit.schema_edit.php?lang=ru&SITE_ID=".$Site['LID']
                                    ),
                                ),
                                array(
                                    "text" => GetMessage("MENU_SCHEMA_SETTINGS_TEXT"),
                                    "url" => "sotbit.schema_settings.php?lang=" . LANGUAGE_ID . '&SITE_ID=' . $Site['LID'],
                                    "title" => $Site['NAME'],
                                    'more_url' => array(
                                        "sotbit.schema_category_edit.php?lang=ru&SITE_ID=".$Site['LID']
                                    ),
                                )
                            ),
                            "title" => GetMessage("MENU_SCHEMA_" . strtoupper($key) . "_TEXT")
                        );
                    }
                }
            }

            $aMenu = array(
                "parent_menu" => 'global_menu_sotbit',
                "section" => 'sotbit.schemaorg',
                "sort" => 800,
                "text" => GetMessage("MENU_SCHEMA_TEXT"),
                "title" => GetMessage("MENU_SCHEMA_TITLE"),
                "icon" => "schema_menu_icon",
                "page_icon" => "schema_page_icon",
                "items_id" => "menu_sotbit.schema",
                "dynamic" => true,
                'items' => $Settings,
            );

            $arGlobalMenu['global_menu_sotbit']['items']['sotbit.schemaorg'] = $aMenu;
        }
    }
}

?>
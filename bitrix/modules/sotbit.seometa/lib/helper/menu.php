<?php

namespace Sotbit\Seometa\Helper;

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
                    \CCSeoMeta::MODULE_ID.'_GLOBAL_MENU'
                ),
                'title'     => Loc::getMessage(
                    \CCSeoMeta::MODULE_ID.'_GLOBAL_MENU'
                ),
                'sort'      => 1000,
                'items_id'  => 'global_menu_sotbit_items',
                "icon"      => "",
                "page_icon" => "",
            ];
        }

        $iModuleID = "sotbit.seometa";
        global $APPLICATION;
        if ($APPLICATION->GetGroupRight($iModuleID) != "D") {

            $rsSites = \CSite::GetList($by="sort", $order="ASC", Array("ACTIVE"=>"Y"));
            while ($arSite = $rsSites->Fetch())
            {
                $Sites[]=$arSite;
            }

            $arDefSites = \CSite::GetList($by="sort", $order="desc", Array("ACTIVE"=>"Y", 'DEFAULT' => 'Y')) ->fetch();

            unset($rsSites);
            unset($arSite);

            $Paths=array('settings'=>'.php');
            if(count($Sites)==1)//If one site
            {
//                $key = 0;
                $Site = current($Sites);
                $Settings[$key]=array(
                    "text" => "[$Site[LID]] ".GetMessage("MENU_SEOMETA_".$key."_SETTINGS_TEXT"),
                    "items_id" => "menu_sotbit.seometa_settings_".$Site[LID],
                    "dynamic" => true,
                    "title" => Loc::getMessage("MENU_SEOMETA_".$key."_SETTINGS_TEXT"),
                    "items" => array(
                        array(
                            "text" => Loc::getMessage("MENU_SEOMETA_LIST_OF_CONDITIONS_TEXT"),
                            "url" => "sotbit.seometa_list.php?lang=" . LANGUAGE_ID,
                            "more_url" => array(
//                                "sotbit.seometa_list.php?lang=" . LANGUAGE_ID,
                                "sotbit.seometa_edit.php",
                                "sotbit.seometa_section_edit.php",
                            ),
                            "title" => Loc::getMessage("MENU_SEOMETA_LIST_OF_CONDITIONS_TITLE")
                        ),
                        array(
                            "text" => Loc::getMessage("MENU_SEOMETA_AUTOGENERATION_OF_CONDITIONS_TEXT"),
                            "url" => "sotbit.seometa_autogeneration_list.php?lang=" . LANGUAGE_ID,
                            "more_url" => array(
                                "sotbit.seometa_autogeneration_list.php",
                                "sotbit.seometa_autogeneration_edit.php",
                            ),
                            "title" => Loc::getMessage("MENU_SEOMETA_AUTOGENERATION_OF_CONDITIONS_TITLE")
                        ),
                        array(
                            "text" => Loc::getMessage("MENU_SEOMETA_SITEMAP_GENERATION_TEXT"),
                            "url" => "sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID,
                            "more_url" => array(
                                "sotbit.seometa_sitemap_list.php",
                                "sotbit.seometa_sitemap_edit.php",
                            ),
                            "title" => Loc::getMessage("MENU_SEOMETA_SITEMAP_GENERATION_TITLE")
                        ),
                        array(
                            "text" => Loc::getMessage("MENU_SEOMETA_CHPU_LIST_TEXT"),
                            "url" => "sotbit.seometa_chpu_list.php?lang=" . LANGUAGE_ID,
                            "more_url" => array(
                                "sotbit.seometa_chpu_list.php",
                                "sotbit.seometa_chpu_edit.php",
                                "sotbit.seometa_section_chpu_edit.php",
                            ),
                            "title" => Loc::getMessage("MENU_SEOMETA_CHPU_LIST_TITLE")
                        ),
                        array(
                            "text" => Loc::getMessage("MENU_SEOMETA_WEBMASTER_TEXT"),
                            "url" => "sotbit.seometa_webmaster_list.php?lang=" . LANGUAGE_ID,
                            "more_url" => array(
                                "sotbit.seometa_webmaster_list.php",
                                "sotbit.seometa_webmaster_edit.php",
                            ),
                            "title" => Loc::getMessage("MENU_SEOMETA_WEBMASTER_TITLE")
                        ),
                        array(
                            "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_TEXT"),
                            "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_TITLE"),
                            "dynamic" => true,
                            "items_id" => "menu_sotbit.seometa.statistic",
                            "items" => array(
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_GRAPHS_TITLE"),
                                    "url" => "sotbit.seometa_stat_graph.php?lang=" . LANGUAGE_ID,
                                    "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_GRAPH_TITLE"),
                                    "more_url" => array(

                                    )
                                ),
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_LIST_TITLE"),
                                    "url" => "sotbit.seometa_stat_list.php?lang=" . LANGUAGE_ID,
                                    "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_LIST_TITLE")
                                ),
                            ),
                        ),
                        array(
                            "text" => Loc::getMessage("MENU_SEOMETA_SETTINGS_TEXT"),
                            "title" => Loc::getMessage("MENU_SEOMETA_SETTINGS_TEXT"),
                            "dynamic" => true,
                            "items_id" => "menu_sotbit.seometa.setting",
                            "items" => array(
                                array(
                                    "title" => Loc::getMessage("MENU_SEOMETA_settings_SETTINGS_TITLE"),
                                    "url" => "sotbit.seometa_settings.php?lang=" . LANGUAGE_ID,
                                    "text" => Loc::getMessage("MENU_SEOMETA_settings_SETTINGS_TITLE"),
                                )
                            )
                        ),
                    )
                );
            }
            else//If some site
            {
                $Items = array();
                foreach ($Paths as $key => $Path) {
                    foreach ($Sites as $Site) {
                        $Settings[$key]=array(
                            "text" => "[$Site[LID]] ".GetMessage("MENU_SEOMETA_".$key."_SETTINGS_TEXT"),
                            "items_id" => "menu_sotbit.seometa_settings".$key,
                            "dynamic" => true,
                            "title" => Loc::getMessage("MENU_SEOMETA_".$key."_SETTINGS_TEXT"),
                            "items" => array(
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_LIST_OF_CONDITIONS_TEXT"),
                                    "url" => "sotbit.seometa_list.php?lang=" . LANGUAGE_ID,
                                    "more_url" => array(
                                        "sotbit.seometa_list.php",
                                        "sotbit.seometa_edit.php",
                                        "sotbit.seometa_section_edit.php",
                                    ),
                                    "title" => Loc::getMessage("MENU_SEOMETA_LIST_OF_CONDITIONS_TITLE")
                                ),
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_AUTOGENERATION_OF_CONDITIONS_TEXT"),
                                    "url" => "sotbit.seometa_autogeneration_list.php?lang=" . LANGUAGE_ID,
                                    "more_url" => array(
                                        "sotbit.seometa_autogeneration_list.php",
                                        "sotbit.seometa_autogeneration_edit.php",
                                    ),
                                    "title" => Loc::getMessage("MENU_SEOMETA_AUTOGENERATION_OF_CONDITIONS_TITLE")
                                ),
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_SITEMAP_GENERATION_TEXT"),
                                    "url" => "sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID,
                                    "more_url" => array(
                                        "sotbit.seometa_sitemap_list.php",
                                        "sotbit.seometa_sitemap_edit.php",
                                    ),
                                    "title" => Loc::getMessage("MENU_SEOMETA_SITEMAP_GENERATION_TITLE")
                                ),
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_CHPU_LIST_TEXT"),
                                    "url" => "sotbit.seometa_chpu_list.php?lang=" . LANGUAGE_ID,
                                    "more_url" => array(
                                        "sotbit.seometa_chpu_list.php",
                                        "sotbit.seometa_chpu_edit.php",
                                        "sotbit.seometa_section_chpu_edit.php",
                                    ),
                                    "title" => Loc::getMessage("MENU_SEOMETA_CHPU_LIST_TITLE")
                                ),
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_WEBMASTER_TEXT"),
                                    "url" => "sotbit.seometa_webmaster_list.php?lang=" . LANGUAGE_ID,
                                    "more_url" => array(
                                        "sotbit.seometa_webmaster_list.php",
                                        "sotbit.seometa_webmaster_edit.php",
                                    ),
                                    "title" => Loc::getMessage("MENU_SEOMETA_WEBMASTER_TITLE")
                                ),
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_TEXT"),
                                    "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_TITLE"),
                                    "items_id" => "menu_sotbit.seometa.statistics",
                                    "dynamic" => true,
                                    "items" => array(
                                        array(
                                            "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_GRAPHS_TITLE"),
                                            "url" => "sotbit.seometa_stat_graph.php?lang=" . LANGUAGE_ID,
                                            "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_GRAPH_TITLE")
                                        ),
                                        array(
                                            "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_LIST_TITLE"),
                                            "url" => "sotbit.seometa_stat_list.php?lang=" . LANGUAGE_ID,
                                            "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_LIST_TITLE")
                                        ),
                                    ),
                                ),
                                array(
                                    "text" => Loc::getMessage("MENU_SEOMETA_SETTINGS_TEXT"),
                                    "title" => Loc::getMessage("MENU_SEOMETA_SETTINGS_TEXT"),
                                    "items_id" => "menu_sotbit.seometa.setting",
                                    "dynamic" => true,
                                    "items" => array(
                                        array(
                                            "title" => Loc::getMessage("MENU_SEOMETA_settings_SETTINGS_TITLE"),
                                            "url" => "sotbit.seometa_settings.php?lang=" . LANGUAGE_ID,
                                            "text" => Loc::getMessage("MENU_SEOMETA_settings_SETTINGS_TITLE"),
                                        )
                                    )
                                ),
                            )
                        );
                    }
                }

            }

            $aMenu = array(
                "parent_menu" => 'global_menu_sotbit',
                "section" => 'sotbit.seometa',
                "sort" => 200,
                "text" => Loc::getMessage("MENU_SEOMETA_TEXT"),
                "title" => Loc::getMessage("MENU_SEOMETA_TITLE"),
                //"url" => "sotbit.seometa_list.php?lang=" . LANGUAGE_ID,
                "icon" => "seometa_menu_icon",
                "page_icon" => "seometa_page_icon",
                'more_url' => array(
                    "sotbit.seometa_list.php",
                ),
                "items_id" => "menu_sotbit.seometa",
                "dynamic" => true,
                'items' => $Settings,
            );

            $arGlobalMenu['global_menu_sotbit']['items']['sotbit.seometa'] = $aMenu;
        }
    }
}

?>
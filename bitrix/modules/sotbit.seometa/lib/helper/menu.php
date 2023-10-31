<?php

namespace Sotbit\Seometa\Helper;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Menu
{
    public static function getAdminMenu(&$arGlobalMenu,&$arModuleMenu) {
        $iModuleID = "sotbit.seometa";
        global $APPLICATION;

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

        if ($APPLICATION->GetGroupRight($iModuleID) != "D") {

            $rsSites = \CSite::GetList($by="sort", $order="ASC", ["ACTIVE"=>"Y"]);
            while ($arSite = $rsSites->Fetch())
            {
                $Sites[]=$arSite;
            }

            unset($rsSites);
            unset($arSite);

            $Paths=['settings'=>'.php'];
            if(count($Sites)==1)//If one site
            {
                $Site = current($Sites);
                $settings[] = [
                    "text" => "[" . $Site['LID'] . "] ".$Site['SITE_NAME'],
                    "items_id" => "menu_sotbit.seometa_settings_".$Site['LID'],
                    "title" => $Site['SITE_NAME'],
                    "url" => "sotbit.seometa_settings.php?lang=" . LANGUAGE_ID . '&site=' . $Site['LID'],
                ];

                $settingsNotConfiguredPages[] = [
                    "text" => "[" . $Site['LID'] . "] ".$Site['SITE_NAME'],
                    "items_id" => "menu_sotbit.seometa_seo_not_configured_pages_".$Site['LID'],
                    "title" => $Site['SITE_NAME'],
                    "url" => "sotbit.seometa_seo_not_configured_pages.php?lang=" . LANGUAGE_ID . '&site_id=' . $Site['LID'],
                ];
            }
            else//If some site
            {
                foreach ($Paths as $key => $Path) {
                    foreach ($Sites as $Site) {
                        $settings[] = [
                            "text" => "[" . $Site['LID'] . "] ".$Site['SITE_NAME'],
                            "items_id" => "menu_sotbit.seometa_settings_".$Site['LID'],
                            "title" => $Site['SITE_NAME'],
                            "url" => "sotbit.seometa_settings.php?lang=" . LANGUAGE_ID . '&site=' . $Site['LID'],
                        ];

                        $settingsNotConfiguredPages[] = [
                            "text" => "[" . $Site['LID'] . "] ".$Site['SITE_NAME'],
                            "items_id" => "menu_sotbit.seometa_seo_not_configured_pages_".$Site['LID'],
                            "title" => $Site['SITE_NAME'],
                            "url" => "sotbit.seometa_seo_not_configured_pages.php?lang=" . LANGUAGE_ID . '&site_id=' . $Site['LID'],
                        ];
                    }
                }
            }

            $aMenu = [
                "parent_menu" => 'global_menu_sotbit',
                "section" => 'sotbit.seometa',
                "sort" => 200,
                "text" => Loc::getMessage("MENU_SEOMETA_TEXT"),
                "title" => Loc::getMessage("MENU_SEOMETA_TITLE"),
                "icon" => "seometa_menu_icon",
                "page_icon" => "seometa_page_icon",
                'more_url' => [
                    "sotbit.seometa_list.php",
                ],
                "items_id" => "menu_sotbit.seometa",
                "dynamic" => true,
                'items' => [
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_LIST_OF_CONDITIONS_TEXT"),
                        "url" => "sotbit.seometa_list.php?lang=" . LANGUAGE_ID,
                        "more_url" => [
                            "sotbit.seometa_list.php",
                            "sotbit.seometa_edit.php",
                            "sotbit.seometa_section_edit.php",
                        ],
                        "title" => Loc::getMessage("MENU_SEOMETA_LIST_OF_CONDITIONS_TITLE")
                    ],
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_AUTOGENERATION_OF_CONDITIONS_TEXT"),
                        "url" => "sotbit.seometa_autogeneration_list.php?lang=" . LANGUAGE_ID,
                        "more_url" => [
                            "sotbit.seometa_autogeneration_list.php",
                            "sotbit.seometa_autogeneration_edit.php",
                        ],
                        "title" => Loc::getMessage("MENU_SEOMETA_AUTOGENERATION_OF_CONDITIONS_TITLE")
                    ],
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_SEO_NOT_CONFIGURED_PAGES_TEXT"),
                        "title" => Loc::getMessage("MENU_SEOMETA_SEO_NOT_CONFIGURED_PAGES_TITLE"),
                        "items_id" => "menu_sotbit.seometa_not_configured_pages",
                        "dynamic" => true,
                        'items' => $settingsNotConfiguredPages
                    ],
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_SITEMAP_GENERATION_TEXT"),
                        "url" => "sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID,
                        "more_url" => [
                            "sotbit.seometa_sitemap_list.php",
                            "sotbit.seometa_sitemap_edit.php",
                        ],
                        "title" => Loc::getMessage("MENU_SEOMETA_SITEMAP_GENERATION_TITLE")
                    ],
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_CHPU_LIST_TEXT"),
                        "url" => "sotbit.seometa_chpu_list.php?lang=" . LANGUAGE_ID,
                        "more_url" => [
                            "sotbit.seometa_chpu_list.php",
                            "sotbit.seometa_chpu_edit.php",
                            "sotbit.seometa_section_chpu_edit.php",
                            "sotbit.seometa_parse_result.php",
                        ],
                        "title" => Loc::getMessage("MENU_SEOMETA_CHPU_LIST_TITLE")
                    ],
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_WEBMASTER_TEXT"),
                        "url" => "sotbit.seometa_webmaster_list.php?lang=" . LANGUAGE_ID,
                        "more_url" => [
                            "sotbit.seometa_webmaster_list.php",
                            "sotbit.seometa_webmaster_edit.php",
                        ],
                        "title" => Loc::getMessage("MENU_SEOMETA_WEBMASTER_TITLE")
                    ],
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_TEXT"),
                        "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_TITLE"),
                        "items_id" => "menu_sotbit.seometa.statistics",
                        "dynamic" => true,
                        "items" => [
                            /*[
                                "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_GRAPHS_TITLE"),
                                "url" => "sotbit.seometa_stat_graph.php?lang=" . LANGUAGE_ID,
                                "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_GRAPH_TITLE")
                            ],*/
                            [
                                "text" => Loc::getMessage("MENU_SEOMETA_STATISTICS_LIST_TITLE"),
                                "url" => "sotbit.seometa_stat_list.php?lang=" . LANGUAGE_ID,
                                "title" => Loc::getMessage("MENU_SEOMETA_STATISTICS_LIST_TITLE")
                            ],
                        ],
                    ],
                    [
                        "text" => Loc::getMessage("MENU_SEOMETA_SETTINGS_TEXT"),
                        "items_id" => "menu_sotbit.seometa_settings",
                        "dynamic" => true,
                        "title" => Loc::getMessage("MENU_SEOMETA_SETTINGS_TEXT"),
                        "items" => $settings,
                    ],
                ]
            ];

            $context = \Bitrix\Main\Application::getInstance()->getContext();
            $request = $context->getRequest();
            $real_path = $request->getScriptFile();

            if($real_path === '/bitrix/admin/sotbit.seometa_import_excel.php' || $real_path === '/bitrix/admin/sotbit.seometa_parse_result.php'){
                if($request->get('entity') === 'cond'){
                    $aMenu['items'][0]['more_url'][] =  "sotbit.seometa_import_excel.php";
                    $aMenu['items'][0]['more_url'][] =  "sotbit.seometa_parse_result.php";
                }else{
                    $aMenu['items'][4]['more_url'][] =  "sotbit.seometa_import_excel.php";
                    $aMenu['items'][4]['more_url'][] =  "sotbit.seometa_parse_result.php";
                }
            }

            $arGlobalMenu['global_menu_sotbit']['items']['sotbit.seometa'] = $aMenu;
        }
    }
}

?>

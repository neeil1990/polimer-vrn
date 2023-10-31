<?
###################################################
# askaron.pro1c module		                      #
# Copyright (c) 2011-2013 Askaron Systems ltd.    #
# http://askaron.ru                               #
# mailto:mail@askaron.ru                          #
###################################################

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
require_once( "prolog.php" );

$module_id = "askaron.pro1c";
$install_status = CModule::IncludeModuleEx($module_id);

if( $install_status==0 )
{
	// module not found (0)
}
elseif( $install_status==3 )
{
	//demo expired (3)
	CAdminMessage::ShowMessage(
		Array(
			"TYPE"=>"ERROR",
			"MESSAGE" => GetMessage("askaron_pro1c_prolog_status_demo_expired"),
			"DETAILS"=> GetMessage("askaron_pro1c_prolog_buy_html"),
			"HTML"=>true
		)
	);
}
else
{
	// novie nastroyki delaem cherez cpisok nastroek
	$arGroups = array(
		"clear_cache" => array(
			"NAME" => GetMessage("askaron_pro1c_header_clear_cache"),
			"HELP" => GetMessage("askaron_pro1c_header_clear_cache_help"),
		),
		"failure_resistance" => array(
			"NAME" => GetMessage("askaron_pro1c_header_failure_resistance"),
		),
		"debug" => array(
			"NAME" => "",//GetMessage("askaron_pro1c_header_debug"),
		),
		"fast" => array(
			"NAME" => GetMessage("askaron_pro1c_header_fast"),
		),
		"quantity" => array(
			"NAME" => GetMessage("askaron_pro1c_header_quantity"),
		),

	);

	$arOptions = array();

	$arOptions[] = array(
		"CODE" => "disable_clear_tag_cache_for_script",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_disable_clear_tag_cache_for_script"),
		"TYPE" => "CHECKBOX",
		"HELP" => GetMessage("askaron_pro1c_disable_clear_tag_cache_for_script_help"),
		"GROUP" =>"clear_cache",
	);



	$last_date = "&mdash;";

	$last_timestamp = COption::GetOptionString( $module_id, "clear_tag_cache_agent_last_timestamp" );
	if ( strlen($last_timestamp) > 0 )
	{
		$last_date = ConvertTimeStamp($last_timestamp, "FULL");
	}

	$arOptions[] = array(
		"CODE" => "clear_tag_cache_agent_enabled",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_clear_tag_cache_agent_enabled"),
		"TYPE" => "CHECKBOX",
		"HELP" => GetMessage("askaron_pro1c_clear_tag_cache_agent_enabled_help", array( "#LAST_DATE#" => $last_date )),
		"GROUP" =>"clear_cache",
	);

	$arOptions[] = array(
		"CODE" => "clear_tag_cache_agent_interval",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_clear_tag_cache_agent_interval"),
		"NAME2" => GetMessage("askaron_pro1c_clear_tag_cache_agent_interval_2"),
		"TYPE" => "INTEGER",
		"MIN" => 1,
		"HELP" => GetMessage("askaron_pro1c_clear_tag_cache_agent_interval_help"),
		"GROUP" =>"clear_cache",
	);

	$arOptions[] = array(
		"CODE" => "clear_tag_cache_agent_main_page",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_clear_tag_cache_agent_main_page"),
		"TYPE" => "CHECKBOX",
		"HELP" => GetMessage("askaron_pro1c_clear_tag_cache_agent_main_page_help"),
		"GROUP" =>"clear_cache",
	);


	// failure_resistance group
	$arOptions[] = array(
		"CODE" => "import_pause",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_import_pause"),
		"NAME2" => GetMessage("askaron_pro1c_import_pause_2"),
		"TYPE" => "INTEGER",
		"MIN" => 0,
		"HELP" => GetMessage("askaron_pro1c_import_pause_help"),
		"GROUP" =>"failure_resistance",
	);

	$arOptions[] = array(
		"CODE" => "time_limit",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_time_limit"),
		"NAME2" => GetMessage("askaron_pro1c_time_limit_2"),
		"TYPE" => "INTEGER",
		"MIN" => 0,
		"HELP" => GetMessage("askaron_pro1c_time_limit_help", array("#TIME_LIMIT#" => ini_get( "max_input_time" ) ) ),
		"GROUP" =>"failure_resistance",
	);

	$arOptions[] = array(
		"CODE" => "memory_limit",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_memory_limit"),
		"NAME2" => GetMessage("askaron_pro1c_memory_limit_2"),
		"TYPE" => "INTEGER",
		"MIN" => -1,
		"HELP" => GetMessage("askaron_pro1c_memory_limit_help", array("#MEMORY_LIMIT#" => ini_get( "memory_limit" ) ) ),
		"GROUP" =>"failure_resistance",
	);





	// fast

	$arOptions[] = array(
		"CODE" => "fast_write",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_fast_write"),
		"TYPE" => "CHECKBOX",
		"HELP" => GetMessage("askaron_pro1c_fast_write_help"),
		"GROUP" => "fast",
	);

	// quantity

	$arOptions[] = array(
		"CODE" => "quantity_set_to_zero",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_quantity_set_to_zero"),
		"TYPE" => "CHECKBOX",
		"HELP" => GetMessage("askaron_pro1c_quantity_set_to_zero_help"),
		"GROUP" => "quantity",
	);


	// debug group


	ob_start();
	?>

		<?=GetMessage("askaron_pro1c_live_log_help" );?>

		<?if(CModule::IncludeModule("pull")):?>

		<?$pull_version = CAskaronPro1c::GetModuleVersion("pull");?>

		<?if ( version_compare( $pull_version, '14.0.0' ) < 0):?>
			<br /><br />
			<?=GetMessage("askaron_pro1c_live_log_version", array("#LANG#" => LANG,  "#CURRENT_VERSION#" => $pull_version )  );?>
		<?endif?>

		<br /><br />
		<?=GetMessage("askaron_pro1c_live_log_open", array("#LANG#" => LANG ) );?>

		<?if ( !CPullOptions::GetNginxStatus() ):?>
			<br /><br />
			<?=GetMessage("askaron_pro1c_pull_notice", array("#LANG#" => LANG ) );?>
		<?endif?>

	<?else:?>
		<br /><br />
		<?=GetMessage("askaron_pro1c_pull_not_installed" );?>

		<?if(@file_exists( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/pull/install/index.php") ):?>
			<br /><br />
			<?=GetMessage("askaron_pro1c_pull_install", array("#LANG#" => LANG ) );?>
		<?endif?>

	<?endif?>

	<?
	$live_log_help = ob_get_contents();
	ob_end_clean();


	$arOptions[] = array(
		"CODE" => "live_log",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_live_log"),
		"TYPE" => "CHECKBOX",
		"HELP" => $live_log_help,
		"GROUP" => "debug",
	);


	$arOptions[] = array(
		"CODE" => "forbidden",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_forbidden"),
		"TYPE" => "CHECKBOX",
		"HELP" => GetMessage("askaron_pro1c_forbidden_help" ),
		"GROUP" => "debug",
	);

	$log_file_name = CAskaronPro1c::GetLogFileName();
	$log_help = GetMessage("askaron_pro1c_log_help", array("#LOG_FILENAME#" => $log_file_name ) );

	$arOptions[] = array(
		"CODE" => "log",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_log"),
		"TYPE" => "CHECKBOX",
		"HELP" => $log_help,
		"GROUP" => "debug",
	);

	$arOptions[] = array(
		"CODE" => "log_trace",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_log_trace"),
		"TYPE" => "INTEGER",
		"HELP" => GetMessage("askaron_pro1c_log_trace_help"),
		"MIN" => 0,
		"GROUP" => "debug",
	);

	$arOptions[] = array(
		"CODE" => "log_max_size",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_log_max_size"),
		"NAME2" => GetMessage("askaron_pro1c_log_max_size_2"),
		"TYPE" => "INTEGER",
		"MIN" => 1,
		"HELP" => GetMessage("askaron_pro1c_log_max_size_help"),
		"GROUP" =>"debug",
	);

	$arOptions[] = array(
		"CODE" => "copy_exchange_files",
		"SITE_ID" => "",
		"SHOW_EXACT_SITE_VALUE" => false,
		"NAME" => GetMessage("askaron_pro1c_copy_exchange_files"),
		"TYPE" => "CHECKBOX",
		"HELP" => GetMessage("askaron_pro1c_copy_exchange_files_help"),
		"GROUP" => "debug",
	);

//	$arSites = array();
//
//	$rsSites = CSite::GetList($by="sort", $order="asc");
//	while ( $arSite = $rsSites->Fetch() )
//	{
//		$arSites[ $arSite["ID"] ] = $arSite;
//	}
//
//
//	foreach ( $arSites as $arSite )
//	{
//		$arGroups[ "group2_".$arSite["ID"] ] = array(
//			"NAME" =>  GetMessage("ASKARON_GEO_GROUP_SITE")." [".$arSite["ID"]."] ".$arSite["NAME"],
//		);
//	}
//	foreach ( $arSites as $arSite )
//	{
//		$arOptions[] = array(
//			"CODE" => "set_location",
//			"SITE_ID" => $arSite["ID"],
//			"SHOW_EXACT_SITE_VALUE" => false,
//			"NAME" => GetMessage("ASKARON_GET_SET_LOCATION"),
//			"TYPE" => "CHECKBOX",
//			"HELP" => "",
//			"GROUP" =>"group2_".$arSite["ID"],
//		);
//
//		$arOptions[] = array(
//			"CODE" => "set_default_location_id",
//			"SITE_ID" => $arSite["ID"],
//			"SHOW_EXACT_SITE_VALUE" => false,
//			"NAME" => GetMessage("ASKARON_GET_SET_DEFAULT_LOCATION_ID"),
//			"TYPE" => "CHECKBOX",
//			"HELP" => "", //GetMessage("ASKARON_GET_SET_DEFAULT_LOCATION_ID_HELP"),
//			"GROUP" =>"group2_".$arSite["ID"],
//		);
//
//		$arOptions[] = array(
//			"CODE" => "default_location_id",
//			"SITE_ID" => $arSite["ID"],
//			"SHOW_EXACT_SITE_VALUE" => false,
//			"NAME" => GetMessage("ASKARON_GET_DEFAULT_LOCATION_ID"),
//			"TYPE" => "LOCATION",
//			"HELP" => GetMessage("ASKARON_GET_DEFAULT_LOCATION_ID_HELP"),
//			"GROUP" =>"group2_".$arSite["ID"],
//		);
//	}




	$RIGHT = $APPLICATION->GetGroupRight($module_id);
	$RIGHT_W = ($RIGHT>="W");
	$RIGHT_R = ($RIGHT>="R");

	if ($RIGHT_R)
	{
		$arErrors = array();
		$arSettings = array();

		if (
			$REQUEST_METHOD=="POST"
			&& strlen($Update)>0
			&& $RIGHT_W
			&& check_bitrix_sessid()
		)
		{
			foreach ($_REQUEST as $key => $value)
			{
				if ( preg_match( '/^askaron_pro1c_settings_([0-9]+)_row$/', $key, $arMatches ) )
				{
					$arItem = array(
						"ACTIVE" => "N",
						"NAME" => "",
						"SKIP_PRODUCTS" => "",
					);

					if ( isset( $value["NAME"] ) && strlen( $value["NAME"] ) > 0 )
					{
						$arItem["NAME"] = $value["NAME"];

						if ( isset( $value["ACTIVE"] ) && $value["ACTIVE"] == "Y" )
						{
							$arItem["ACTIVE"] = $value["ACTIVE"];
						}

						if ( isset( $value["SKIP_PRODUCTS"] ) && $value["SKIP_PRODUCTS"] == "Y" )
						{
							$arItem["SKIP_PRODUCTS"] = "Y";
						}
						else
						{
							$arItem["SKIP_PRODUCTS"] = "N";
						}

						$arSettings[] = $arItem;
//
//						$field_name_html = htmlspecialcharsbx($arItem["NAME"]);
//						
//						if ( strlen( $arItem["CODE"] ) == 0 )
//						{
//							$arErrors[] = GetMessage( "askaron_pro1c_error_empty", array( "#TRAIT#" => $field_name_html ) );
//						}
//						elseif (  preg_match( '/^([0-9]+).*?$/', $arItem["CODE"] ) )
//						{
//							$arErrors[] = GetMessage( "askaron_pro1c_error_first_symbol", array( "#TRAIT#" => $field_name_html ) );							
//						}
//						elseif ( !preg_match( '/^([a-zA-Z0-9_]+)$/', $arItem["CODE"] ) )
//						{
//							$arErrors[] = GetMessage( "askaron_pro1c_error_format", array( "#TRAIT#" => $field_name_html ) );							
//						}
					}
				}
			}

			if (count( $arErrors ) == 0 )
			{
				$bSaved = CAskaronPro1C::SetSettings( $arSettings );
				if (!$bSaved)
				{
					$arErrors[] = GetMessage("askaron_pro1c_error_save");
				}
			}

			if (intval($_REQUEST[ "import_pause" ]) > 0)
			{
				COption::SetOptionString($module_id, "import_pause", intval( $_REQUEST[ "import_pause" ] ) );
			}
			else
			{
				COption::SetOptionString($module_id, "import_pause", "0" );
			}

			$_REQUEST[ "time_limit" ] = trim( $_REQUEST[ "time_limit" ] );
			if ( strlen( $_REQUEST[ "time_limit" ] ) > 0 )
			{
				if ( intval($_REQUEST[ "time_limit" ]) > 0 )
				{
					COption::SetOptionString($module_id, "time_limit", intval( $_REQUEST[ "time_limit" ] ) );
				}
				else
				{
					COption::SetOptionString($module_id, "time_limit", "0" );
				}

			}
			else
			{
				COption::SetOptionString($module_id, "time_limit", "" );
			}


			$_REQUEST[ "memory_limit" ] = trim( $_REQUEST[ "memory_limit" ] );
			if ( strlen( $_REQUEST[ "memory_limit" ] ) > 0 )
			{
				COption::SetOptionString($module_id, "memory_limit", intval( $_REQUEST[ "memory_limit" ] ) );
			}
			else
			{
				COption::SetOptionString($module_id, "memory_limit", "" );
			}



			// novie nastroyki
			foreach ( $arOptions as $key => $arOption )
			{
				if ( $arOption["TYPE"] == "CHECKBOX" )
				{
					if ( isset( $_REQUEST[ "arrOptions__".$key ] ) && $_REQUEST[ "arrOptions__".$key ] == "Y" )
					{
						COption::SetOptionString($module_id, $arOption["CODE"], "Y", false, $arOption["SITE_ID"] );
					}
					else
					{
						COption::SetOptionString($module_id, $arOption["CODE"], "N", false, $arOption["SITE_ID"] );
					}
				}

				if ( $arOption["TYPE"] == "TEXT" )
				{
					if ( isset( $_REQUEST[ "arrOptions__".$key ] ) )
					{
						COption::SetOptionString( $module_id, $arOption["CODE"], $_REQUEST[ "arrOptions__".$key ], false, $arOption["SITE_ID"] );
					}
				}

				if ( $arOption["TYPE"] == "INTEGER"
					//|| $arOption["TYPE"] == "LOCATION"
				)
				{
					if ( isset( $_REQUEST[ "arrOptions__".$key ] ) )
					{
						if ( strlen( $_REQUEST[ "arrOptions__".$key ] ) > 0 )
						{
							$val = intval( $_REQUEST[ "arrOptions__".$key ] );
							$min = $arOption["MIN"];

							if ( strlen( $min ) > 0 && $val < $min )
							{
								$val = $min;
							}

							COption::SetOptionString( $module_id, $arOption["CODE"], $val, false, $arOption["SITE_ID"] );
						}
					}
				}

//				if ( $arOption["TYPE"] == "IMAGE" )
//				{
//					$arFile = $_FILES[ "arrOptions__".$key];
//					$arFile["del"] = $_REQUEST[ "arrOptions__".$key."_del" ];
//					$arFile["MODULE_ID"] = $module_id;
//
//					$check_image_error = CFile::CheckImageFile( $arFile );
//
//					if ( strlen( $check_image_error ) > 0 )
//					{
//						$arWarnings[] = $check_image_error;
//					}
//					else
//					{
//						if ( strlen($arFile["name"]) > 0 || strlen($arFile["del"] ) > 0 )
//						{
//							$arFile["old_file"] = COption::GetOptionString( $module_id, $arOption["CODE"], "", $arOption["SITE_ID"], true );
//							$val = CFile::SaveFile( $arFile, $module_id );
//							COption::SetOptionString( $module_id, $arOption["CODE"], $val, false, $arOption["SITE_ID"] );
//						}
//					}
//				}
			}


			CAskaronPro1CCache::SetAgentByOptions();
		}


		if (
			$REQUEST_METHOD=="POST"
			&& $RIGHT_W
			&& strlen($RestoreDefaults)>0
			&& check_bitrix_sessid()
		)
		{
			// save some options value
			$arSaveOptions = array(
				"random_value" => "",
				"check_catalog" => "",
				"check_orders" => "",
				"last_success_import_date" => "",
				"last_success_offers_date" => "",
				"last_success_prices_date" => "",
				"last_success_rests_date" => "",
			);

			//			$random_value_tmp = COption::GetOptionString( $module_id, "random_value" );
			foreach ( $arSaveOptions as $key => $value )
			{
				$arSaveOptions[ $key ] = COption::GetOptionString( $module_id, $key );
			}

			// remove all
			COption::RemoveOption("askaron.pro1c");

			//			COption::SetOptionString( $module_id, "random_value", $random_value_tmp );

			// restore
			foreach ( $arSaveOptions as $key => $value )
			{
				COption::SetOptionString( $module_id, $key, $value );
			}





			$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"), $get_users_amount = "N");
			while($zr = $z->Fetch())
			{
				$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
			}

			CAskaronPro1CCache::SetAgentByOptions();
		}

		if (count( $arErrors ) == 0 )
		{
			$arSettings = CAskaronPro1C::GetSettings();
		}


		$import_pause = intval( COption::GetOptionString( $module_id, "import_pause") );
		$time_limit = COption::GetOptionString( $module_id, "time_limit");
		$memory_limit = COption::GetOptionString( $module_id, "memory_limit");




		// novie nastroyki - spisok
		$arDisplayOptions = array();

		foreach ( $arOptions as $key=> $arOption )
		{
			$arOptionAdd = $arOption;

			$option_value = COption::GetOptionString( $module_id, $arOption["CODE"], "", $arOption["SITE_ID"], $arOption["SHOW_EXACT_SITE_VALUE"] );

			$arOptionAdd["INPUT_ID"] = "option_".$key;
			$arOptionAdd["INPUT_NAME"] = "arrOptions__".$key;
			$arOptionAdd["~INPUT_VALUE"] = $option_value;
			$arOptionAdd["INPUT_VALUE"] = htmlspecialcharsbx( $option_value );

			$arDisplayOptions[ $key ] = $arOptionAdd;
		}

		foreach ( $arGroups as $group_key => $arGroup )
		{
			$arGroups[$group_key]["~NAME"] = $arGroup["NAME"];
			$arGroups[$group_key]["NAME"] = htmlspecialcharsbx( $arGroup["NAME"] );
		}


		if ( count( $arErrors ) > 0 )
		{
			CAdminMessage::ShowMessage(
				Array(
					"TYPE"=>"ERROR",
					"MESSAGE" => GetMessage("askaron_pro1c_error_save_header"),
					"DETAILS"=> implode( "<br />", $arErrors ),
					"HTML"=>true
				)
			);
		}

		$aTabs = array(
			array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
			array("DIV" => "edit2", "TAB" => GetMessage("askaron_pro1c_tab_debug"), "ICON" => "", "TITLE" => GetMessage("askaron_pro1c_tab_debug") ),
			array("DIV" => "edit3", "TAB" => GetMessage("askaron_pro1c_additional_settings"), "ICON" => "", "TITLE" => GetMessage("askaron_pro1c_additional_settings") ),
			array("DIV" => "edit4", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
		);

		$tabControl = new CAdminTabControl("tabControl", $aTabs);
		$tabControl->Begin();

		$rowIndex = 0;

		?>

		<form method="post"
			  action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&lang=<?=LANGUAGE_ID?>&mid_menu=<?=urlencode($_REQUEST["mid_menu"])?>"
		>
			<?=bitrix_sessid_post()?>
			<?$tabControl->BeginNextTab();?>
			<tr>
				<td width="100%" style="" colspan="2">
					<?
					//demo (2)
					if ( $install_status == 2 )
					{
						CAdminMessage::ShowMessage(
							Array(
								"TYPE"=>"OK",
								"MESSAGE" => GetMessage("askaron_pro1c_prolog_status_demo"),
								"DETAILS"=> GetMessage("askaron_pro1c_prolog_buy_html"),
								"HTML"=>true
							)
						);
					}
					?>
				</td>
			</tr>
			<tr class="heading">
				<td valign="top" colspan="2" align="center"><?=GetMessage("askaron_pro1c_header_files")?></td>
			</tr>
			<tr>
				<td width="100%" style="" colspan="2">
					<table style="width: auto; margin: 0 auto;">
						<tr>
							<td>
								<table class="internal" cellspacing="0" cellpadding="0" border="0">
									<thead>
									<tr class="heading">
										<td><?=GetMessage("askaron_pro1c_field_active")?></td>
										<td><?=GetMessage("askaron_pro1c_field_name")?></td>
										<td><?=GetMessage("askaron_pro1c_field_skip_product")?></td>
									</tr>
									</thead>
									<tbody id="askaron_pro1c_settings_body">
									<?foreach ( $arSettings as $arItem ):?>
										<tr>
											<td style="text-align: center;"><input name="askaron_pro1c_settings_<?=$rowIndex?>_row[ACTIVE]" value="Y" type="checkbox"<?if ($arItem["ACTIVE"] == "Y" ) echo ' checked="checked"'; ?> /></td>
											<td><input name="askaron_pro1c_settings_<?=$rowIndex?>_row[NAME]" value="<?=htmlspecialcharsbx($arItem["NAME"])?>" type="text" size="60" /></td>
											<td style="text-align: center;"><input name="askaron_pro1c_settings_<?=$rowIndex?>_row[SKIP_PRODUCTS]" value="Y" type="checkbox"<?if ($arItem["SKIP_PRODUCTS"] == "Y" ) echo ' checked="checked"'; ?> /></td>
										</tr>
										<?$rowIndex++;?>
									<?endforeach?>

									<?for ( $i = 0; $i < 1; $i++ ):?>
										<tr>
											<td style="text-align: center;"><input name="askaron_pro1c_settings_<?=$rowIndex?>_row[ACTIVE]" value="Y" type="checkbox" checked="checked" /></td>
											<td><input name="askaron_pro1c_settings_<?=$rowIndex?>_row[NAME]" value="" type="text" size="60" /></td>
											<td style="text-align: center;"><input name="askaron_pro1c_settings_<?=$rowIndex?>_row[SKIP_PRODUCTS]" value="Y" type="checkbox" /></td>
										</tr>
										<?$rowIndex++;?>
									<?endfor?>
									</tbody>
								</table>
								<br  />
								<div style="width: 100%; text-align: center;">
									<input type="button" value="<?=GetMessage("askaron_pro1c_more")?>" onclick="askaron_pro1c_add_row();" />
								</div>

								<div style="clear: both"> </div>
								<br  />
								<?=BeginNote();?>
								<?=GetMessage("askaron_pro1c_settings_help")?>
								<?=EndNote();?>
							</td>
						<tr>
					</table>
				</td>
			</tr>




			<?if ( COption::GetOptionString("main", "component_managed_cache_on") == "N" ): ?>
				<tr class="heading">
					<td valign="top" colspan="2" align="center"><?=$arGroups["clear_cache"]["NAME"]?></td>
				</tr>
				<tr>
					<td valign="top" colspan="2">
						<?
						CAdminMessage::ShowMessage(
							Array(
								"TYPE"=>"ERROR",
								"MESSAGE" => GetMessage("askaron_pro1c_managed_cache_off"),
								"DETAILS"=> "",
								"HTML"=>true
							)
						);
						?>
					</td>
				</tr>
			<?else:?>

				<?//CAskaronPro1COptions::ShowAll( $arGroups, $arDisplayOptions );?>

				<?CAskaronPro1COptions::ShowGroup( "clear_cache", $arGroups, $arDisplayOptions );?>
			<?endif?>


			<?CAskaronPro1COptions::ShowGroup( "failure_resistance", $arGroups, $arDisplayOptions );?>


<?/*
			<tr class="heading">
				<td valign="top" colspan="2" align="center"><?=GetMessage("askaron_pro1c_header_failure_resistance")?></td>
			</tr>

			<tr>
				<td valign="top" width="50%" class="field-name"><label for='askaron_pro1c_import_pause'><?=GetMessage("askaron_pro1c_import_pause")?></label></td>
				<td valign="top" width="50%">
					<input
						type="text"
						value="<?=$import_pause?>"
						id="askaron_pro1c_import_pause"
						name="import_pause"
						/> <?=GetMessage("askaron_pro1c_import_pause_2")?>

					<?=BeginNote();?>
					<?=GetMessage("askaron_pro1c_import_pause_help", array("#LANG#" => LANG ) );?>
					<?=EndNote();?>
				</td>
			</tr>

			<tr>
				<td valign="top" width="50%" class="field-name"><label for='askaron_pro1c_time_limit'><?=GetMessage("askaron_pro1c_time_limit")?></label></td>
				<td valign="top" width="50%">
					<input
						type="text"
						value="<?=$time_limit?>"
						id="askaron_pro1c_time_limit"
						name="time_limit"
						/> <?=GetMessage("askaron_pro1c_time_limit_2")?>

					<?=BeginNote();?>
					<?=GetMessage("askaron_pro1c_time_limit_help", array("#TIME_LIMIT#" => ini_get( "max_execution_time" ) ) );?>
					<?=EndNote();?>
				</td>
			</tr>

			<tr>
				<td valign="top" width="50%" class="field-name"><label for='askaron_pro1c_memory_limit'><?=GetMessage("askaron_pro1c_memory_limit")?></label></td>
				<td valign="top" width="50%">
					<input
						type="text"
						value="<?=$memory_limit?>"
						id="askaron_pro1c_memory_limit"
						name="memory_limit"
						/> <?=GetMessage("askaron_pro1c_memory_limit_2")?>

					<?=BeginNote();?>
					<?=GetMessage("askaron_pro1c_memory_limit_help", array("#MEMORY_LIMIT#" => ini_get( "memory_limit" ) ) );?>
					<?=EndNote();?>
				</td>
			</tr>
*/?>
			<?$tabControl->BeginNextTab();?>

			<?CAskaronPro1COptions::ShowGroup( "debug", $arGroups, $arDisplayOptions );?>


			<?/*
			<tr>
				<td valign="top" width="50%" class="field-name"><label><?=GetMessage("askaron_pro1c_import_date")?></label></td>
				<td valign="top" width="50%"><?=CAskaronPro1C::GetLastSuccessImportDate("&mdash;");?></td>				
			</tr>
			<tr>
				<td valign="top" width="50%" class="field-name"><label><?=GetMessage("askaron_pro1c_offers_date")?></label></td>
				<td valign="top" width="50%"><?=CAskaronPro1C::GetLastSuccessOffersDate("&mdash;");?></td>				
			</tr>		
			 */?>

		<?$tabControl->BeginNextTab();?>

			<?CAskaronPro1COptions::ShowGroup( "fast", $arGroups, $arDisplayOptions );?>

			<?CAskaronPro1COptions::ShowGroup( "quantity", $arGroups, $arDisplayOptions );?>

		<?$tabControl->BeginNextTab();?>

			<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
			<?$tabControl->Buttons();?>
			<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
			<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
			<?$tabControl->End();?>


		</form>

		<table style="display: none;">
			<tbody id="askaron_pro1c_settings_body_from">
			<tr>
				<td style="text-align: center;"><input name="ACTIVE" value="Y" type="checkbox" checked="checked" /></td>
				<td><input name="NAME" value="" type="text" size="60" /></td>
				<td style="text-align: center;"><input name="SKIP_PRODUCTS" value="Y" type="checkbox" /></td>
			</tr>
			</tbody>
		</table>
		<script type="text/javascript">
			var askaron_pro1c_add_row_index = <?=$rowIndex?>;

			var askaron_pro1c_add_row = function()
			{
				var from = document.getElementById("askaron_pro1c_settings_body_from");
				var to = document.getElementById("askaron_pro1c_settings_body");

				// clone the first children <tr>
				var node = from.getElementsByTagName("tr")[0].cloneNode(true);

				// inputs
				var children = node.getElementsByTagName("input");
				for(var i=0; i<children.length; i++)
				{
					children[i].name = "askaron_pro1c_settings_" + askaron_pro1c_add_row_index + "_row[" + children[i].name + "]";
				}

				// selects
				var children = node.getElementsByTagName("select");
				for(var i=0; i<children.length; i++)
				{
					children[i].name = "askaron_pro1c_settings_" + askaron_pro1c_add_row_index + "_row[" + children[i].name + "]";
				}

				to.appendChild( node );
				askaron_pro1c_add_row_index++;
			};
		</script>
		<?
	}
}
?>
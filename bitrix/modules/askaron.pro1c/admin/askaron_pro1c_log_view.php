<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once( dirname(__FILE__)."/../prolog.php" );

IncludeModuleLangFile(__FILE__);

// messages
$install_status=CModule::IncludeModuleEx("askaron.pro1c");

// demo expired (3)
if( $install_status==3 )
{
	$APPLICATION->SetTitle(GetMessage("askaron_pro1c_title"));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	CAdminMessage::ShowMessage(
		Array(
			"TYPE"=>"ERROR",
			"MESSAGE"=>GetMessage("askaron_pro1c_prolog_status_demo_expired"),
			"DETAILS"=>GetMessage("askaron_pro1c_prolog_buy_html"),
			"HTML"=>true
		)
	);
}
else
{
	$log_file_name = CAskaronPro1c::GetLogFileName();
	$bFileExists = file_exists ( $log_file_name );
	$file_size = "";
	$file_time = "";
	if ($bFileExists)
	{
		$file_size = filesize( $log_file_name );
		$file_time = ConvertTimeStamp( filemtime($log_file_name), FULL );
	}


	$arResult = array(
		"COUNT_LINES" => 500,
		//"FILTER_TEXT" => "",
		"FILE_ABS_PATH" => $log_file_name,
		"DOWNLOAD_URL" => "",
		"bFileExists" => $bFileExists,
		"file_size" => $file_size,
		"file_time" => $file_time,
	);

	if ( strpos($log_file_name, $_SERVER["DOCUMENT_ROOT"] ) === 0 )
	{
		$count_replace = 1;
		$url = $log_file_name;
		$url = str_replace($_SERVER["DOCUMENT_ROOT"], "", $url, $count_replace);
		$url = str_replace("\\", "/", $url);
		$arResult["DOWNLOAD_URL"] = $url;
	}

	$RIGHT=$APPLICATION->GetGroupRight("askaron.pro1c");
	$RIGHT_W = ($RIGHT>="W");
	$RIGHT_R = ($RIGHT>="R");
	if( $RIGHT=="D" )
	{
		$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
	}

	if ($RIGHT_R)
	{
		if (
			check_bitrix_sessid()
				&&
			$_REQUEST['askaron_pro1c_update'] == "Y"
		)
		{
			if ( intval( $_REQUEST["askaron_pro1c_count_lines"] ) > 0 )
			{
				$arResult["COUNT_LINES"] = intval( $_REQUEST["askaron_pro1c_count_lines"] );
			}

			//$arResult["FILTER_TEXT"] = $_REQUEST["askaron_pro1c_filter_text"];
		}

		if (
			check_bitrix_sessid()
				&&
			$_REQUEST['askaron_pro1c_clear'] == "Y"
				&&
			$RIGHT_W
		)
		{
			@file_put_contents( $arResult["FILE_ABS_PATH"], "" );
			LocalRedirect( $APPLICATION->GetCurPageParam( "", array("askaron_pro1c_clear") ) );
		}

	}

	// Title
	$APPLICATION->SetTitle(GetMessage("askaron_pro1c_log_view_title"));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	?>



	<?
	// demo (2)
	if( $install_status==2 )
	{
		CAdminMessage::ShowMessage(
			Array(
				"TYPE"=>"OK",
				"MESSAGE"=>GetMessage("askaron_pro1c_prolog_status_demo"),
				"DETAILS"=>GetMessage("askaron_pro1c_prolog_buy_html"),
				"HTML"=>true
			)
		);
	}
	?>

	<table>
		<tr>
			<td valign="top">
				<a href="settings.php?mid=askaron.pro1c&lang=<?=LANGUAGE_ID?>&mid_menu=2"><?=GetMessage("ASKARON_PRO1C_NASTROYKI_MODULA")?></a>:
			</td>

			<td valign="top">
				<?=GetMessage("ASKARON_PRO1C_ZAPISYVATQ_VSE_SAGI")?><?if ( COption::GetOptionString( "askaron.pro1c", "log" ) == "Y" ):?>
					<strong><?=GetMessage("ASKARON_PRO1C_DA")?></strong>
				<?else:?>
					<strong><?=GetMessage("ASKARON_PRO1C_NET")?></strong>
				<?endif?>
				<br>
				<?=GetMessage("ASKARON_PRO1C_MAKSIMALQNYY_RAZMER")?><strong><?=COption::GetOptionString( "askaron.pro1c", "log_max_size" )?></strong>
				<?=GetMessage("ASKARON_PRO1C_MEGABAYT")?></td>
		<tr>
	</table>

	<?if ( !$arResult["bFileExists"] ):?>

		<?
		CAdminMessage::ShowMessage(
			Array(
				"TYPE"=>"ERROR",
				"MESSAGE"=>GetMessage("askaron_pro1c_log_view_file_not_found"),
				"DETAILS"=>htmlspecialcharsbx( $arResult["FILE_ABS_PATH"] ),
				"HTML"=>true
			)
		);
		?>
	<?else:?>

		<form method="POST">
			<table>
				<tr>
					<td valign="top">

						<input type="submit"
							   name="askaron_pro1c_clear"
							   value="<?=GetMessage("askaron_pro1c_clear")?>"
							   onclick="return confirm( '<?=GetMessage("askaron_pro1c_confirm")?>' );"
						/>
						<input type="hidden" name="askaron_pro1c_clear" value="Y" />
						<?=bitrix_sessid_post()?>

					</td>
					<td valign="top">&nbsp;&nbsp;&nbsp;</td>

					<td valign="top">
						<?=htmlspecialcharsbx( $arResult["FILE_ABS_PATH"] );?>

						<br>
						<?=CFile::FormatSize( $arResult["file_size"] )?>,
						<?=GetMessage("askaron_pro1c_changed");?> <?=$arResult["file_time"]?>,
						<?if ( strlen( $arResult["DOWNLOAD_URL"] ) ):?>
							<a target="_blank" href="<?=$arResult["DOWNLOAD_URL"]?>"><?=GetMessage("ASKARON_PRO1C_OTKRYTQ_V_NOVOY_VKLA")?></a>
						<?endif?>

					</td>
				<tr>
			</table>
		</form>


		<form method="POST">
			<table>
				<tr>
					<td>
						<input type="submit" name="askaron_pro1c_update" value="<?=GetMessage("askaron_pro1c_show")?>" />
						<input type="hidden" name="askaron_pro1c_update" value="Y" />
					</td>
					<td>
						<?=GetMessage("ASKARON_PRO1C_POSLEDNIE")?>
					</td>
					<td>
						<input name="askaron_pro1c_count_lines" value="<?=$arResult["COUNT_LINES"]?>">
						<?=GetMessage("ASKARON_PRO1C_STROK1")?>
						<?$command = "cat ".escapeshellarg($arResult["FILE_ABS_PATH"])." | wc -l;"?>
						<?echo `$command`?>
					</td>
				</tr>
			</table>
			<?=bitrix_sessid_post()?>
		</form>
		<?
			$command = "";
			//$command = 'tail -n '.$arResult["COUNT_LINES"].' '.$arResult["FILE_ABS_PATH"];
//			if ( strlen( $arResult["FILTER_TEXT"] ) > 0 )
//			{
//				//escapeshellarg
//				$command .= "cat ".escapeshellarg($arResult["FILE_ABS_PATH"])." | grep ".escapeshellarg($arResult["FILTER_TEXT"])." | tail -n ".$arResult["COUNT_LINES"];
//			}
//			else
//			{
//				$command = "tail -n ".$arResult["COUNT_LINES"]." ".escapeshellarg($arResult["FILE_ABS_PATH"]);
//			}

			$command = "tail -n ".$arResult["COUNT_LINES"]." ".escapeshellarg($arResult["FILE_ABS_PATH"]);

			$showContent = `$command`;
		?>
		<div style="clear: both;">
		<br>
		<p style="color: green;"><?=htmlspecialcharsbx( $command )?></p>

		<div style='width: 100%; min-height: 200px; background-color: #FFF; border: 1px solid; padding: 5px; margin: 10px 0;'>

			<pre><?=htmlspecialcharsbx( $showContent );?></pre>

		</div>



	<?endif?>


<?}?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
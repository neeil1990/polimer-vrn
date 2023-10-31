<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

//$module_root = realpath(dirname(__FILE__)."/..");
//require_once( $module_root."/prolog.php" );
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
			$RIGHT_W
			&& check_bitrix_sessid()
			&& isset($_REQUEST['askaron_pro1c_action']) && $_REQUEST['askaron_pro1c_action'] == "update_date"
		)
		{
			$option = "last_export_time_committed_".$_REQUEST["askaron_pro1c_path"];
			
			$date = trim($_REQUEST["askaron_pro1c_date"]);

			if ( strlen( $date ) > 0 )
			{
				$timestamp = MakeTimeStamp( $date );
				if ( $timestamp !== false )
				{
					COption::SetOptionString( "sale", $option, $timestamp );
				}
			}
			else
			{
				COption::SetOptionString( "sale", $option, "" );
			}
				
			$url = $APPLICATION->GetCurPageParam( "", array() );
			LocalRedirect($url);
		}
	}	
	
	
	$aTabs = array(array("DIV" => "edit1", "TAB" => GetMessage('askaron_pro1c_last_title') ) );
	$tabControl = new CAdminTabControl("tabControl", $aTabs);
	
	// Title
	$APPLICATION->SetTitle(GetMessage("askaron_pro1c_last_title"));
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
	
	
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	?>

	<tr class="heading">
		<td width="100%" colspan="2"><?=GetMessage("askaron_pro1c_last_exchange_catalog");?></td>
	</tr>
	<tr>
		<td width="50%"><?=GetMessage("askaron_pro1c_last_exchange_catalog_import");?> (import*.xml):</td>
		<td width="50%"><?=CAskaronPro1C::GetLastSuccessImportDate("&mdash;");?></td>
	</tr>
	<tr>
		<td width="50%"><?=GetMessage("askaron_pro1c_last_exchange_catalog_offers");?> (offers*.xml):</td>
		<td width="50%"><?=CAskaronPro1C::GetLastSuccessOffersDate("&mdash;");?></td>
	</tr>
	<tr>
		<td width="50%"><?=GetMessage("askaron_pro1c_last_exchange_catalog_prices");?> (prices*.xml):</td>
		<td width="50%"><?=CAskaronPro1C::GetLastSuccessPricesDate("&mdash;");?></td>
	</tr>
	<tr>
		<td width="50%"><?=GetMessage("askaron_pro1c_last_exchange_catalog_rests");?> (rests*.xml):</td>
		<td width="50%"><?=CAskaronPro1C::GetLastSuccessRestsDate("&mdash;");?></td>
	</tr>
	<tr>
		<td width="100%" colspan="2">
			<?=BeginNote();?>
				<?=GetMessage("askaron_pro1c_last_exchange_catalog_notes");?>
			<?=EndNote();?>			
		</td>
	</tr>
	
	<?
		$arPaths = CAskaronPro1C::GetKnownOrderDatePaths();
		//d($arPaths);
	?>
	
	
	<tr class="heading">
		<td width="100%" colspan="2"><?=GetMessage("askaron_pro1c_last_exchange_order");?></td>
	</tr>
	
	<?foreach ($arPaths as $sub_path => $arFullPaths):?>
	
		<tr>
			<td width="50%" style="vertical-align: top">
				 <?if ( strlen( $sub_path )>=22 ):?>
					<?=GetMessage("askaron_pro1c_last_exchange_order_pages");?> <strong><?=$sub_path;?>*</strong>
					<?if ($sub_path === "/bitrix/admin/1c_excha"):?>
						<br>(<?=GetMessage("askaron_pro1c_last_exchange_order_for_example");?>, /bitrix/admin/1c_exchange.php)
					<?endif?>
				 <?else:?>
					<?=GetMessage("askaron_pro1c_last_exchange_order_page");?> <?=$sub_path;?>
				 <?endif?>
			</td>
			<td width="50%" style="vertical-align: top">
				<form name="" action="" method="POST">
					<?=bitrix_sessid_post()?>
					<?=GetMessage("askaron_pro1c_last_exchange_order_time");?>:<br>
					<?=CAskaronPro1C::GetDateOptionFromTimestamp( "sale", "last_export_time_".$sub_path, "&mdash;");?>
					<br>
					<br>
					<?=GetMessage("askaron_pro1c_last_exchange_order_time_committed");?>:<br><br>
					<?echo CAdminCalendar::CalendarDate(  "askaron_pro1c_date", CAskaronPro1C::GetDateOptionFromTimestamp( "sale", "last_export_time_committed_".$sub_path, ""), 10, true );?>&nbsp;&nbsp;<input type="submit" name="askaron_pro1c_action" value="<?=GetMessage("askaron_pro1c_last_exchange_order_button");?>">
					<input type="hidden" name="askaron_pro1c_path" value="<?=$sub_path?>" />
					<input type="hidden" name="askaron_pro1c_action" value="update_date" />
					<br><br>
				</form>
			</td>
		</tr>
	
	<?endforeach?>

	<tr>
		<td width="100%" colspan="2">
			<?=BeginNote();?>
				<?=GetMessage("askaron_pro1c_last_exchange_order_notes");?>
			<?=EndNote();?>		
				
			<?=BeginNote();?>
				<?=GetMessage("askaron_pro1c_last_settings", array("#LANG#" => LANG ));?>
			<?=EndNote();?>				
		</td>
	</tr>	
	<?
	$tabControl->End();
	?>
<?}?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
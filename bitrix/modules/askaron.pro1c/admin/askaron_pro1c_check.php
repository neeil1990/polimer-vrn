<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

//$module_root = realpath(dirname(__FILE__)."/..");
//require_once( $module_root."/prolog.php" );
require_once( dirname(__FILE__)."/../prolog.php" );

IncludeModuleLangFile(__FILE__);

$module_id = "askaron.pro1c";

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
			&& isset($_REQUEST['Update'])
		)
		{
			$val = $_REQUEST["askaron_pro1c_check_catalog"];
			if ( $val == "Y"
				|| $val == "N"
				|| $val == "D"
			)
			{
				COption::SetOptionString( $module_id, "check_catalog", $val );

				if ( $val == "Y" )
				{
					COption::SetOptionString("catalog", "DEFAULT_SKIP_SOURCE_CHECK", "N" );
				}

				if ( $val == "N" || $val == "D" )
				{
					COption::SetOptionString("catalog", "DEFAULT_SKIP_SOURCE_CHECK", "Y" );
				}
			}


			$val = $_REQUEST["askaron_pro1c_check_orders"];
			if ( $val == "Y"
				|| $val == "N"
				|| $val == "D"
			)
			{
				COption::SetOptionString( $module_id, "check_orders", $val );

				if ( $val == "Y" )
				{
					COption::SetOptionString("sale", "secure_1c_exchange", "Y" );
				}

				if ( $val == "N" || $val == "D" )
				{
					COption::SetOptionString("sale", "secure_1c_exchange", "N" );
				}
			}


			$url = $APPLICATION->GetCurPageParam( "", array() );
			LocalRedirect($url);
		}
	}


	$askaron_pro1c_check_catalog = COption::GetOptionString( $module_id, "check_catalog" );
	if ($askaron_pro1c_check_catalog == "N" || $askaron_pro1c_check_catalog == "Y")
	{
		if ( COption::GetOptionString("catalog", "DEFAULT_SKIP_SOURCE_CHECK", "Y" ) === "Y" )
		{
			$askaron_pro1c_check_catalog = "N";
		}
		else
		{
			$askaron_pro1c_check_catalog = "Y";
		}
	}


	$askaron_pro1c_check_orders = COption::GetOptionString( $module_id, "check_orders" );
	if ( $askaron_pro1c_check_orders == "N" || $askaron_pro1c_check_orders == "Y" )
	{
		$askaron_pro1c_check_orders = COption::GetOptionString("sale", "secure_1c_exchange", "N" );
	}



	
	$aTabs = array(array("DIV" => "edit1", "TAB" => GetMessage('askaron_pro1c_check_title') ) );
	$tabControl = new CAdminTabControl("tabControl", $aTabs);
	
	// Title
	$APPLICATION->SetTitle(GetMessage("askaron_pro1c_check_title"));
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
	<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>">
	<?=bitrix_sessid_post()?>	
		<?
		$tabControl->Begin();
		$tabControl->BeginNextTab();
		?>
		<tr>
			<td width="100%" colspan="2">
				<?=BeginNote();?>
					<?=GetMessage("askaron_pro1c_check_notes");?>
				<?=EndNote();?>				
			</td>
		</tr>			
		<tr class="heading">
			<td width="100%" colspan="2"><?=GetMessage("askaron_pro1c_check_catalog");?></td>
		</tr>
		<tr>
			<td width="50%" valign="top"><?=GetMessage("askaron_pro1c_check_source")?>:</td>
			<td width="50%">
				<input type="radio" name="askaron_pro1c_check_catalog" id="askaron_pro1c_check_catalog_Y" value="Y" 
					<?if ( $askaron_pro1c_check_catalog == "Y" ):?>
						 checked
					<?endif?>
				> <label for="askaron_pro1c_check_catalog_Y"><?=GetMessage("askaron_pro1c_check_source_Y")?></label>
				<br>
				<input type="radio" name="askaron_pro1c_check_catalog" id="askaron_pro1c_check_catalog_N" value="N"
					<?if ( $askaron_pro1c_check_catalog == "N" ):?>
						 checked
					<?endif?>
				> <label for="askaron_pro1c_check_catalog_N"><?=GetMessage("askaron_pro1c_check_source_N")?></label>
				<br>
				<input type="radio" name="askaron_pro1c_check_catalog" id="askaron_pro1c_check_catalog_D" value="D"
					<?if ( $askaron_pro1c_check_catalog == "D" ):?>
						checked
					<?endif?>
					> <label for="askaron_pro1c_check_catalog_D"><?=GetMessage("askaron_pro1c_check_source_D")?></label>

				<?=BeginNote();?>
				<?=GetMessage("askaron_pro1c_check_source_catalog_help");?>
				<?=EndNote();?>
			</td>
		</tr>
		<tr class="heading">
			<td width="100%" colspan="2"><?=GetMessage("askaron_pro1c_check_orders");?></td>
		</tr>
		<tr>
			<td width="50%" valign="top"><?=GetMessage("askaron_pro1c_check_source")?>:</td>
			<td width="50%">
				<input type="radio" name="askaron_pro1c_check_orders" id="askaron_pro1c_check_orders_Y" value="Y"
					<?if ( $askaron_pro1c_check_orders == "Y" ):?>
						 checked
					<?endif?>					   				
				> <label for="askaron_pro1c_check_orders_Y"><?=GetMessage("askaron_pro1c_check_source_Y")?></label>
				<br>
				<input type="radio" name="askaron_pro1c_check_orders" id="askaron_pro1c_check_orders_N" value="N"
					<?if ( $askaron_pro1c_check_orders == "N" ):?>
						 checked
					<?endif?>
				> <label for="askaron_pro1c_check_orders_N"><?=GetMessage("askaron_pro1c_check_source_N")?></label>

				<br>
				<input type="radio" name="askaron_pro1c_check_orders" id="askaron_pro1c_check_orders_D" value="D"
					<?if ( $askaron_pro1c_check_orders == "D" ):?>
						checked
					<?endif?>
					> <label for="askaron_pro1c_check_orders_D"><?=GetMessage("askaron_pro1c_check_source_D")?></label>

				<?=BeginNote();?>
					<?=GetMessage("askaron_pro1c_check_source_orders_help");?>
				<?=EndNote();?>
			</td>
		</tr>

		<?$tabControl->Buttons();?>	
		<input <?if(!$RIGHT_W) echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">		
		<?
		$tabControl->End();
		?>
	</form>
<?}?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
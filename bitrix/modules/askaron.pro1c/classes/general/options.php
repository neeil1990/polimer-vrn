<?
class CAskaronPro1COptions
{
	static public function ShowGroup( $group_key, $arGroups, $arDisplayOptions )
	{
		global $APPLICATION;?>

		<?$arGroup = $arGroups[$group_key];?>

		<?if ( strlen($arGroup["NAME"]) > 0 ):?>
			<tr class="heading">
				<td valign="top" colspan="2" align="center"><?=$arGroup["NAME"]?></td>
			</tr>
		<?endif?>

		<?if ( strlen( $arGroup["HELP"] ) > 0 ):?>

			<tr>
				<td valign="top" colspan="2">
					<?=BeginNote();?>
					<?=$arGroup["HELP"];?>
					<?=EndNote();?>
				</td>
			</tr>
		<?endif?>

		<?foreach ( $arDisplayOptions as $key => $arInput  ):?>

			<?if ( $group_key == $arInput["GROUP"] ):?>
				<tr>
					<td valign="top" width="50%" class="field-name"><label for="<?=$arInput["INPUT_ID"]?>"><?=$arInput["NAME"]?></label></td>
					<td valign="top" width="50%">
						<?if ( $arInput["TYPE"] == "CHECKBOX" ):?>
							<input
								type="checkbox"
								value="Y"
								id="<?=$arInput["INPUT_ID"]?>"
								<?if ( $arInput["INPUT_VALUE"] == "Y" ):?>
									checked="checked"
								<?endif?>
								name="<?=$arInput["INPUT_NAME"]?>"
								/>

							<?=$arInput["NAME2"]?>
						<?endif?>

						<?if ( ($arInput["TYPE"] == "TEXT" && $arInput["ROWS"] <= 1) || $arInput["TYPE"] == "INTEGER" ):?>
							<input
								type="text"
								value="<?=$arInput["INPUT_VALUE"]?>"
								id="<?=$arInput["INPUT_ID"]?>"
								name="<?=$arInput["INPUT_NAME"]?>"
								<?//size="40"?>
								/>

							<?=$arInput["NAME2"]?>
						<?endif?>

						<?if ( $arInput["TYPE"] == "LOCATION" ):?>

							<?$APPLICATION->IncludeComponent(
								"bitrix:sale.location.selector.search",
								"",
								Array(
									"CACHE_TIME" => "36000000",
									"CACHE_TYPE" => "A",
									"CODE" => "",
									"COMPONENT_TEMPLATE" => ".default",
									"EXCLUDE_SUBTREE" => "",
									"FILTER_BY_SITE" => "N",
									"FILTER_SITE_ID" => $arInput["SITE_ID"],
									"ID" => $arInput["INPUT_VALUE"],
									"INPUT_NAME" => $arInput["INPUT_NAME"],
									"JSCONTROL_GLOBAL_ID" => "",
									"JS_CALLBACK" => "",
									"PROVIDE_LINK_BY" => "id",
									"SEARCH_BY_PRIMARY" => "N",
									"SHOW_DEFAULT_LOCATIONS" => "N"
								),
								null,
								Array(
									'HIDE_ICONS' => 'N'
								)
							);?>
						<?endif?>

						<?if ( $arInput["TYPE"] == "TEXT" && $arInput["ROWS"] > 1  ):?>
							<textarea id="<?=$arInput["INPUT_ID"]?>" name="<?=$arInput["INPUT_NAME"]?>" rows="<?=$arInput["ROWS"]?>" cols="<?=$arInput["COLS"]?>"><?=$arInput["INPUT_VALUE"]?></textarea>
						<?endif?>

						<?if ( $arInput["TYPE"] == "IMAGE" ):?>

							<?=CFile::InputFile( $arInput["INPUT_NAME"], 20,  $arInput["~INPUT_VALUE"], "/upload/");?>

							<?if (strlen($arInput["~INPUT_VALUE"])>0):?>
								<br><?=CFile::ShowImage( $arInput["~INPUT_VALUE"], 150, 150, "border=0", "", true );?>
							<?endif;?>
						<?endif?>

						<?if ( strlen( $arInput["HELP"] ) > 0 ):?>
							<?=BeginNote();?>
							<?=$arInput["HELP"];?>
							<?=EndNote();?>
						<?endif?>
					</td>
				</tr>
			<?endif?>

		<?endforeach?>
	<?
	}

	static public function ShowAll( $arGroups, $arDisplayOptions )
	{
		foreach ( $arGroups as $group_key => $arGroup )
		{
			self::ShowGroup( $group_key, $arGroups, $arDisplayOptions);
		}
	}
}
?>

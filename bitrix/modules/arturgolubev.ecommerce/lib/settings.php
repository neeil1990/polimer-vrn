<?
namespace Arturgolubev\Ecommerce; //1.1

class Settings {
	function showInitUI(){
		if (class_exists('\Bitrix\Main\UI\Extension')) {
		   \Bitrix\Main\UI\Extension::load("ui.hint");
		   ?>
			<script>BX.ready(function() {BX.UI.Hint.init(BX('adm-workarea')); });</script>
			<style>.simple-hint {display: none !important;}</style>
			<?
		}
		?>
		<style>
			#bx-admin-prefix form .adm-info-message {
				color:#111;
				background:#fff;
				border: 1px solid #bbb;
				padding: 10px 15px;
				margin-top: 0;
				text-align: left;
			}
		</style>
		<?
	}
	
	function showSettingsList($module_id, $arOptions, $tab){
		
		$settingList = $arOptions[$tab["OPTIONS"]];
			
		foreach ($settingList as $Option) {
			
			// echo '<pre>'; print_r($Option); echo '</pre>';
			
			if($tab["OPTIONS"] == 'help'){
				?>
				<tr>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%">
						<?=$Option["1"]?>
					</td>
					<td class="adm-detail-content-cell-r" width="50%">
						<?=htmlspecialchars_decode($Option["2"])?>
					</td>
				</tr>
				<?
			}elseif($Option["3"]["0"] == 'colorbox'){
				$value = \COption::GetOptionString($module_id, $Option[0]);
				?>
					<tr>
						<td class="adm-detail-content-cell-l" width="50%"><?=$Option["1"]?><a name="opt_<?=$Option["0"]?>"></a></td>
						<td class="adm-detail-content-cell-r" width="50%">
							<?$pickID = "pick_color_".$Option["0"];?>
							
							<input type="text" id="<?=$pickID?>" size="" maxlength="255" value="<?=$value?>" name="<?=$Option["0"]?>">
							
							<script type="text/javascript">
							BX.ready(function() {
								var element = BX('<?=$pickID?>');

								BX.bind(element, 'focus', function () {
									new BX.ColorPicker({
										bindElement: element,
										defaultColor: '',
										allowCustomColor: true,
										onColorSelected: function (item) {
											element.value = item;
										},
										popupOptions: {
											angle: true,
											autoHide: true,
											closeByEsc: true,
										}
									}).open();
								});
							  });
							</script>
						</td>
					</tr>
				<?
			}else{
				__AdmSettingsDrawRow($module_id, $Option);
			}
		}
	}
}
<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$module_id = 'arturgolubev.watcher';
$module_name = str_replace('.', '_', $module_id);
$MODULE_NAME = strtoupper($module_name);

$path = "/bitrix/gadgets/arturgolubev/watcher/";

global $APPLICATION;
$APPLICATION->SetAdditionalCSS($path.'/styles.css');

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].$path."/.parameters.php");

$jsFunctionName = str_replace('@', '', $arGadget['ID']).'_js_worker';
?>

<div class="ag-gadgets ag-watcher">
	<div class="ag-watcher-content">
		<div class="ag-watcher-loader"></div>
	</div>

	<script type="text/javascript">
		if(typeof <?=$jsFunctionName?> !== 'function'){
			function <?=$jsFunctionName?>() {
				var obGadget = BX("t<?=$arGadget['ID']?>");
				
				if(obGadget){
					var obStatemailGadget = BX.findChildren(obGadget, {'className':'ag-gadgets'}, true);
					var obStatemailContent = BX.findChildren(obGadget, {'className':'ag-watcher-content'}, true);
					
					BX.ajax({
						url: '<?=$path?>ajax.php',
						method: 'POST',
						data: {'module': '<?=$module_id?>', 'target': '<?=$arGadgetParams["SHOW_TARGET"]?>'},
						dataType: 'html',
						timeout: 30,
						async: true,
						start: true,
						cache: false,
						onsuccess: function(data){
							BX.addClass(obStatemailGadget[0], 'ag-loaded');
							BX.adjust(obStatemailContent[0], {html: data});
						},
						onfailure: function(){
							BX.adjust(obStatemailContent[0], {html: '<div class="ag-watcher-error"><?=GetMessage("ARTURGOLUBEV_WATCHER_DB_ERROR")?></div>'});
						}
					});
				}
				
			}
		}

		<?=$jsFunctionName?>();
    </script>	
</div>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Corsik\YaDelivery\Handler;
use Corsik\YaDelivery\Table\WarehousesTable;

$module_id = 'corsik.yadelivery';
$messages = Loc::loadLanguageFile(__FILE__);
$messagesJS = Loc::loadLanguageFile(dirname(__DIR__) . '/corsik_js_message.php');
Extension::load(["jquery2", "translit"]);

Loader::includeModule('main');
Loader::includeModule($module_id);

global $APPLICATION;

$type = 'warehouses';
$request = Context::getCurrent()->getRequest();
$handler = Handler::getInstance();
$warehousesList = new WarehousesTable();
$tabs = [
	[
		'DIV' => 'tab-1',
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_WAREHOUSES"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_WAREHOUSES"),
	],
	[
		'DIV' => 'tab-2',
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_PROPERTIES_WAREHOUSES"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_PROPERTIES_WAREHOUSES"),
	],
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$fields = ['ID' => 0, 'NAME', 'ACTIVE' => 'Y', 'SORT' => '500', 'COORDINATES'];
$ID = $request->getQuery('ID');
$delivery = [];

if ($ID > 0)
{
	$fields = WarehousesTable::getList(['filter' => ['ID' => $ID], 'select' => ['*']])->fetchRaw();
	$delivery = $handler->getDeliveryInProperties($fields['COORDINATES'], $type);
	if (!$handler->hasFeatures($fields['COORDINATES']))
	{
		$handler->helper::showArrayErrors(['DETAILS' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_NOT_FOUND_WAREHOUSES")]);
	}
}

$tabControl = new CAdminTabControl('tabControl', $tabs);
$context = new CAdminContextMenu([
	[
		'TEXT' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_BACK"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_BACK"),
		'LINK' => 'corsik_yadelivery_warehouses.php?lang=' . LANGUAGE_ID,
		'ICON' => 'btn_list',
	],
]);

if ($request->getRequestMethod() == "POST" && (($request->getPost("save") || $request->getPost("apply"))))
{
	if (!check_bitrix_sessid())
		throw new ArgumentException("Bad sessid.");

	$postList = $request->getPostList()->toArray();
	if (!empty($postList['COORDINATES']))
	{
		$postList['COORDINATES'] = $handler->setPropertiesToJson($postList, $type);
	}

	$postValues = WarehousesTable::getMapMatchArray($postList);

	//    if (!empty($postProperties))
	//        $postValues['COORDINATES'] = $handler->saveAreaProperties($postValues['COORDINATES'], $postProperties);

	if ($postList['ID'] == 0)
	{
		unset($postValues['ID']);
		$result = WarehousesTable::add($postValues);
	}
	else
	{
		$result = WarehousesTable::update($postValues['ID'], $postValues);
	}

	$fields = $postValues;
	if (!$result->isSuccess())
	{
		$handler->helper::showArrayErrors($result->getErrorMessages());
	}
	else
	{
		$postValues['ID'] = $fields['ID'] = $result->getId();
		if ($request->getPost("save"))
		{
			$url = "corsik_yadelivery_warehouses.php?lang=" . LANGUAGE_ID;
		}
		else
		{
			$url = $APPLICATION->GetCurPage() . "?lang=" . LANGUAGE_ID . "&ID=" . $fields['ID'] . "&" . $tabControl->ActiveTabParam();
		}
		LocalRedirect($url);
	}
}
$APPLICATION->SetTitle($fields['NAME']);
?>
<form name="ya_delivery" method="POST"
		action="<? echo $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>"
		ENCTYPE="multipart/form-data">
	<?= bitrix_sessid_post() ?>
	<input type="hidden" name="ID" value="<?= $fields['ID'] ?>" />
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>" />
	<?
	$context->Show();
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	?>
	<tr>
		<td><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_SITE_ID") ?></td>
		<td><?= SelectBoxFromArray("SITE_ID", Handler::getSites(true), $fields['SITE_ID']); ?></td>
	</tr>
	<tr>
		<td><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_ACTIVE") ?></td>
		<td><?= InputType("checkbox", "ACTIVE", "Y", $fields['ACTIVE']); ?></td>
	</tr>
	<tr class="adm-detail-field">
		<td width="30%"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_SORT") ?></td>
		<td width="70%">
			<input type="text" name="SORT" size="30" maxlength="255"
					value="<?= htmlspecialcharsbx($fields['SORT']) ?>">
		</td>
	</tr>
	<tr class="adm-detail-required-field">
		<td width="30%"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_NAME") ?></td>
		<td width="70%">
			<input type="text" name="NAME" size="30" maxlength="255"
					value="<?= htmlspecialcharsbx($fields['NAME']) ?>">
		</td>
	</tr>
	<tr>
		<td><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_CHOOSE_ZONE") ?></td>
		<td>
			<?= SelectBoxFromArray("ZONE_ID", $handler->getDataToSelect('zones', true), $fields['ZONE_ID']); ?>
			<input type="button" class="adm-btn-save apply_zone" name="yandex_apply_zone"
					value="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_SHOW') ?>"
					title="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_SHOW') ?>">
		</td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top adm-detail-content-cell-l"
				width="30%"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_COORDINATES") ?></td>
		<td class="adm-detail-content-cell-r" width="60%">
                <textarea class="ya_geoJson" rows="8" cols="80"
						name="COORDINATES"><?= $fields['COORDINATES'] ?></textarea>
		</td>
	</tr>
	<tr>
		<td width="30%"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_GEOJSON") ?></td>
		<td width="70%">
			<input type="hidden" name="ya_gejson_file" id="ya_gejson_file" size="30" maxlength="255">
			<input type="button" name="yandex_json_geo" class="openDialog" value="..."
					onclick="browseJsonPath();"
					title="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_UPLOAD') ?>">
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<div id="ya_warehouse" style="width: 100%; height: 500px;"></div>
		</td>
	</tr>
	<? $tabControl->BeginNextTab(); ?>
	<tr class="heading">
		<td colspan="2">
			<b><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_HEADING_PRICE_WAREHOUSES") ?></b></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<table cellspacing="0" cellpadding="0" border="0" class="internal">
				<tbody>
				<tr class="heading">
					<td
							align="top"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_WAREHOUSES") ?></td>
					<td align="top"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_POLYGONS") ?></td>
					<td align="top"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_PRICE") ?></td>
					<td align="top"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE") ?></td>
					<td align="top"></td>
				</tr>
				<?
				$firstDelivery = current($delivery);
				foreach ($delivery as $i => $d)
				{
					$all = ['all' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ALL")];
					$no = ['no' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_NO")];
					?>
					<tr class="polygon_row">
						<td><?= SelectBoxFromArray("PROPERTIES[DELIVERY][$i][FROM]", $handler->getDataToSelect('warehouses', true, $fields['ID'], $all), $d['FROM']); ?></td>
						<td><?= SelectBoxFromArray("PROPERTIES[DELIVERY][$i][TO]", $handler->getDataToSelect('zones', true, $fields['ZONE_ID'], $all), $d['TO']); ?></td>
						<td><input type="text" name="PROPERTIES[DELIVERY][<?= $i ?>][PRICE]" size="10"
									value="<?= $d['PRICE'] ?>" maxlength="255" placeholder="">
						</td>
						<td class="column_rules">
							<?= SelectBoxFromArray("PROPERTIES[DELIVERY][$i][RULE]", $handler->getDataToSelect('rules', true, 0, $no), $d['RULE']); ?>
						</td>
						<td>
							<div class="column_action">
								<div>
                                <span class="delete row_delete <?= $firstDelivery === $d ? 'hidden_block' : '' ?>">
                                <img
										src="/bitrix/themes/.default/images/actions/delete_button.gif" border="0"
										width="20" height="20">
                                </span>
								</div>
								<!--                                <div>-->
								<!--                                <span class="delete row_delete -->
								<?php //= $firstDelivery === $d ? 'hidden_block' : ''
								?><!--">-->
								<!--                                <img-->
								<!--                                        src="/bitrix/themes/.default/images/actions/delete_button.gif" border="0"-->
								<!--                                        width="20" height="20">-->
								<!--                                </span>-->
								<!--                                </div>-->
							</div>
						</td>
					</tr>
				<? } ?>
				<tr>
					<td colspan="3" align="center">
						<input type="button" name="add_polygon_price" class="add_warehouse_price"
								value="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_ADD') ?>"
								title="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_ADD') ?>">
					</td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<?
	$tabControl->Buttons(["back_url" => "corsik_yadelivery_warehouses.php?lang=" . LANGUAGE_ID]);
	$tabControl->End();
	?>
</form>

<?
$yaSrc = "//api-maps.yandex.ru/2.1/?lang=ru_RU";
$yaApiKey = Option::get('fileman', "yandex_map_api_key");
$src = ($yaApiKey) ? "$yaSrc&apikey=$yaApiKey" : $yaSrc;
CAdminFileDialog::ShowScript
([
	"event" => "browseJsonPath",
	"arResultDest" => ["ELEMENT_ID" => "ya_gejson_file"],
	"arPath" => [],
	"select" => 'F',
	"operation" => 'O',
	"showUploadTab" => true,
	"showAddToMenuTab" => true,
	"fileFilter" => 'geojson',
	"allowAllFiles" => false,
	"SaveConfig" => true,
]);
?>
<div id="ya_map_delivery" style="width:500px; height:400px; display: none;"></div>
<link href="/bitrix/css/<?= $module_id ?>/admin/nano.min.css" type="text/css" rel="stylesheet">
<link href="/bitrix/css/<?= $module_id ?>/admin/admin.edit.css" type="text/css" rel="stylesheet">
<script type="text/javascript" src="<?= $src ?>"></script>
<script type="text/javascript" src="/bitrix/js/<?= $module_id ?>/admin/pickr.min.js"></script>
<script type="text/javascript"
		src="/bitrix/js/<?= $module_id ?>/admin/admin.edit-warehouse.js"></script>
<script>
	BX.message(<?=CUtil::PhpToJSObject($messagesJS)?>);
</script>
<?
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Corsik\YaDelivery\Handler;
use Corsik\YaDelivery\Helper;
use Corsik\YaDelivery\Table\ZonesTable;

Loc::loadLanguageFile(__FILE__);
$messagesJS = Loc::loadLanguageFile(dirname(__DIR__) . '/corsik_js_message.php');
Extension::load(["jquery2", "translit"]);
Loader::includeModule('main');
Loader::includeModule(Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID"));
$type = 'zones';
$request = Context::getCurrent()->getRequest();
$handler = Handler::getInstance();
$tabs = [
	[
		'DIV' => 'zones',
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_ZONE"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_ZONE"),
	],
	[
		'DIV' => 'zonesPrice',
		'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ZONE_PRICE"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ZONE_PRICE"),
	],
	//    ['DIV' => 'zonesRestriction', 'TAB' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ZONES_RESTRICTION"), 'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ZONES_RESTRICTION")],
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

global $APPLICATION, $by, $order;

$fields = ['ID' => 0, 'NAME', 'ACTIVE' => 'Y', 'SORT' => '500', 'COORDINATES'];
$properties = ['hint-content', 'fill', 'fill-opacity', 'stroke', 'stroke-width'];
$ID = $request->getQuery('ID');
$delivery = [];

if ($ID > 0)
{
	$fields = ZonesTable::getList(['filter' => ['ID' => $ID], 'select' => ['*']])->fetchRaw();
	$delivery = $handler->getDeliveryInProperties($fields['COORDINATES'], $type);
}

$tabControl = new CAdminTabControl('tabControl', $tabs);
$context = new CAdminContextMenu([
	[
		'TEXT' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_BACK"),
		'TITLE' => Loc::getMessage("CORSIK_DELIVERY_SERVICE_BACK"),
		'LINK' => 'corsik_yadelivery_zones.php?lang=' . LANGUAGE_ID,
		'ICON' => 'btn_list',
	],
]);

if ($request->getRequestMethod() == "POST" && (($request->getPost("save") || $request->getPost("apply"))))
{
	if (!check_bitrix_sessid())
	{
		throw new ArgumentException("Bad sessid.");
	}

	$postList = $request->getPostList()->toArray();
	$postList['COORDINATES'] = $handler->setPropertiesToJson($postList, $type);
	$postValues = ZonesTable::getMapMatchArray($postList);
	//    $postProperties = $handler->areaPropertiesValues($postList);
	//    if (!empty($postProperties))
	//        $postValues['COORDINATES'] = $handler->saveAreaProperties($postValues['COORDINATES'], $postProperties);

	if ($postList['ID'] == 0)
	{
		unset($postValues['ID']);
		$result = ZonesTable::add($postValues);
	}
	else
	{
		$result = ZonesTable::update($postValues['ID'], $postValues);
	}

	$fields = $postValues;
	if (!$result->isSuccess())
	{
		Helper::showArrayErrors($result->getErrorMessages());
	}
	else
	{
		$postValues['ID'] = $fields['ID'] = $result->getId();
		if ($request->getPost("save"))
		{
			$url = "corsik_yadelivery_zones.php?lang=" . LANGUAGE_ID;
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
		/**
		 * >>>TAB ZONES<<<
		 */
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
			<td class="adm-detail-valign-top adm-detail-content-cell-l"
					width="30%"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_COORDINATES") ?></td>
			<td class="adm-detail-content-cell-r" width="60%">
                <textarea class="ya_geoJson" rows="8" cols="80"
						name="COORDINATES"><?= $fields['COORDINATES'] ?></textarea>
			</td>
		</tr>
		<tr>
			<td width="30%"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_POLYGON") ?></td>
			<td width="70%">
				<input type="hidden" name="ya_gejson_file" id="ya_gejson_file" size="30" maxlength="255">
				<input type="button" name="yandex_json_geo" class="openDialog" value="..."
						onclick="browseJsonPath();"
						title=<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_UPLOAD') ?>"">
				<div class="float-right">
					<input type="button" class="ya_clear"
							value="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_CLEAR_ALL') ?>"
							title="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_CLEAR_ALL') ?>">
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div id="ya_polygon" style="width: 100%; height: 500px;"></div>
			</td>
		</tr>
		<?
		/**
		 * >>>END TAB ZONES<<<
		 */
		$tabControl->BeginNextTab();
		/**
		 * >>>TAB ZONES PRICE<<<
		 */
		?>
		<tr class="heading">
			<td colspan="2"><b><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_HEADING_PRICE_ZONES") ?></b>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<table cellspacing="0" cellpadding="0" border="0" class="internal">
					<tbody>
					<tr class="heading">
						<td
								align="top"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_POLYGONS") ?></td>
						<td align="top"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_COUNT_KM") ?></td>
						<td align="top"><?= Loc::getMessage("CORSIK_DELIVERY_SERVICE_LABEL_COST") ?></td>
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
							<td><?= SelectBoxFromArray("PROPERTIES[DELIVERY][$i][POLYGON]", $handler->getDataToSelect('zones', true, $fields['ID'], $all), $d['POLYGON']); ?></td>
							<td><input type="text" name="PROPERTIES[DELIVERY][<?= $i ?>][KM]" size="10"
										value="<?= $d['KM'] ?>" maxlength="255" placeholder="">
							<td><input type="text" name="PROPERTIES[DELIVERY][<?= $i ?>][PRICE]" size="10"
										value="<?= $d['PRICE'] ?>" maxlength="255" placeholder="">
							</td>
							<td class="column_rules">
								<?= SelectBoxFromArray("PROPERTIES[DELIVERY][$i][RULE]", $handler->getDataToSelect('rules', true, 0, $no), $d['RULE']); ?>
							</td>
							<td class="column_action">
                <span class="delete row_delete <?= $firstDelivery === $d ? 'hidden_block' : '' ?>">
                      <img
							  src="/bitrix/themes/.default/images/actions/delete_button.gif" border="0"
							  width="20" height="20">
                    </span>
							</td>
						</tr>
					<? } ?>
					<tr>
						<td colspan="3" align="center">
							<input type="button" name="add_out_polygon_price" class="add_out_polygon_price"
									value="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_ADD') ?>"
									title="<?= Loc::getMessage('CORSIK_DELIVERY_SERVICE_LABEL_ADD') ?>">
						</td>
					</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<?
		/**
		 * END >>>TAB ZONES PRICE<<<
		 */
		/**
		 * END >>>TAB ZONES RESTRICTION<<<
		 */
		$tabControl->Buttons(["back_url" => "corsik_yadelivery_zones.php?lang=" . LANGUAGE_ID]);
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
<?php
Asset::getInstance()->addString('<link href="/bitrix/css/' . Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID") . '/admin/nano.min.css" type="text/css" rel="stylesheet">');
Asset::getInstance()->addString('<link href="/bitrix/css/' . Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID") . '/admin/admin.edit.css" type="text/css" rel="stylesheet">');
Asset::getInstance()->addString('<script type="text/javascript" src="' . $src . '"></script>');
Asset::getInstance()->addJs("/bitrix/js/" . Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID") . "/admin/pickr.min.js");
Asset::getInstance()->addJs("/bitrix/js/" . Loc::getMessage("CORSIK_DELIVERY_SERVICE_MENU_ID") . "/admin/admin.edit-zone.js");
?>
	<script>
		BX.message(<?=CUtil::PhpToJSObject($messagesJS)?>);

		function saveGeoJson(filename, path)
		{
			$.ajax({
				url: `/bitrix/tools/corsik.yadelivery/parseGeoJson.php`,
				data: { geoJson: path + filename },
			})
				.done((res) => {
					$("#tab-2").remove();
					$(".ya_geoJson").text(res);
					$("[name=apply]").click();
				})
				.fail(console.error);
		}
	</script>
<?
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

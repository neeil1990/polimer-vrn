<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('MAXYSS_OZON_GENERAL_TITLE'));

CJSCore::Init( 'jquery' );

global $APPLICATION;
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency;
\Bitrix\Main\UI\Extension::load("ui.hint");?>
    <script type="text/javascript">
        BX.ready(function() {
            BX.UI.Hint.init(BX('adm-detail-content-item-block'));
        })
    </script>
<?

if(CModule::IncludeModuleEx(MAXYSS_MODULE_NAME) == 2)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_OZON_MODULE_TRIAL_2').'</font>';
if(CModule::IncludeModuleEx(MAXYSS_MODULE_NAME) == 3)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_OZON_MODULE_TRIAL_3').'</font>';

if(Loader::includeModule('catalog') && Loader::includeModule('iblock') && CModule::IncludeModule(MAXYSS_MODULE_NAME)){


    if($_REQUEST['save'] || $_REQUEST['apply']){
        Option::set(MAXYSS_MODULE_NAME, "LOG_ON", ($_REQUEST['LOG_ON'])? "Y" : "N");
        Option::set(MAXYSS_MODULE_NAME, "REQURED_MORE", ($_REQUEST['ozon_requared'])? "Y" : "N");
        if($_REQUEST['ozon_v3'] == "Y") {
            Option::set(MAXYSS_MODULE_NAME, "OZON_V3", ($_REQUEST['ozon_v3']) ? "Y" : "N");

            $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonLoadOrder(%"));
            if ($arRes = $res->GetNext()) {
                CAgent::Delete($arRes['ID']);
            }
            $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonUploadProduct(%"));
            if($arRes = $res->GetNext()) {
                CAgent::Delete($arRes['ID']);
            }
            foreach ($_REQUEST['lid'] as $l){
                Option::set(MAXYSS_MODULE_NAME, "ACTIVE_ON", "N", $l);
                Option::set(MAXYSS_MODULE_NAME, "ACTIVE_ORDER_ON", "N", $l);
            }
        }
        CMaxyssOzon:: saveOptions('server_name');
        CMaxyssOzon:: saveOptions('ozon_api_key');
        CMaxyssOzon:: saveOptions('ozon_id');
        CMaxyssOzon:: saveOptions('delivery_service_v3');
        CMaxyssOzon:: saveOptions('deactivate');
        CMaxyssOzon:: saveOptions('limit');
        CMaxyssOzon:: saveOptions('limit_price');
        CMaxyssOzon:: saveOptions('sklad_id_v3');
        Option::set(MAXYSS_MODULE_NAME, "OZON_V2", "Y");

    }

    $site = '';
    $site = Option::get(MAXYSS_MODULE_NAME, "SITE", "");
    $rsSites = CSite::GetList($by="def", $order="desc", Array());
    while ($arSite = $rsSites->Fetch())
    {
        $selected = '';
        if($site == $arSite['LID'])
            $selected = 'selected = "selected"';
        $site_select .= '<option '.$selected.' value="'.$arSite['LID'].'">'.'['.$arSite['LID'].'] '.$arSite['NAME'].'</option>';
    }

// табуляторы
    $by = "id";
    $sort = "asc";

    $arSites = array();
    $db_res = CSite::GetList($by, $sort, array("ACTIVE"=>"Y"));
    while($res = $db_res->Fetch()){
        $arSites[] = $res;
    }

    $arTabs = array();
    foreach($arSites as $key => $arSite){
//        $arBackParametrs = $moduleClass::GetBackParametrsValues($arSite["ID"], false);
        $arTabs[] = array(
            "DIV" => "edit".($key+1),
            "TAB" => GetMessage("MAXYSS_OZON_GENERAL_TITLE_TAB", array("#SITE_NAME#" => $arSite["NAME"], "#SITE_ID#" => $arSite["ID"])),
            "ICON" => "settings",
            // "TITLE" => GetMessage("MAIN_OPTIONS_TITLE"),
            "PAGE_TYPE" => "site_settings",
            "SITE_ID" => $arSite["ID"],
            "SITE_DIR" => $arSite["DIR"],
//            "TEMPLATE" => CNext::GetSiteTemplate($arSite["ID"]),
//            "OPTIONS" => $arBackParametrs,
        );
    }

    $tabControl = new CAdminTabControl("tabControl", $arTabs);

    if(!count($arTabs)){?>
        <div class="adm-info-message-wrap adm-info-message-red">
            <div class="adm-info-message">
                <div class="adm-info-message-title">No site installed</div>
                <div class="adm-info-message-icon"></div>
            </div>
        </div>
    <?}else{?>

            <form action="<?=MAXYSS_MODULE_NAME?>_ozon_maxyss_general.php?lang=<?=LANGUAGE_ID?>" method="post">
                <input type="hidden" name="ozon_v2" value="Y" id="ozon_v2">
                <input type="checkbox" name="LOG_ON" style="display: none!important;" value="Y">


                <?$tabControl->Begin();
                // табуляторы
                ?>
                <?

                // склады
                $dbSklad = CCatalogStore::GetList(
                    array('TITLE'=>'ASC','ID' => 'ASC'),
                    array('ACTIVE' => 'Y'),
                    false,
                    false,
                    array()
                );
                while ($arSklad = $dbSklad->Fetch())
                {
                    $arSklads[] = $arSklad;
                }

                // доставки
                $arDelivery = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
                foreach ($arDelivery as $key_deliver => $deliver){
                    $arDeliverySelect[$key_deliver] = $deliver['NAME'];
                }

                foreach($arTabs as $key => $arTab)
                {
                    $arOptions = CMaxyssOzon::getOptions($arTab["SITE_ID"], array('OZON_ID', 'OZON_API_KEY', "SERVER_NAME", "DELIVERY_SERVICE_V3", "SKLAD_ID_V3", "DEACTIVATE", "OZON_V3", "LIMIT", "LIMIT_PRICE"));
                    $tabControl->BeginNextTab();?>
                    <input type="hidden" name="lid[<?=$arTab["SITE_ID"]?>]" value="<?=$arTab["SITE_ID"]?>">
                                <tr class="heading">
                                    <td colspan="2"><?=GetMessage('MAXYSS_OZON_ACCOUNT_OZON')?></td>
                                </tr>
                                <tr>
                                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_OZON_ID')?></td>
                                    <td class="adm-detail-content-cell-r">
                                        <input name="ozon_id[<?=$arTab["SITE_ID"]?>]" value="<?echo (Option::getRealValue(MAXYSS_MODULE_NAME, "OZON_ID", $arTab["SITE_ID"]))?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_OZON_API_KEY')?></td>
                                    <td class="adm-detail-content-cell-r">
                                        <input name="ozon_api_key[<?=$arTab["SITE_ID"]?>]" value="<?echo (Option::getRealValue(MAXYSS_MODULE_NAME, "OZON_API_KEY", $arTab["SITE_ID"]))?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_V3')?></td>
                                    <td class="adm-detail-content-cell-r">
                                        <input type="hidden" name="ozon_v3"  class="" value="N">
                                        <input type="checkbox" value="Y" name="ozon_v3" id="ozon_v3_<?=$arTab["SITE_ID"]?>" class="" <?echo  (Option::get(MAXYSS_MODULE_NAME, "OZON_V3", "") == 'Y')? 'checked = "checked" disabled' : ''?>>
                                    </td>
                                </tr>
                            <?
                            if(Option::getRealValue(MAXYSS_MODULE_NAME, "OZON_ID", $arTab["SITE_ID"]) !='' && Option::get(MAXYSS_MODULE_NAME, "OZON_V3", "") == 'Y'){
                                $warehouses = array();

                                $ClientId = Option::getRealValue(MAXYSS_MODULE_NAME, "OZON_ID", $arTab["SITE_ID"]);
                                $ApiKey = CMaxyssOzon::GetApiKey($ClientId);

                                $warehouses = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, "{}", "/v1/warehouse/list");
                                if(!$warehouses['error'] && !empty($warehouses)){?>
                                <tr class="heading">
                                    <td colspan="2"><?=GetMessage("MAXYSS_OZON_SKLADS")?></td>
                                </tr>
                            <?
                            $arDeactivateWarehouses = unserialize($arOptions[$arTab["SITE_ID"]]["DEACTIVATE"]);
                            $arLimitWarehouses = unserialize($arOptions[$arTab["SITE_ID"]]["LIMIT"]);
                            $arLimitWarehousesPrice = unserialize($arOptions[$arTab["SITE_ID"]]["LIMIT_PRICE"]);

                                    foreach ($warehouses as $warehouse){
                                $delivery_method = array();
                                    $data_string = array(
                                         "filter"=>array(
    //                                    "provider_id"=> 0,
                                         "status"=> "ACTIVE",
                                         "warehouse_id"=> $warehouse["warehouse_id"],
                                         ),
                                         "limit"=> 50,
                                         "offset"=> 0
                                    );
                                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);

                                    $delivery_method = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v1/delivery-method/list");
                                ?>
                                <?
                                $arDeliverysMetod = unserialize($arOptions[$arTab["SITE_ID"]]["DELIVERY_SERVICE_V3"]);

                                $sklad_select = '';
                                $sklad_id = unserialize($arOptions[$arTab["SITE_ID"]]["SKLAD_ID_V3"]);
                                if(is_array($arSklads)){
                                    foreach ($arSklads as $sklad)
                                    {
                                        $selected = '';
                                        if(in_array($sklad['ID'], $sklad_id[$warehouse["warehouse_id"]]))
                                            $selected = 'selected = "selected"';
                                        $sklad_select .= '<option '.$selected.' value="'.$sklad['ID'].'">'.'['.$sklad['ID'].'] '.$sklad['TITLE'].'</option>';
                                    }
                                }

                                ?>

                                <tr>
                                    <td colspan="2" style="font-weight: 600; text-align: center"><?=$warehouse["name"]?></td>
                                </tr>
                                    <td colspan="2" style=" text-align: center">
                                        <?=GetMessage('MAXYSS_OZON_V3_DEACTIVATE_WAREHOUSE')?>
                                        <input  type="hidden" name="deactivate[<?=$arTab["SITE_ID"]?>][<?=$warehouse["warehouse_id"]?>]" value="N">
                                        <input id="<?=$warehouse["warehouse_id"]?>" type="checkbox" name="deactivate[<?=$arTab["SITE_ID"]?>][<?=$warehouse["warehouse_id"]?>]" <?echo ($arDeactivateWarehouses[$warehouse["warehouse_id"]] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                                        <span style="display: inline-block; width: 50px"></span>
                                        <?=GetMessage('MAXYSS_OZON_V3_LIMIT_WAREHOUSE')?>
                                        <input id="<?=$warehouse["warehouse_id"]?>" type="number" name="limit[<?=$arTab["SITE_ID"]?>][<?=$warehouse["warehouse_id"]?>]"  value="<?echo $arLimitWarehouses[$warehouse["warehouse_id"]];?>"><span data-hint="<?=GetMessage('MAXYSS_OZON_V3_LIMIT_WAREHOUSE_TIP')?>"></span>

                                        <span style="display: inline-block; width: 50px"></span>
                                        <?=GetMessage('MAXYSS_OZON_V3_LIMIT_WAREHOUSE_PRICE')?>
                                        <input id="<?=$warehouse["warehouse_id"]?>" type="number" name="limit_price[<?=$arTab["SITE_ID"]?>][<?=$warehouse["warehouse_id"]?>]"  value="<?echo $arLimitWarehousesPrice[$warehouse["warehouse_id"]];?>"><span data-hint="<?=GetMessage('MAXYSS_OZON_V3_LIMIT_WAREHOUSE_PRICE_TIP')?>"></span>

                                    </td>
                                </tr>
                                <tr style="background-color: #e0e8ea"><td style="text-align: center"><?=GetMessage("MAXYSS_OZON_SKLAD_TEXT")?></td><td style="text-align: center"><?=GetMessage("MAXYSS_OZON_DELYVERY_TEXT")?></td></tr>
                                <tr>
                                    <td class="adm-detail-content-cell-l">
                                        <input type="hidden" name="sklad_id_v3[<?=$arTab["SITE_ID"]?>][<?=$warehouse["warehouse_id"]?>]" value="">
                                        <select multiple name="sklad_id_v3[<?=$arTab["SITE_ID"]?>][<?=$warehouse["warehouse_id"]?>][]">
                                            <?echo $sklad_select?>
                                        </select>
                                    </td>
                                    <td class="adm-detail-content-cell-r">
                                        <?if(!$delivery_method['error'] && !empty($delivery_method)){?>
                                            <table>
                                            <?
                                            $arDeliverysMetod = unserialize($arOptions[$arTab["SITE_ID"]]["DELIVERY_SERVICE_V3"]);
                                            foreach ($delivery_method as $d_metod){
                                                ?>
                                                <tr>
                                                    <td class="adm-detail-content-cell-l"><?=$d_metod['name']?></td>
                                                    <td class="adm-detail-content-cell-r">
                                                        <select name="delivery_service_v3[<?=$arTab["SITE_ID"]?>][<?=$warehouse["warehouse_id"]?>][<?=$d_metod['id']?>]">
                                                            <?
                                                            foreach ($arDeliverySelect as $key_type => $type){?>
                                                                <option value="<?=$key_type?>" <?echo ($arDeliverysMetod[$warehouse["warehouse_id"]][$d_metod['id']] == $key_type)? 'selected = "selected"' : ''?>><?=$type?></option>
                                                                <?
                                                            }?>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?}?>
                                            </table>
                                            <?
                                        }?>
                                    </td>
                                </tr>
                                <tr><td colspan="2"><br><br><br></td></tr>

                            <?}?>

                            <?
                        }

                    }
                    ?>



                                <tr class="heading">
                                    <td colspan="2"><?=GetMessage('MAXYSS_OZON_INTEGRATION_SETTINGS')?></td>
                                </tr>
                                <tr>
                                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_REQUARED_MORE')?></td>
                                    <td class="adm-detail-content-cell-r">
                                        <input type="checkbox" name="ozon_requared" id="ozon_requared_<?=$arTab["SITE_ID"]?>" class="" <?echo  (Option::get(MAXYSS_MODULE_NAME, "REQURED_MORE", "") == 'Y')? 'checked = "checked"' : ''?>>
<!--                                        <label class="adm-designed-checkbox-label" for="ozon_requared_--><?//=$arTab["SITE_ID"]?><!--" title=""></label>-->
                                    </td>
                                </tr>

                                <?/*?><tr>
                                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_SITE_ID')?></td>
                                    <td class="adm-detail-content-cell-r">
                                        <select name="site">
                                            <?echo $site_select?>
                                        </select>
                                    </td>
                                </tr>
                                <?*/?>
                                <tr>
                                    <td width="40%" class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_DOMEN_NAME')?></td>
                                    <td width="60%" class="adm-detail-content-cell-r">
                                        <input type="text" name="server_name[<?=$arTab["SITE_ID"]?>]" value="<?echo (Option::getRealValue(MAXYSS_MODULE_NAME, "SERVER_NAME", $arTab["SITE_ID"]))?>" size="50"> <input type="button" onclick="this.form['server_name[<?=$arTab["SITE_ID"]?>]'].value = window.location.host;" value="<?=GetMessage('MAXYSS_OZON_DOMEN_NAME_REAL')?>">
                                    </td>
                                </tr>
                            <div id="ans"></div>
                <?
                }
                ?>
                <?$tabControl->Buttons(array(
                    "back_url"=>MAXYSS_MODULE_NAME."_ozon_maxyss_general.php?lang=".LANGUAGE_ID,

                ));?>

                <?$tabControl->End();?>

            </form>
<script type="text/javascript">
    $(document).on("change", "input[name='ozon_requared']", function () {
        var check = $(this).prop('checked');
        $("input[name='ozon_requared']").each(function (index, value) {
            $(this).prop('checked', check);
        })
    });
    $(document).on("change", "input[name='ozon_v3']", function () {
        var check = $(this).prop('checked');
        $("input[name='ozon_v3']").each(function (index, value) {
            $(this).prop('checked', check);
            if($(this).prop('checked') && index === 0) alert('<?=GetMessage('MAXYSS_OZON_V3_WARNING')?>')
        })
    });
</script>
    <?}?>
<?}else
    die();
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>
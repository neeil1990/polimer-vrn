<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('MAXYSS_OZON_TITLE'));

CJSCore::Init( 'jquery' );

global $APPLICATION;
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency,
    Bitrix\Main\Web\Json;

global $USER_FIELD_MANAGER;
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

if(Loader::includeModule('catalog') && Loader::includeModule('iblock') && Loader::includeModule(MAXYSS_MODULE_NAME)){
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.ozon/filter_conditions/script.js");

    if($_REQUEST['save'] || $_REQUEST['apply']){
    CMaxyssOzon:: saveOptions('active_on');
    CMaxyssOzon:: saveOptions('no_upload_product');
    CMaxyssOzon:: saveOptions('no_upload_price');
    CMaxyssOzon:: saveOptions('period');
    CMaxyssOzon:: saveOptions('max_count');
    CMaxyssOzon:: saveOptions('iblock_type');
    CMaxyssOzon:: saveOptions('iblock_id');
    CMaxyssOzon:: saveOptions('description');
    CMaxyssOzon:: saveOptions('name_prodact');
    CMaxyssOzon:: saveOptions('base_picture');
    CMaxyssOzon:: saveOptions('more_picture');
    CMaxyssOzon:: saveOptions('article');
    CMaxyssOzon:: saveOptions('barcode');
//
    CMaxyssOzon:: saveOptions('CUSTOM_FILTER');
    CMaxyssOzon:: saveOptions('deactivate_element_yes');
//    CMaxyssOzon:: saveOptions('filter_prop');
//    CMaxyssOzon:: saveOptions('filter_prop_id');
    CMaxyssOzon:: saveOptions('brand_prop');
    CMaxyssOzon:: saveOptions('sklad_id');
    CMaxyssOzon:: saveOptions('limit_v2');
//
    CMaxyssOzon:: saveOptions('price_type');
    CMaxyssOzon:: saveOptions('price_prop');
    CMaxyssOzon:: saveOptions('price_type_prop');
    CMaxyssOzon:: saveOptions('price_type_no_discount');
    CMaxyssOzon:: saveOptions('price_type_formula');
    CMaxyssOzon:: saveOptions('price_type_formula_action');
//
    CMaxyssOzon:: saveOptions('price_type_old');
    CMaxyssOzon:: saveOptions('price_prop_old');
    CMaxyssOzon:: saveOptions('price_type_old_prop');
    CMaxyssOzon:: saveOptions('price_type_old_no_discount');
    CMaxyssOzon:: saveOptions('price_type_old_formula');
    CMaxyssOzon:: saveOptions('price_type_old_formula_action');
//
    CMaxyssOzon:: saveOptions('price_type_premium');
    CMaxyssOzon:: saveOptions('price_prop_premium');
    CMaxyssOzon:: saveOptions('price_type_premium_prop');
    CMaxyssOzon:: saveOptions('price_type_premium_no_discount');
    CMaxyssOzon:: saveOptions('price_type_premium_formula');
    CMaxyssOzon:: saveOptions('price_type_premium_formula_action');

    Option::set(MAXYSS_MODULE_NAME, "STOCK_REALY_TIME", ($_REQUEST['stock_realy_time'])? "Y" : "N");
    Option::set(MAXYSS_MODULE_NAME, "CALLBACK_BX", ($_REQUEST['callback_bx'])? "Y" : "N");

//
    if($_REQUEST['stock_realy_time']){
            $eventManager = \Bitrix\Main\EventManager::getInstance();
            $eventManager->registerEventHandler("catalog", "Bitrix\Catalog\Model\Product::OnBeforeUpdate", MAXYSS_MODULE_NAME, "CMaxyssOzonStockUpdate", "updateStock");
    }else{
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler("catalog","Bitrix\Catalog\Model\Product::OnBeforeUpdate", MAXYSS_MODULE_NAME,"CMaxyssOzonStockUpdate","updateStock");
    }

    $arActive = CMaxyssOzon::getOptions(false, array('ACTIVE_ON', 'PERIOD'));
    foreach ($arActive as $key=>$active){
        if($active['ACTIVE_ON'] == 'Y'){
            $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonUploadProduct('".$key."'%"));
            if($arRes = $res->GetNext()) {
                if (intval($arRes['ID']) > 0 && $arRes['AGENT_INTERVAL'] != $active['PERIOD'])
                {
                    $arFieldAgent = array(
                        "AGENT_INTERVAL"=>$active['PERIOD'] != '' ? $active['PERIOD'] : '600',
                    );
                    CAgent::Update(intval($arRes['ID']), $arFieldAgent);
                }

            }
            elseif ( intval($arRes['ID']) == 0 && intval($active['PERIOD']) > 0)
            {
                CAgent::AddAgent(
                    "CMaxyssOzonAgent::OzonUploadProduct('".$key."',1);",
                    "maxyss.ozon",
                    "N",
                    $active['PERIOD'] != '' ? $active['PERIOD'] : '600',
                    "",
                    "Y",
                    "",
                    100);
            };
        }else{
            $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonUploadProduct('".$key."'%"));
            if($arRes = $res->GetNext()) {
                CAgent::Delete($arRes['ID']);
            }
        }
    }

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
    $key = 0;

    $arOptionsCheck = CMaxyssOzon::getOptions(false, array("OZON_ID"));

    foreach($arSites as $key => $arSite){
        if($arOptionsCheck[$arSite["ID"]]["OZON_ID"] != '') {
        $arTabs[] = array(
            "DIV" => "edit_ozon".($key+1),
            "TAB" => GetMessage("MAXYSS_OZON_PRODUCT_TITLE_TAB", array("#SITE_NAME#" => $arSite["NAME"], "#SITE_ID#" => $arSite["ID"])),
            "ICON" => "settings",
            // "TITLE" => GetMessage("MAIN_OPTIONS_TITLE"),
            "PAGE_TYPE" => "site_settings",
            "SITE_ID" => $arSite["ID"],
            "SITE_DIR" => $arSite["DIR"],
//            "OPTIONS" => $arBackParametrs,
        );
    }
    }

    $tabControl = new CAdminTabControl("tabControl", $arTabs);
    if(!count($arTabs)){?>
        <div class="adm-info-message-wrap adm-info-message-red">
            <div class="adm-info-message">
                <div class="adm-info-message-title"><?=GetMessage("MAXYSS_OZON_GENERAL_NO_SITE_INSTALED")?></div>
                <div class="adm-info-message-icon"></div>
            </div>
        </div>
    <?}else{
    ?>
    <form action="<?=MAXYSS_MODULE_NAME?>_ozon_maxyss.php?lang=<?=LANGUAGE_ID?>" method="post">
        <?$tabControl->Begin();
        // табуляторы
        ?>
        <?
        foreach($arTabs as $key => $arTab)
        {
            $arOptions = CMaxyssOzon::getOptions($arTab["SITE_ID"]);
            // get a list of info blocks
            $iblock_id = '';
            $iblock_id = $arOptions[$arTab["SITE_ID"]]["IBLOCK_ID"];
            /*if(intval($iblock_id) > 0){?>
                <script type="text/javascript">var iblock_id_g = <?=$iblock_id?>;</script>
            <?}*/
            $iblock_id_select = '<option value=""></option>';

            $iblock_type = '';
            if($iblock_type = $arOptions[$arTab["SITE_ID"]]["IBLOCK_TYPE"])
            {

                $arIBlock = array();
                $iblockFilter =  array('TYPE' => $iblock_type, 'ACTIVE' => 'Y');
                $rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
                while ($arr = $rsIBlock->Fetch())
                {
                    $selected = '';
                    $id = (int)$arr['ID'];
                    if($iblock_id == $id)
                        $selected = 'selected = "selected"';

                    $tpResult = CCatalogSKU::GetInfoByOfferIBlock($id);
                    if (!$tpResult) {
                        $arIBlock[$id] = '[' . $id . '] ' . $arr['NAME'];
                        $iblock_id_select .= '<option ' . $selected . ' value="' . $id . '">' . '[' . $id . '] ' . $arr['NAME'] . '</option>';
                    }
                }
            }

            // get the list of infoblock properties if it is written in b_option
            $iblock_prop_select = '<option value=""></option>';
            if(intval($iblock_id)>0)
            {
                $iblock_prop = '';
                $iblock_prop = $arOptions[$arTab["SITE_ID"]]["MORE_PICTURE"];
                $res = CIBlock::GetProperties(intval($iblock_id), Array(), Array("PROPERTY_TYPE" => "F"));
                while ($res_arr = $res->Fetch())
                {
                    $selected = '';
                    if($iblock_prop == $res_arr['CODE'])
                        $selected = 'selected = "selected"';
                    $iblock_prop_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
                }
            }

            // get the list of infoblock properties for selecting the article if it is written in b_option
            $iblock_art_select = '<option value=""></option>';
            $iblock_price_select = '<option value=""></option>';
            $iblock_price_old_select = '<option value=""></option>';
            $iblock_price_premium_select = '<option value=""></option>';
            if(intval($iblock_id)>0)
            {
                $iblock_art = '';
                $iblock_art = $arOptions[$arTab["SITE_ID"]]["ARTICLE"];

                $iblock_descr = '';
                $iblock_descr = $arOptions[$arTab["SITE_ID"]]["DESCRIPTION"];
                $iblock_descr_select = '';

                $iblock_name_product = '';
                $iblock_name_product = $arOptions[$arTab["SITE_ID"]]["NAME_PRODACT"];
                $selected_name_select = '';

                $iblock_barcode = '';
                $iblock_barcode = $arOptions[$arTab["SITE_ID"]]["BARCODE"];
                $iblock_barcode_select = '<option value=""></option>';


                $iblock_price = '';
                $iblock_price = $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PROP"];

                $iblock_price_old = '';
                $iblock_price_old = $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_PROP"];

                $iblock_price_premium = '';
                $iblock_price_premium = $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_PROP"];

                $iblock_prop_ozon_id = '<option value=""></option>';

                $res = CIBlock::GetProperties(intval($iblock_id), Array('name'=>'asc'), Array("MULTIPLE"	=> "N", "PROPERTY_TYPE" => "S"));
                while ($res_arr = $res->Fetch())
                {
                    if($iblock_art != '' && $iblock_art != $res_arr['CODE']){
                        $iblock_prop_ozon_id .= '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
                    }elseif($iblock_art == ''){
                        $iblock_prop_ozon_id .= '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
                    }

                    $selected = '';
                    if($iblock_art !='' && $iblock_art == $res_arr['CODE'])
                        $selected = 'selected = "selected"';
                    $iblock_art_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

                    $selected_descr = '';
                    if($iblock_descr !='' && $iblock_descr == $res_arr['CODE'])
                        $selected_descr = 'selected = "selected"';
                    $iblock_descr_select .= '<option '.$selected_descr.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

                    $selected_name = '';
                    if($iblock_name_product !='' && $iblock_name_product == $res_arr['CODE'])
                        $selected_name = 'selected = "selected"';
                    $selected_name_select .= '<option '.$selected_name.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

                    $selected_barcode = '';
                    if($iblock_barcode !='' && $iblock_barcode == $res_arr['CODE']) {
                        $selected_barcode = 'selected = "selected"';
                    }
                    $iblock_barcode_select .= '<option ' . $selected_barcode . ' value="' . $res_arr['CODE'] . '">' . '[' . $res_arr['ID'] . '] ' . $res_arr['NAME'] . '</option>';

                    $selected_price = '';
                    if($iblock_price !='' && $iblock_price == $res_arr['CODE'])
                        $selected_price = 'selected = "selected"';
                    $iblock_price_select .= '<option '.$selected_price.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

                    $selected_old_price = '';
                    if($iblock_price_old !='' && $iblock_price_old == $res_arr['CODE'])
                        $selected_old_price = 'selected = "selected"';
                    $iblock_price_old_select .= '<option '.$selected_old_price.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

                    $selected_pr_price = '';
                    if($iblock_price_premium !='' && $iblock_price_premium == $res_arr['CODE'])
                        $selected_pr_price = 'selected = "selected"';
                    $iblock_price_premium_select .= '<option '.$selected_pr_price.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
                }
            }

            // get the list of infoblock properties for selecting the filtering property of elements for unloading if it is written in b_option
            $iblock_filter_select = '<option value=""></option>';
            $iblock_filter_id_prop = '';
            if(intval($iblock_id)>0)
            {
                $iblock_filter = '';
                $iblock_filter = $arOptions[$arTab["SITE_ID"]]["FILTER_PROP"];

                $res = CIBlock::GetProperties(intval($iblock_id), Array('name'=>'asc'), Array("PROPERTY_TYPE" => "L"));
                while ($res_arr = $res->Fetch())
                {
                    $selected = '';
                    if($iblock_filter !='' &&  $iblock_filter == $res_arr['CODE'])
                    {
                        $selected = 'selected = "selected"';
                        $iblock_filter_id_prop = $res_arr['ID'];
                    }
                    $iblock_filter_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
                }

                $filter_property_enums_select = '<select name="filter_prop_id['.$arTab["SITE_ID"].']"><option value=""></option>';
                if($iblock_filter){
                    $filter_property_enums_id = $arOptions[$arTab["SITE_ID"]]["FILTER_PROP_ID"];
                    $filter_property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>intval($iblock_id), "CODE"=>$iblock_filter));
                    while($enum_fields = $filter_property_enums->GetNext())
                    {
                        $selected = '';
                        if($filter_property_enums_id == $enum_fields["ID"])
                            $selected = 'selected = "selected"';
                        $filter_property_enums_select .= '<option '.$selected.' value="'.$enum_fields["ID"].'">'.'['.$enum_fields["ID"].'] '.$enum_fields["VALUE"].'</option>';
                    }
                }
                $filter_property_enums_select .= '</select>';




                // brands
                $brands = '';
                $iblock_brands_select = '';
                $brands = $arOptions[$arTab["SITE_ID"]]["BRAND_PROP"];
                $res_br = CIBlock::GetProperties(intval($iblock_id), Array('name'=>'asc'), array('MULTIPLE'=>'N'));
                while ($res_arr_br = $res_br->Fetch())
                {
                    $selected = '';
                    if($brands !='' && $brands == $res_arr_br['CODE'])
                        $selected = 'selected = "selected"';
                    $iblock_brands_select .= '<option '.$selected.' value="'.$res_arr_br['CODE'].'">'.'['.$res_arr_br['ID'].'] '.$res_arr_br['NAME'].'</option>';
                }


            }


            // get a list of infoblock types
            $arIBlockType = CIBlockParameters::GetIBlockTypes();
            $arIBlock = array();
            $iblockFilter = (
            !empty($arCurrentValues['IBLOCK_TYPE'])
                ? array('TYPE' => $arCurrentValues['IBLOCK_TYPE'], 'ACTIVE' => 'Y')
                : array('ACTIVE' => 'Y')
            );
            $rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
            while ($arr = $rsIBlock->Fetch())
            {
                $id = (int)$arr['ID'];

                $tpResult = CCatalogSKU::GetInfoByOfferIBlock($id);
                if ($tpResult) continue;

                $arIBlock[$id] = '['.$id.'] '.$arr['NAME'];
            }
            unset($id, $arr, $rsIBlock, $iblockFilter);


            $price_type = '';
            $price_type = $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE"];

            $price_type_old = '';
            $price_type_old = $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD"];

            $price_type_premium = '';
            $price_type_premium = $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM"];

            $dbPriceType = CCatalogGroup::GetList(
                array("SORT" => "ASC")
            );
            $price_type_select = '';
            $price_type_old_select = '';
            $price_type_premium_select = '<option value=""></option>';

            while ($arPriceType = $dbPriceType->Fetch())
            {
                $selected = '';
                if($price_type == $arPriceType['ID'])
                    $selected = 'selected = "selected"';
                $price_type_select .= '<option '.$selected.' value="'.$arPriceType['ID'].'">'.'['.$arPriceType['ID'].'] '.$arPriceType['NAME_LANG'].'</option>';

                $selected = '';
                if($price_type_old == $arPriceType['ID'])
                    $selected = 'selected = "selected"';
                $price_type_old_select .= '<option '.$selected.' value="'.$arPriceType['ID'].'">'.'['.$arPriceType['ID'].'] '.$arPriceType['NAME_LANG'].'</option>';

                $selected = '';
                if($price_type_premium == $arPriceType['ID'])
                    $selected = 'selected = "selected"';
                $price_type_premium_select .= '<option '.$selected.' value="'.$arPriceType['ID'].'">'.'['.$arPriceType['ID'].'] '.$arPriceType['NAME_LANG'].'</option>';
            }


            $dbSklad = CCatalogStore::GetList(
                array('TITLE'=>'ASC','ID' => 'ASC'),
                array('ACTIVE' => 'Y'),
                false,
                false,
                array()
            );
            $arSklad = array();
            $sklad_select = '';
            if($arOptions[$arTab["SITE_ID"]]["SKLAD_ID"] == ''){
                $sklad_id = '';
                while ($arSklad = $dbSklad->Fetch())
                {
                    $sklad_select .= '<option value="'.$arSklad['ID'].'">'.'['.$arSklad['ID'].'] '.$arSklad['TITLE'].'</option>';
                }
            }elseif($sklad_id = unserialize($arOptions[$arTab["SITE_ID"]]["SKLAD_ID"])){
                while ($arSklad = $dbSklad->Fetch())
                {
                    $selected = '';
                    if(in_array($arSklad['ID'], $sklad_id))
                        $selected = 'selected = "selected"';
                    $sklad_select .= '<option '.$selected.' value="'.$arSklad['ID'].'">'.'['.$arSklad['ID'].'] '.$arSklad['TITLE'].'</option>';
                }
            }else{
                $sklad_id = $arOptions[$arTab["SITE_ID"]]["SKLAD_ID"];
                while ($arSklad = $dbSklad->Fetch())
                {
                    $selected = '';
                    if($sklad_id == $arSklad['ID'])
                        $selected = 'selected = "selected"';
                    $sklad_select .= '<option '.$selected.' value="'.$arSklad['ID'].'">'.'['.$arSklad['ID'].'] '.$arSklad['TITLE'].'</option>';
                }
            }




        $tabControl->BeginNextTab();?>
<!--    <div class="adm-detail-content-item-block ozon_conainer">-->
<!--        <table class="adm-detail-content-table edit-table" id="tab1_edit_table">-->
<!--            <tbody>-->
            <tr>
                <td colspan="2">
                    <? echo "<span style='color: blue'>OZON_ID ".$arOptions[$arTab["SITE_ID"]]["OZON_ID"]."</span>"?>
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_OZON_MODULE_ACTIVITY')?></td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_AGENT_ACTIVE')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="active_on[<?=$arTab["SITE_ID"]?>]" value="N">
                    <input type="checkbox" name="active_on[<?=$arTab["SITE_ID"]?>]" id="ozon_save_<?=$arTab["SITE_ID"]?>" class="adm-designed-checkbox" <?echo  ($arOptions[$arTab["SITE_ID"]]["ACTIVE_ON"] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="ozon_save_<?=$arTab["SITE_ID"]?>" title=""></label>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_PERIOD_AGENT_TIME')?></td>
                <td class="adm-detail-content-cell-r">
                    <input name="period[<?=$arTab["SITE_ID"]?>]" value="<?echo ($arOptions[$arTab["SITE_ID"]]["PERIOD"] != '')? $arOptions[$arTab["SITE_ID"]]["PERIOD"] : 600;?>">
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_MAX_COUNT')?></td>
                <td class="adm-detail-content-cell-r">
                    <input name="max_count[<?=$arTab["SITE_ID"]?>]" value="<?echo (isset($arOptions[$arTab["SITE_ID"]]["MAX_COUNT"]) && $arOptions[$arTab["SITE_ID"]]["MAX_COUNT"] !== '')? $arOptions[$arTab["SITE_ID"]]["MAX_COUNT"] : 100;?>"><span data-hint="<?=GetMessage('MAXYSS_OZON_MAX_COUNT_TIP')?>"></span>
                </td>
            </tr>

            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_AGENT_UPLOAD_PRODUCT')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="no_upload_product[<?=$arTab["SITE_ID"]?>]" value="N">
                    <input type="checkbox" name="no_upload_product[<?=$arTab["SITE_ID"]?>]" id="no_upload_product[<?=$arTab["SITE_ID"]?>]" class="adm-designed-checkbox" <?echo  ($arOptions[$arTab["SITE_ID"]]["NO_UPLOAD_PRODUCT"] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="no_upload_product[<?=$arTab["SITE_ID"]?>]" title=""></label>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_AGENT_UPLOAD_PRICE')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="no_upload_price[<?=$arTab["SITE_ID"]?>]" value="N">
                    <input type="checkbox" name="no_upload_price[<?=$arTab["SITE_ID"]?>]" id="no_upload_price[<?=$arTab["SITE_ID"]?>]" class="adm-designed-checkbox" <?echo  ($arOptions[$arTab["SITE_ID"]]["NO_UPLOAD_PRICE"] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="no_upload_price[<?=$arTab["SITE_ID"]?>]" title=""></label>
                </td>
            </tr>

            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_DOWNLOAD_ID_OZON')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="downLoadProp[<?=$arTab["SITE_ID"]?>]" id="downLoadProp_<?=$arTab["SITE_ID"]?>">
                        <?echo $iblock_prop_ozon_id?>
                    </select>
                    <input type="button" onclick="downLoadId('<?=$arTab["SITE_ID"]?>')" value="<?=GetMessage('MAXYSS_OZON_DOWNLOAD_ID_OZON_RUN')?>"><span data-hint="<?=GetMessage('MAXYSS_OZON_DOWNLOAD_ID_OZON_TIP')?>"></span>
                </td>
            </tr>


            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_OZON_INTEGRATION_SETTINGS')?></td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_IBLOCK_TYPE')?></td>
                <td class="adm-detail-content-cell-r">
                    <select onchange="iblock_update($(this).val(), '<?=$arTab["DIV"]?>');" name="iblock_type[<?=$arTab["SITE_ID"]?>]">
                        <option value=""></option>
                        <?foreach ($arIBlockType as $key => $type){?>
                        <option value="<?=$key?>" <?echo (($arOptions[$arTab["SITE_ID"]]["IBLOCK_TYPE"] == $key)? 'selected = "selected"' : '')?>><?=$type?></option>
                        <?}?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_IBLOCK_ID')?></td>
                <td class="adm-detail-content-cell-r">
                    <select onchange="property_update($(this).val(), '<?=$arTab["DIV"]?>', '<?=$arTab["SITE_ID"]?>');" name="iblock_id[<?=$arTab["SITE_ID"]?>]">
                        <?echo $iblock_id_select;?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_ANONS')?></td>
                <td class="adm-detail-content-cell-r">

                    <select name="description[<?=$arTab["SITE_ID"]?>]">
                        <option value="DETAIL_TEXT" <?echo ( $arOptions[$arTab["SITE_ID"]]["DESCRIPTION"] == "DETAIL_TEXT" )? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_ANONS_DETAIL')?></option>
                        <option value="PREVIEW_TEXT" <?echo ( $arOptions[$arTab["SITE_ID"]]["DESCRIPTION"] == "PREVIEW_TEXT" )? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_ANONS_ANONS')?></option>
                        <?if(!empty($iblock_descr_select)){?>
                        <optgroup label="<?=GetMessage('MAXYSS_OZON_PROP_TITLE')?>">
                        <?echo $iblock_descr_select?>
                        </optgroup>
                        <?}?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_NAME_TITLE')?></td>
                <td class="adm-detail-content-cell-r">

                    <select name="name_prodact[<?=$arTab["SITE_ID"]?>]">
                        <option value="NAME" <?echo ( $arOptions[$arTab["SITE_ID"]]["NAME_PRODACT"] == "NAME" )? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_NAME_PRODUCT')?></option>
                        <?if(!empty($selected_name_select)){?>
                            <optgroup label="<?=GetMessage('MAXYSS_OZON_PROP_TITLE')?>">
                                <?echo $selected_name_select?>
                            </optgroup>
                        <?}?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_PICTURE')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="base_picture[<?=$arTab["SITE_ID"]?>]">
                        <option value="DETAIL_PICTURE" <?echo ( $arOptions[$arTab["SITE_ID"]]["BASE_PICTURE"] == "DETAIL_PICTURE" )? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PICTURE_DETAIL')?></option>
                        <option value="PREVIEW_PICTURE" <?echo ( $arOptions[$arTab["SITE_ID"]]["BASE_PICTURE"] == "PREVIEW_PICTURE" )? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PICTURE_ANONS')?></option>
                    </select>
                </td>
            </tr>
            <tr>
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_PICTURE_MORE_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="more_picture[<?=$arTab["SITE_ID"]?>]">
                        <?echo $iblock_prop_select?>
                    </select>
                </td>
            </tr>
            <tr>
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_ARTICLE_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="article[<?=$arTab["SITE_ID"]?>]">
                        <?echo $iblock_art_select?>
                    </select>
                </td>
            </tr>
            <tr>
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_BARCODE_PROP')?></td>
            <td class="adm-detail-content-cell-r">
                <select name="barcode[<?=$arTab["SITE_ID"]?>]">
                    <?echo $iblock_barcode_select?>
                </select><span data-hint="<?=GetMessage('MAXYSS_OZON_BARCODE_PROP_TIP')?>"></span>
            </td>
            </tr>
           <tr>
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_BRAND_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="brand_prop[<?=$arTab["SITE_ID"]?>]">
                        <option value="" selected></option>
                        <?echo $iblock_brands_select?>
                    </select>
                </td>
            </tr>
            <tr id="custom_filter_td_<?=$arTab["SITE_ID"]?>">
                    <?
                    $filterDataValues = array();
                    if(intval($iblock_id)>0)
                    {
                        $arCurrentValues['IBLOCK_ID'] = $iblock_id;
                        $filterDataValues['iblockId'] = (int)$arCurrentValues['IBLOCK_ID'];
                        $offers = CCatalogSku::GetInfoByProductIBlock($arCurrentValues['IBLOCK_ID']);
                        if (!empty($offers))
                        {
                            $filterDataValues['offersIblockId'] = $offers['IBLOCK_ID'];
                            $propertyIterator = Iblock\PropertyTable::getList(array(
                                'select' => array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'LINK_IBLOCK_ID', 'USER_TYPE', 'SORT'),
                                'filter' => array('=IBLOCK_ID' => $offers['IBLOCK_ID'], '=ACTIVE' => 'Y', '!=ID' => $offers['SKU_PROPERTY_ID']),
                                'order' => array('SORT' => 'ASC', 'NAME' => 'ASC')
                            ));
                            while ($property = $propertyIterator->fetch())
                            {
                                $propertyCode = (string)$property['CODE'];

                                if ($propertyCode === '')
                                {
                                    $propertyCode = $property['ID'];
                                }

                                $propertyName = '['.$propertyCode.'] '.$property['NAME'];
                                $arProperty_Offers[$propertyCode] = $propertyName;

                                if ($property['PROPERTY_TYPE'] != Iblock\PropertyTable::TYPE_FILE)
                                {
                                    $arProperty_OffersWithoutFile[$propertyCode] = $propertyName;
                                }
                            }
                            unset($propertyCode, $propertyName, $property, $propertyIterator);
                        }
                    }
                    if (!empty($filterDataValues))
                    {
                    $arComponentParameters['CUSTOM_FILTER'] = array(
                    'PARENT' => 'DATA_SOURCE',
                    'NAME' => GetMessage('MAXYSS_OZON_FILTER_CUSTOM'),
                    'TYPE' => 'CUSTOM',
                    'JS_FILE' => '/bitrix/tools/maxyss.ozon/filter_conditions/script.js?16217988881',//CatalogSectionComponent::getSettingsScript($componentPath, 'filter_conditions'),
                    'JS_EVENT' => 'initFilterConditionsControl',
                    'JS_MESSAGES' => Json::encode(array(
                    'invalid' => GetMessage('MAXYSS_OZON_FILTER_CUSTOM_INVALID')
                    )),
                    'JS_DATA' => Json::encode($filterDataValues),
                    'DEFAULT' => ''
                    );

                    $params_['propertyParams'] = $arComponentParameters['CUSTOM_FILTER'];
                    $params_['data'] = $arComponentParameters['CUSTOM_FILTER']['JS_DATA'];
                    $params_['propertyID'] ='CUSTOM_FILTER_'.$arTab["SITE_ID"];
                    $params_['oInput'] ='';
                    $params_['oCont'] ='';

//                    $filter_custom = new FilterCustomOzon();
//                    if($arOptions[$arTab["SITE_ID"]]["CUSTOM_FILTER"])
//                        $filter = $filter_custom->parseCondition(Json::decode( htmlspecialchars_decode($arOptions[$arTab["SITE_ID"]]["CUSTOM_FILTER"])), array());

                    if(!$arOptions[$arTab["SITE_ID"]]["CUSTOM_FILTER"] && $iblock_filter_id_prop !='' && $filter_property_enums_id !=''){
                        $filter_arr = array(
                        "CLASS_ID" => "CondGroup",
                        "DATA" => array("All" => "AND", "True" => "True" ),
                        "CHILDREN" => array(
                             Array
                            (
                                "CLASS_ID" => "CondIBProp:".$iblock_id.":".$iblock_filter_id_prop,
                                "DATA" => Array
                                    (
                                        "logic" => "Equal",
                                        "value" => $filter_property_enums_id
                                    )

                            )
                        )
                        );

                        $filter_string = Json::encode($filter_arr);
                    }
                    else $filter_string = $arOptions[$arTab["SITE_ID"]]["CUSTOM_FILTER"];
                    ?>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_FILTER_CUSTOM')?></td>
                <td>
                    <div id = 'CUSTOM_FILTER_DIV_<?=$arTab["SITE_ID"]?>'>
                        <input name="CUSTOM_FILTER[<?=$arTab["SITE_ID"]?>]" id = 'CUSTOM_FILTER_<?=$arTab["SITE_ID"]?>' value='<?echo $filter_string?>' type="hidden">
                    </div>
                    <script>
                        let propertyParamsJs_<?=$arTab["SITE_ID"]?> = <?=CUtil::PhpToJSObject($params_)?>;
                        propertyParamsJs_<?=$arTab["SITE_ID"]?>['oCont'] = document.querySelector('#CUSTOM_FILTER_DIV_<?=$arTab["SITE_ID"]?>');
                        propertyParamsJs_<?=$arTab["SITE_ID"]?>['oInput'] = document.querySelector('#CUSTOM_FILTER_<?=$arTab["SITE_ID"]?>');
                        initFilterConditionsControl(propertyParamsJs_<?=$arTab["SITE_ID"]?>);
                    </script>
                </td>

                    <?}?>

            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_FILTER_ACTIVE')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="deactivate_element_yes[<?=$arTab["SITE_ID"]?>]" value="N">
                    <input type="checkbox" name="deactivate_element_yes[<?=$arTab["SITE_ID"]?>]" id="deactivate_element_yes_<?=$arTab["SITE_ID"]?>" <?echo ($arOptions[$arTab["SITE_ID"]]["DEACTIVATE_ELEMENT_YES"]=="Y")? 'checked' : '' ;?> class="adm-designed-checkbox" value="Y">
                    <label class="adm-designed-checkbox-label" for="deactivate_element_yes_<?=$arTab["SITE_ID"]?>" title=""></label>
                </td>
            </tr>
<!--            <tr>-->
<!--            <td class="adm-detail-content-cell-l">--><?//=GetMessage('MAXYSS_OZON_FILTER_PROP')?><!--</td>-->
<!--                <td class="adm-detail-content-cell-r">-->
<!--                    <select name="filter_prop[--><?//=$arTab["SITE_ID"]?><!--]" onchange="filter_property_update($(this));">-->
<!--                        --><?//echo $iblock_filter_select?>
<!--                    </select>-->
<!--                        --><?//echo $filter_property_enums_select?>
<!--                </td>-->
<!--            </tr>-->
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_PRICE_TIPE')?></td>
                <td class="adm-detail-content-cell-r">
                        <table>
                            <tr>
                                <td>
                                    <?echo GetMessage('MAXYSS_OZON_PRICE_FROM_PROP')?>
                                </td>
                                <td>
                                    <input type="hidden" name="price_prop[<?=$arTab["SITE_ID"]?>]" value="">
                                    <input type="checkbox" name="price_prop[<?=$arTab["SITE_ID"]?>]" id="price_prop_<?=$arTab["SITE_ID"]?>" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP"]=="Y")? 'checked' : '' ;?> class="adm-designed-checkbox price_to_prop" value="Y">
                                    <label class="adm-designed-checkbox-label" for="price_prop_<?=$arTab["SITE_ID"]?>" title=""></label>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP"]=="Y")? '' : 'style="display: none"' ;?>>
                                    <select name="price_type_prop[<?=$arTab["SITE_ID"]?>]">
                                        <?echo $iblock_price_select?>
                                    </select>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP"]=="Y")? 'style="display: none"' : '' ;?>>
                                    <select name="price_type[<?=$arTab["SITE_ID"]?>]">
                                        <?echo $price_type_select?>
                                    </select>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP"]=="Y")? 'style="display: none"' : '' ;?>>
                                    <input type="hidden" name="price_type_no_discount[<?=$arTab["SITE_ID"]?>]" value="">
                                    <input type="checkbox" name="price_type_no_discount[<?=$arTab["SITE_ID"]?>]" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_NO_DISCOUNT"]=="Y")? 'checked' : '';?> id="price_type_no_discount_<?=$arTab["SITE_ID"]?>" class="adm-designed-checkbox"  value="Y">
                                    <label class="adm-designed-checkbox-label" for="price_type_no_discount_<?=$arTab["SITE_ID"]?>" title=""></label>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP"]=="Y")? 'style="display: none"' : '' ;?>><?echo GetMessage('MAXYSS_OZON_PRICE_WITHOUT_DISCOUNT')?></td>
                                <td>
                                    <select name="price_type_formula_action[<?=$arTab["SITE_ID"]?>]">
                                        <option value="NOT" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_FORMULA_ACTION"] == 'NOT')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_NOT')?></option>
                                        <option value="MULTIPLY" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_FORMULA_ACTION"] == 'MULTIPLY')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_MULTIPLY')?></option>
                                        <option value="DIVIDE" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_FORMULA_ACTION"] == 'DIVIDE')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_DIVIDE')?></option>
                                        <option value="ADD" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_FORMULA_ACTION"] == 'ADD')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_ADD')?></option>
                                        <option value="SUBTRACT" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_FORMULA_ACTION"] == 'SUBTRACT')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_SUBTRACT')?></option>
                                    </select>
                                    <input type="text" name="price_type_formula[<?=$arTab["SITE_ID"]?>]" value="<?echo $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_FORMULA"];?>">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_PRICE_OLD')?></td>
                    <td class="adm-detail-content-cell-r">
                        <table>
                            <tr>
                                <td>
                                    <?echo GetMessage('MAXYSS_OZON_PRICE_FROM_PROP')?>
                                </td>
                                <td>
                                    <input type="hidden" name="price_prop_old[<?=$arTab["SITE_ID"]?>]" value="">
                                    <input type="checkbox" name="price_prop_old[<?=$arTab["SITE_ID"]?>]" id="price_prop_old_<?=$arTab["SITE_ID"]?>" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_OLD"]=="Y")? 'checked' : '' ;?> class="adm-designed-checkbox price_to_prop"  value="Y">
                                    <label class="adm-designed-checkbox-label" for="price_prop_old_<?=$arTab["SITE_ID"]?>" title=""></label>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_OLD"]=="Y")? '' : 'style="display: none"' ;?>>
                                    <select name="price_type_old_prop[<?=$arTab["SITE_ID"]?>]">
                                        <?echo $iblock_price_old_select?>
                                    </select>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_OLD"]=="Y")? 'style="display: none"' : '' ;?>>
                                    <select name="price_type_old[<?=$arTab["SITE_ID"]?>]">
                                        <?echo $price_type_old_select?>
                                    </select>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_OLD"]=="Y")? 'style="display: none"' : '' ;?>>
                                    <input type="hidden" name="price_type_old_no_discount[<?=$arTab["SITE_ID"]?>]" value="">
                                    <input type="checkbox" name="price_type_old_no_discount[<?=$arTab["SITE_ID"]?>]" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_NO_DISCOUNT"]=="Y")? 'checked' : '' ;?> id="price_type_old_no_discount_old_<?=$arTab["SITE_ID"]?>" class="adm-designed-checkbox" value="Y">
                                    <label class="adm-designed-checkbox-label" for="price_type_old_no_discount_old_<?=$arTab["SITE_ID"]?>" title=""></label>
                                </td>
                                <td
                                    <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_OLD"]=="Y")? 'style="display: none"' : '' ;?>
                                ><?echo GetMessage('MAXYSS_OZON_PRICE_WITHOUT_DISCOUNT')?></td>
                                <td>
                                    <select name="price_type_old_formula_action[<?=$arTab["SITE_ID"]?>]">
                                        <option value="NOT" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_FORMULA_ACTION"] == 'NOT')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_NOT')?></option>
                                        <option value="MULTIPLY" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_FORMULA_ACTION"] == 'MULTIPLY')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_MULTIPLY')?></option>
                                        <option value="DIVIDE" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_FORMULA_ACTION"] == 'DIVIDE')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_DIVIDE')?></option>
                                        <option value="ADD" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_FORMULA_ACTION"] == 'ADD')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_ADD')?></option>
                                        <option value="SUBTRACT" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_FORMULA_ACTION"] == 'SUBTRACT')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_SUBTRACT')?></option>
                                    </select>
                                    <input type="text" name="price_type_old_formula[<?=$arTab["SITE_ID"]?>]" value="<?echo $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_OLD_FORMULA"];?>">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_PRICE_PREMIUM')?></td>
                    <td class="adm-detail-content-cell-r">
                        <table>
                            <tr>
                                <td>
                                    <?echo GetMessage('MAXYSS_OZON_PRICE_FROM_PROP')?>
                                </td>
                                <td>
                                    <input type="hidden" name="price_prop_premium[<?=$arTab["SITE_ID"]?>]" value="">
                                    <input type="checkbox" name="price_prop_premium[<?=$arTab["SITE_ID"]?>]" id="price_prop_premium_<?=$arTab["SITE_ID"]?>" class="adm-designed-checkbox price_to_prop" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_PREMIUM"]=="Y")? 'checked' : '' ;?> value="Y">
                                    <label class="adm-designed-checkbox-label" for="price_prop_premium_<?=$arTab["SITE_ID"]?>" title=""></label>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_PREMIUM"]=="Y")? '' : 'style="display: none"' ;?>>
                                    <select name="price_type_premium_prop[<?=$arTab["SITE_ID"]?>]">
                                        <?echo $iblock_price_premium_select?>
                                    </select>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_PREMIUM"]=="Y")? 'style="display: none"' : '' ;?>>
                                    <select name="price_type_premium[<?=$arTab["SITE_ID"]?>]">
                                        <?echo $price_type_premium_select?>
                                    </select>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_PREMIUM"]=="Y")? 'style="display: none"' : '' ;?>>
                                    <input type="hidden" name="price_type_premium_no_discount[<?=$arTab["SITE_ID"]?>]" value="">
                                    <input type="checkbox" name="price_type_premium_no_discount[<?=$arTab["SITE_ID"]?>]" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_NO_DISCOUNT"]=="Y")? 'checked' : '' ;?> id="price_type_premium_no_discount_old_<?=$arTab["SITE_ID"]?>" class="adm-designed-checkbox"  value="Y">
                                    <label class="adm-designed-checkbox-label" for="price_type_premium_no_discount_old_<?=$arTab["SITE_ID"]?>" title=""></label>
                                </td>
                                <td <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_PROP_PREMIUM"]=="Y")? 'style="display: none"' : '' ;?>><?echo GetMessage('MAXYSS_OZON_PRICE_WITHOUT_DISCOUNT')?></td>
                                <td>
                                    <select name="price_type_premium_formula_action[<?=$arTab["SITE_ID"]?>]">
                                        <option value="NOT" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_FORMULA_ACTION"] == 'NOT')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_NOT')?></option>
                                        <option value="MULTIPLY" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_FORMULA_ACTION"] == 'MULTIPLY')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_MULTIPLY')?></option>
                                        <option value="DIVIDE" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_FORMULA_ACTION"] == 'DIVIDE')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_DIVIDE')?></option>
                                        <option value="ADD" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_FORMULA_ACTION"] == 'ADD')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_ADD')?></option>
                                        <option value="SUBTRACT" <?echo ($arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_FORMULA_ACTION"] == 'SUBTRACT')? 'selected' : '';?>><?=GetMessage('MAXYSS_OZON_PRICE_TYPE_FORMULA_ACTION_SUBTRACT')?></option>
                                    </select>
                                    <input type="text" name="price_type_premium_formula[<?=$arTab["SITE_ID"]?>]" value="<?echo $arOptions[$arTab["SITE_ID"]]["PRICE_TYPE_PREMIUM_FORMULA"];?>">
                                </td>
                            </tr>
                        </table>
                    </td>
            </tr>
            <?if(!VERSION_OZON_3){?>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_SKLAD')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="sklad_id[<?=$arTab["SITE_ID"]?>]" value="">
                    <select multiple name="sklad_id[<?=$arTab["SITE_ID"]?>][]">
                        <?echo $sklad_select?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_V3_LIMIT_WAREHOUSE')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="number" name="limit_v2[<?=$arTab["SITE_ID"]?>]"  value="<?echo $arOptions[$arTab["SITE_ID"]]["LIMIT_V2"];?>"><span data-hint="<?=GetMessage('MAXYSS_OZON_V3_LIMIT_WAREHOUSE_TIP')?>"></span>
                </td>
            </tr>
            <?}?>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_CALLBACK_BX')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="checkbox" name="callback_bx" id="callback_bx_<?=$arTab["SITE_ID"]?>" class="adm-designed-checkbox" <?echo  (Option::get(MAXYSS_MODULE_NAME, "CALLBACK_BX", "") == 'Y')? 'checked = "checked"' : ''?>>
                    <label class="adm-designed-checkbox-label" for="callback_bx_<?=$arTab["SITE_ID"]?>" title=""></label><span data-hint="<?=GetMessage('MAXYSS_OZON_CALLBACK_BX_TIP')?>"></span>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_STOCK_REALY_TIME')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="checkbox" name="stock_realy_time" id="stock_realy_time_<?=$arTab["SITE_ID"]?>" class="adm-designed-checkbox" <?echo  (Option::get(MAXYSS_MODULE_NAME, "STOCK_REALY_TIME", "") == 'Y')? 'checked = "checked"' : ''?>>
                    <label class="adm-designed-checkbox-label" for="stock_realy_time_<?=$arTab["SITE_ID"]?>" title=""></label><span data-hint="<?=GetMessage('MAXYSS_OZON_STOCK_REALY_TIME_TIP')?>"></span>
                </td>
            </tr>
<!--            </tbody>-->
<!--        </table>-->
            <tr>
            <td colspan="2">
                <div style="padding-top: 20px" class="log_file">
                    <?
                    if (!function_exists('name_day')) {
                        function name_day($file)
                        {
                            switch ($file){
                                case '1.txt':
                                    $day = GetMessage('MAXYSS_OZON_MONDAY');
                                    break;
                                case '2.txt':
                                    $day = GetMessage('MAXYSS_OZON_TUESDAY');
                                    break;
                                case '3.txt':
                                    $day = GetMessage('MAXYSS_OZON_WEDNESDAY');
                                    break;
                                case '4.txt':
                                    $day = GetMessage('MAXYSS_OZON_THURSDAY');
                                    break;
                                case '5.txt':
                                    $day = GetMessage('MAXYSS_OZON_FRIDAY');
                                    break;
                                case '6.txt':
                                    $day = GetMessage('MAXYSS_OZON_SATURDAY');
                                    break;
                                case '7.txt':
                                    $day = GetMessage('MAXYSS_OZON_SUNDAY');
                                    break;
                            }
                            return $day;
                        }
                    }
                    foreach (glob($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/".$arTab["SITE_ID"]."_log_user_*") as $filename) {
                        $num_day = str_replace($_SERVER['DOCUMENT_ROOT']. "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/".$arTab["SITE_ID"]."_log_user_", '', $filename)
                        ?>    <a style="margin-right: 20px" class="log_view" href="<?=$filename?>"><?=name_day($num_day)?></a>  <?
                    }
                    ?>
                    <div style="margin-top: 30px;" id="log_file" class="log_file_result"></div>
                </div>
            </td>
            </tr>
<!--    </div>-->
        <?}?>
        <?$tabControl->Buttons(array(
            "back_url"=>MAXYSS_MODULE_NAME."_ozon_maxyss_general.php?lang=".LANGUAGE_ID,

        ));?>

        <?$tabControl->End();?>
<!--    <div class="adm-detail-content-btns-wrap" id="editTab_buttons_div" style="left: 0px;">-->
<!--        <div class="adm-detail-content-btns">-->
<!--            <input type="submit" name="save" value="--><?//=GetMessage('MAXYSS_OZON_MODULE_SAVE')?><!--">-->
<!--        </div>-->
<!--    </div>-->
</form>
<?}?>

<script type="text/javascript">

    function property_update(iblock_id, div, sid){
        var tab = $('#' + div);
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/settings.php?iblock_id=' + iblock_id,
            data:{action: 'get_prop_foto'},
            success: function(data) {tab.find($('[name*="more_picture"]')).empty().html(data);},
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_OZON_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/settings.php?iblock_id=' + iblock_id,
            data:{action: 'get_prop_article'},
            success: function(data) {
                tab.find($('[name*="article"]')).empty().html('<option value=""></option>'+data);
                tab.find($('[name*="description"]')).empty().html('<option value="DETAIL_TEXT" ><?=GetMessage('MAXYSS_OZON_ANONS_DETAIL')?></option><option value="PREVIEW_TEXT" ><?=GetMessage('MAXYSS_OZON_ANONS_ANONS')?></option><optgroup label="Свойства">' + data + '</optgroup>');
                tab.find($('[name*="barcode"]')).empty().html('<option value=""></option>'+data);
                tab.find($('[name*="price_type_prop"]')).empty().html('<option value=""></option>'+data);
                tab.find($('[name*="price_type_old_prop"]')).empty().html('<option value=""></option>'+data);
                tab.find($('[name*="price_type_premium_prop"]')).empty().html('<option value=""></option>'+data);
                },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_OZON_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/settings.php?iblock_id=' + iblock_id,
            data:{
                action: 'get_filter_property',
                sid: sid,
            },
            success: function(data) {
                // tab.find($('[name*="filter_prop"]')).empty().html(data);
                // tab.find($('[name*="filter_prop_id"]')).empty();
                tab.find($('#custom_filter_td_'+ sid)).empty().html(data);

                },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_OZON_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/settings.php?iblock_id=' + iblock_id,
            data:{action: 'get_prop_brand'},
            success: function(data) {tab.find($('[name*="brand_prop"]')).empty().html(data);},
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_OZON_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        iblock_id_g = iblock_id;
    }

    function iblock_update(iblock_type, div) {
        var tab = $('#' + div);
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/settings.php?iblock_type=' + iblock_type,
            data:{action: 'get_iblock_id'},
            success: function(data) {
                tab.find($('[name*="iblock_id"]')).empty().html(data);
                tab.find($('[name*="more_picture"]')).empty();
                tab.find($('[name*="article"]')).empty();
                tab.find($('[name*="filter_prop"]')).empty();
                tab.find($('[name*="filter_prop_id"]')).remove();
                tab.find($('[name*="brand_prop"]')).empty();
                tab.find($('[name*="barcode"]')).empty();
                // $('#ans').html(data);
                },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_OZON_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
          });
    }

    function filter_property_update(th){
        var parent_td = th.parent();
        var iblock_id_g = th.parent().parent().parent().find($('[name*="iblock_id"]')).val();
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.ozon/settings.php?iblock_id=' + iblock_id_g,
            data:{
                action: 'get_filter_property_enum',
                filter_property: th.val()
            },
            success: function(data) {
                parent_td.find($('[name*="filter_prop_id"]')).empty().html(data);
                },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_OZON_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
    };

    $(document).on("change", "input[name='stock_realy_time']", function () {
        var check = $(this).prop('checked');
        $("input[name='stock_realy_time']").each(function (index, value) {
            $(this).prop('checked', check);
        })
    });
    $(document).on("change", "input[name='callback_bx']", function () {
        var check = $(this).prop('checked');
        $("input[name='callback_bx']").each(function (index, value) {
            $(this).prop('checked', check);
        })
    });

    $(document).on('click', '.log_view', function (e) {
        e.preventDefault();
        var text_file = $(this).attr('href');
        var div_log = $(this).parent().find('.log_file_result');
        console.log(div_log);
        $.ajax({
                 type: 'GET',
                 url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
                 data: {
                    action: 'get_log',
                    file: text_file
                    },
                 success: function(data) {
                    div_log.html(data);
                 },
                 error:  function(xhr, str){
                    alert('Error: ' + xhr.responseCode);
                    }
              });
        console.log(text_file);
    })

        $(document).on('change', '.price_to_prop', function () {
            console.log($(this).prop('checked'));
            var price_block = $(this);
            if(price_block.prop('checked')){
                price_block.parent().parent().find('td:eq(2)').css({'display' : 'table-cell'});
                price_block.parent().parent().find('td:eq(3)').css({'display' : 'none'});
                price_block.parent().parent().find('td:eq(4)').css({'display' : 'none'});
                price_block.parent().parent().find('td:eq(5)').css({'display' : 'none'});
            }else{
                price_block.parent().parent().find('td:eq(2)').css({'display' : 'none'});
                price_block.parent().parent().find('td:eq(3)').css({'display' : 'table-cell'});
                price_block.parent().parent().find('td:eq(4)').css({'display' : 'table-cell'});
                price_block.parent().parent().find('td:eq(5)').css({'display' : 'table-cell'});
            }
        })
</script>
    <?}else
        die();
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>
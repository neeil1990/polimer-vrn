<? require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
use Bitrix\Iblock,
    Bitrix\Main\Web\Json;


CModule::IncludeModule('iblock');

if($_REQUEST['action'] == 'get_iblock_id')
{
    $arIBlock = array();
    $iblockFilter = (
    !empty($_REQUEST['iblock_type'])
        ? array('TYPE' => $_REQUEST['iblock_type'], 'ACTIVE' => 'Y')
        : array('ACTIVE' => 'Y')
    );
    $rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
    echo '<option value=""></option>';
    while ($arr = $rsIBlock->Fetch())
    {
        $id = (int)$arr['ID'];

        $tpResult = CCatalogSKU::GetInfoByOfferIBlock($id);
        if (!$tpResult) {
        $arIBlock[$id] = '['.$id.'] '.$arr['NAME'];
        echo '<option value="'.$id.'">'.'['.$id.'] '.$arr['NAME'].'</option>';
    }
}
}
if($_REQUEST['action'] == 'get_prop_foto')
{
    if(!empty($_REQUEST['iblock_id'])){
        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array(), Array("PROPERTY_TYPE" => "F"));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['ID'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
    }

}
if($_REQUEST['action'] == 'get_prop_article')
{
    if(!empty($_REQUEST['iblock_id'])){
//        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array('name'=>'asc'), Array("PROPERTY_TYPE" => "S"));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
    }

}
if($_REQUEST['action'] == 'get_prop_brand')
{
    if(!empty($_REQUEST['iblock_id'])){
        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array('name'=>'asc'), array('MULTIPLE'=>'N'));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
    }

}
if($_REQUEST['action'] == 'get_filter_property')
{
    if(!empty($_REQUEST['iblock_id']) && !empty($_REQUEST['sid'])){
        /*
        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array('name'=>'asc'), Array("PROPERTY_TYPE" => "L"));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
        */
        $iblock_id = intval($_REQUEST['iblock_id']);
        $sid = htmlspecialcharsbx($_REQUEST['sid']);
        $filterDataValues = array();
        if($iblock_id > 0)
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
            $params_['propertyID'] ='CUSTOM_FILTER_'.$sid;
            $params_['oInput'] ='';
            $params_['oCont'] ='';

            ?>
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_OZON_FILTER_CUSTOM')?></td>
            <td>
            <div id = 'CUSTOM_FILTER_DIV_<?=$sid?>'>
                <input  name="CUSTOM_FILTER[<?=$sid?>]" id = 'CUSTOM_FILTER_<?=$sid?>' value='<?//echo $filter_string?>' type="hidden">
            </div>
            </td>
            <script>
                let propertyParamsJs_<?=$sid?> = <?=CUtil::PhpToJSObject($params_)?>;
                propertyParamsJs_<?=$sid?>['oCont'] = document.querySelector('#CUSTOM_FILTER_DIV_<?=$sid?>');
                propertyParamsJs_<?=$sid?>['oInput'] = document.querySelector('#CUSTOM_FILTER_<?=$sid?>');
                initFilterConditionsControl(propertyParamsJs_<?=$sid?>);
            </script>
            <?}
    }

}
if($_REQUEST['action'] == 'get_filter_property_enum')
{
    if(!empty($_REQUEST['iblock_id']) && !empty($_REQUEST['filter_property'])){

        $filter_property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>intval($_REQUEST['iblock_id']), "CODE"=>$_REQUEST['filter_property']));
        $filter_property_enums_select = '';
        $count_enum=0;

        while($enum_fields = $filter_property_enums->GetNext())
        {
            $count_enum++;
            $filter_property_enums_select .= '<option value="'.$enum_fields["ID"].'">'.'['.$enum_fields["ID"].'] '.$enum_fields["VALUE"].'</option>';
        }

        if($count_enum !=0)
            echo $filter_property_enums_select;

    }
}
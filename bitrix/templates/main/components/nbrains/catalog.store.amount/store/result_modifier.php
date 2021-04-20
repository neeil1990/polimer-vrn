<?php
foreach($arResult["STORES"] as $pid => &$arProperty){
    if($arProperty['USER_FIELDS']['UF_STORE']){

        $storeIds = $arProperty['USER_FIELDS']['UF_STORE']['VALUE'];
        $rsProps = CCatalogStore::GetList(
            array('SORT' => 'ASC', 'ID' => 'ASC'),
            ["ACTIVE" => "Y", "PRODUCT_ID" => $arParams["ELEMENT_ID"], "+SITE_ID" => SITE_ID, "ISSUING_CENTER" => 'Y', "ID" => $storeIds],
            false,
            false,
            ['ID', 'TITLE', 'PRODUCT_AMOUNT']
        );
        while ($prop = $rsProps->GetNext())
        {
            $arProperty['AMOUNT'] += $prop['PRODUCT_AMOUNT'];
        }
    }
}



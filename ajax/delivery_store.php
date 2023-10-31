<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(CModule::IncludeModule("catalog")) {
    $store = unserialize(html_entity_decode($_REQUEST['DELIVERY_ID']));
    $dbStoreProps = CCatalogStore::GetList(array('SORT' => 'ASC', 'ID' => 'ASC'), array("ACTIVE" => "Y", "ID" => $store), false, false, false);
    if($dbStoreProps->result->num_rows > 0):
    ?>

        <div class="group store" style="">
            <span style="font: 500 14px/40px 'Fira Sans',sans-serif;text-align: center;color: #333;">Адрес магазина для самовывоза</span>
        <div class="form_registration" style="height: 100%">
        <div class="face_type" style="height: auto">

<?
$i = 0;
    while ($arProp = $dbStoreProps->GetNext()) {
        if ($arProp['ID']) {
            ?>
            <label style="display: block;margin: 10px 5px;">
                <input type="radio" <?if($i == 0){print "checked";}?> id="" name="<?=$_REQUEST['ID_FIELD']?>" value="<?=$arProp['ADDRESS']?>">
                <span><?=$arProp['ADDRESS']?></span>
            </label>
            <?
        }
        $i++;
    }
    ?>

        </div>
        </div>
        </div>

    <?
    endif;
}

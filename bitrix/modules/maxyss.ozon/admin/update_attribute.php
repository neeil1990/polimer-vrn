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
    Bitrix\Currency;
\Bitrix\Main\UI\Extension::load("ui.hint");?>
    <script type="text/javascript">
        BX.ready(function() {
            BX.UI.Hint.init(BX('ozon_conainer'));
        })
    </script>
    <style>
        .adm-detail-content-cell-l > span:first-child{
            color: red;
            padding-left: 10px;
        }
    </style>
<?

if(CModule::IncludeModuleEx(MAXYSS_MODULE_NAME) == 2)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_OZON_MODULE_TRIAL_2').'</font>';
if(CModule::IncludeModuleEx(MAXYSS_MODULE_NAME) == 3)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_OZON_MODULE_TRIAL_3').'</font>';


if(Loader::includeModule('sale') && Loader::includeModule('iblock') && CModule::IncludeModule(MAXYSS_MODULE_NAME)) {
    if(Option::get(MAXYSS_MODULE_NAME, "OZON_V2", "") != 'Y'){
        echo GetMessage('MAXYSS_OZON_V2_NOT_INCLUDE');
    }else{

        $res = CIBlock::GetList(
            Array(),
            Array(
                'CODE'=>'ozon',
            ), false
        );
        if($ar_res = $res->Fetch())
        {
            $iblock_id = $ar_res['ID'];
        }

        if($iblock_id) {
            if ($_REQUEST['save_custom_cat'] && !empty($_REQUEST['cat_ids'])) {
                foreach ($_REQUEST['cat_ids'] as $cat) {
                    $cat_=explode('/',$cat);

                    $category_id = $cat_[0];
                    $section_id = '';

                    $arFilter = Array('IBLOCK_CODE' => 'ozon', '=NAME' => $category_id);
                    $db_list = CIBlockSection::GetList(Array('name' => 'asc'), $arFilter, false);
                    if ($ar_result = $db_list->GetNext()) {
                        $section_id = $ar_result['ID'];
                    }
                    if (!$section_id) {
                        $bs = new CIBlockSection;
                        $arFields = Array(
                            "ACTIVE" => 'Y',
                            "IBLOCK_ID" => $iblock_id,
                            "NAME" => $category_id,
                            "SORT" => $category_id,
                            "CODE" => $category_id,
                            "DESCRIPTION" => $cat_[1],
                            "DESCRIPTION_TYPE" => 'text'
                        );

                        if (!$section_id = $bs->Add($arFields))
                            echo $bs->LAST_ERROR;
                    }
                }
            }

            $arFilterSet = Array('IBLOCK_CODE' => 'ozon');
            $db_list = CIBlockSection::GetList(Array('name' => 'asc'), $arFilterSet, false);
            $arSections = array();
            while ($ar_result = $db_list->GetNext()) {
                $arSections[$ar_result['ID']] = $ar_result['NAME'];
                $arSections_[] = $ar_result['NAME'];
                $arSectionsHelp[$ar_result['ID']] = $ar_result['DESCRIPTION'].' ('.$ar_result['NAME'].')';
            }

            $arAttribute = array();
            $arFilter = Array("IBLOCK_ID"=>$iblock_id);
            $res_attr = CIBlockElement::GetList(Array('property_id'=>'asc'), $arFilter, false, false, array('ID', 'IBLOCK_ID', 'PROPERTY_id', 'IBLOCK_SECTION_ID'));
            while($ob_attr = $res_attr->GetNextElement())
            {
                $arFields = $ob_attr->GetFields();
                //            echo '<pre>', print_r($arFields), '</pre>' ;
                $arAttribute[$arFields['ID']]['element_id'] = $arFields['ID'];
                $arAttribute[$arFields['ID']]['id'] = $arFields['PROPERTY_ID_VALUE'];
                $arAttribute[$arFields['ID']]['category_id'] = $arSections[$arFields['IBLOCK_SECTION_ID']];
            }

            $all_atrribute = count($arAttribute);
            $arAttribute = array_values($arAttribute);

        }else{
            echo GetMessage('MAXYSS_IBLOCK_CAT_NOT_FOUNDE');
            die();
        }
        function sort_cat($a, $b)
        {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        }
        ?>
        <form action="<?=MAXYSS_MODULE_NAME?>_update_attribute.php?lang=<?=LANGUAGE_ID?>" method="post">
            <?

            $ozon_id_options = CMaxyssOzon::getOptions(false, array('OZON_ID', 'OZON_API_KEY'));

            ?>
            <div class="adm-detail-content-btns">
                <?
                if(count($ozon_id_options)>1) {
                echo GetMessage("OZON_MAXYSS_GET_CABINET");
                ?>
                <select name="ozon_id">
                    <?
                    foreach ($ozon_id_options as $key => $val) {
                        if($val['OZON_ID'] !='') {
                            ?>
                            <option name="cabinet_select" <?echo ($_REQUEST['ozon_id'] == $val['OZON_ID'])? 'selected' : ''; ?> value="<?=$val['OZON_ID']?>"><?=$val['OZON_ID']?></option>';
                            <?
                        }
                    }
                    ?>
                </select>
                <input type="submit" name="cabinet" value="<?= GetMessage('OZON_MAXYSS_SAVE_OZON_ID') ?>"><span data-hint="<?=GetMessage('OZON_MAXYSS_GET_CABINET_TIP')?>"></span>
            </div>
        <?
        }
        if($_REQUEST['ozon_id'])
        {
            $ApiKey = CMaxyssOzon::GetApiKey($_REQUEST['ozon_id']);
            $ClientId = $_REQUEST['ozon_id'];
        }
        else
        {

            $site_def = Option::get("maxyss.ozon", "SITE" );
            if ($site_def == '') {
                $rsSites = CSite::GetList($by = "def", $order = "desc", Array('DEFAULT' => "Y"));
                if ($arSite = $rsSites->Fetch()) {
                    $site_def = $arSite['LID'];
                }
            }
            $ApiKey = $ozon_id_options[$site_def]["OZON_API_KEY"];
            $ClientId = $ozon_id_options[$site_def]["OZON_ID"];
        }
        ?>
            <div class="adm-detail-content-item-block ozon_conainer">
                <div class="help_maxyss"><?=GetMessage('MAXYSS_ATTRIBUTE_HELP')?></div>

                <table class="adm-detail-content-table edit-table" id="tab1_edit_table">
                    <tbody>
                    <tr class="heading">
                        <td><?=GetMessage('MAXYSS_OZON_UPDATE_CATEGORY')?></td>
                    </tr>
                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <div class="adm-detail-content-btns-wrap" id="editTab_buttons_div" style="text-align: left">
                                <div class="adm-detail-content-btns">
                                    <?if(is_set($arSectionsHelp)){?>
                                        <?$sec_text = implode(', ', $arSectionsHelp)?>
                                        <div class="custom_category">
                                            <?echo GetMessage('MAXYSS_ACTIVE_CATEGORY')?><?echo $sec_text;?>
                                        </div>
                                    <?}?>
                                    <!--                                <form action="" name="save_cat">-->
                                    <?
                                    $arCategorys = array();

                                    if (_is_curl_installed()) {
                                        $data_string = array(
                                            'language' => 'RU'
                                        );
                                        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                                        $arCategorys = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v1/category/tree");
                                        $sortCategorys = array();
                                        if(!empty($arCategorys)&& !isset($arCategorys['error'])){
                                            foreach ($arCategorys as $sort) {
                                                $sortCategorys[$sort['category_id']] = $sort;
                                            }
                                            usort($sortCategorys, "sort_cat");
                                        }
                                    }
                                    else
                                    {
                                        echo "cURL is <span style=\"color:#dc4f49\">not installed</span> on this server";
                                    }
                                    if(!empty($sortCategorys)) {

                                        if (!function_exists('array_write')) {

                                            function array_write($array, $i=0, $arSections)
                                            {
                                                foreach ($array as $key => $value) {

                                                    if (count($value['children']) > 0) {
                                                        if($key == 0) {$i++; $px= $i*20;}

                                                        if($i == 2) echo '<div class="view-source">';
                                                        if($i == 2)
                                                            echo '<a href="javascript:void(0);" style="display:block; padding-left: '.$px.'px"><span> + </span>'.$value['title'].'</a>';
                                                        if($i == 1)
                                                            echo '<div style="display:block; padding-left: '.$px.'px">'.$value['title'].'</div>';

                                                        if($i!=1)    echo '<div class="hide">';
                                                        array_write($value['children'], $i, $arSections);
                                                        if($i!=1)    echo '</div>';

                                                        if($i==2) echo '</div>';


                                                    } else {
                                                        $i=0;
                                                        ?>
                                                        <div style="padding-left: 60px">
                                                            <input type="checkbox" name="cat_ids[]" <?echo in_array($value['category_id'], $arSections)? 'checked' : ''?> id="id_<?=$value['category_id']?>" value="<?=$value['category_id'].'/'.$value['title']?>"><label for="id_<?=$value['category_id']?>"><?=$value['title'].' ('.$value['category_id'].')'?></label>
                                                        </div>
                                                        <?
                                                    }
                                                }
                                            }
                                        }
                                        array_write($sortCategorys, 0, $arSections);
                                        //                                        echo '<pre>', print_r($arCategorys), '</pre>' ;
                                        ?>
                                        <div class="" style="text-align: center">
                                            <input type="submit" name="save_custom_cat" id="save_custom_cat" value="<?=GetMessage('MAXYSS_OZON_CAT_SAVE')?>">
                                        </div>
                                    <?}else{echo '<span style="color:red;">'.GetMessage('MAXYSS_OZON_NOT_KEY').'</span>';}?>
                                    <!--                                </form>-->
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?if(!empty($sortCategorys)) {?>
                        <tr class="heading">
                            <td><?=GetMessage('MAXYSS_OZON_UPDATE_ATTRIBUTE')?></td>
                        </tr>
                        <tr>
                            <td class="adm-detail-content-cell-l">
                                <div class="adm-detail-content-btns-wrap" id="editTab_buttons_div" style="text-align: center">
                                    <div class="adm-detail-content-btns">
                                        <?if($all_atrribute > 0){?>
                                            <div class="custom_category">
                                                <?echo GetMessage('MAXYSS_ACTIVE_ATRIBUTE')?> <?echo $all_atrribute;?>
                                            </div>
                                        <?}?>
                                        <input type="button" onclick="get_update('get_section_attr_v2')" name="get_attr_v2" id="get_attr_v2" value="<?=GetMessage('MAXYSS_OZON_START')?>">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="heading">
                            <td><?=GetMessage('MAXYSS_OZON_UPDATE_ATTRIBUTE_VALUE')?></td>
                        </tr>
                        <tr>
                            <td class="adm-detail-content-cell-l">
                                <div class="adm-detail-content-btns-wrap" id="editTab_buttons_div" style="text-align: center">

                                    <div class="adm-detail-content-btns">
                                        <input type="button" onclick="get_update_value('get_attr_value_v2', 1)" name="get_attr_value_v2" id="get_attr_value_v2" value="<?=GetMessage('MAXYSS_OZON_START_VALUE_UPDATE')?>">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?}?>
                    </tbody>
                </table>
            </div>
        </form>

        <script type="text/javascript">
            var DialogOzon = new BX.CDialog({
                title: "<?=GetMessage('MAXYSS_OZON_POPUP_NO_CLOSE')?>",
                content: '<form method="POST" style="overflow:hidden;" action="" id="wait"><br><div class="answer_title" style="display: none"><?=GetMessage("MAXYSS_OZON_NO_CLOSE")?></div>\n' +
                '    <div class="answer"></div><br></form>',
                icon: 'head-block',
                resizable: true,
                draggable: true,
                height: '200',
                width: '500',
                buttons: [BX.CDialog.btnClose]
            });

            function  two_level_ajax(el_id, attr_id, category_id, last_el_id, step_two, step) {
                // console.log(step_two);
                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
                    data: {
                        el_id: el_id,
                        attr_id: attr_id,
                        category_id: category_id,
                        last_el_id: last_el_id,
                        action: 'add_next_50',
                        iblock_id: '<?=$iblock_id?>',
                        step_two: step_two,
                        step: step,
                        client_id: '<?=$ClientId?>',
                    },
                    success: function (data) {
                        var obj = $.parseJSON(data);

                        if (obj.last_el_id && step_two < 15) {
                            two_level_ajax(obj.el_id, obj.attr_id, obj.category_id, obj.last_el_id, obj.step_two, step);
                        }else{
                            one_level_ajax('get_attr_value_v2', obj.step + 1, '<?=$ClientId?>');
                        }

                        console.log(obj.last_el_id);
                    },
                    error: function (xhr, str) {
                        alert('An error has occurred: ' + xhr.responseCode);
                    }
                });
            }

            function one_level_ajax(action, step, client_id){
                var elements = <?=CUtil::PhpToJSObject($arAttribute)?>,
                    all_atrribute = '<?=$all_atrribute?>';
                // console.log(action);
                // console.log(step);
                $('.answer').html('<div>' + '<?=GetMessage("MAXYSS_OZON_UPDATE_ATTRIBUTE_MESS");?>'+ step + ' <?=GetMessage("MAXYSS_OZON_IZ")?> ' + all_atrribute +'</div>');
                // console.log(elements[step-1]);
                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
                    data: {
                        attr: elements[step-1],
                        action: action,
                        iblock_id: '<?=$iblock_id?>',
                        client_id: client_id
                    },
                    success: function(data) {
                        var obj=$.parseJSON(data);


                        if(obj.last_el_id) {

                            console.log(obj.last_el_id);
                            two_level_ajax(obj.el_id, obj.attr_id, obj.category_id, obj.last_el_id, obj.step_two, step);

                        }else{

                            if(step < all_atrribute)
                            {
                                step = step + 1;
                                one_level_ajax(action, step, client_id);
                            }
                            else
                            {
                                $('.answer').html('<div>'+'<?=GetMessage("MAXYSS_OZON_UPDATE_ATTRIBUTE_MESS_END")?>'+'</div>');
                                $('.answer_title').prop("style", "display:none");
                                $('#'+action).prop('disabled',false);
                                setTimeout(function () {
                                    $('#'+action).prop('disabled',false);
                                }, 2000);

                            }
                        }

                        if(obj.TYPE == "ERROR") {
                            $('.answer').html('<div style="color: red;  text-align: center">' + obj.MESSAGE + '</div>');
                            setTimeout(function () {
                                $('#'+action).prop('disabled',false);
                            }, 2000);
                        }
                        //$('.answer').html(data);
                    },
                    error:  function(xhr, str){
                        alert('An error has occurred: ' + xhr.responseCode);
                    }
                });



            }

            function get_update_value(action, step) {
                $('.answer').html('');
                $('#'+action).prop('disabled',true);
                $('.answer_title').prop("style", "display: block");
                DialogOzon.Show();
                one_level_ajax(action, step, '<?=$ClientId?>');

            }

            // function section_attr_update(action, section ) {
            //     flag = false;
            //
            // }

            function recursive(sections) {
                var sec_array = jQuery.makeArray(sections);
                var sections_new = sec_array.slice(1,sec_array.length);
                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                    data: {
                        section: sec_array[0],
                        action: 'get_section_attr_v2',
                        client_id: '<?=$ClientId?>'
                    },
                    success: function(data) {
                        var obj=$.parseJSON(data);
                        // console.log(data);

                        $('.answer').html('<div>' + '<?=GetMessage("MAXYSS_OZON_START_VALUE_UPDATE_MESS_NEW")?>'+ sections_new.length +'</div>')
                        if(sections_new.length > 0) {
                            recursive(sections_new)
                        }else{
                            console.log('loading is complete');
                            $('#get_attr_v2').prop('disabled',false);
                            $('.answer').html('<div>'+'<?=GetMessage("MAXYSS_OZON_START_VALUE_UPDATE_MESS_END")?>'+'</div>');
                            $('.answer_title').prop("style", "display: none");
                            setTimeout(function () {
                                // $('#get_attr_v2').prop('disabled',false);
                                window.location.href = window.location.href + '&ozon_id=' + '<?=$ClientId?>';
                            }, 2000);
                        }
                    },
                    error:  function(xhr, str){
                        alert('An error has occurred: ' + xhr.responseCode);
                    }
                });
            }

            function get_update(action) {
                $('.answer').html('');
                DialogOzon.Show();

                var sections = <?=CUtil::PhpToJSObject($arSections_)?>;

                $('#get_section_attr_v2').prop('disabled',true);
                $('.answer_title').prop("style", "display: block");

                recursive(sections);

            }

            $(function() {
                $('.view-source .hide').hide();
                $a = $('.view-source a');
                $a.on('click', function(event) {
                    event.preventDefault();
                    $a.not(this).next().slideUp(500);
                    $(this).next().slideToggle(500);
                });
            });


        </script>
    <?  }
}else
    die();
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>
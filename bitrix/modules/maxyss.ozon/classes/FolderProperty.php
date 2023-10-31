<?php
use \Bitrix\Main,
    \Bitrix\Main\UserField;
CJSCore::Init(array('maxyss_ozon'));
    class CMaxyssOzonFolder
    {

        /**
         * Метод возвращает массив описания собственного типа свойств
         * @return array
         */
        public function GetOzonTypeDescription()
        {
            return array(
                "USER_TYPE_ID" => 'maxyss_ozon_cat', //Уникальный идентификатор типа свойств
                "CLASS_NAME" => __CLASS__,
                "DESCRIPTION" =>  GetMessage('CATEGORY_NAME_TEXT'),
                "BASE_TYPE" => 'string',
//                "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING,
            );
        }

        /**
         * Обязательный метод для определения типа поля таблицы в БД при создании свойства
         * @param $arUserField
         * @return string
         */
        function GetDBColumnType($arUserField)
        {
            global $DB;
            switch (strtolower($DB->type)) {
                case "mysql":
                    return "text";
                case "oracle":
                    return "text";
                case "mssql":
                    return "text";
            }
            return "text";
        }


        /**
         * Получить HTML формы для редактирования свойства
         * @param $arUserField
         * @param $arHtmlControl
         * @return string
         */
        public function GetEditFormHTML($arUserField, $arHtmlControl)
        {
            $strHTMLControlName = $arHtmlControl;

            $arOzonAttrCategory = array();
            $category = '';
            if (strlen($strHTMLControlName['VALUE']) > 0) {
                if (LANG_CHARSET == 'windows-1251')
                    $arOzonAttrCategory = iconv('windows-1251', 'UTF-8//IGNORE', $strHTMLControlName['VALUE']);
                else
                    $arOzonAttrCategory = $strHTMLControlName['VALUE'];

//                $arOzonAttrCategory = json_decode(htmlspecialchars_decode($arOzonAttrCategory), true);
                $arOzonAttrCategory = CUtil::JsObjectToPhp(htmlspecialchars_decode($arOzonAttrCategory));


                if (LANG_CHARSET == 'windows-1251') $arOzonAttrCategory = CMaxyssOzonAgent::deepIconv($arOzonAttrCategory);

                $arFilter = Array('IBLOCK_CODE' => 'ozon', 'NAME' => $arOzonAttrCategory['category']['id']);
                $db_list = CIBlockSection::GetList(Array('name' => 'asc'), $arFilter, false);
                if ($ar_result = $db_list->GetNext()) {
                    $category = $ar_result['DESCRIPTION'];
                }
            }


            $result = '';
            $ID = intval($_REQUEST['ID']); //
            global $APPLICATION;
            if ($APPLICATION->GetCurPage() != '/bitrix/admin/iblock_list_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/iblock_element_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/cat_product_admin.php') {

                $result = '<input type="text" id="autocomplete_ozon" class="autocomplete_ozon"  title="' . GetMessage('CATEGORY_ENTER_TEXT') . '" placeholder="' . GetMessage('CATEGORY_SEARCH_TEXT') . '" name="' . $arUserField["FIELD_NAME"] . '_' . $arUserField["ENTITY_VALUE_ID"] . '" value="' . $category . '">';
                $result .= '<input id="value_ozon" data-category-ozon="" type="text" readonly name="' . $strHTMLControlName['NAME'] . '" value="' . $strHTMLControlName['VALUE'] . '"><a style="margin-left: 10px" href="javascript:void(0);" onclick=edit_value();>edit</a>';
                $result .= '<div class="ozon_attr"></div>';

                $arCat = CCustomTypeOzonCat::GetCatOzonFromBD();
                if (is_array($arCat)) {
                    if (count($arCat) > 0) {
                        foreach ($arCat as $key => $Cat):
                            $c['value'] = $key;
                            $c['label'] = $Cat;
                            $arCat_[] = $c;
                        endforeach;
                        ?>
                        <script data-skip-moving="true" type="text/javascript">
                            var availableTags =<?=CUtil::PhpToJSObject($arCat_)?>;
                            var name_val =<?=CUtil::PhpToJSObject($strHTMLControlName['NAME'])?>;
                        </script>
                        <?
                        if (strlen($strHTMLControlName['VALUE']) > 0) {
                            $arOzonAttrFolder =  CUtil::JsObjectToPhp(htmlspecialchars_decode($strHTMLControlName['VALUE']));
                            ?>
                            <script data-skip-moving="true" type="text/javascript">
                                var folder_val =<?=htmlspecialchars_decode($strHTMLControlName['VALUE'])?>;
                                function get_custom_html() {
                                    BX.ajax({
                                        method: 'POST',
                                        dataType: 'html',
                                        timeout: 30,
                                        url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                                        data: {
                                            category: folder_val.category.id,
                                            action: 'get_attr_from_bd',
                                            iblock_id: '<?=$_REQUEST["IBLOCK_ID"]?>',
                                        },
                                        onsuccess: function (data) {
                                            if (data != null) {
                                                document.querySelector('.ozon_attr').innerHTML = data;
                                                setTimeout(function () {
                                                    $.each(folder_val, function (index, val) {
                                                        if(index !== 'category'){
                                                            $.each(val.values, function (c, dictionary_value) {
                                                                if(dictionary_value.dictionary_value_id > 0)
                                                                    $('[data-ozon-attrid="'+index+'"] option[value="'+dictionary_value.dictionary_value_id+'"]').prop('selected', true);
                                                                else {
                                                                    $('[data-ozon-attrid="' + index + '"]').val(dictionary_value.value);
                                                                }
                                                            })
                                                        }
                                                        else
                                                        {
                                                            $("input[name='" + name_val + "']").data('category-ozon', val.id);
                                                            $("input[name='" + name_val + '_'  + '<?=$arUserField["ENTITY_VALUE_ID"]?>' + "']").val('<?=$arCat[$arOzonAttrFolder['category']['id']]?>');
                                                        }
                                                    })

                                                }, 500);
                                            }
                                        },
                                        onfailure: function () {
                                            new Error("Request failed");
                                        }
                                    });
                                }

                                $(document).ready(function () {
                                    var html_ok = get_custom_html();
                                });

                            </script>
                            <?
                        }
                        ?>
                        <script data-skip-moving="true" type="text/javascript">
                            $(document).ready(function () {
                                var counte_arCat = '<?=count($arCat)?>';
                                if (name_val) {

                                    var input_val = $("input[name='" + name_val + "']");

                                    function attr_get() {
                                        var attr = {};

                                        $('.ozon_atr').each(function (index, value) {
                                            var type_elem = '',
                                                type_elem_input = '';
                                            type_elem = $(this).get(0).nodeName;
                                            type_elem_input = $(this).attr('type');

                                            if (type_elem == 'INPUT' && type_elem_input == 'checkbox' && $(this).prop('checked')) {
                                                attr[$(this).data('ozon-attrid')] = {};
                                                attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');

                                                attr[$(this).data('ozon-attrid')]['values'] = [];
                                                attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                                attr[$(this).data('ozon-attrid')]['values'][0]['dictionary_value_id'] = 0;
                                                attr[$(this).data('ozon-attrid')]['values'][0]['value'] = "true";

                                            }
                                            if (type_elem == 'INPUT' && type_elem_input == 'text' && $(this).data('ozon-child-attrid') && $(this).val()) {
                                                var complex_value = [];
                                                $('[data-ozon-attrid=' + $(this).data('ozon-attrid') + ']').each(function (index) {
                                                    complex_value[index] = {};
                                                    complex_value[index].id = $(this).data('ozon-child-attrid');
                                                    complex_value[index].value = $(this).val();
                                                });

                                                attr[$(this).data('ozon-attrid')] = {};
                                                attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                                attr[$(this).data('ozon-attrid')].complex_collection = complex_value;
                                            }

                                            if (type_elem == 'INPUT' && type_elem_input == 'text' && !$(this).data('ozon-child-attrid') && $(this).val()) {
                                                attr[$(this).data('ozon-attrid')] = {};
                                                attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                                attr[$(this).data('ozon-attrid')]['values'] = [];
                                                attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                                attr[$(this).data('ozon-attrid')]['values'][0]['value'] = $(this).val();
                                            }

                                            if (type_elem == 'INPUT' && type_elem_input == 'number' && !$(this).data('ozon-child-attrid') && $(this).val()) {

                                                attr[$(this).data('ozon-attrid')] = {};
                                                attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                                attr[$(this).data('ozon-attrid')]['values'] = [];
                                                attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                                attr[$(this).data('ozon-attrid')]['values'][0]['value'] = $(this).val();
                                                console.log(attr[$(this).data('ozon-attrid')]);
                                            }

                                            if (type_elem == 'TEXTAREA' && $(this).val()) {
                                                attr[$(this).data('ozon-attrid')] = {};
                                                attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                                attr[$(this).data('ozon-attrid')]['values'] = [];
                                                attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                                attr[$(this).data('ozon-attrid')]['values'][0]['value'] = $(this).val();
                                            }

                                            if (type_elem == 'SELECT' && $(this).prop('multiple') && $(this).val()) {
                                                var ozon_attrid = $(this).data('ozon-attrid');
                                                attr[ozon_attrid] = {};
                                                attr[ozon_attrid].id = $(this).data('ozon-attrid');
                                                attr[ozon_attrid]['values'] = [];
                                                $.each($(this).val(), function (index, value) {
                                                    attr[ozon_attrid]['values'][index] = {};
                                                    attr[ozon_attrid]['values'][index]['dictionary_value_id'] = value;
                                                })

                                            }
                                            if (type_elem == 'SELECT' && !$(this).prop('multiple') && $(this).val()) {

                                                attr[$(this).data('ozon-attrid')] = {};
                                                attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                                attr[$(this).data('ozon-attrid')]['values'] = [];
                                                attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                                attr[$(this).data('ozon-attrid')]['values'][0]['dictionary_value_id'] = $(this).val();
                                            }
                                        });

                                        attr['category'] = {};
                                        attr['category'].id = $("input[name='" + name_val + "']").data('category-ozon');
                                        var text = JSON.stringify(attr);

                                        input_val.val(text);
                                    }

                                    $("body").delegate(".ozon_atr", "change", function () {
                                        attr_get();
                                    });

                                    $(".autocomplete_ozon").autocomplete({
                                        source: availableTags,
                                        select: function (event, ui) {
                                            event.preventDefault();
                                            $(this).val(ui.item.label);

                                            if ($(this).next().val() != ui.item.value) {
                                                $("input[name='<?=$strHTMLControlName['NAME']?>']").data('category-ozon', ui.item.value);
                                                // $(this).next().val(ui.item.value);
                                                $('[name="PROPERTY_DEFAULT_VALUE"]').val(ui.item.value);
                                                var action = 'get_attr_from_bd';

                                                $.ajax({
                                                    type: 'GET',
                                                    url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
                                                    data: {
                                                        category: ui.item.value,
                                                        action: action,
                                                        iblock_id: '<?=$_REQUEST["IBLOCK_ID"]?>',
                                                    },
                                                    success: function (data) {
                                                        $('.ozon_attr').html(data);

                                                        setTimeout(function () {
                                                            attr_get();
                                                        }, 500);
                                                    },
                                                    error: function (xhr, str) {
                                                        alert('Error get_attr: ' + xhr.responseCode);
                                                    }
                                                });

                                            }
                                            $('[data="' + ui.item.value + '"]').click();
                                        }
                                    });
                                }
                            });
                        </script>
                        <?
                    } else {
                        echo '<br />';
                        echo GetMessage("OZON_MAXYSS_ERROR");
                    }
                }

            }
            return $result;
        }

        function GetAdminListViewHTML($arUserField, $arHtmlControl)
        {
            if (strlen($arHtmlControl["VALUE"]) > 0)
                return $arHtmlControl["VALUE"];
            else
                return '&nbsp;';
        }
    }
<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('MAXYSS_OZON_TITLE'));

CJSCore::Init('jquery');

global $APPLICATION;
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency;

\Bitrix\Main\UI\Extension::load("ui.hint"); ?>
    <script type="text/javascript">
        BX.ready(function () {
            BX.UI.Hint.init(BX('ozon_conainer'));
        })
    </script>
    <style>
        .adm-detail-content-cell-l > span:first-child {
            color: red;
            padding-left: 10px;
        }

        .status_list_span {
            width: 250px;
            display: inline-block;
        }
    </style>
<?

if (CModule::IncludeModuleEx(MAXYSS_MODULE_NAME) == 2)
    echo '<font style="color:red;">' . GetMessage('MAXYSS_OZON_MODULE_TRIAL_2') . '</font>';
if (CModule::IncludeModuleEx(MAXYSS_MODULE_NAME) == 3)
    echo '<font style="color:red;">' . GetMessage('MAXYSS_OZON_MODULE_TRIAL_3') . '</font>';


if (Loader::includeModule('sale') && Loader::includeModule('iblock') && CModule::IncludeModule(MAXYSS_MODULE_NAME)) {
    if ($_REQUEST['save'] || $_REQUEST['apply']) {
        CMaxyssOzon:: saveOptions('active_order_on');
        CMaxyssOzon:: saveOptions('period_order');
        CMaxyssOzon:: saveOptions('valuta_order');
        CMaxyssOzon:: saveOptions('person_type');
        CMaxyssOzon:: saveOptions('delivery_service');
        CMaxyssOzon:: saveOptions('paysystem');
        CMaxyssOzon:: saveOptions('user_defaulte');
        CMaxyssOzon:: saveOptions('RESPONSIBLE_ID');
        CMaxyssOzon:: saveOptions('period_order_day');

        // статусы
        CMaxyssOzon:: saveOptions('AWAITING_PACKAGING');
        CMaxyssOzon:: saveOptions('AWAITING_DELIVER');
        CMaxyssOzon:: saveOptions('NOT_ACCEPTED');
        CMaxyssOzon:: saveOptions('DELIVERING');
        CMaxyssOzon:: saveOptions('DELIVERED');
        CMaxyssOzon:: saveOptions('CANCELLED');

        CMaxyssOzon:: saveOptions('ACCEPTED_FROM_CUSTOMER');
        CMaxyssOzon:: saveOptions('WAITING_FOR_SELLER');
        CMaxyssOzon:: saveOptions('READY_FOR_SHIPMENT');
        CMaxyssOzon:: saveOptions('RETURNED_TO_SELLER');
        CMaxyssOzon:: saveOptions('CANCELLED_WITH_COMPENSATION');
        CMaxyssOzon:: saveOptions('DISPOSED');

        CMaxyssOzon:: saveOptions('STATUS_SHIP_BITRIX');
        CMaxyssOzon:: saveOptions('FLAG_SHIPMENT_UP');
        CMaxyssOzon:: saveOptions('FLAG_CANCELLED_UP');
        CMaxyssOzon:: saveOptions('FLAG_PAYMENT_UP');

        CMaxyssOzon:: saveOptions('ARBITRATION');
        CMaxyssOzon:: saveOptions('DRIVER_PICKUP');

        CMaxyssOzon:: saveOptions('status_no_change');

        Option::set(MAXYSS_MODULE_NAME, "PROPERTY_ORDER_OZON", $_REQUEST['property_order_ozon']);
        Option::set(MAXYSS_MODULE_NAME, "PROPERTY_DATE_OZON", $_REQUEST['property_date_ozon']);


        $arActive = CMaxyssOzon::getOptions(false, array('ACTIVE_ORDER_ON', 'PERIOD_ORDER'));
        foreach ($arActive as $key => $active) {
            if ($active["ACTIVE_ORDER_ON"] == 'Y') {
                $res_status = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonStatusPut::ListOrderStatus('" . $key . "'%"));
                $arResStatus = $res_status->GetNext();
                if (intval($arResStatus['ID']) == 0) {
                    CAgent::AddAgent(
                        "CMaxyssOzonStatusPut::ListOrderStatus('" . $key . "');",
                        "maxyss.ozon",
                        "N",
                        "21600",
                        "",
                        "Y",
                        "",
                        1);
                }else{
                    $arFieldAgent = array(
                        "ACTIVE" => 'Y',
                    );
                    CAgent::Update(intval($arResStatus['ID']), $arFieldAgent);
                }

                $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonLoadOrder('" . $key . "'%"));
                if ($arRes = $res->GetNext()) {
                    if (intval($arRes['ID']) > 0 /*&& $arRes['AGENT_INTERVAL'] != $active["PERIOD_ORDER"]*/) {
                        $arFieldAgent = array(
                            "AGENT_INTERVAL" => $active['PERIOD_ORDER'] != '' ? $active['PERIOD_ORDER'] : '1200',
                            "ACTIVE" => 'Y',
                        );
                        CAgent::Update(intval($arRes['ID']), $arFieldAgent);
                    }

                } elseif (intval($arRes['ID']) == 0 && intval($active['PERIOD_ORDER']) > 0) {
                    CAgent::AddAgent(
                        "CMaxyssOzonAgent::OzonLoadOrder('" . $key . "',0);",
                        "maxyss.ozon",
                        "N",
                        $active['PERIOD_ORDER'],
                        "",
                        "Y",
                        "",
                        100);
                };

                // необработанные "CMaxyssOzonAgent::OzonLoadUnfulfilledOrder('" . $lid . "');"
                $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonLoadUnfulfilledOrder('" . $key . "');"));
                if ($arRes = $res->GetNext()) {
                    if (intval($arRes['ID']) > 0 /*&& $arRes['AGENT_INTERVAL'] != $active["PERIOD_ORDER"]+40*/) {
                        $arFieldAgent = array(
                            "AGENT_INTERVAL" => '300',
                            "ACTIVE" => 'Y',
                        );
                        CAgent::Update(intval($arRes['ID']), $arFieldAgent);
                    }

                } elseif (intval($arRes['ID']) == 0 && intval($active['PERIOD_ORDER']) > 0) {
                    CAgent::AddAgent(
                        "CMaxyssOzonAgent::OzonLoadUnfulfilledOrder('" . $key . "');",
                        "maxyss.ozon",
                        "N",
                        300,
                        "",
                        "Y",
                        "",
                        1);
                };

            } else {
                $res_status = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonStatusPut::ListOrderStatus('" . $key . "'%"));
                if ($arResStatus = $res_status->GetNext()) {
                    CAgent::Delete($arResStatus['ID']);
                }

                $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonLoadOrder('" . $key . "'%"));
                if ($arRes = $res->GetNext()) {
                    CAgent::Delete($arRes['ID']);
                }
                $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonLoadUnfulfilledOrder('" . $key . "%"));
                if ($arRes = $res->GetNext()) {
                    CAgent::Delete($arRes['ID']);
                }
            }
        }
    }

    // табуляторы
    $by = "id";
    $sort = "asc";

    $arSites = array();
    $db_res = CSite::GetList($by, $sort, array("ACTIVE" => "Y"));
    while ($res = $db_res->Fetch()) {
        $arSites[] = $res;
    }

    $arTabs = array();
    $key = 0;

    $arOptionsCheck = CMaxyssOzon::getOptions(false, array("OZON_ID"));

    foreach ($arSites as $key => $arSite) {
        if ($arOptionsCheck[$arSite["ID"]]["OZON_ID"] != '') {
            $arTabs[] = array(
                "DIV" => "edit_ozon" . ($key + 1),
                "TAB" => GetMessage("MAXYSS_OZON_GENERAL_TITLE_TAB", array("#SITE_NAME#" => $arSite["NAME"], "#SITE_ID#" => $arSite["ID"])),
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
    if (!count($arTabs)) {
        ?>
        <div class="adm-info-message-wrap adm-info-message-red">
            <div class="adm-info-message">
                <div class="adm-info-message-title"><?= GetMessage("MAXYSS_OZON_GENERAL_NO_SITE_INSTALED") ?></div>
                <div class="adm-info-message-icon"></div>
            </div>
        </div>
    <?
    } else {
        ?>
        <form action="<?= MAXYSS_MODULE_NAME ?>_order_ozon_maxyss.php?lang=<?= LANGUAGE_ID ?>" method="post">
            <?
            $tabControl->Begin();
            // табуляторы
            ?>
            <?
            foreach ($arTabs as $key_tab => $arTab) {
                $arOptions = CMaxyssOzon::getOptions($arTab["SITE_ID"]);
                $tabControl->BeginNextTab();
                ?>
                <!--        <div class="adm-detail-content-item-block ozon_conainer">-->
                <tr>
                    <td colspan="5">
                        <? echo "<span style='color: blue'>OZON_ID " . $arOptions[$arTab["SITE_ID"]]["OZON_ID"] . "</span>" ?>
                    </td>
                </tr>
                <tr class="heading">
                    <td colspan="5"><?= GetMessage('MAXYSS_OZON_MODULE_ACTIVITY') ?></td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_AGENT_ORDER_ACTIVE') ?></td>
                    <td class="adm-detail-content-cell-r">
                        <input type="hidden" name="active_order_on[<?= $arTab["SITE_ID"] ?>]" value="N">
                        <input type="checkbox" name="active_order_on[<?= $arTab["SITE_ID"] ?>]"
                               id="active_order_on_<?= $arTab["SITE_ID"] ?>" class="adm-designed-checkbox" <?
                        echo ($arOptions[$arTab["SITE_ID"]]["ACTIVE_ORDER_ON"] == 'Y') ? 'checked = "checked"' : '' ?>
                               value="Y">
                        <label class="adm-designed-checkbox-label" for="active_order_on_<?= $arTab["SITE_ID"] ?>"
                               title=""></label>
                    </td>
                </tr>

                <tr class="heading">
                    <td colspan="5"><?= GetMessage('MAXYSS_OZON_REPLACE') ?></td>
                </tr>
                <?
                $valuta = \Bitrix\Currency\CurrencyManager::getCurrencyList(); ?>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_VALUTA_ORDER') ?><span>*</span>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="valuta_order[<?= $arTab["SITE_ID"] ?>]">
                            <?
                            foreach ($valuta as $key => $valut) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo(($arOptions[$arTab["SITE_ID"]]["VALUTA_ORDER"] == $key) ? 'selected = "selected"' : '') ?>><?= $valut ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                </tr>

                <?
                $db_ptype = CSalePersonType::GetList(Array("SORT" => "ASC"), Array("LID" => $arTab["SITE_ID"]));
                while ($ptype = $db_ptype->Fetch()) {
                    $PersonType[$ptype['ID']] = $ptype['NAME'];
                }
                ?>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_PERSON_TYPE') ?><span>*</span>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="person_type[<?= $arTab["SITE_ID"] ?>]">
                            <?
                            foreach ($PersonType as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo(($arOptions[$arTab["SITE_ID"]]["PERSON_TYPE"] == $key) ? 'selected = "selected"' : '') ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                </tr>

                <?
                if (!VERSION_OZON_3) {
                    $arDelivery = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
                    foreach ($arDelivery as $key => $deliver) {
                        $arDeliverySelect[$key] = $deliver['NAME'];
                    }
                    ?>
                    <tr>
                        <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_DELIVERY') ?><span>*</span>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <select name="delivery_service[<?= $arTab["SITE_ID"] ?>]">
                                <?
                                foreach ($arDeliverySelect as $key => $type) {
                                    ?>
                                    <option value="<?= $key ?>" <?
                                    echo ($arOptions[$arTab["SITE_ID"]]["DELIVERY_SERVICE"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                                <?
                                } ?>
                            </select>
                        </td>
                    </tr>
                <?
                } ?>
                <?
                $db_paysystem = CSalePaySystem::GetList($arOrder = Array("SORT" => "ASC", "PSA_NAME" => "ASC"), Array("ACTIVE" => "Y"));
                while ($paysystem = $db_paysystem->Fetch()) {
                    $arPaySystem[$paysystem['ID']] = $paysystem["NAME"];
                } ?>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_PAYSYSTEM') ?><span>*</span></td>
                    <td class="adm-detail-content-cell-r">
                        <select name="paysystem[<?= $arTab["SITE_ID"] ?>]">
                            <?
                            foreach ($arPaySystem as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo(($arOptions[$arTab["SITE_ID"]]["PAYSYSTEM"] == $key) ? 'selected = "selected"' : '') ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_USER_DEFAULTE_OZON') ?></td>
                    <td class="adm-detail-content-cell-r">
                        <input name="user_defaulte[<?= $arTab["SITE_ID"] ?>]" value="<?
                        echo $arOptions[$arTab["SITE_ID"]]["USER_DEFAULTE"] ?>"><span
                                data-hint="<?= GetMessage('MAXYSS_OZON_USER_DEFAULTE_TIP') ?>"></span>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_DEFAULTE_RESPONSIBLE_ID') ?></td>
                    <td class="adm-detail-content-cell-r">
                        <input name="RESPONSIBLE_ID[<?= $arTab["SITE_ID"] ?>]" value="<?
                        echo $arOptions[$arTab["SITE_ID"]]["RESPONSIBLE_ID"] ?>"><span
                                data-hint="<?= GetMessage('MAXYSS_OZON_DEFAULTE_RESPONSIBLE_ID_TIP') ?>"></span>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_PROPERTY_ORDER_OZON') ?>
                        <span>*</span></td>
                    <td class="adm-detail-content-cell-r">
                        <input name="property_order_ozon" value="<?
                        echo Option::get(MAXYSS_MODULE_NAME, "PROPERTY_ORDER_OZON", ""); ?>"><span
                                data-hint="<?= GetMessage('MAXYSS_OZON_PROPERTY_ORDER_OZON_TIP') ?>"></span>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_PROPERTY_DATE_OZON') ?>
                        <span>*</span></td>
                    <td class="adm-detail-content-cell-r">
                        <input name="property_date_ozon" value="<?
                        echo Option::get(MAXYSS_MODULE_NAME, "PROPERTY_DATE_OZON", ""); ?>"><span
                                data-hint="<?= GetMessage('MAXYSS_OZON_PROPERTY_DATE_OZON_TIP') ?>"></span>
                    </td>
                </tr>
                <tr class="heading">
                    <td colspan="5"><?= GetMessage('MAXYSS_OZON_STATUS_LIST') ?></td>
                </tr>

                <!--соответствие статусов-->
                <?
                $db_osatatus = CSaleStatus::GetList(array("SORT" => "ASC"), array(), false, false, array());
                while ($osatatus = $db_osatatus->Fetch()) {
                    $arStatus[$osatatus['ID']] = $osatatus['NAME'];
                }
                $statusResult = \Bitrix\Sale\Internals\StatusLangTable::getList(array(
                    'order' => array('STATUS.SORT' => 'ASC'),
                    'filter' => array('STATUS.TYPE' => 'D', 'LID' => LANGUAGE_ID),
                    //    'select' => array('STATUS_ID','NAME','DESCRIPTION','NOTIFY'=>'STATUS.NOTIFY'),
                ));

                while ($status = $statusResult->fetch()) {
                    $arStatusShipment[$status['STATUS_ID']] = $status['NAME'];
                }
                $arStatusSipmentBitrixBD = unserialize($arOptions[$arTab["SITE_ID"]]["STATUS_SHIP_BITRIX"]);
                $arFlagSipmentBitrixBD = unserialize($arOptions[$arTab["SITE_ID"]]["FLAG_SHIPMENT_UP"]);
                $arFlagCancelledBitrixBD = unserialize($arOptions[$arTab["SITE_ID"]]["FLAG_CANCELLED_UP"]);
                $arFlagPaymentBitrixBD = unserialize($arOptions[$arTab["SITE_ID"]]["FLAG_PAYMENT_UP"]);
                ?>
                <tr>
                    <th style="text-align: center"><span class="status_list_span"><?= GetMessage('MAXYSS_OZON_STATUS_LIST_OZON') ?></span><?= GetMessage("MAXYSS_OZON_SETTINGS_STATUS_ORDER_TH") ?></th>
                    <th style="text-align: center"><?= GetMessage("MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_TH") ?></th>
                    <th style="text-align: center"><?= GetMessage("MAXYSS_OZON_SETTINGS_STATUS_CANCELLED_FLAG_TH") ?></th>
                    <th style="text-align: center"><?= GetMessage("MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_FLAG_TH") ?></th>
                    <th style="text-align: center"><?= GetMessage("MAXYSS_OZON_SETTINGS_STATUS_PAYMENT_FLAG_TH") ?></th>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_AWAITING_PACKAGING') ?></span>
                        <select name="AWAITING_PACKAGING[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?echo ($arOptions[$arTab["SITE_ID"]]["AWAITING_PACKAGING"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                                <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][AWAITING_PACKAGING]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["AWAITING_PACKAGING"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td></td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_PACKAGING]"
                               value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_AWAITING_PACKAGING_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_PACKAGING]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['AWAITING_PACKAGING'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_PACKAGING]"
                               value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_AWAITING_PACKAGING_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_PACKAGING]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['AWAITING_PACKAGING'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_AWAITING_DELIVER') ?></span>
                        <select name="AWAITING_DELIVER[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["AWAITING_DELIVER"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][AWAITING_DELIVER]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["AWAITING_DELIVER"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td></td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_DELIVER]"
                               value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_AWAITING_DELIVER_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_DELIVER]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['AWAITING_DELIVER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_DELIVER]"
                               value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_AWAITING_DELIVER_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][AWAITING_DELIVER]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['AWAITING_DELIVER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_NOT_ACCEPTED') ?></span>
                        <select name="NOT_ACCEPTED[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["NOT_ACCEPTED"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][NOT_ACCEPTED]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["NOT_ACCEPTED"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td></td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][NOT_ACCEPTED]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_NOT_ACCEPTED_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][NOT_ACCEPTED]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['NOT_ACCEPTED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][NOT_ACCEPTED]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_NOT_ACCEPTED_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][NOT_ACCEPTED]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['NOT_ACCEPTED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_ARBITRATION') ?></span>
                        <select name="ARBITRATION[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["ARBITRATION"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][ARBITRATION]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["ARBITRATION"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td></td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][ARBITRATION]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_ARBITRATION_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][ARBITRATION]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['ARBITRATION'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][ARBITRATION]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_ARBITRATION_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][ARBITRATION]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['ARBITRATION'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_DELIVERING') ?></span>
                        <select name="DELIVERING[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["DELIVERING"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][DELIVERING]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["DELIVERING"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td></td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERING]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_DELIVERING_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERING]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['DELIVERING'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERING]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_DELIVERING_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERING]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['DELIVERING'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_DRIVER_PICKUP') ?></span>
                        <select name="DRIVER_PICKUP[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["DRIVER_PICKUP"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][DRIVER_PICKUP]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["DRIVER_PICKUP"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td></td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DRIVER_PICKUP]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_DRIVER_PICKUP_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DRIVER_PICKUP]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['DRIVER_PICKUP'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DRIVER_PICKUP]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_DRIVER_PICKUP_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DRIVER_PICKUP]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['DRIVER_PICKUP'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_DELIVERED') ?></span>
                        <select name="DELIVERED[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["DELIVERED"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][DELIVERED]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["DELIVERED"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td></td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERED]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_DELIVERED_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERED]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['DELIVERED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERED]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_DELIVERED_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DELIVERED]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['DELIVERED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_CANCELLED') ?></span>
                        <select name="CANCELLED[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["CANCELLED"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][CANCELLED]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["CANCELLED"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED]" value="N">
                        <input type="checkbox" id="FLAG_CANCELLED_UP_CANCELLED_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED]"
                               value="Y" <? echo ($arFlagCancelledBitrixBD['CANCELLED'] == 'Y' || empty($arFlagCancelledBitrixBD)) ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_CANCELLED_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['CANCELLED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_CANCELLED_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['CANCELLED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                    <!--соответствие статусов возвратов-->
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_accepted_from_customer') ?></span>
                        <select name="ACCEPTED_FROM_CUSTOMER[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["ACCEPTED_FROM_CUSTOMER"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][ACCEPTED_FROM_CUSTOMER]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["ACCEPTED_FROM_CUSTOMER"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][ACCEPTED_FROM_CUSTOMER]" value="N">
                        <input type="checkbox" id="FLAG_CANCELLED_UP_ACCEPTED_FROM_CUSTOMER_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][ACCEPTED_FROM_CUSTOMER]"
                               value="Y" <? echo ($arFlagCancelledBitrixBD['ACCEPTED_FROM_CUSTOMER'] == 'Y' || empty($arFlagCancelledBitrixBD)) ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][ACCEPTED_FROM_CUSTOMER]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_ACCEPTED_FROM_CUSTOMER_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][ACCEPTED_FROM_CUSTOMER]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['ACCEPTED_FROM_CUSTOMER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][ACCEPTED_FROM_CUSTOMER]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_ACCEPTED_FROM_CUSTOMER_<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][ACCEPTED_FROM_CUSTOMER]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['ACCEPTED_FROM_CUSTOMER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_waiting_for_seller') ?></span>
                        <select name="WAITING_FOR_SELLER[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["WAITING_FOR_SELLER"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][WAITING_FOR_SELLER]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["WAITING_FOR_SELLER"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][WAITING_FOR_SELLER]" value="N">
                        <input type="checkbox" id="FLAG_CANCELLED_UP_WAITING_FOR_SELLER<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][WAITING_FOR_SELLER]"
                               value="Y" <? echo ($arFlagCancelledBitrixBD['WAITING_FOR_SELLER'] == 'Y' || empty($arFlagCancelledBitrixBD)) ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][WAITING_FOR_SELLER]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_WAITING_FOR_SELLER<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][WAITING_FOR_SELLER]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['WAITING_FOR_SELLER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][WAITING_FOR_SELLER]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_WAITING_FOR_SELLER<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][WAITING_FOR_SELLER]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['WAITING_FOR_SELLER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_ready_for_shipment') ?></span>
                        <select name="READY_FOR_SHIPMENT[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["READY_FOR_SHIPMENT"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][READY_FOR_SHIPMENT]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["READY_FOR_SHIPMENT"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][READY_FOR_SHIPMENT]" value="N">
                        <input type="checkbox" id="FLAG_CANCELLED_UP_READY_FOR_SHIPMENT<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][READY_FOR_SHIPMENT]"
                               value="Y" <? echo ($arFlagCancelledBitrixBD['READY_FOR_SHIPMENT'] == 'Y' || empty($arFlagCancelledBitrixBD)) ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][READY_FOR_SHIPMENT]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_READY_FOR_SHIPMENT<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][READY_FOR_SHIPMENT]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['READY_FOR_SHIPMENT'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][READY_FOR_SHIPMENT]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_READY_FOR_SHIPMENT<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][READY_FOR_SHIPMENT]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['READY_FOR_SHIPMENT'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_returned_to_seller') ?></span>
                        <select name="RETURNED_TO_SELLER[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["RETURNED_TO_SELLER"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][RETURNED_TO_SELLER]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["RETURNED_TO_SELLER"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][RETURNED_TO_SELLER]" value="N">
                        <input type="checkbox" id="FLAG_CANCELLED_UP_RETURNED_TO_SELLER<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][RETURNED_TO_SELLER]"
                               value="Y" <? echo ($arFlagCancelledBitrixBD['RETURNED_TO_SELLER'] == 'Y' || empty($arFlagCancelledBitrixBD)) ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][RETURNED_TO_SELLER]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_RETURNED_TO_SELLER<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][RETURNED_TO_SELLER]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['RETURNED_TO_SELLER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][RETURNED_TO_SELLER]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_RETURNED_TO_SELLER<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][RETURNED_TO_SELLER]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['RETURNED_TO_SELLER'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_cancelled_with_compensation') ?></span>
                        <select name="CANCELLED_WITH_COMPENSATION[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["CANCELLED_WITH_COMPENSATION"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][CANCELLED_WITH_COMPENSATION]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["CANCELLED_WITH_COMPENSATION"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>

                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED_WITH_COMPENSATION]" value="N">
                        <input type="checkbox" id="FLAG_CANCELLED_UP_CANCELLED_WITH_COMPENSATION<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED_WITH_COMPENSATION]"
                               value="Y" <? echo ($arFlagCancelledBitrixBD['CANCELLED_WITH_COMPENSATION'] == 'Y' || empty($arFlagCancelledBitrixBD)) ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED_WITH_COMPENSATION]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_CANCELLED_WITH_COMPENSATION<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED_WITH_COMPENSATION]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['CANCELLED_WITH_COMPENSATION'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED_WITH_COMPENSATION]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_CANCELLED_WITH_COMPENSATION<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][CANCELLED_WITH_COMPENSATION]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['CANCELLED_WITH_COMPENSATION'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-r">
                        <span class="status_list_span"><?= GetMessage('MAXYSS_OZON_disposed') ?></span>
                        <select name="DISPOSED[<?= $arTab["SITE_ID"] ?>]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatus as $key => $type) {
                                ?>
                                <option value="<?= $key ?>" <?
                                echo ($arOptions[$arTab["SITE_ID"]]["DISPOSED"] == $key) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td class="adm-detail-content-cell-r">
                        <select name="STATUS_SHIP_BITRIX[<?= $arTab["SITE_ID"] ?>][DISPOSED]">
                            <option value=""><?= GetMessage('MAXYSS_OZON_SETTINGS_STATUS_SHIPMENT_NOT_USE_TEXT') ?></option>
                            <?
                            foreach ($arStatusShipment as $key_h => $type) {
                                ?>
                                <option value="<?= $key_h ?>" <?
                                echo ($arStatusSipmentBitrixBD["DISPOSED"] == $key_h) ? 'selected = "selected"' : '' ?>><?= $type ?></option>
                            <?
                            } ?>
                        </select>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][DISPOSED]" value="N">
                        <input type="checkbox" id="FLAG_CANCELLED_UP_DISPOSED<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_CANCELLED_UP[<?= $arTab["SITE_ID"] ?>][DISPOSED]"
                               value="Y" <? echo ($arFlagCancelledBitrixBD['DISPOSED'] == 'Y' || empty($arFlagCancelledBitrixBD)) ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DISPOSED]" value="N">
                        <input type="checkbox" id="FLAG_SHIPMENT_UP_DISPOSED<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_SHIPMENT_UP[<?= $arTab["SITE_ID"] ?>][DISPOSED]"
                               value="Y" <? echo ($arFlagSipmentBitrixBD['DISPOSED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                    <td style="text-align: center" class="adm-detail-content-cell-r">
                        <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DISPOSED]" value="N">
                        <input type="checkbox" id="FLAG_PAYMENT_UP_DISPOSED<?= $arTab["SITE_ID"] ?>"
                               name="FLAG_PAYMENT_UP[<?= $arTab["SITE_ID"] ?>][DISPOSED]"
                               value="Y" <? echo ($arFlagPaymentBitrixBD['DISPOSED'] == 'Y') ? 'checked = "checked"' : '' ?>>
                    </td>
                </tr>
                    <!--соответствие статусов возвратов-->
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_STATUS_NO_CHANGE') ?></td>
                    <td class="adm-detail-content-cell-r">
                        <input type="hidden" name="status_no_change[<?= $arTab["SITE_ID"] ?>]" value="N">
                        <input type="checkbox" name="status_no_change[<?= $arTab["SITE_ID"] ?>]"
                               id="status_no_change_<?= $arTab["SITE_ID"] ?>" class="adm-designed-checkbox" <?
                        echo ($arOptions[$arTab["SITE_ID"]]["STATUS_NO_CHANGE"] == 'Y') ? 'checked = "checked"' : '' ?>
                               value="Y">
                        <label class="adm-designed-checkbox-label" for="status_no_change_<?= $arTab["SITE_ID"] ?>"
                               title=""></label><span
                                data-hint="<?= GetMessage('MAXYSS_OZON_STATUS_NO_CHANGE_TIP') ?>"></span>
                    </td>
                </tr>
                <!--соответствие статусов-->
                
                <tr class="heading">
                    <td colspan="5"><?= GetMessage('MAXYSS_OZON_PERIOD_AGENT') ?></td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_PERIOD_AGENT_TIME') ?></td>
                    <td class="adm-detail-content-cell-r">
                        <input name="period_order[<?= $arTab["SITE_ID"] ?>]" value="<?
                        echo ($arOptions[$arTab["SITE_ID"]]["PERIOD_ORDER"] != '') ? $arOptions[$arTab["SITE_ID"]]["PERIOD_ORDER"] : "1200" ?>">
                    </td>
                </tr>

                <tr class="heading">
                    <td colspan="5"><?= GetMessage('MAXYSS_OZON_PERIOD_ORDER') ?></td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?= GetMessage('MAXYSS_OZON_PERIOD_ORDER_DAY') ?></td>
                    <td class="adm-detail-content-cell-r">
                        <input name="period_order_day[<?= $arTab["SITE_ID"] ?>]" type="number" value="<?
                        echo ($arOptions[$arTab["SITE_ID"]]["PERIOD_ORDER_DAY"] != '') ? $arOptions[$arTab["SITE_ID"]]["PERIOD_ORDER_DAY"] : "7" ?>"><span
                                data-hint="<?= GetMessage('MAXYSS_OZON_PERIOD_ORDER_DAY_TIP') ?>"></span>
                    </td>
                </tr>
                <!--        </div>-->
            <?
            } ?>
            <?
            $tabControl->Buttons(array(
                "back_url" => MAXYSS_MODULE_NAME . "_ozon_maxyss_general.php?lang=" . LANGUAGE_ID,

            )); ?>

            <?
            $tabControl->End(); ?>
        </form>
    <?
    } ?>
    <script type="text/javascript">
        $(document).on("change", "input[name='property_order_ozon']", function () {
            var val_prop = $(this).val();
            $("input[name='property_order_ozon']").each(function (index, value) {
                $(this).val(val_prop);
            })
        });
    </script>
<?
} else
    die();
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'); ?>
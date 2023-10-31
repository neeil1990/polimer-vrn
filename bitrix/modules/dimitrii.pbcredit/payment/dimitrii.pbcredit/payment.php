<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?><?
include(GetLangFileName(dirname(__FILE__) . "/", "/pochtabank.php"));

CJSCore::Init(array('jquery'));
Bitrix\Main\Page\Asset::getInstance()->addJs('https://my.pochtabank.ru/sdk/v1/pos-credit.js');

function phone_number($sPhone)
{
    $sPhone = preg_replace("[^0-9]", '', $sPhone);
    if (strlen($sPhone) != 11) {
        if (strlen($sPhone) > 11) {
            return substr($sPhone, 0, 11);
        } else if (strlen($sPhone) == 10) {
            return ('7' . $sPhone);
        } else return ('');
    }
    return ($sPhone);
}

$arParams = Array();
$ORDER_ID = (strlen(CSalePaySystemAction::GetParamValue("ORDER_ID")) > 0) ? CSalePaySystemAction::GetParamValue("ORDER_ID") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"];
$ORDER = CSaleOrder::GetByID($ORDER_ID);
//$SITE_NAME = COption::GetOptionString("main", "server_name", "");
//$dateInsert = (strlen(CSalePaySystemAction::GetParamValue("DATE_INSERT")) > 0) ? CSalePaySystemAction::GetParamValue("DATE_INSERT") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"];
//$arParams['issuer_id'] = base64_encode($ORDER_ID);
$arParams['ttCode'] = (strlen(CSalePaySystemAction::GetParamValue("KEY")) > 0) ? CSalePaySystemAction::GetParamValue("KEY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["KEY"];
$arParams['ttName'] = (strlen(CSalePaySystemAction::GetParamValue("NAME")) > 0) ? CSalePaySystemAction::GetParamValue("NAME") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["NAME"];
$arParams['fullName'] = $GLOBALS["SALE_INPUT_PARAMS"]["PROPERTY"]["FIO"];
$arParams['phone'] = phone_number($GLOBALS["SALE_INPUT_PARAMS"]["PROPERTY"]["PHONE"]);
$arParams['category'] = (strlen(CSalePaySystemAction::GetParamValue("CATEGORY")) > 0) ? CSalePaySystemAction::GetParamValue("CATEGORY") : "";

$obBasket = \Bitrix\Sale\Basket::getList(array('filter' => array('ORDER_ID' => $ORDER_ID)));
$bGetBasket = false;
$arItems = Array();
while ($bItem = $obBasket->Fetch()) {
    $arItems[] = array(
        'model' => $bItem['NAME'],
        'price' => $bItem['PRICE'],
        'quantity' => $bItem['QUANTITY']
    );
    $bGetBasket = true;
}
if ($bGetBasket) {
    $arParams['manualOrderInput'] = false;
    $arParams['order'] = $arItems;
} else {
    $arParams['manualOrderInput'] = true;
    $arParams['payAmount'] = $ORDER["PRICE"];
}
$arOrderParams = array(
    'ACTION' => 'SET_ID',
    'ORDER' => array(
        'ID' => $ORDER_ID,)
);
?>

<? if ($ORDER['PS_STATUS_CODE'] == null): ?>

    <a id="pos-credit-open" href="javascript:void(null);"><?=GetMessage("DI_MESS_MAKE_CREDIT")?></a>

<? else: ?>

    <div id="pos-credit-result">
        <?=GetMessage("DI_MESS_CREDIT_ID")?> <span><?=$ORDER['PS_STATUS_MESSAGE']?></span>
    </div>

<? endif ?>

<div id="pos-credit-container" data-status="<?= $ORDER['PS_STATUS_CODE'] ?>"></div>
<script>

    window.pbcContainer = '#pos-credit-container';
    window.pbcUrl = '/bitrix/admin/dimitrii.pbcredit_ajax.php?<?=bitrix_sessid_get()?>';
    window.pbcData = <?=CUtil::PhpToJSObject($arOrderParams, false, true)?>;
    window.pbcSettings = <?=CUtil::PhpToJSObject($arParams, false, true)?>;

    if (window.pbcSettings.order) {
        window.pbcSettings.order = window.pbcSettings.order.map(function (order) {
            order.price = parseFloat(order.price);
            order.quantity = parseInt(order.quantity);
            return order
        });
    }

    $(document).ready(function () {

        function mount(container, options) {
            var status = $(container).attr('data-status');
            if (!status) {
                window.PBSDK.posCredit.mount(container, options);
            } else {
                console.log('<?=GetMessage("DI_MESS_CREDIT_ALREADY_REG")?>');
            }
        }


        window.PBSDK.posCredit.on('done', function (id) {
            console.log('ID: ' + id);
            window.pbcData['ORDER']['PAY_ID'] = id;
            $.ajax({
                url: window.pbcUrl,
                cache: false,
                data: window.pbcData,
                success: function (result) {
                    console.log(result);
                }
            });
        });

        mount(window.pbcContainer, window.pbcSettings);

        $("#pos-credit-open").on("click", function (event) {
            event.preventDefault();
            mount(window.pbcContainer, window.pbcSettings);
            var id = $(this);
            var top = $(id).offset().top;
            $('body,html').animate({scrollTop: top}, 1500);
        });

    });
</script>




<?

$isUnitellerOrder = false;

$orderId = $sOrderID = $_REQUEST['ID'];
$orderinfo = CSaleOrder::getById($orderId);

if ($arPaySys = CSalePaySystem::GetByID($orderinfo['PAY_SYSTEM_ID'])) {   
    if(stripos($arPaySys['ACTION_FILE'], "step2use.uniteller")!==false) {
        $isUnitellerOrder = true;
    }
}
//var_dump($isUnitellerOrder);
if($isUnitellerOrder) {


CJSCore::Init(array("jquery"));

//include(GetLangFileName(dirname(__FILE__) . '/', '/uniteller.php'));
if (!class_exists('ps_uniteller')) {
	include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/step2use.uniteller/payment/step2use.uniteller/tools.php');
}

$basketItems = CSaleBasket::GetList(array(), array("ORDER_ID" => $orderId),false, false, array('NAME', 'PRICE', 'QUANTITY', 'PRODUCT_ID'));
while($item = $basketItems->GetNext()){
    $arBasketItems[] = $item;
    //$itemsID[] = $item['PRODUCT_ID'];
}

$deliveryObj = Bitrix\Sale\Delivery\Services\Table::getList(array(
    'filter' => array(
        'ID' => $orderinfo['DELIVERY_ID'],
    ),
    'select' => array('CODE', 'NAME')
));
if($deliveryInfo = $deliveryObj->Fetch()) {
    $arBasketItems[] = [
        'NAME' => $deliveryInfo['NAME'],
        'PRICE' => $orderinfo['PRICE_DELIVERY'],
        'QUANTITY' => 1,
    ];
}


//var_dump($orderinfo);

ps_uniteller::setMerchantData($orderId);

$sHouldPay = sprintf('%01.2f', CSalePaySystemAction::GetParamValue('SHOULD_PAY'));

//var_dump($sHouldPay);

?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/md5.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/sha256.js"></script>

<script>
// Create Base64 Object
var Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/\r\n/g,"\n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}

var atlUnitellerPassword = '<?=ps_uniteller::$Password?>';
var atlUnitellerShopID = '<?=ps_uniteller::$Shop_ID?>';

function atl_uniteller_open_send_order_reciept(orderId) {
    //alert(orderId);
    
    var html = $('#atl-uniteller-form-send-reciept-wrapper').parent().html();
		$('#atl-uniteller-form-send-reciept-wrapper').replaceWith('');
    
    atlUnitellerSendOrderRecieptDialog = new BX.CDialog({
        title: "<?=GetMessage('ATL_UNITELLER_BTN_CREATE_RECEIPT_TITLE')?>",
        content: html,
        icon: 'head-block',
        resizable: true,
        draggable: true,
        height: '500',
        width: '800',
        buttons: [
            '<input type=\"button\" value=\"<?=GetMessage('ATL_SEND')?>\" class=\"adm-btn-save\" id=\"atl-uniteller-send-order-reciept\"/>',
        ]
    });
    atlUnitellerSendOrderRecieptDialog.Show();
}
    
$(function() {
    
    $('.adm-detail-toolbar').find('.adm-detail-toolbar-right').prepend("<a href='javascript:void(0)' onclick='atl_uniteller_open_send_order_reciept(\"<?=$orderId?>\")' class='adm-btn'><?=GetMessage('ATL_UNITELLER_BTN_CREATE_RECEIPT_TITLE')?></a>");
    
    //
});

$("body").on("click", "#atl-uniteller-order-lines-wrapper .item-wrapper .close", function(e) {
    e.preventDefault();
    console.log("close");
    if($("#atl-uniteller-order-lines-wrapper .item-wrapper").length>1) {
        $(this).parents(".item-wrapper:first").remove();
    }
});

$("body").on("click", "#atl-uniteller-order-lines-add-line", function(e) {
    console.log("add");
    e.preventDefault();
    $("#atl-uniteller-order-lines-wrapper .item-wrapper:last").clone().appendTo($("#atl-uniteller-order-lines-wrapper"));
    
});

$("body").on("click", "#atl-uniteller-send-order-reciept", function(e) {
    //alert("send");
    
    var reciept = {
        "lines": [
            {
                "vat": -1,
                "taxmode": 2,
                "name": "",
                "price": 0,
                "qty": 1,
                "sum": 0,
                "payattr": 4,
                "lineattr": 3
            }
        ],
        "total": 0,
        "taxmode": 2,
        "payments": [
            {
                "kind": 4,
                "type": 4,
                "amount": 0
            }
        ]
    };
    
    if($("#atl-uniteller-order-kind").val()) {
        reciept.payments[0].kind = $("#atl-uniteller-order-kind").val();
    }
    if($("#atl-uniteller-order-type").val()) {
        reciept.payments[0].type = $("#atl-uniteller-order-type").val();
    }
    if($("#atl-uniteller-order-taxmode").val()) {
        reciept.taxmode = $("#atl-uniteller-order-taxmode").val();
    }
    
    var lines = [];
    var linesSum = 0;
    $("#atl-uniteller-order-lines-wrapper .item-wrapper").each(function(i, el) {
        var line = {};
        line.name = $(this).find('.line-name:first').val();
        line.price = parseFloat($(this).find('.line-price:first').val());
        line.qty = parseInt($(this).find('.line-qt:first').val());
        line.sum = line.price * line.qty;
        line.payattr = $(this).find('.line-payattr:first').val();
        line.lineattr = $(this).find('.line-lineattr:first').val();
        line.vat = $(this).find('.line-vat:first').val();
        line.taxmode = reciept.taxmode;
        lines.push(line);
        linesSum += line.sum;
    });
    reciept.lines = lines;
    
    reciept.payments[0].amount = linesSum;
    reciept.total = linesSum;
    var recieptString = Base64.encode(JSON.stringify(reciept));
    
    //console.log(reciept);
    //console.log(Base64.encode(JSON.stringify(reciept)));
    
    // генерим Чек
    $("#atl-uniteller-form-send-reciept input[name='Receipt']").val(recieptString);
    
    // генерим подпись Чека
    /**
     * strtoupper(hash('sha256', hash('sha256', self::$Shop_ID) . "&" . hash('sha256', $orderId) . "&" . hash('sha256', $sHouldPay) . "&" . hash('sha256', $Receipt) . "&" . hash('sha256', self::$Password)));
     */
    var signReceipt = CryptoJS.SHA256(atlUnitellerShopID) + '&' 
                        + CryptoJS.SHA256($("#atl-uniteller-order-id").val()) + '&' 
                        + CryptoJS.SHA256($("#atl-uniteller-order-sum").val()) + '&' 
                        + CryptoJS.SHA256(recieptString) + '&' 
                        + CryptoJS.SHA256(atlUnitellerPassword);
    signReceipt = CryptoJS.SHA256(signReceipt).toString().toUpperCase();
    
    $("#atl-uniteller-form-send-reciept input[name='ReceiptSignature']").val(signReceipt);
    //CryptoJS.SHA256("Message")
    
    
    // генерим подпись Запроса
    /**
     * $signature = strtoupper(md5(md5(ps_uniteller::$Shop_ID) . '&' . md5($sOrderID) . '&' . md5($sHouldPay)
     *           . '&' . md5('') . '&' . md5('') . '&' . md5($sLiftime) . '&' . md5('') . '&' . md5('') . '&' . md5('')
     *           . '&' . md5('') . '&' . md5(ps_uniteller::$Password)));
     */
    var sign = CryptoJS.MD5(atlUnitellerShopID) + '&' 
                + CryptoJS.MD5($("#atl-uniteller-order-id").val()) + '&' 
                + CryptoJS.MD5($("#atl-uniteller-order-sum").val()) + '&' 
                + CryptoJS.MD5('') + '&' 
                + CryptoJS.MD5('') + '&' 
                + CryptoJS.MD5('0') + '&' 
                + CryptoJS.MD5('') + '&' 
                + CryptoJS.MD5('') + '&' 
                + CryptoJS.MD5('') + '&' 
                + CryptoJS.MD5('') + '&' 
                + CryptoJS.MD5(atlUnitellerPassword);
    sign = CryptoJS.MD5(sign).toString().toUpperCase();
    $("#atl-uniteller-form-send-reciept input[name='Signature']").val(sign);
    
    $("#atl-uniteller-form-send-reciept").submit();
});
</script>

<div style='display:none'>
	<div id='atl-uniteller-form-send-reciept-wrapper'>
        <form action="<?= ps_uniteller::$url_uniteller_pay ?>" method="post" target="_blank" id="atl-uniteller-form-send-reciept">
        
            <input type="hidden" name="Shop_IDP"
                value="<?= ps_uniteller::$Shop_ID ?>">
            
            <input type="hidden"
                   name="Signature" value="">
            
            <input type="hidden" name="Receipt"
                   value="">
            <input type="hidden" name="ReceiptSignature"
                   value="">
                
            <?if($sCurrency):?>
            <input type="hidden" name="Currency" value="<?=$sCurrency?>">
            <?endif;?>
            
            <div>
                <label for="Order_IDP"><?=GetMessage('ATL_UNITELLER_ORDER_NUM')?></label><br/>
                <input id="atl-uniteller-order-id" type="text" name="Order_IDP" value="<?=$sOrderID?>/1">
                <br/><br/>
            </div>
            
            <div>
                <label for="Subtotal_P"><?=GetMessage('ATL_UNITELLER_SUM')?></label><br/>
                <input id="atl-uniteller-order-sum"
                    type="text" name="Subtotal_P"
                    value="0"> <? /* <?= (str_replace(',', '.', $sHouldPay)) ?> */ ?>
                <br/><br/>
            </div>
        
            <div>
                <label for="taxmode"><?=GetMessage('ATL_UNITELLER_TAXMODE')?></label><br/>
                <select name="taxmode" id="atl-uniteller-order-taxmode">
                    <?for($i=0;$i<=5;$i++):?>
                    <option value="<?=$i?>" <?if($i==0) {echo 'selected';}?>><?=$i?> - <?=GetMessage('ATL_UNITELLER_TAXMODE_VAL_'.$i)?></option>
                    <?endfor;?>
                </select>
                <br/><br/>
            </div>
            
            <div>
                <label for="kind"><?=GetMessage('ATL_UNITELLER_KIND')?></label><br/>
                <select name="kind" id="atl-uniteller-order-kind">
                    <?for($i=1;$i<=4;$i++):?>
                    <option value="<?=$i?>" <?if($i==4) {echo 'selected';}?>><?=GetMessage('ATL_UNITELLER_KIND_VAL_'.$i)?></option>
                    <?endfor;?>
                </select>
                <br/><br/>
            </div>
            
            <div>
                <label for="type"><?=GetMessage('ATL_UNITELLER_KIND')?></label><br/>
                <select name="type" id="atl-uniteller-order-type">
                    <?for($i=1;$i<=14;$i++):?>
                    <option value="<?=$i?>" <?if($i==4) {echo 'selected';}?>><?=$i?> - <?=GetMessage('ATL_UNITELLER_TYPE_VAL_'.$i)?></option>
                    <?endfor;?>
                </select>
                <br/><br/>
            </div>
            
            <div id="atl-uniteller-order-lines-wrapper">
                <div style="margin-bottom: 10px;"><label><?=GetMessage('ATL_UNITELLER_RECEIPT_LINES')?>:</label></div>
                <?foreach($arBasketItems as $item):?>
                    <div class="item-wrapper">
                        <div><?=GetMessage('ATL_UNITELLER_NAME')?>:</div>
                        <div><textarea style="width: 90%;" class="line-name"><?=$item['NAME']?></textarea></div>
                        
                        <div><?=GetMessage('ATL_UNITELLER_PRICE')?>:</div>
                        <div><input type="text" size="4" value="<?=round($item['PRICE'], 2)?>" class="line-price"></div>
                        
                        <div><?=GetMessage('ATL_UNITELLER_QT')?>:</div>
                        <div><input type="text" size="4" value="<?=$item['QUANTITY']?>" class="line-qt"></div>
                        
                        <div><?=GetMessage('ATL_UNITELLER_PAYATTR')?></div>
                        <div>
                            <select name="payattr" class="line-payattr">
                                <option value="1">1 - <?=GetMessage('ATL_UNITELLER_PAYATTR_VAL_1')?></option>
                                <option value="2">2 - <?=GetMessage('ATL_UNITELLER_PAYATTR_VAL_2')?></option>
                                <option value="3">3 - <?=GetMessage('ATL_UNITELLER_PAYATTR_VAL_3')?></option>
                                <option value="4" selected>4 - <?=GetMessage('ATL_UNITELLER_PAYATTR_VAL_4')?></option>
                                <option value="5">5 - <?=GetMessage('ATL_UNITELLER_PAYATTR_VAL_5')?></option>
                                <option value="6">6 - <?=GetMessage('ATL_UNITELLER_PAYATTR_VAL_6')?></option>
                                <option value="7">7 - <?=GetMessage('ATL_UNITELLER_PAYATTR_VAL_7')?></option>
                            </select>
                        </div>
                        
                        <div><?=GetMessage('ATL_UNITELLER_LINEATTR')?></div>
                        <div>
                            <select name="lineattr" class="line-lineattr">
                                <?for($i=1;$i<=19;$i++):?>
                                <option value="<?=$i?>" <?if($i==1) {echo 'selected';}?>><?=$i?> - <?=GetMessage('ATL_UNITELLER_LINEATTR_VAL_'.$i)?></option>
                                <?endfor;?>
                            </select>
                        </div>
                        
                        <div><?=GetMessage('ATL_UNITELLER_VAT')?></div>
                        <div>
                            <select name="vat" class="line-vat">
                                <option value="-1" selected><?=GetMessage('ATL_UNITELLER_VAT_-1')?></option>
                                <option value="0"><?=GetMessage('ATL_UNITELLER_VAT_0')?></option>
                                <option value="10"><?=GetMessage('ATL_UNITELLER_VAT_10')?></option>
                                <option value="20"><?=GetMessage('ATL_UNITELLER_VAT_20')?></option>
                                <option value="110"><?=GetMessage('ATL_UNITELLER_VAT_110')?></option>
                                <option value="120"><?=GetMessage('ATL_UNITELLER_VAT_120')?></option>
                            </select>
                        </div>
                        
                        

                        
                        
                        
                        <a href="#" class="close">x</a>
                    </div>
                <?endforeach;?>
            </div>
            <a href="#" id="atl-uniteller-order-lines-add-line"><?=GetMessage('ATL_UNITELLER_ADD_LINE')?></a>
            <?//var_dump(ps_uniteller::$Shop_ID)?>
        </form>
    </div>
</div>

<style>
#atl-uniteller-order-lines-wrapper {
    
    padding: 10px;
}
#atl-uniteller-order-lines-wrapper .item-wrapper {
    background: #f3f3f3;
    padding: 10px 10px 30px 10px;
    margin-bottom: 10px;
    position: relative;
}
#atl-uniteller-order-lines-wrapper .item-wrapper .close {
    position: absolute;
    right: 5px;
    bottom: 0;
    font-size: 18px;
    text-decoration: none;
}

#atl-uniteller-order-lines-add-line {
    margin-left: 10px;
}
</style>
<?
}
?>
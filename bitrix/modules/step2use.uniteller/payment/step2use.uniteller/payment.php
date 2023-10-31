<?php
/**
 *
 * @author јтлант mp@atlant2010.ru
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
include(GetLangFileName(dirname(__FILE__) . '/', '/uniteller.php'));
if (!class_exists('ps_uniteller')) {
	include(dirname(__FILE__) . '/tools.php');
}

$app = \Bitrix\Main\Application::getInstance();
$request = $app->getContext()->getRequest();

$sOrderID = (strlen(CSalePaySystemAction::GetParamValue('ORDER_ID')) > 0) ? CSalePaySystemAction::GetParamValue('ORDER_ID') : $GLOBALS['SALE_INPUT_PARAMS']['ORDER']['ID'];

$aCheckData = array();
//???? ?????????? ????? ??????

if (!CSaleOrder::GetByID($sOrderID)) {
	$res = CSaleOrder::GetList(array(), Array("ACCOUNT_NUMBER" => $sOrderID));
	while($ob = $res->GetNext(false,false)):
	   $orderID = (int)$ob['ID'];
	endwhile;
	$arOrder = CSaleOrder::GetByID($orderID);
	ps_uniteller::doSyncStatus($arOrder, $aCheckData);
	ps_uniteller::setMerchantData($orderID);
} else {
	 $arOrder = CSaleOrder::GetByID($sOrderID);
	 ps_uniteller::doSyncStatus($arOrder, $aCheckData);
	 ps_uniteller::setMerchantData($sOrderID);
	 $orderID = $sOrderID;

}

// Ёто дл€ частичной оплаты - пока закомментим, т.к. со стороны Uniteller не готова реализаци€ (см. переписку по email)
/*
if ($GLOBALS['SALE_INPUT_PARAMS']['ORDER']['PRICE'] != $GLOBALS['SALE_INPUT_PARAMS']['PAYMENT']['SUM']) {
	$sOrderID = $sOrderID ."-". $GLOBALS['SALE_INPUT_PARAMS']['PAYMENT']['ID'];
}
//var_dump($GLOBALS['SALE_INPUT_PARAMS']['PAYMENT']['ACCOUNT_NUMBER']);
//var_dump(CSalePaySystemAction::GetParamValue('SHOULD_PAY'));
*/

// ???? ???? ??????, ?? ??????? ?????? ??????.
if ($arOrder['PAYED']=='Y'/*$aCheckData['response_code'] !== '' && $aCheckData['status'] !== 'Not Authorized'*/) {
	$arCurrentStatus = CSaleStatus::GetByID($arOrder['STATUS_ID']);
	echo '<br><strong>' . $arCurrentStatus['NAME'] . '</strong>'; 
} else {
	// ???? ?????? ??? ?? ???? ???????????, ?? ??????? ????? ??? ?????? ??????.
	$sDateInsert = (strlen(CSalePaySystemAction::GetParamValue('DATE_INSERT')) > 0) ? CSalePaySystemAction::GetParamValue('DATE_INSERT') : $GLOBALS['SALE_INPUT_PARAMS']['ORDER']['DATE_INSERT'];
	$sDateInsert = trim($sDateInsert);


	$includeDelivery = CSalePaySystemAction::GetParamValue('INCLUDE_DELIVERY') == 'Y';
	$deliveryPrice = $GLOBALS['SALE_INPUT_PARAMS']['ORDER']['PRICE_DELIVERY'];
	$deliveryId = $GLOBALS['SALE_INPUT_PARAMS']['ORDER']['DELIVERY_ID'];
	if($includeDelivery){
		$fHouldPay = (strlen(CSalePaySystemAction::GetParamValue('SHOULD_PAY')) > 0) ? CSalePaySystemAction::GetParamValue('SHOULD_PAY') : $GLOBALS['SALE_INPUT_PARAMS']['ORDER']['SHOULD_PAY'];
		$sHouldPay = sprintf('%01.2f', $fHouldPay);

	}else{

		$fHouldPay = (strlen(CSalePaySystemAction::GetParamValue('SHOULD_PAY')) > 0) ? CSalePaySystemAction::GetParamValue('SHOULD_PAY') : $GLOBALS['SALE_INPUT_PARAMS']['ORDER']['SHOULD_PAY'];

		$fHouldPay = $fHouldPay - $deliveryPrice;
		$sHouldPay = sprintf('%01.2f', $fHouldPay);
	}
	//echo "<pre>"; print_r($GLOBALS['SALE_INPUT_PARAMS']['ORDER']); echo "</pre>";
	$sCurrency = (strlen(CSalePaySystemAction::GetParamValue('CURRENCY')) > 0) ? CSalePaySystemAction::GetParamValue('CURRENCY') : $GLOBALS['SALE_INPUT_PARAMS']['ORDER']['CURRENCY'];
	$sCurrency = trim($sCurrency);

	$iLiftime = (int)CSalePaySystemAction::GetParamValue('LIFE_TIME');
	$URL_RETURN_OK = trim(CSalePaySystemAction::GetParamValue('SUCCESS_URL'));
	$URL_RETURN_NO = trim(CSalePaySystemAction::GetParamValue('FAIL_URL'));

	if ($iLiftime > 0) {
		$sLiftime = (string)$iLiftime;
//		$signature = strtoupper(md5(ps_uniteller::$Shop_ID . $sOrderID . $sHouldPay . $iLiftime . ps_uniteller::$Password));
	} else {
		$sLiftime = '';
//		$signature = strtoupper(md5(ps_uniteller::$Shop_ID . $sOrderID . $sHouldPay . ps_uniteller::$Password));
	}
	
//	da(ps_uniteller::$Shop_ID);
//	da($sOrderID);
//	da($sHouldPay);
//	da($sLiftime);
//	da(ps_uniteller::$Password);
?>

<form action="<?= ps_uniteller::$url_uniteller_pay ?>" method="post" <?/*target="_blank"*/?> id="s2u-uniteller-payment-form">
	<font class="tablebodytext">
		<? /* ???? ???????????? ????? ?????? ? ????????????? */ ?>
		<?
        $useFiskal = CSalePaySystemAction::GetParamValue('USE_FISKAL') == 'Y';
        $taxmode = CSalePaySystemAction::GetParamValue('TAXMODE');
        
        // определ€ем "ѕризнак способа расчета"
        $payattr = CSalePaySystemAction::GetParamValue('PAYATTR');
        // по-умолчанию - ѕолна€ предварительна€ оплата до момента передачи предмета расчЄта
        if(!$payattr) {
            $payattr = 1;
        }
        
        // определ€ем "ѕризнак предмета расчета"
        $lineattr = CSalePaySystemAction::GetParamValue('LINEATTR');
        // по-умолчанию - о реализуемом товаре, за исключением подакцизного товара (наименование и иные сведени€, описывающие товар
        if(!$lineattr) {
            $lineattr = 1;
        }

        $Receipt = ps_uniteller::buildReceipt($sOrderID, $taxmode, $includeDelivery, $deliveryPrice, $deliveryId, $payattr, $lineattr);
        $ReceiptDecoded = json_decode(base64_decode($Receipt), true);
        //var_dump($ReceiptDecoded);exit;
        $sHouldPay = $ReceiptDecoded['total'];
        
        $signature = strtoupper(md5(md5(ps_uniteller::$Shop_ID) . '&' . md5($sOrderID) . '&' . md5($sHouldPay)
            . '&' . md5('') . '&' . md5('') . '&' . md5($sLiftime) . '&' . md5('') . '&' . md5('') . '&' . md5('')
            . '&' . md5('') . '&' . md5(ps_uniteller::$Password)));
        
        $ReceiptSignature = ps_uniteller::getReceiptSignature($Receipt, $sOrderID, $sHouldPay);	
        //echo gettype($sOrderID).'</br>'.$taxmode.'</br>'.$includeDelivery.'</br>'.$deliveryPrice.'</br>'.$deliveryId;die;
        
        
        ?>
        
    <br><?= GetMessage('SUSP_ACCOUNT_NO') ?>
	<?//var_dump($GLOBALS['SALE_INPUT_PARAMS']['ORDER']);?>
	<?= $sOrderID . GetMessage('SUSP_ORDER_FROM') . date("d.m.Y H:i:s", strtotime($sDateInsert)) ?><br> <?= GetMessage('SUSP_ORDER_SUM') ?><b><?= SaleFormatCurrency($sHouldPay, $sCurrency) ?>
	</b><br> <br>

		<?if($useFiskal):?>
				<input type="hidden" name="Receipt"
					   value="<?= $Receipt ?>">
				<input type="hidden" name="ReceiptSignature"
					   value="<?= $ReceiptSignature ?>">
		<?endif;?>

		<input type="hidden" name="Shop_IDP"
		value="<?= ps_uniteller::$Shop_ID ?>">
        <?if($sCurrency):?>
        <input type="hidden" name="Currency" value="<?=$sCurrency?>">
        <?endif;?>
		<input type="hidden" name="Order_IDP" value="<?=$sOrderID?>">
		<input
		type="hidden" name="Subtotal_P"
		value="<?= (str_replace(',', '.', $sHouldPay)) ?>"> <?if ($iLiftime > 0):?>
		<input type="hidden" name="Lifetime"
		value="<?= $iLiftime ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('LANGUAGE')) > 0):?>
		<input type="hidden" name="Language"
		value="<?= substr(CSalePaySystemAction::GetParamValue('LANGUAGE'), 0, 2) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('COMMENT')) > 0):?> <input
		type="hidden" name="Comment"
		value="<?= substr(CSalePaySystemAction::GetParamValue('COMMENT'), 0, 255) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('COUNTRY')) > 0):?> <input
		type="hidden" name="Country"
		value="<?= substr(CSalePaySystemAction::GetParamValue('COUNTRY'), 0, 3) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('STATE')) > 0):?> <input
		type="hidden" name="State"
		value="<?= substr(CSalePaySystemAction::GetParamValue('STATE'), 0, 3) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('FIRST_NAME')) > 0):?>
		<input type="hidden" name="FirstName"
		value="<?= substr(CSalePaySystemAction::GetParamValue('FIRST_NAME'), 0, 64) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('LAST_NAME')) > 0):?>
		<input type="hidden" name="LastName"
		value="<?= substr(CSalePaySystemAction::GetParamValue('LAST_NAME'),0 , 64) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('MIDDLE_NAME')) > 0): ?>
		<input type="hidden" name="MiddleName"
		value="<?= substr(CSalePaySystemAction::GetParamValue('MIDDLE_NAME'), 0, 64) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('EMAIL')) > 0): ?> <input
		type="hidden" name="Email"
		value="<?= substr(CSalePaySystemAction::GetParamValue('EMAIL'), 0, 64) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('PHONE')) > 0): ?> <input
		type="hidden" name="Phone"
		value="<?= substr(CSalePaySystemAction::GetParamValue('PHONE'), 0 , 64) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('ADDRESS')) > 0): ?>
		<input type="hidden" name="Address"
		value="<?= substr(CSalePaySystemAction::GetParamValue('ADDRESS'), 0, 128) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('CITY')) > 0): ?> <input
		type="hidden" name="City"
		value="<?= substr(CSalePaySystemAction::GetParamValue('CITY'), 0, 64) ?>"> <?endif;?>
		<?if (strlen(CSalePaySystemAction::GetParamValue('ZIP')) > 0): ?> <input
		type="hidden" name="Zip"
		value="<?= substr(CSalePaySystemAction::GetParamValue('ZIP'), 0, 64) ?>"> <?endif;?>
		<?if (strlen($signature) > 0): ?> <input type="hidden"
		name="Signature" value="<?= $signature ?>"> <?endif;?> <?if (strlen($URL_RETURN_OK) > 0): ?>
		<input type="hidden" name="URL_RETURN_OK"
		value="<?= substr($URL_RETURN_OK, 0, 128) ?>">
		<?endif;?> <?if (strlen($URL_RETURN_NO) > 0): ?>
		<input type="hidden" name="URL_RETURN_NO"
		value="<?= substr(($URL_RETURN_NO . '?ID=' . $sOrderID), 0, 128) ?>">
		<?endif;?> <input type="submit" name="Submit"
		value="<?echo GetMessage('SUSP_UNITELLER_PAY_BUTTON') ?>"> </font>
</form>
<p align="justify">
	<font class="tablebodytext"><b><?echo GetMessage('SUSP_DESC_TITLE') ?>
	</b> </font>
</p>
<p align="justify">
	<font class="tablebodytext"><?echo CSalePaySystemAction::GetParamValue('DESC') ?>
	</font>
</p>

<? /* автоматическа€ отправка формы в случае, если в настройках платежной системы установлена галочка "ќткрывать в новом окне" */ ?>
<? /* если передать get-параметр S2U_NO_AUTOSUBMIT, то автоматическа€ отправка формы проводитс€ не будет (удобно дл€ тестировани€) */?>
<?if($request->get('ORDER_ID') && $request->get('PAYMENT_ID') && !$request->get('S2U_NO_AUTOSUBMIT')):?>
<script>
document.getElementById("s2u-uniteller-payment-form").submit();
</script>
<?endif;?>
<?php
}
?>
<?php 
/** 
 *
 * @author Атлант mp@atlant2010.ru
 */ 
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 

//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/uniteller_log.txt', "[".date("d.m.Y H:i:s")."]\n", FILE_APPEND);
//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/uniteller_log.txt', "[".date("d.m.Y H:i:s")."]\n".var_export($_POST, true)."\n".var_export($_GET, true)."\n\n", FILE_APPEND); 

$receipt = false;

if ($_REQUEST['Order_ID']) { 

}

function parseID($id) {
   if(empty($id)) {
       return array(null, null);
   }
    
   if($a = !CSaleOrder::GetByID($id)){
        $res = CSaleOrder::GetList(array(), Array("ACCOUNT_NUMBER" => $id));
        while($ob = $res->GetNext()):
           $order_real_id = (int)$ob['ID'];
        endwhile;
        if($order_real_id == NULL){
             $pos = strpos($id, '-');
             $orId = substr($id, 0, $pos);
             $payID = substr($id, $pos+1, strlen($id));
             $order_real_id = parseID($orId)[0];
        }
    } else {
        $pos = strpos($id, '-');
        if ($pos) {
            $payID = substr($id, $pos+1, strlen($id));
            $order_real_id = (int)$id;
        } else {
            $order_real_id = (int)$id;
        }
    }
    $arr = array($order_real_id, $payID);
    return $arr;
}

if (/*$_SERVER['REQUEST_METHOD'] == 'POST' &&*/ isset($_REQUEST['Order_ID']) && isset($_REQUEST['Status']) && isset($_REQUEST['Signature'])) { 


    include(GetLangFileName(dirname(__FILE__) . '/', '/uniteller.php')); 
    if (!class_exists('ps_uniteller')) { 
        include(dirname(__FILE__) . '/tools.php'); 
    }    
    CModule::IncludeModule('sale');
    //$order_real_id = parseID($_REQUEST['Order_ID'])[0];
    //$payID = parseID($_REQUEST['Order_ID'])[1];
    list($order_real_id, $payID) = parseID($_REQUEST['Order_ID']);
    //var_dump($order_real_id);
    $isPaid = true;
    
    $paySystemIds = array();
    $paySystems = \Bitrix\Sale\PaySystem\Manager::getList();
    while ($paySystem = $paySystems->fetch()) {
        if (stripos($paySystem['ACTION_FILE'], 'step2use.uniteller')!==false) {
            $paySystemIds[] = $paySystem['ID'];
        }
    }

    if ($payID != NULL) { 
        $order = \Bitrix\Sale\Order::load($order_real_id);
        $paymentCollection = $order->getPaymentCollection();
        $isPaid = $paymentCollection->isPaid();
        foreach ($paymentCollection as $payment) {
            if ($payID == $payment->getId()) {
                $payment->setPaid("Y"); 
                $order->save();
                break;
            }
        }
    }

    $status = trim($_REQUEST['Status']); 
    $signature = trim($_REQUEST['Signature']);    
    
    $arOrder = CSaleOrder::GetByID($order_real_id);

    if ($arOrder && $isPaid)  
    { 
        CSalePaySystemAction::InitParamArrays($arOrder, $arOrder['ID']); 

        // ���������� ID ����������� �������� ������� Uniteller 
        $uniteller_payment_id = -1; 
        $dbPaySystem = CSalePaySystem::GetList(); 
        while ($arPaySystem = $dbPaySystem->Fetch()) { 
            if (strtolower($arPaySystem['NAME']) == 'uniteller') { 
                $uniteller_payment_id = (int)$arPaySystem['ID']; 
            } 
        } 
        $order_payment_id = (int)$arOrder['PAY_SYSTEM_ID']; 

        ps_uniteller::setMerchantData($order_real_id); // это обязательно надо, чтобы получить потом ps_uniteller::$Password
        $sign = strtoupper(md5($_REQUEST['Order_ID'] . $status . ps_uniteller::$Password)); 

        $status = strtolower($status); 
        
        if($sign !== $signature) {
            die('wrong sign');
        }
        
        //CSalePaySystemAction::GetParamValue('SHOP_PASSWORD')
        
        // Если указан Чек, то из него выдергиваем сумму оплаты и именно эту сумму проставляем как оплаченную (а не то что изначально было в оплате по заказу). Будет работать только в случае Фискализации
        $paidSum = 0;
        if (!empty($_REQUEST['Receipt'])) {
            $receipt = json_decode(base64_decode($_REQUEST['Receipt']), true);
            if (!empty($receipt[0]) && !empty($receipt[0]) && !empty($receipt[0]['total'])) {
                $paidSum = $receipt[0]['total'];
            }
        }
        
        if ($order_real_id != NULL) { 
            //if ($payID != NULL) { 
            $order = \Bitrix\Sale\Order::load($order_real_id);
            $paymentCollection = $order->getPaymentCollection();
            $isPaid = $paymentCollection->isPaid();
            foreach ($paymentCollection as $payment) {
                // помечаем платеж как оплаченный
                // если по № заказа удалось определить id оплаты, то помечаем именно ее
                // если по № заказа не удалось определить id оплаты, то помечаем первую попавшуюся оплату
                if (($payID && $payID == $payment->getId())
                    || 
                    (!$payID && in_array($payment->getPaymentSystemId(), $paySystemIds))
                ) {
                    if($status == 'canceled') {
                        $payment->setPaid("N"); 
                    }
                    else {
                        if ($paidSum) {
                            $payment->setPaid("N"); 
                            $payment->setField('SUM', $paidSum);
                            $payment->save();
                        }
                        $payment->setPaid("Y"); 
                    }
                    $order->save();
                    break;
                }
            }
        }
        
        //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/uniteller_log.txt', "[".date("d.m.Y H:i:s")."]\n".var_export([$paidSum, $receipt, $_POST], true)."\n".var_export($_GET, true)."\n\n", FILE_APPEND); 

        /*
        $info = $sign.'_'.$signature;
        $info .= var_export($_POST, true);
        $path = realpath(dirname(_SITE_DIR_)."/");
        $file = $path.'/info3.txt';
        file_put_contents($file, $info);
        file_put_contents($file, "\n".var_export(array($sign, $signature, $order_payment_id, $uniteller_payment_id, $status, ps_uniteller::$Password, $_REQUEST['Order_ID'], $arOrder), true)."\n", FILE_APPEND);*/


        // ��������� ��������� � ������� ������ � ����� ����������� ��������� ������� Uniteller. 
        // ��� ���� ������ ������� � Uniteller = 'authorized' - ������ ������� ������ ������ 
        if ($sign === $signature && $order_payment_id === $uniteller_payment_id && $status === 'authorized') { 
            ps_uniteller::setStatusCode($order_real_id, $status);
        } 
         
        // ��������� ��������� � ������� ������ � ����� ����������� ��������� ������� Uniteller. 
        else if ($sign === $signature && $order_payment_id === $uniteller_payment_id) { 
            $status = strtolower($status); 
            $statusCode = ps_uniteller::getStatusCode($order_real_id); 

            // ����� � ��������� '�� ���������� �������'. 
            if ($statusCode === 'O') { 
                ps_uniteller::setStatusCode($order_real_id, $status); 
            } 
            // ����� � ��������� '���������� �������', � ����� - ���. 
            if ($statusCode === 'A' 
                && ($status === 'paid' || $status === 'canceled') 
            ) { 
                ps_uniteller::setStatusCode($order_real_id, $status); 
            } 
            // ����� � ��������� '�������� �����', � ����� - ���. 
            if ($statusCode === 'P' 
                && ($status === 'authorized' || $status === 'canceled') 
            ) { 
                ps_uniteller::setStatusCode($order_real_id, $status); 
            } 
            // ����� � ��������� '�������� ����������', � ����� � ��������� '�������� ��������������' ��� '�������� �����'. 
            if ($statusCode === 'C' 
                && ($status === 'authorized' || $status === 'paid') 
            ) { 
                if (!ps_uniteller::setUnitellerCancel($order_real_id)) { 
                    // ���� �������� ����� �� �������, �� ������ ������ ������. 
                    ps_uniteller::setStatusCode($order_real_id, $status); 
                } 
            } 
            // ����� � ��������� '����������'. 
            if ($statusCode === 'W') { 
                ps_uniteller::setStatusCode($order_real_id, $status); 
            } 
        } 
    } 
} 
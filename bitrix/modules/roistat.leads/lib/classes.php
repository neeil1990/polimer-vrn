<?php
IncludeModuleLangFile(__FILE__);

class RoiStat {

    public function send($arFields, $arTemplate){

        $mess = "";
        foreach($arFields as $keyField => $arField)
            $mess .= $keyField .':'. $arField.'; ';

        $roistatData = array(
            'roistat' => isset($_COOKIE['roistat_visit']) ? $_COOKIE['roistat_visit'] : 'nocookie',
            'key'     => COption::GetOptionString('roistat.leads', "RoiProxyLeads"),
            'title' => $arTemplate['ID'].' : '.$arTemplate['EVENT_NAME'],
            'name' =>  $arFields['FIO'] . $arFields['NAME'] . $arFields['ORDER_USER'],
            'comment' => strip_tags($mess),
            'phone'   => $arFields['PHONE'],
            'email'   => $arFields['EMAIL'],
            'fields'  => array(
                "price" => $arFields['PRICE'],
                "bcc" => $arTemplate['BCC'],
                "request" => $_SERVER['REQUEST_URI'],
            ),
        );
        //AddMessage2Log($roistatData, "roistat.leads");
        try {
            file_get_contents("https://cloud.roistat.com/api/proxy/1.0/leads/add?" . http_build_query($roistatData));
        } catch (Exception $e) {}
    }

}
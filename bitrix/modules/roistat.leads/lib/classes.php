<?php
IncludeModuleLangFile(__FILE__);

class RoiStat {

    public function send($arFields, $arTemplate){

        //AddMessage2Log(null, "roistat.leads");

        $module = "roistat.leads";

        if(empty(COption::GetOptionString($module, "RoiProxyLeads")) ||
            empty(COption::GetOptionString($module, "RoiEvent")))
            return false;

        $RoiEvent = unserialize(COption::GetOptionString($module, "RoiEvent"));

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

        try {
            if(in_array($arTemplate['ID'], $RoiEvent))
                file_get_contents("https://cloud.roistat.com/api/proxy/1.0/leads/add?" . http_build_query($roistatData));
        } catch (Exception $e) {}
    }

}
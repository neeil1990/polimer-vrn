<?php                     
    use Sotbit\Seometa\SeometaStatisticsTable;                                                                  
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");                            
    if(!\Bitrix\Main\Loader::includeModule('sotbit.seometa') || !\Bitrix\Main\Loader::includeModule('iblock'))
    {
        return false;
    }   
    global $APPLICATION;
    $base_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];        

    $cookie = $APPLICATION->get_cookie("sotbit_seometa_statistic");                 
    $from = $_REQUEST['from'];
    $url = $_REQUEST['to'];   
    if(!$from){
        $referer_domain = explode('//', $from);
//        $referer_domain = explode('//',$_SERVER['HTTP_REFERER']);
        $referer_domain = explode('/',$referer_domain[1]);
        $referer_domain = $referer_domain[0];
    } else {
        $referer_domain = '';
    }
    $sources = \Bitrix\Main\Config\Option::get("sotbit.seometa",'SOURCE_'.SITE_ID,"yandex.ru\ngoogle.ru\nwww.yahoo.com\nwww.rambler.ru");        
    $sources = explode("\n",$sources); 
    $so = array();     
    foreach($sources as $s){
        $so[] = str_replace(array(chr(13),chr(9),' '),'',$s);
    }                                                    
    if(!empty($cookie) && $cookie==bitrix_sessid() && SeometaStatisticsTable::getBySessId($cookie)){  
        $stat = SeometaStatisticsTable::getBySessId($cookie);       
        $stat['PAGES_COUNT']++;
        SeometaStatisticsTable::update($stat['ID'],$stat);                                     
    
        $APPLICATION->set_cookie('sotbit_seometa_statistic', bitrix_sessid(), time()+3*60*60);
    } elseif(in_array($referer_domain,$so)) {                   
        $APPLICATION->set_cookie('sotbit_seometa_statistic', bitrix_sessid(), time()+3*60*60);
                                     
        $d = SeometaStatisticsTable::add(array(
            'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
            'URL_FROM'=>$referer_domain,
            'URL_TO'=>$url,
            'SESS_ID'=>bitrix_sessid(),      
            'CONDITION_ID'=>$condition_id,
            'PAGES_COUNT'=>1,
        ));                                                                       
    }                  
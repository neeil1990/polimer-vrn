<?php                     
use Sotbit\Seometa\SeometaStatisticsTable;
use Sotbit\Seometa\SeometaUrlTable;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!\Bitrix\Main\Loader::includeModule('sotbit.seometa') || !\Bitrix\Main\Loader::includeModule('iblock'))
{
    return false;
}

global $APPLICATION;
$base_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];

$cookie = $APPLICATION->get_cookie("sotbit_seometa_statistic");
$from = $_REQUEST['from'];
$url = str_replace('?clear_cache=Y', '', $_REQUEST['to']);

if(!empty($from))
{
    $referer_domain = explode('//',$_SERVER['HTTP_REFERER']);
    $referer_domain = explode('/',$referer_domain[1]);
    $referer_domain = $referer_domain[0];
} else {
    $referer_domain = '';
}

$sources = \Bitrix\Main\Config\Option::get("sotbit.seometa",'SOURCE_'.SITE_ID,"yandex.by\nyandex.ru\ngoogle.ru\nwww.yahoo.com\nwww.rambler.ru");
$sources = explode("\n",$sources);
$so = array();
foreach($sources as $s)
{
    $so[] = str_replace(array(chr(13),chr(9),' '),'',$s);
}

$domain = explode('//',$url);
$domain = explode('/', $domain[1]);
$domain = $domain[0];
$urlCondition = explode($domain, $url);
$urlCondition = str_replace('?clear_cache=Y', '', $urlCondition[1]);

$arCondition = SeometaUrlTable::getList(array(
    'select' => array('*'),
    'filter'=>array(
        'LOGIC'=>'OR',
        array('REAL_URL' => $urlCondition),
        array('NEW_URL' => $urlCondition)
    ),
    'order'  => array('ID'=> 'DESC'),
    'limit' => 1
    ))->fetch();

if(!empty($cookie) && $cookie==bitrix_sessid())
{
    //$stats = SeometaStatisticsTable::getBySessId($cookie);
    $stat = SeometaStatisticsTable::getList(array(
        'select' => array('*'),
        'filter'=>array('SESS_ID'=>$cookie, 'URL_TO' => $url, '>DATE_CREATE' => date('d.m.Y')),
        'order'  => array('ID')
    ))->fetch();

    if(!empty($stat))
    {
        $stat['PAGES_COUNT']++;
        SeometaStatisticsTable::update($stat['ID'],$stat);
    }
    else
    {
        $d = SeometaStatisticsTable::add(array(
            'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
            'URL_FROM'=>$referer_domain,
            'URL_TO'=>$url,
            'SESS_ID'=>bitrix_sessid(),
            'CONDITION_ID'=>$arCondition['CONDITION_ID'],
            'PAGES_COUNT'=>1,
        ));
    }

    $APPLICATION->set_cookie('sotbit_seometa_statistic', bitrix_sessid(), time()+3*60*60);

} elseif(in_array($referer_domain,$so) || (empty($cookie)  && empty($referer_domain))) {
    $APPLICATION->set_cookie('sotbit_seometa_statistic', bitrix_sessid(), time()+3*60*60);

    $d = SeometaStatisticsTable::add(array(
        'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
        'URL_FROM'=>$referer_domain,
        'URL_TO'=>$url,
        'SESS_ID'=>bitrix_sessid(),
        'CONDITION_ID'=>$arCondition['CONDITION_ID'],
        'PAGES_COUNT'=>1,
    ));
}
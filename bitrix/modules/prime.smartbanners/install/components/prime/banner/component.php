<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var $this CBitrixComponent */

$arResult = Array();
global $DB;
$curPage = $GLOBALS['APPLICATION']->GetCurUri(true);
if($arParams['ACTIVE'] == "Y"){

    $arResult['ID'] = $arParams['ID'];
    $arResult['NAME'] = $arParams['NAME'];
    $arResult['PAUSE'] = $arParams['PAUSE'];
    $arResult['IMAGE'] = CFile::GetPath($arParams['IMAGE_ID']);
    $arResult['SHOW_OFF'] = $arParams['SHOW_OFF'];
    $arResult['SID'] = $arParams['SID'];
    $arResult['SHOW_FROM'] = $arParams['SHOW_FROM'];
    $arResult['SHOW_TO'] = $arParams['SHOW_TO'];
    $arResult['URL'] = $arParams['URL'];
    $arResult['OPEN_NEW_WINDOW'] = $arParams['OPEN_NEW_WINDOW'];
    $arResult['SHOW_OUT_SITE'] = $arParams['SHOW_OUT_SITE'];
    $arResult['SHOW_TIME'] = $arParams['SHOW_TIME'];

    if($_COOKIE['primeBannerClose-'.$arResult['ID']] AND !$_REQUEST["prime-test"])
        return false;

    if(unserialize(htmlspecialchars_decode($arResult['SID']))[SITE_ID] != "Y")
        return false;

    if($arResult['SHOW_FROM'] and $arResult['SHOW_TO']){
        $now_date = date($DB->DateFormatToPHP("Y.m.d"), time());
        if(
            !(date("Y.m.d",MakeTimeStamp($arResult['SHOW_FROM'],"YYYY-MM-DD")) <= $now_date &&
            date("Y.m.d",MakeTimeStamp($arResult['SHOW_TO'],"YYYY-MM-DD")) >= $now_date)
        ){
            return false;
        }
    }

    if(!empty($arResult['SHOW_OFF']))
    {
        $no_show = unserialize(htmlspecialchars_decode($arResult['SHOW_OFF']));
        foreach ($no_show as $banner_out)
        {
            $banner_out = trim($banner_out);
            if(strlen($banner_out)>0){
                $str = str_replace("*", ".*", $banner_out);
                $str = str_replace("/", "\/", $str);
                if(preg_match("/^$str/", $curPage)){
                    return false;
                }
            }
        }
    }


    if(!$arParams['PAUSE']){
        $arResult['PAUSE'] = 5000;
    }else{
        $arResult['PAUSE'] = ($arParams['PAUSE']*1000);
    }

    $this->AbortResultCache();
    $this->IncludeComponentTemplate();
}


?>

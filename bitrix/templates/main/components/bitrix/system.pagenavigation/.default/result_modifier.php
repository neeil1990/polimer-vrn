<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

// page numbers
$arResult["NAV"]["RECORD_COUNT"] = $arResult["NavRecordCount"];
$arResult["NAV"]["PAGE_COUNT"] = $arResult["NavPageCount"];
$arResult["NAV"]["PAGE_NUMBER"] = $arResult["NavPageNomer"];
$arResult["NAV"]["PAGE_SIZE"] = $arResult["NavPageSize"];
$arResult["NAV"]["START_PAGE"] = $arResult["nStartPage"];
$arResult["NAV"]["END_PAGE"] = $arResult["nEndPage"];
$arResult["NAV"]["SHOW_ALL_MODE"] = $arResult["NavShowAll"];
$arResult["NAV"]["DO_SHOW_ALL"] = $arResult["bShowAll"];

$arResult["NavQueryString"] = str_replace('&amp;', '&', $arResult["NavQueryString"]);

parse_str($arResult["NavQueryString"], $GLOBALS["NAV"]["parsed_url"]);

// filename
$GLOBALS["NAV"]["nav_filename"] = $arResult["sUrlPath"];

// identificator of pager
$arResult["NAV"]["PAGER_ID"] = "PAGEN_".$arResult["NavNum"];
$arResult["NAV"]["SHOWALL_ID"] = "SHOWALL_".$arResult["NavNum"];


if(!function_exists('MakeNewNavUrl'))
{
    function MakeNewNavUrl($arAdd)
    {
        $nav_url = $GLOBALS["NAV"]["nav_filename"]."?".http_build_query(array_merge($GLOBALS["NAV"]["parsed_url"], $arAdd), '', '&');
        return $nav_url;
    }
}

// first page url
$arResult["NAV"]["URL"]["FIRST_PAGE"] = MakeNewNavUrl(array($arResult["NAV"]["PAGER_ID"]=>"1"));

// previous page url
$arResult["NAV"]["URL"]["PREV_PAGE"]  = MakeNewNavUrl(array($arResult["NAV"]["PAGER_ID"]=>$arResult["NAV"]["PAGE_NUMBER"]-1));

// last page url
$arResult["NAV"]["URL"]["LAST_PAGE"]  = MakeNewNavUrl(array($arResult["NAV"]["PAGER_ID"]=>$arResult["NAV"]["PAGE_COUNT"]));

// next page url
$arResult["NAV"]["URL"]["NEXT_PAGE"] = MakeNewNavUrl(array($arResult["NAV"]["PAGER_ID"]=>$arResult["NAV"]["PAGE_NUMBER"]+1));

// some page url
for ($i=$arResult["NAV"]["START_PAGE"]; $i<=$arResult["NAV"]["END_PAGE"]; $i++)
{
    $arResult["NAV"]["URL"]["SOME_PAGE"][$i] = MakeNewNavUrl(array($arResult["NAV"]["PAGER_ID"]=>$i));
}
// show all url
$arResult["NAV"]["URL"]["SHOW_ALL"] = MakeNewNavUrl(array($arResult["NAV"]["SHOWALL_ID"]=>1));

// show by page url
$arResult["NAV"]["URL"]["SHOW_BY_PAGE"] = MakeNewNavUrl(array($arResult["NAV"]["SHOWALL_ID"]=>0));
?>
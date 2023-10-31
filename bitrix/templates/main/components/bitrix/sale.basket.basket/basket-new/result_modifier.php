<?php

$curPage = $APPLICATION->GetCurPage().'?'.$arParams["ACTION_VARIABLE"].'=';
$arUrls = array(
    "delete" => $curPage."delete&id=#ID#",
    "delay" => $curPage."delay&id=#ID#",
    "add" => $curPage."add&id=#ID#",
);
unset($curPage);

$arEmptyPreview = false;
$strEmptyPreview = $this->GetFolder().'/images/no_photo.png';
if (file_exists($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview))
{
    $arSizes = getimagesize($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview);
    if (!empty($arSizes))
    {
        $arEmptyPreview = array(
            'SRC' => $strEmptyPreview,
            'WIDTH' => (int)$arSizes[0],
            'HEIGHT' => (int)$arSizes[1]
        );
    }
    unset($arSizes);
}
unset($strEmptyPreview);



$_SESSION['DISCOUNT_PRICE_ALL_FORMATED'] = $arResult["allSum"];
foreach($arResult["GRID"]["ROWS"] as &$row){
    if(!$row['PREVIEW_PICTURE_SRC']){
        $row['PREVIEW_PICTURE_SRC'] = $arEmptyPreview['SRC'];
    }

    $precent[] = $row['DISCOUNT_PRICE_PERCENT_FORMATED'];
}
$_SESSION['DISCOUNT_PRICE_PERCENT_FORMATED'] = implode(',',$precent);


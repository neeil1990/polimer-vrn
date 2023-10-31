<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

//delayed function must return a string
if (empty($arResult)) {
    return '';
}

$strReturn = '';

$css = $APPLICATION->GetCSSArray();

$strReturn .= '<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">';

$itemSize = count($arResult);
for ($index = 0; $index < $itemSize; $index++) {
    $title = htmlspecialcharsex($arResult[$index]['TITLE']);
    $arrow = ($index > 0 ? '<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>' : '');

    if ($arResult[$index]['LINK'] <> '' && $index != $itemSize - 1) {
        $strReturn .= '
                ' . $arrow . '
				<li class="breadcrumb-item text-muted">
                    <a href="' . $arResult[$index]['LINK'] . '" title="' . $title . '" class="text-muted text-hover-primary">
                        ' . $title . '
                    </a>
				</li>';
    } else {
        $strReturn .= $arrow . '<li class="breadcrumb-item text-muted">
            <a href="' . $arResult[$index]['LINK'] . '" title="' . $title . '" class="text-muted text-hover-primary">
                ' . $title . '
            </a>
        </li>';
    }
}

$strReturn .= '</ul>';

return $strReturn;

<?php
/**
 * Created by PhpStorm.
 * User: Dimitrii
 * Date: 27.06.16
 * Time: 11:33
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$arComponentDescription = array(
    "NAME" => GetMessage("DIMITRII_COMPONENT_NAME"),
    "DESCRIPTION" => GetMessage("DIMITRII_COMPONENT_DESCRIPTION"),
    "PATH" => array(
        "ID" => "Dimitrii",
        "NAME" => GetMessage("DIMITRII_SECTION_NAME"),
    ),
    "ICON" => "/images/icon.gif",
);
?>
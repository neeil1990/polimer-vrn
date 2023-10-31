<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$headers = [];

foreach ($adminList->aHeaders as $header)
{
	$headers[] = [
		'id' => $header['id'],
		'name' => $header['content'],
		'sort' => $header['sort'],
		'default' => $header['default'],
	];
}

$arResult['GRID_PARAMETERS'] += [
	'HEADERS' => $headers,
];
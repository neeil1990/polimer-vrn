<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Рабочий стол');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:dashboard.sale',
    '',
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
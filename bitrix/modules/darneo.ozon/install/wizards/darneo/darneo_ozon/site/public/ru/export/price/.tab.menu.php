<?php

$aMenuLinks = [
    [
        'Основные',
        '#SITE_DIR#export/price/detail/#ID#/',
        [],
        ['DESC' => 'Базовые настройки'],
        ''
    ],
    [
        'Выгрузка цен',
        '#SITE_DIR#export/price/export/#ID#/',
        [],
        ['DESC' => 'Отправка цен на OZON'],
        ''
    ],
    [
        'Автоматизация',
        '#SITE_DIR#export/price/cron/#ID#/',
        [],
        ['DESC' => 'Автоматическая выгрузка данных'],
        ''
    ],
];
?>
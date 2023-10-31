<?php

$aMenuLinks = [
    [
        'Основные',
        '#SITE_DIR#export/stock/detail/#ID#/',
        [],
        ['DESC' => 'Базовые настройки'],
        ''
    ],
    [
        'Выгрузка остатков',
        '#SITE_DIR#export/stock/export/#ID#/',
        [],
        ['DESC' => 'Отправка остатков на OZON'],
        ''
    ],
    [
        'Автоматизация',
        '#SITE_DIR#export/stock/cron/#ID#/',
        [],
        ['DESC' => 'Автоматическая выгрузка данных'],
        ''
    ],
];
?>
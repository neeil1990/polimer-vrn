<?php

$aMenuLinks = [
    [
        'Основные',
        '#SITE_DIR#export/product/detail/#ID#/',
        [],
        ['DESC' => 'Базовые настройки'],
        ''
    ],
    [
        'Категории',
        '#SITE_DIR#export/product/section/#ID#/',
        [],
        ['DESC' => 'Сопоставление категорий'],
        ''
    ],
    [
        'Характеристики',
        '#SITE_DIR#export/product/attribute/#ID#/',
        [],
        ['DESC' => 'Характеристики категорий'],
        ''
    ],
    [
        'Выгрузка товаров',
        '#SITE_DIR#export/product/export/#ID#/',
        [],
        ['DESC' => 'Отправка товаров на OZON'],
        ''
    ],
    [
        'Автоматизация',
        '#SITE_DIR#export/product/cron/#ID#/',
        [],
        ['DESC' => 'Автоматическая выгрузка данных'],
        ''
    ],
];
?>
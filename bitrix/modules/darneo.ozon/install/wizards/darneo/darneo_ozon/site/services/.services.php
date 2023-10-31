<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arServices = [
    'main' =>
        [
            'NAME' => Loc::getMessage('SERVICE_MAIN_SETTINGS'),
            'STAGES' =>
                [
                    'files.php',
                    'template.php',
                    'cleancache.php'
                ]

        ]
];

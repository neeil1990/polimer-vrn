<?php
IncludeModuleLangFile(__FILE__);

global $MESS, $DOCUMENT_ROOT, $APPLICATION;

CModule::AddAutoloadClasses(
    'thebrainstech.copyiblock',
    array(
        'Smarty' => 'vendor/smarty/libs/Smarty.class.php',
        'NBrains\CopyIBlock\Events' => 'lib/Events.php',
        'ActionController' => 'lib/ActionController.php',
        'Menu' => 'lib/Menu.php',
        'DialogMenu' => 'lib/DialogMenu.php',
        'CIBlockMain' => 'lib/CIBlockMain.php',
        'IBlockCreator' => 'lib/creator/IBlockCreator.php',
        'IBlockCreatorDecorator' => 'lib/creator/IBlockCreatorDecorator.php',
        'IBlockComponent' => 'lib/creator/IBlockComponent.php',
        'SectionDecorator' => 'lib/creator/SectionDecorator.php',
        'ElementDecorator' => 'lib/creator/ElementDecorator.php',
    )
);

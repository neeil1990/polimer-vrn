<?php
IncludeModuleLangFile(__FILE__);

global $MESS, $DOCUMENT_ROOT;

CModule::AddAutoloadClasses(
    'roistat.leads',
    array(
        'RoiStat' => 'lib/classes.php'
    )
);

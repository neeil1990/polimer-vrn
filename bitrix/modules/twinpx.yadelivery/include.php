<?
use \Bitrix\Main\Application,
	\Bitrix\Main\EventManager,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Context,
	\Bitrix\Main\IO\File,
	\Bitrix\Main\Page\Asset;

$module_id = 'twinpx.yadelivery';

$request = Context::getCurrent()->getRequest();
$session = Application::getInstance()->getSession();

IncludeModuleLangFile(__FILE__);
include(__DIR__.'/install/version.php'); //версия модуля

//D7
Loader::registerAutoLoadClasses(
    $module_id,
    array(
        "TwinpxDelivery"=> "classes/general/TwinpxDelivery.php", //управление доставки
        "TwinpxEvent"   => "classes/general/TwinpxEvent.php" //событии
    )
);

//регистрируем стили и скрипты для модуля
$arJsConfig = array(
    'twinpx_lib'	=> array(
        'js'  		=> '/bitrix/js/'.$module_id.'/script.js?ver='.$arModuleVersion['VERSION'],
        'css' 		=> '/bitrix/css/'.$module_id.'/style.css',
        'lang'		=> '/bitrix/modules/'.$module_id.'/lang/'.LANGUAGE_ID.'/js/js_script.php'
    ),
    'twinpx_admin_lib'=> array(
        'js'  		=> '/bitrix/js/'.$module_id.'/admin/script.js',
        'css' 		=> '/bitrix/css/'.$module_id.'/admin/style.css',
        'lang'		=> '/bitrix/modules/'.$module_id.'/lang/'.LANGUAGE_ID.'/js/admin/js_script.php',
        'rel'   	=> array('jquery3')
    ),
    'twinpx_schedule_chunk' => array(
        'js'		=> '/bitrix/js/'.$module_id.'/admin/chunk-vendors.93f85daa.js'
    ),
    'twinpx_schedule_app' => array(
        'js'		=> '/bitrix/js/'.$module_id.'/admin/app.ef1751b5.js',
        'css'      	=> '/bitrix/css/'.$module_id.'/admin/app.e8ca5091.css',
        'rel'      	=> array('twinpx_schedule_chunk')
    )
);
foreach ($arJsConfig as $ext => $arExt) {
    CJSCore::RegisterExt($ext, $arExt); //собствено регистрация
}

//tools
if($request->get('twpx-ver')){
	Asset::getInstance()->addString('<script>var '.$module_id.' = "'.$arModuleVersion['VERSION'].'"</script>');
}

//событие
EventManager::getInstance()->addEventHandler("sale", "OnSaleOrderBeforeSaved",  array("TwinpxEvent","OnSaleOrderBeforeSaved")); //перед сохранение заказа
EventManager::getInstance()->addEventHandler("main", "OnAdminContextMenuShow", array("TwinpxEvent","OnAdminContextMenuShow")); //кнопка в меню заказа
EventManager::getInstance()->addEventHandler('sale', 'OnSalePayOrder', array("TwinpxEvent", "OnSaleOnSalePayOrder"));  //после оплаты

//безопасность, если нет файлы не подключаем событие
if(File::isFileExists(__DIR__.'/classes/general/TwinpxEvent.php')){
	EventManager::getInstance()->addEventHandler('main', 'OnEndBufferContent', array("TwinpxEvent", "OnEndBuffer")); //при выдачи контента
}
?>

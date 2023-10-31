<?php

namespace Darneo\Ozon\Api;

use Bitrix\Main\Loader;
use CJSCore;
use Darneo\Ozon\Analytics;
use Darneo\Ozon\Export;
use Darneo\Ozon\Import;
use Darneo\Ozon\Main;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;
use Darneo\Ozon\Order;
use DarneoSettingsOzon;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/darneo.ozon/settings.php');

$settingsKeyId = DarneoSettingsOzon::getKeyId();
$requestKeyId = isset($_GET['key']) ? (int)$_GET['key'] : 0;

$moduleNames = ['iblock', 'catalog', 'sale'];
foreach ($moduleNames as $moduleName) {
    Loader::includeModule($moduleName);
}

CJSCore::Init(
    [
        'ui.hint',
        'ui.dialogs.messagebox',
    ]
);

$keyId = 0;
if ($settingsKeyId || $requestKeyId) {
    $clientId = $settingsKeyId > 0 ? $settingsKeyId : $requestKeyId;
    $key = Main\Table\ClientKeyTable::getById($clientId)->fetch();
    if ($key) {
        $keyId = $key['ID'];
        HelperSettings::setKeyIdCurrent($keyId);
    }
} else {
    $keyId = HelperSettings::getKeyIdCurrent();
}

if ($keyId) {
    Main\Table\TreeDisableTable::setTablePrefix($keyId);
    Main\Table\SettingsCronTable::setTablePrefix($keyId);
    Import\Table\TreeTable::setTablePrefix($keyId);
    Import\Table\StockTable::setTablePrefix($keyId);
    Import\Table\ProductListTable::setTablePrefix($keyId);
    Import\Table\ConnectionOfferProductTable::setTablePrefix($keyId);
    Export\Table\ProductListTable::setTablePrefix($keyId);
    Export\Table\ProductLogTable::setTablePrefix($keyId);
    Export\Table\ProductCronTable::setTablePrefix($keyId);
    Export\Table\StockListTable::setTablePrefix($keyId);
    Export\Table\StockLogTable::setTablePrefix($keyId);
    Export\Table\StockCronTable::setTablePrefix($keyId);
    Export\Table\PriceListTable::setTablePrefix($keyId);
    Export\Table\PriceLogTable::setTablePrefix($keyId);
    Export\Table\PriceCronTable::setTablePrefix($keyId);
    Export\Table\PriceFilterTable::setTablePrefix($keyId);
    Export\Table\ProductFilterTable::setTablePrefix($keyId);
    Export\Table\StockFilterTable::setTablePrefix($keyId);
    Order\Table\FboListTable::setTablePrefix($keyId);
    Order\Table\FbsListTable::setTablePrefix($keyId);
    Analytics\Table\SaleTable::setTablePrefix($keyId);
}
?>
<?php

use Bitrix\Main;
use Yandex\Market;

Main\Loader::registerAutoLoadClasses('yandex.market', [
	Market\Api\OAuth2\Token\Table::class => '/lib/api/oauth2/token/table.php',
	Market\Reference\Storage\Table::class => '/lib/reference/storage/table.php',
	Market\Export\Setup\Table::class => '/lib/export/setup/table.php',
	Market\Export\IblockLink\Table::class => '/lib/export/iblocklink/table.php',
	Market\Export\Param\Table::class => '/lib/export/param/table.php',
	Market\Export\ParamValue\Table::class => '/lib/export/paramvalue/table.php',
	Market\Export\Filter\Table::class => '/lib/export/filter/table.php',
	Market\Export\FilterCondition\Table::class => '/lib/export/filtercondition/table.php',
	Market\Export\Delivery\Table::class => '/lib/export/delivery/table.php',
	Market\Export\Promo\Table::class => '/lib/export/promo/table.php',
	Market\Export\PromoProduct\Table::class => '/lib/export/promoproduct/table.php',
	Market\Export\PromoGift\Table::class => '/lib/export/promogift/table.php',
	Market\Export\Track\Table::class => '/lib/export/track/table.php',
	Market\Logger\Table::class => '/lib/logger/table.php',
	Market\Logger\Trading\Table::class => '/lib/logger/trading/table.php',
	Market\Trading\Setup\Table::class => '/lib/trading/setup/table.php',
	Market\Trading\Settings\Table::class => '/lib/trading/settings/table.php',
	Market\Confirmation\Setup\Table::class => '/lib/confirmation/setup/table.php',
	Market\Trading\Service\MarketplaceDbs\Options\Timetable::class => '/lib/trading/service/marketplacedbs/options/timetable.php',
	Market\Trading\Entity\Reference\OutletSelectable::class => '/lib/trading/entity/reference/outletselectable.php',
	Market\Export\Collection\Table::class => '/lib/export/collection/table.php',
	Market\Export\CollectionProduct\Table::class => '/lib/export/collectionproduct/table.php',
	Market\SalesBoost\Product\Table::class => '/lib/salesboost/product/table.php',
	Market\SalesBoost\Setup\Table::class => '/lib/salesboost/setup/table.php',
	Market\Trading\Business\Table::class => '/lib/trading/business/table.php',
	Market\Export\Run\Data\EntityExportable::class => '/lib/export/run/data/entityexportable.php',
]);
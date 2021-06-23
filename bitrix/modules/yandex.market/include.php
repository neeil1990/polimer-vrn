<?php

use Bitrix\Main;

Main\Loader::registerAutoLoadClasses('yandex.market', [
	'Yandex\Market\Api\OAuth2\Token\Table' => '/lib/api/oauth2/token/table.php',
	'Yandex\Market\Reference\Storage\Table' => '/lib/reference/storage/table.php',
	'Yandex\Market\Export\Setup\Table' => '/lib/export/setup/table.php',
	'Yandex\Market\Export\IblockLink\Table' => '/lib/export/iblocklink/table.php',
	'Yandex\Market\Export\Param\Table' => '/lib/export/param/table.php',
	'Yandex\Market\Export\ParamValue\Table' => '/lib/export/paramvalue/table.php',
	'Yandex\Market\Export\Filter\Table' => '/lib/export/filter/table.php',
	'Yandex\Market\Export\FilterCondition\Table' => '/lib/export/filtercondition/table.php',
	'Yandex\Market\Export\Delivery\Table' => '/lib/export/delivery/table.php',
	'Yandex\Market\Export\Promo\Table' => '/lib/export/promo/table.php',
	'Yandex\Market\Export\PromoProduct\Table' => '/lib/export/promoproduct/table.php',
	'Yandex\Market\Export\PromoGift\Table' => '/lib/export/promogift/table.php',
	'Yandex\Market\Export\Track\Table' => '/lib/export/track/table.php',
	'Yandex\Market\Logger\Table' => '/lib/logger/table.php',
	'Yandex\Market\Logger\Trading\Table' => '/lib/logger/trading/table.php',
	'Yandex\Market\Trading\Setup\Table' => '/lib/trading/setup/table.php',
	'Yandex\Market\Trading\Settings\Table' => '/lib/trading/settings/table.php',
	'Yandex\Market\Confirmation\Setup\Table' => '/lib/confirmation/setup/table.php',
	'Yandex\Market\Trading\Service\MarketplaceDbs\Options\Timetable' => '/lib/trading/service/marketplacedbs/options/timetable.php',
]);

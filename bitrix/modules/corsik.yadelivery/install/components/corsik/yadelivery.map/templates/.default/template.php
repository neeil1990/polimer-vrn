<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

$this->setFrameMode(true);

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Corsik\YaDelivery\Helper;

/**
 * @var array $arParams
 * @var array $arResult
 */

Bitrix\Main\UI\Extension::load(['popup']);
$context = Main\Application::getInstance()->getContext();
$helper = Helper::getInstance();
$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
$isModal = $arParams['DISPLAY_MAP'] === 'MODAL';
$typePrompts = strtolower($arParams['TYPE_PROMPTS']);
$isYandexPrompts = $typePrompts === 'yandex';
?>

<div class="corsik_yaDeliveryMap">
	<div class="corsik_yaDeliveryMap__header">
		<h2><?= Loc::getMessage('CORSIK_YADELIVERY_MAP_CALCULATE_TITLE') ?></h2>
		<div class="corsik_yaDeliveryMap__notification">
			<p><?= Loc::getMessage('CORSIK_YADELIVERY_MAP_CALCULATE_TITLE_NOTIFICATION') ?></p>
		</div>
	</div>
	<div class="corsik_yaDeliveryMap__content">
		<input type="hidden" name="PERSON_TYPE" value="<?= $arParams['PERSON_TYPE'] ?>">
		<div class="corsik_yaDeliveryMap__content__wrapper">
			<div class="corsik_yaDeliveryMap__inputBlock">
				<h4><?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_ADDRESS') ?></h4>
				<div class="corsik_yaDeliveryMap__inputGroup">
					<label for="corsik_yaDeliveryMap__addressDelivery">
						<?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_INPUT_PLACEHOLDER_ADDRESS') ?>
					</label>
					<input type="text"
							class="form-control <?= $isYandexPrompts ? "corsik_yaDeliveryMap_readonly" : "" ?>"
							id="corsik_yaDeliveryMap__addressDelivery">
				</div>
				<? if ($isModal) { ?>
					<button class="corsik_yaDeliveryMap__mapPoint" onclick="window.showYaDeliveryModal()">
						<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd"
									d="M12 20.62c-2.43-2.142-4.217-4.1-5.406-5.883C5.323 12.831 4.8 11.224 4.8 9.868 4.8 5.76 8.02 2.8 12 2.8s7.2 2.96 7.2 7.068c0 1.356-.523 2.963-1.794 4.87-1.189 1.781-2.976 3.74-5.406 5.882zm-.658 1.814c.378.325.938.325 1.316 0C18.188 17.675 21 13.52 21 9.868 21 4.696 16.903 1 12 1S3 4.696 3 9.868c0 3.652 2.811 7.807 8.342 12.566z"></path>
							<path d="M12 12a2 2 0 100-4 2 2 0 000 4z"></path>
						</svg>
						<span><?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_CHECK_ADDRESS') ?></span>
					</button>
				<? } ?>
			</div>
			<div class="corsik_yaDeliveryMap__inputBlock">
				<h4><?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_ORDER_PARAMETERS') ?></h4>
				<div class="corsik_yaDeliveryMap__orderParams">
					<div class="corsik_yaDeliveryMap__inputGroup">
						<label for="corsik_yaDeliveryMap__orderPrice">
							<?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_ORDER_PRICE') ?>
						</label>
						<input type="number" class="form-control" id="corsik_yaDeliveryMap__orderPrice"
								placeholder="0">
					</div>
					<div class="corsik_yaDeliveryMap__inputGroup">
						<label for="corsik_yaDeliveryMap__orderWeight">
							<?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_ORDER_WEIGHT') ?>
						</label>
						<input type="number" class="form-control" id="corsik_yaDeliveryMap__orderWeight"
								placeholder="0">
					</div>
				</div>
			</div>
		</div>
		<div class="corsik_yaDeliveryMap__coastBlock">
			<p class="corsik_yaDelivery__total">
                 <span class="corsik_yaDelivery__total__price">
                     <?= max($arParams['START_PRICE'], 0) ?>
                 </span>
				<span class="corsik_yaDelivery__total__currency">
                    <?= empty($arResult['CURRENCY']) ? Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_CURRENCY') : $arResult['CURRENCY'] ?>
                </span>
			</p>
			<p class="corsik_yaDelivery__route" style="display:none">
				<span class="corsik_yaDelivery__route__value">0</span>
				<span class="corsik_yaDelivery__route__unit">
                    <?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_KM') ?>
                </span>
			</p>
			<p class="corsik_yaDeliveryMap__coastBlock__detail">
				<?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_ORDER_DETAIL_TEXT') ?>
			</p>
			<p class="corsik_yaDeliveryMap__coastBlock__info">
				<?= Loc::getMessage('CORSIK_YADELIVERY_MAP_DELIVERY_ORDER_INFO_TEXT') ?>
			</p>
		</div>
	</div>
	<? if (!$isModal) { ?>
		<div id="corsik_yaDeliveryMap__map">
			<div class="corsik_yaDeliveryMap__alert"></div>
		</div>

	<? } ?>
</div>
<?
$jsData = CUtil::PhpToJSObject([
	'displayMap' => $arParams['DISPLAY_MAP'],
	'typePrompts' => $typePrompts,
	'mapSettings' => [
		'points' => [
			'warehouse' => $arParams['SELECT_WAREHOUSE'],
		],
		'startPrice' => $arParams['START_PRICE'],
		'addZonePrice' => $arParams['ADD_ZONE_PRICE'],
	],
	'TOTAL' => [
		'ORDER_TOTAL_PRICE' => 0,
		'ORDER_WEIGHT' => 0,
	],
]);

Asset::getInstance()->addString("<script>window.jsonMapsParameters = $jsData </script>");
Asset::getInstance()->addString("<script src=\"" . $scheme . "://api-maps.yandex.ru/2.1/?apikey=" . $arResult['YANDEX_MAPS_API_KEY'] . "&lang=ru_RU\"></script>");
Asset::getInstance()->addJs("./lodash.min.js");
?>

<?
namespace Zverushki\Microm\Reading;

use Zverushki\Microm,
	Zverushki\Microm\Tools\Page,
	Zverushki\Microm\Tools\Component;

/**
* class Product
*
*
* @const NAME - уникальный ключ типа
* @var $__instance - объект
*
* @param $__options - входные параметры (настраиваются в модуле)
*
* @package Zverushki\Microm\Reading\Product
*/
class Product extends Type {
	const NAME = 'Product';
	private static $__instance = null;

	protected function __construct ($options) {
		parent::__construct(self::NAME, $options);

		$this->startCache();
	}

	public static function getInstance ($options = array()) {
		if (static::$__instance === null)
			static::$__instance = new self($options);

		return static::$__instance;
	}

	public static function getAdditionalOptions ($siteId) {
		$options = array(
			'schema' => array(
				'props' => array()
			)
		);

        $optionProduct = ($db = Microm\MicromTable::getList(
            [
                'filter' => ['CODE' => 'product', 'SITE_ID' => $siteId],
                'cache'  => ['ttl' => 86400],
            ]
        ))
            ->fetch();

        $optionProduct['VALUE']['vote_count'] = $optionProduct['VALUE']['vote_count'] ? $optionProduct['VALUE']['vote_count'] : 'vote_count';
		$optionProduct['VALUE']['vote_sum'] = 'vote_sum';
		$optionProduct['VALUE']['rating'] = $optionProduct['VALUE']['rating'] ? $optionProduct['VALUE']['rating'] : 'rating';

		$apRating = array('vote_count', 'vote_sum', 'rating');
		$apOtherData = array('brand', 'manufacturer', 'model', 'sku');

		foreach (array(
			'rating' => $apRating,
			'other' => $apOtherData
			) as $type => $arr) {

			foreach ($arr as $k)
				if ($optionProduct['VALUE'][$k]) {
					$options['schema']['props'][$k] = $optionProduct['VALUE'][$k];
					$options['schema'][$type][$k] = $optionProduct['VALUE'][$k];
				}
		}

		$options['componentId'] = array('bitrix:catalog', 'bitrix:catalog.element', 'dresscode:catalog');

		return $options;
	}

	/**
	 * Формируем данные
	 * @return array - массив данных
	 */
	protected function model () {
		$Page = Page\UrlRewrite::getInstance();

		foreach ($this->__options['componentId'] as $componentId)
			if (($rule = $Page->getRuleByComponentId($componentId)) !== false) {
				$data = array();

				if (!empty($this->__options['schema']['props']))
					$data['params']['DETAIL_PROPERTY_CODE'] = array_values($this->__options['schema']['props']);

				$component = new Component($componentId);

				$result = $component
							->setData($data)
							->setPath($rule['PATH'])
							->execute();

				// if ($result !== false)
				// 	$this->addKeyCache('tag', 'iblock_id_'.$result['ID']);

				if ($result === false)
					$this->__status = 'Error. Execution result is empty'; // Результат выполнения пуст

				return $result;
			}

		$this->__status = 'Page does not apply to products catalog'; // Страница не относится к каталогу товаров

		return false;
	}

	protected function __onPrepareResult ($model) {
		$site = Microm\Options::entity()->getSite();

		foreach (array('DETAIL_PICTURE', 'PREVIEW_PICTURE') as $fc)
			if (isset($model[$fc]) && $model[$fc]) {
				$picture = $site['url'].\CFile::getPath($model[$fc]);
				break;
			}

		$description = trim($model['PREVIEW_TEXT'] ? $model['PREVIEW_TEXT'] : $model['DETAIL_TEXT']);
		$description = strlen($description) > 0 ? preg_replace("/\s{2,}|\n/", ' ', htmlspecialcharsEx(strip_tags($description))) : false;

		$priceId = false;
		foreach ($model['CAT_PRICES'] as $price) {
			$priceId = $price['ID'];
			break;
		}

		$offers = $priceId !== false ? $this->__getOffers($model) : false;
		$incProps = array();

		if (!empty($this->__options['schema']['other']))
			foreach ($this->__options['schema']['other'] as $key => $codeProp) {
				$incProps[$key] = false;

				if ($codeProp && array_key_exists($codeProp, $model['DISPLAY_PROPERTIES'])) {
					$propName = $model['DISPLAY_PROPERTIES'][$codeProp]['DISPLAY_VALUE'];
					$propName = strip_tags(is_array($propName) ? implode(', ', $propName) : $propName);

					if ($propName)
						$incProps[$key] = array('name' => $propName);
				}
			}

		$result = array_merge(
			array(
				'.template' => !empty($model['OFFERS']) ? 'AggregateOffer' : 'Product',
				'name' => $model['NAME'],
				'picture' => $picture,
				'description' => $description,
				'weight' => $this->__getWeight($model),
				'height' => $this->__getHeight($model),
				'width' => $this->__getWidth($model),
				'rating' => $this->__getRating($model),
				'offers' => $offers
			),
			$incProps
		);

		return $result;
	}

	protected function __getOffers (&$model) {
		if (!empty($model['OFFERS'])) {
			$lowPrice = -1;
			$highPrice = -1;
			$__offers = array();

			foreach ($model['OFFERS'] as $ar) {
				if ($ar['MIN_PRICE']['DISCOUNT_VALUE'] < $lowPrice || $lowPrice == -1)
					$lowPrice = $ar['MIN_PRICE']['DISCOUNT_VALUE'];

				if ($ar['MIN_PRICE']['DISCOUNT_VALUE'] > $highPrice || $highPrice == -1)
					$highPrice = $ar['MIN_PRICE']['DISCOUNT_VALUE'];

				$__offers[] = array(
					'@type' => 'Offer',
					'name' => $ar['NAME'],
					'serialNumber' => $ar['ID'],
					'price' => $this->__rightPrice($ar['MIN_PRICE']['DISCOUNT_VALUE'], $ar['MIN_PRICE']['CURRENCY']),
					'priceCurrency' => $ar['MIN_PRICE']['CURRENCY']
				);
			}

			$offers = array(
				'offerCount' => count($model['OFFERS']),
				'lowPrice' => $this->__rightPrice($lowPrice, $model['OFFERS'][0]['MIN_PRICE']['CURRENCY']),
				'highPrice' => $this->__rightPrice(($highPrice > $lowPrice ? $highPrice : $lowPrice), $offers['priceCurrency']),
				'serialNumber' => $model['ID'],
				'priceCurrency' => $model['OFFERS'][0]['MIN_PRICE']['CURRENCY'],
				'offerCount' => count($__offers),
				'offers' => $__offers
			);

			if ($model['CATALOG_AVAILABLE'] == 'Y')
				$offers['availability'] = 'http://schema.org/InStock';

		} else {
			if ($model['MIN_PRICE'])
				$offers = array(
					'serialNumber' => $model['ID'],
					'priceCurrency' => $model['MIN_PRICE']['CURRENCY'],
					'price' => $this->__rightPrice($model['MIN_PRICE']['DISCOUNT_VALUE'], $model['MIN_PRICE']['CURRENCY']),
				);

			else {
				$__currency = '';
				$__prices = array();

				foreach ($model['PRICE_MATRIX']['MATRIX'] as $priceId => $prices)
					foreach ($prices as $p) {
						$__prices[] = $p['DISCOUNT_PRICE'];
						$__currency = $p['CURRENCY'];
					}

				if (($minPrice = min($__prices)) != ($maxPrice = max($__prices)))
					$offers = array(
						'serialNumber' => $model['ID'],
						'priceCurrency' => $__currency,
						'lowPrice' => $this->__rightPrice($minPrice, $__currency),
						'highPrice' => $this->__rightPrice($maxPrice, $__currency)
					);

				else
					$offers = array(
						'serialNumber' => $model['ID'],
						'priceCurrency' => $__currency,
						'price' => $this->__rightPrice($minPrice, $__currency)
					);
			}

			if ($model['CATALOG_AVAILABLE'] == 'Y')
				$offers['availability'] = 'http://schema.org/InStock';

			$site = Microm\Options::entity()->getSite();
			$offers['url'] = $site['url'].$_SERVER['REQUEST_URI'];
		}

		return $offers;
	}

	private function __getRating (&$model) {
		return !$model['DISPLAY_PROPERTIES'][$this->__options['schema']['rating']['rating']]['VALUE']
					? false
					: array(
						'value' => $model['DISPLAY_PROPERTIES'][$this->__options['schema']['rating']['rating']]['VALUE'],
						'count' => $model['DISPLAY_PROPERTIES'][$this->__options['schema']['rating']['vote_count']]['VALUE']
					);
	}

	private function __getWeight (&$model) {
		$min = 0;
		$max = 0;

		if (!empty($model['OFFERS']))
			foreach ($model['OFFERS'] as $ar) {
				$min = $min > $ar['CATALOG_WEIGHT'] ? $ar['CATALOG_WEIGHT'] : $min;
				$max = $max < $ar['CATALOG_WEIGHT'] ? $ar['CATALOG_WEIGHT'] : $max;
			}

		else
			$min = $max = $ar['CATALOG_WEIGHT'];

		return $min == 0 && $max == 0
			? false
			: array(
				'min' => $min,
				'max' => $max,
				'unitCode' => 'GRM'
			);
	}

	private function __getHeight (&$model) {
		$min = 0;
		$max = 0;

		if (!empty($model['OFFERS']))
			foreach ($model['OFFERS'] as $ar) {
				$min = $min > $ar['CATALOG_HEIGHT'] ? $ar['CATALOG_HEIGHT'] : $min;
				$max = $max < $ar['CATALOG_HEIGHT'] ? $ar['CATALOG_HEIGHT'] : $max;
			}

		else
			$min = $max = $ar['CATALOG_HEIGHT'];

		return $min == 0 && $max == 0
			? false
			: array(
				'min' => $min,
				'max' => $max,
				'unitCode' => 'MMT'
			);
	}

	private function __getWidth (&$model) {
		$min = 0;
		$max = 0;

		if (!empty($model['OFFERS']))
			foreach ($model['OFFERS'] as $ar) {
				$min = $min > $ar['CATALOG_WIDTH'] ? $ar['CATALOG_WIDTH'] : $min;
				$max = $max < $ar['CATALOG_WIDTH'] ? $ar['CATALOG_WIDTH'] : $max;
			}

		else
			$min = $max = $ar['CATALOG_WIDTH'];

		return $min == 0 && $max == 0
			? false
			: array(
				'min' => $min,
				'max' => $max,
				'unitCode' => 'MMT'
			);
	}

	private function __rightPrice ($price, $currency) {
		$price = preg_replace(array(/*"/\s{1,}/", */"/[a-zA-ZА-Яа-я\s]/"), '', $price);

		$arCurFormat = \CCurrencyLang::GetFormatDescription($currency);
		$intDecimals = $arCurFormat['DECIMALS'];

		if (\CCurrencyLang::isAllowUseHideZero() && $arCurFormat['HIDE_ZERO'] == 'Y') {
			if (round($price, $arCurFormat["DECIMALS"]) == round($price, 0))
				$intDecimals = 0;
		}

		return number_format($price, $intDecimals, $arCurFormat['DEC_POINT'] = '.', $arCurFormat['THOUSANDS_SEP'] = '');
	}

	public function getName() { return self::NAME;}

}
?>
<?
namespace Zverushki\Microm\Reading;

use Zverushki\Microm;

/**
* class Business
*
* When users search for a business on Google Search or Maps,
* they may see a prominent "Knowledge Panel" card with details about the searched-for business.
*
*
* @var NAME - уникальный ключ типа
* @var $__instance - объект
*
* @param $__options - входные параметры (настраиваются в модуле)
*
* @package Zverushki\Microm\Reading\Business
*/
class Business extends Type {
	const NAME = 'Business';
	private static $__instance = array();

	protected function __construct ($options) {
		parent::__construct(self::NAME, $options);
	}

	public static function getInstance ($options = array()) {
		$k = md5(serialize($options));

		return array_key_exists($k, static::$__instance)
			? static::$__instance[$k]
			: (static::$__instance[$k] = new self($options));
	}


	/**
	 * Формируем данные
	 * @return array - массив данных
	 */
	protected function model () {
        $model = Microm\MicromTable::getList(
            [
                'filter' => ['CODE' => 'info', 'SITE_ID' => $this->siteId],
                'cache'  => ['ttl' => 86400],
            ]
        )
            ->fetch();

        if ($model === false) {
			$this->__status = 'Error. Data not found (info)'; // Данные не найдены

			return false;

		} elseif ($model['VALUE']['pageUrl'] && $this->__options['noCheckPageUrl'] !== true) {
			$pageUrl = parse_url($model['VALUE']['pageUrl']);

			if (!\CSite::InDir($pageUrl['path']))
				return false;
		}

		return $model;
	}

	protected function __onPrepareResult ($model) {
		$result = array(
			'@id' => $model['VALUE']['@id'],
			'name' => $model['VALUE']['name'],
			'priceRange' => $model['VALUE']['priceRange'],
			'address' => array(
				'streetAddress' => $model['VALUE']['address_streetAddress'],
				'addressLocality' => $model['VALUE']['address_addressLocality'],
				'postalCode' => $model['VALUE']['address_postalCode'],
				'addressCountry' => $model['VALUE']['address_addressCountry'],
			)
		);

		if ($model['VALUE']['address_addressRegion'])
			$result['address']['addressRegion'] = $model['VALUE']['address_addressRegion'];

		if ($model['VALUE']['geo_latitude'])
			$result['geo']['latitude'] = $model['VALUE']['geo_latitude'];

		if ($model['VALUE']['geo_longitude'])
			$result['geo']['longitude'] = $model['VALUE']['geo_longitude'];

		if ($model['VALUE']['url'])
			$result['url'] = $model['VALUE']['url'];

		if ($model['VALUE']['image'])
			$result['image'] = $model['VALUE']['image'];

		if ($model['VALUE']['telephone'])
			$result['telephone'] = $model['VALUE']['telephone'];

		if ($model['VALUE']['email'])
			$result['email'] = $model['VALUE']['email'];

		if ($model['VALUE']['logo'])
			$result['logo'] = $model['VALUE']['logo'];

		return $result;
	}

	public function getName() { return self::NAME;}

}
?>
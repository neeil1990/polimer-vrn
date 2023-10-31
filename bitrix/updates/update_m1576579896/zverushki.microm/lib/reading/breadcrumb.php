<?
namespace Zverushki\Microm\Reading;

/**
* class Breadcrumb
*
* Breadcrumb trails on a page indicate the page's position in the site hierarchy.
* A user can navigate all the way up in the site hierarchy, one level at a time, by starting from the last breadcrumb in the breadcrumb trail.
*
*
* @var NAME - уникальный ключ типа
* @var $__instance - объект
*
* @param $__options - входные параметры (настраиваются в модуле)
*
* @package Zverushki\Microm\Reading\Breadcrumb
*/
class Breadcrumb extends Type {
	const NAME = 'Breadcrumb';
	private static $__instance = null;

	protected function __construct ($options) {
		parent::__construct(self::NAME, $options);

	} // end __construct()

	public static function getInstance ($options) {
		if (static::$__instance === null)
			static::$__instance = new self($options);

		return static::$__instance;
	} // end function getInstance


	/**
	 * Формируем данные
	 * @return array - массив данных
	 */
	protected function model () {
		global $APPLICATION;

		$model = array();

		/*$APPLICATION->arAdditionalChain*/
		$list = $APPLICATION->GetNavChain(false, false, false, true);
		$list = !is_array($list) ? array() : $list;
		foreach ($list as $item) {
			if (!$this->checkUrl($item['LINK']))
				continue;

			$model[] = $item;
		}

		if (empty($model))
			$this->__status = 'List is empty'; // Список пустой

		return !empty($model) ? $model : false;
	} // end function model


	protected function __onPrepareResult ($model) {
		$result = array(
			'itemListElement' => array()
		);

		foreach ($model as $item)
			$result['itemListElement'][] = array(
				'position' => count($result['itemListElement']) + 1,
				'@id' => $item['LINK'],
				'name' => htmlspecialcharsEx($item['TITLE'])
			);

		return $result;
	} // end function __onPrepareResult


	/**
	 * Проверяем ссылку на валидность
	 * @param  string $url - ссылка
	 * @return bool
	 */
	public function checkUrl ($url) {
		return !(in_array($url, array('/')) || !$url);
	} // function checkUrl


	public function getName() { return self::NAME;}
} // end class Breadcrumb
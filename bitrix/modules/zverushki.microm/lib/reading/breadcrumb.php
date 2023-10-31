<?
namespace Zverushki\Microm\Reading;

/**
* class Breadcrumb
*
* Breadcrumb trails on a page indicate the page's position in the site hierarchy.
* A user can navigate all the way up in the site hierarchy, one level at a time, by starting from the last breadcrumb in the breadcrumb trail.
*
*
* @var NAME - ���������� ���� ����
* @var $__instance - ������
*
* @param $__options - ������� ��������� (������������� � ������)
*
* @package Zverushki\Microm\Reading\Breadcrumb
*/
class Breadcrumb extends Type {
	const NAME = 'Breadcrumb';
	private static $__instance = null;

	protected function __construct ($options) {
		parent::__construct(self::NAME, $options);
	}

	public static function getInstance ($options = array()) {
		if (static::$__instance === null)
			static::$__instance = new self($options);

		return static::$__instance;
	}

	/**
	 * ��������� ������
	 * @return array - ������ ������
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

			if (!$item['LINK'])
				$item['LINK'] = $GLOBALS['APPLICATION']->getCurPage(false);

			$model[] = $item;
		}

		if (empty($model))
			$this->__status = 'List is empty'; // ������ ������

		return !empty($model) ? $model : false;
	}

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
	}

	/**
	 * ��������� ������ �� ����������
	 * @param  string $url - ������
	 * @return bool
	 */
	public function checkUrl ($url) { return true;}

	public function getName() { return self::NAME;}

}
?>
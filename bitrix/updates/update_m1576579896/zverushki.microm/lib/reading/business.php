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
* @var NAME - ���������� ���� ����
* @var $__instance - ������
*
* @param $__options - ������� ��������� (������������� � ������)
*
* @package Zverushki\Microm\Reading\Business
*/
class Business extends Type {
	const NAME = 'Business';
	private static $__instance = null;

	protected function __construct ($options) {
		parent::__construct(self::NAME, $options);

	} // end __construct

	public static function getInstance ($options) {
		if (static::$__instance === null)
			static::$__instance = new self($options);

		return static::$__instance;
	} // end function getInstance


	/**
	 * ��������� ������
	 * @return array - ������ ������
	 */
	protected function model () {
		$model = Microm\MicromTable::getList(array(
			'filter' => array('CODE' => 'info')
		))->fetch();

		if ($model === false) {
			$this->__status = 'Error. Data not found (info)'; // ������ �� �������

			return false;
		}

		return $model;
	} // end function model

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
	} // end function __onPrepareResult


	public function getName() { return self::NAME;}
} // end class Business
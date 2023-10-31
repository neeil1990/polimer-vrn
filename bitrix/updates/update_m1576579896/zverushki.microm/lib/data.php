<?
namespace Zverushki\Microm;


/**
* class Data
*
*
* @var $stack - ���� ���������� �����
*
* @package Zverushki\Microm\Data
*/
abstract class Data {
	protected static $stack = array();
	private $siteId = false;

	function __construct () {
		$this->siteId = SITE_ID;

		if ($GLOBALS["BX_STATE"] != 'EA')
			return false;

		$this->setData();
	} // end __construct


	public function show () {
		foreach (static::$stack as $data)
			echo $data['template'];
	} // end function show


	/**
	 * ������ ��������� ���������� (� ������)
	 * ��������� ������� �����
	 * ��������� ���� � ���� ����� ������
	 */
	abstract protected function setData ();

} // end class Data
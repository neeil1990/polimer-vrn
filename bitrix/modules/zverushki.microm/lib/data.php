<?
namespace Zverushki\Microm;


/**
* class Data
*
*
* @var $stack
*
* @package Zverushki\Microm
*/
class Data {
	private static $stack = array();

	function __construct () {
		if ($GLOBALS["BX_STATE"] != 'EA')
			return false;

		$this->setData();
	}

	public function show () {
		foreach (static::$stack as $data)
			echo $data['template'];
	}

	public function getStack () { return static::$stack;}

	/**
	 * Читает настройки параметров (в модуле)
	 * Выполняет шаблоны типов
	 * Формирует стэк с всех типов данных
	 */
	private function setData () {
		$Options = Options::entity();

		foreach ($Options->settingsType() as $type) {
			$ClassName = $type['ClassName'];

			if (class_exists($ClassName))
				static::$stack[$type['key']] = $ClassName::getInstance($type['options'])->get();
		}
	}

}
?>
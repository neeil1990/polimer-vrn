<?
namespace Zverushki\Microm;

use Bitrix\Main,
	Bitrix\Main\IO,
	Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


/**
* class Template
*
* @var $__type - ��� ������ ��� �������� ����� �������� ������
* @var $__directory - ������ ���������� ������� ���� ������
* @var $__extensions - ���������� ���������� ������ ��������
* @var $__isExists - ������ ������������� ������� ����
* @var $__templates - ������ ��������
*
* @package Zverushki\Microm\Template
*/
class Template {
	private $__type = false;
	private $__directory = null;
	private $__extensions = array('php', 'html');
	private $__isExists = false;
	private $__templates = array();

	function __construct ($type) {
		$this->__type = $type;

		$this->__directory = new IO\Directory(static::__getPathTemplates().'/'.strtolower($this->__type));
		$this->__isExists = $this->__directory->isExists();

		if ($this->__isExists) {
			foreach ($this->__directory->getChildren() as $directory)
				if ($directory->isFile())
					if (($result = $this->__checkTemplate($directory)) !== false) {
						$this->__templates[$result['name']] = array(
							'path' => $directory->getPath()
						);
					}
		}

		if (empty($this->__templates))
			$this->__isExists = false;
	} // end function __construct


	/**
	 * @return bool - ������ ������������� �������
	 */
	public function isExists () { return $this->__isExists;}


	/**
	 * �������� ������� ������
	 * @param  string $template - ������������ �������
	 * @return html - ������ � ������� ��� ����������
	 */
	public function getResult ($template, $result) {
		if (!array_key_exists($template, $this->__templates))
			return false;

		if ($GLOBALS["BX_STATE"] != 'EA' && $GLOBALS["BX_STATE"] != 'WA')
			return false;

		ob_start();
			include $this->__templates[$template]['path'];

		// $html = ob_get_contents();
		$html = ob_get_clean();

		return $html;
	} // end function getResult ()


	/**
	 * �������� ������� ������
	 * @param  IO\File $directory - ������ ����������
	 * @return boll - ������ ����������� ����������
	 */
	private function __checkTemplate (IO\File $directory) {
		foreach ($this->__extensions as $extension) {
			$lenExtension = strlen($directory->getName()) - strlen('.'.$extension);

			if (substr($directory->getName(), $lenExtension) == '.'.$extension)
				return array(
					'status' => true,
					'name' => substr($directory->getName(), 0, $lenExtension)
				);
		}

		return false;
	} // end function __checkTemplate ()


	/**
	 * @return string - ���� � ��������
	 */
	private static function __getPathTemplates () {
		$path = pathinfo(__DIR__);
		return $path['dirname'].'/templates';
	} // end function __getPathTemplates ()

} // end class Template